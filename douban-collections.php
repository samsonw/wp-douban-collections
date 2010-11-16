<?php
/* 
Plugin Name: Douban Collections
Plugin URI: http://blog.samsonis.me/tag/douban-collections
Version: 0.5.0
Author: <a href="http://blog.samsonis.me/">Samson Wu</a>
Description: Douban Collections provides a douban collections (books, movies, musics) page for WordPress.

**************************************************************************

Copyright (C) 2008 Samson Wu

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more deDoubanCollectionstails.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

**************************************************************************
 */

define('DOUBAN_COLLECTIONS_VERSION', '0.5.0');

/**
 * Guess the wp-content and plugin urls/paths
 */
// Pre-2.6 compatibility
if ( ! defined( 'WP_CONTENT_URL' ) )
    define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( ! defined( 'WP_CONTENT_DIR' ) )
    define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) )
    define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) )
    define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );


define('DOUBAN_COLLECTIONS_TRANSIENT_KEY', 'douban_collections_transient');
define('DOUBAN_COLLECTIONS_OPTION_NAME', 'douban_collections_options');


if (!class_exists("DoubanCollections")) {
    class DoubanCollections {
        var $options;

        function DoubanCollections() {
            $this->plugin_url = WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__));

            add_action('wp_print_styles', array(&$this, 'load_styles'));
            add_shortcode('douban_collections', array(&$this, 'display_collections'));

            // admin menu
            add_action('admin_menu', array(&$this, 'douban_collections_settings'));

            register_activation_hook(__FILE__, array(&$this, 'install'));
        }
        
        private function cmp_collections_status_order($status_a, $status_b){
            // TODO: make $collections_status_order an opition
            $collections_status_order = array('reading' => '0', 'read' => '1', 'wish' => '2');
            return strcmp($collections_status_order[$status_a], $collections_status_order[$status_b]);
        }
        
        private function get_douban_collections($user_id = '', $start_index = 1, $category = 'book', $api_key = '00b80c3a9c5d966d022824afd518c347', $max_results = 50){
            $url = 'http://api.douban.com/people/' . $user_id . '/collection?cat=' . $category . '&start-index=' . $start_index . '&max-results=' . $max_results . '&alt=json';
            if (!empty($api_key)){
                $url .= '&apikey=' . $api_key;
            }
            // TODO: exception handling
            $raw_collections = json_decode(file_get_contents($url), true);
            
            return $raw_collections;
        }
        
        private function get_collections($user_id = '', $start_index = 1, $category = 'book'){
            // If we have a non-expire cached copy, use that instead
            if($collections = get_transient(DOUBAN_COLLECTIONS_TRANSIENT_KEY)) {
                return $collections;
            }
            
            // TODO: make $max_display_results an option
            $max_display_results = 500;
            $items_per_request = 50;
            
            $i = 0;
            // just make $total_results bigger than $start_index
            $total_results = $start_index + 1;
            do{
                $current_index = $start_index + $items_per_request * $i;
                if($current_index > $total_results){
                    break;
                }
                $raw_collections = $this->get_douban_collections($user_id, $current_index, $category);
                $total_results = $raw_collections['opensearch:totalResults']['$t'];
                foreach ($raw_collections['entry'] as $entry) {
                    $subject_entry = $entry['db:subject'];
                    $subject_entry['updated'] = $entry['updated']['$t'];
                    $collections[ $entry['db:status']['$t'] ][] = $subject_entry;
                    $subject_entry = null;
                    $entry = null;
                }
                $raw_collections = null;
                $i++;
            }while($current_index <= $max_display_results);
            
            uksort($collections, array(&$this, 'cmp_collections_status_order'));
            
            // Store the results into the WordPress transient, expires in 30 mins
            set_transient(DOUBAN_COLLECTIONS_TRANSIENT_KEY, $collections, 60 * 30);
            
            return $collections;
        }
        
        private function get_author_name($author){
            return $author['name']['$t'];
        }
        
        private function get_authors($authors){
            $author_names = '';
            if(!empty($authors)){
                $author_names = implode(', ', array_map(array(&$this, 'get_author_name'), $authors));
            }
            return $author_names;
        }
        
        private function get_attribute($attributes, $attr_name){
            foreach($attributes as $attr) {
                if($attr_name === $attr['@name']){
                    return $attr['$t'];
                }
            }
            return '';
        }
        
        private function compose_html($collections) {
            $html = '<div id="douban_collections" class="douban_collections_column">'
                . '<ul>';
            foreach($collections as $status => $status_collections){
                $html .= '<li class="dc_status">' . $this->options['status_text'][$status] . '</li>';
                foreach($status_collections as $entry){
                    $html .= '<li class="dc_list">'
                        . '<div class="dc_entry">'
                        . '<div class="dc_pic">'
                        . '<a href="' . $entry['link'][1]['@href'] . '" title="' . $entry['title']['$t'] . '" target="_blank">'
                        . '<img class="dc_image" src="' . $entry['link'][2]['@href'] . '" alt="' . $entry['title']['$t'] . '" />'
                        . '</a></div>'
                        . '<ul class="dc_info">'
                        . '<li class="dc_info_title">'
                        . '<a href="' . $entry['link'][1]['@href'] . '" title="' . $entry['title']['$t'] . '" target="_blank">' . $entry['title']['$t'] . '</a>'
                        . '</li>'
                        . '<li class="dc_info_item">' . $this->get_authors($entry['author']) . '</li>'
                        . '<li class="dc_info_item">' . $this->get_attribute($entry['db:attribute'], 'publisher') . '</li>'
                        . '<li class="dc_info_item">' . $this->get_attribute($entry['db:attribute'], 'pubdate') . '</li>'
                        . '</ul>'
                        . '</div>'
                        . '<p class="dc_updated">' . $status . ' at ' . mysql2date('j M Y', $entry['updated']) . '</p>'
                        . '</li>';
                }
            }
            $html .= '</ul>' . '</div>';
            return $html;
        }

        function display_collections($atts){
            $this->options = $this->get_options();
            $collections = $this->get_collections($this->options['douban_user_id']);
            return $this->compose_html($collections);
        }
        
        private function get_options() {
            $options = array('douban_user_id' => 'samsonw', 'status_text' => array('reading' => '在读 ...', 'read' => '读过 ...', 'wish' => '想读 ...'));
            $saved_options = get_option(DOUBAN_COLLECTIONS_OPTION_NAME);
        
            if (!empty($saved_options)) {
                foreach ($saved_options as $key => $option)
                    $options[$key] = $option;
            }
        
            if ($saved_options != $options) {
                update_option(DOUBAN_COLLECTIONS_OPTION_NAME, $options);
            }
            return $options;
        }
        
        function handle_douban_collections_settings() {
            if(!current_user_can('manage_options'))  {
                wp_die( __('You do not have sufficient permissions to access this page.') );
            }
        
            $options = $this->get_options();
        
            if(isset($_POST['submit'])) {
                check_admin_referer('douban-collections-nonce');
        
                $options = array();
        
                $options['douban_user_id'] = $_POST['douban_user_id'];
                $options['status_text']['reading'] = $_POST['status_reading_text'];
                $options['status_text']['read'] = $_POST['status_read_text'];
                $options['status_text']['wish'] = $_POST['status_wish_text'];
        
                update_option(DOUBAN_COLLECTIONS_OPTION_NAME, $options);
        
                $this->delete_cache();
                echo '<div class="updated" id="message"><p>Settings saved.</p></div>';
            }else if(isset($_POST['refresh'])){
                check_admin_referer('douban-collections-nonce');
                
                $this->delete_cache();
                echo '<div class="updated" id="message"><p>Page Refreshed.</p></div>';
            }
            include_once("douban-collections-options.php");
        }

        function douban_collections_settings() {
            add_options_page('Douban Collections Settings', 'Douban Collections', 'manage_options', 'douban-collections-settings', array(&$this, 'handle_douban_collections_settings'));
        }

        function load_styles(){
            $css_url = $this->plugin_url . '/douban-collections.css';
            wp_register_style('douban_collections', $css_url, array(), DOUBAN_COLLECTIONS_VERSION, 'screen');
            wp_enqueue_style('douban_collections');
        }

        function delete_cache() {
            delete_transient(DOUBAN_COLLECTIONS_TRANSIENT_KEY);
        }

        function install() {
            $this->options = $this->get_options();
        }
    }
}

if (class_exists("DoubanCollections")) {
    $douban_collections = new DoubanCollections();
}

?>
