<?php
/* 
Plugin Name: Douban Collections
Plugin URI: http://blog.samsonis.me/tag/douban-collections
Version: 0.9.0
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

define('DOUBAN_COLLECTIONS_VERSION', '0.9.0');

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
define('DOUBAN_COLLECTIONS_USER_TRANSIENT_KEY', 'douban_collections_user_transient');
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
        
        private function get_douban_user($user_id = '', $api_key = '00b80c3a9c5d966d022824afd518c347'){
            // If we have a non-expire cached copy, use that instead
            if($douban_user = get_transient(DOUBAN_COLLECTIONS_USER_TRANSIENT_KEY)) {
                return $douban_user;
            }
            
            $url = 'http://api.douban.com/people/' . $user_id . '?alt=json';
            if (!empty($api_key)){
                $url .= '&apikey=' . $api_key;
            }
            // TODO: exception handling
            $douban_user = json_decode(file_get_contents($url), true);
            // we need the big user icon instead of the default small one, if exists
            $big_icon_url = preg_replace('/u(\d+)/i', 'ul$1', $douban_user['link'][2]['@href']);
            $headers = get_headers($big_icon_url);
            if(strpos($headers[0], '404') === false){
                $douban_user['link'][2]['@href'] = $big_icon_url;
            }

            // Store the results into the WordPress transient, expires in 24 hours
            set_transient(DOUBAN_COLLECTIONS_USER_TRANSIENT_KEY, $douban_user, 60 * 60 * 24);
            
            return $douban_user;
        }
        
        private function cmp_collections_status_order($status_a, $status_b){
            // TODO: make $collections_status_order an opition
            $collections_status_order = array('reading' => '0', 'read' => '1', 'wish' => '2', 'watched' => '3');
            return strcmp($collections_status_order[$status_a], $collections_status_order[$status_b]);
        }
        
        private function get_douban_collections($user_id = '', $category = 'book', $start_index = 1, $api_key = '00b80c3a9c5d966d022824afd518c347', $max_results = 50){
            $url = 'http://api.douban.com/people/' . $user_id . '/collection?cat=' . $category . '&start-index=' . $start_index . '&max-results=' . $max_results . '&alt=json';
            if (!empty($api_key)){
                $url .= '&apikey=' . $api_key;
            }
            // TODO: exception handling
            $raw_collections = json_decode(file_get_contents($url), true);
            
            return $raw_collections;
        }
        
        private function get_collections($user_id = '', $category = 'book', $start_index = 1){
            // If we have a non-expire cached copy, use that instead
            if($collections = get_transient(DOUBAN_COLLECTIONS_TRANSIENT_KEY . $category)) {
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
                $raw_collections = $this->get_douban_collections($user_id, $category, $current_index);
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
            
            switch ($category) {
                case 'book':
                    $collections['reading'] = array_slice($collections['reading'], 0, $this->options['status_max_results']['book']['reading'], true);
                    $collections['read'] = array_slice($collections['read'], 0, $this->options['status_max_results']['book']['read'], true);
                    $collections['wish'] = array_slice($collections['wish'], 0, $this->options['status_max_results']['book']['wish'], true);
                    break;
                case 'movie':
                    $collections['watched'] = array_slice($collections['watched'], 0, $this->options['status_max_results']['movie']['watched'], true);
                    $collections['wish'] = array_slice($collections['wish'], 0, $this->options['status_max_results']['movie']['wish'], true);
                    break;
            }
            
            uksort($collections, array(&$this, 'cmp_collections_status_order'));
            
            // Store the results into the WordPress transient, expires in 30 mins
            set_transient(DOUBAN_COLLECTIONS_TRANSIENT_KEY . $category, $collections, 60 * 30);
            
            return $collections;
        }
        
        private function get_author_name($author){
            return $author['name']['$t'];
        }
        
        private function get_authors($entry){
            $author_names = '';
            if(!empty($entry['author'])){
                $author_names = implode(', ', array_map(array(&$this, 'get_author_name'), $entry['author']));
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
        
        private function compose_html($category, $with_user_info) {
            $html = '<div id="douban_collections" class="douban_collections_column">';
            if('true' === strtolower($with_user_info)) {
                $douban_user = $this->get_douban_user($this->options['douban_user_id']);
                $html .= '<div id="dc_user">'
                    . '<div id="dc_user_pic">'
                    . '<a href="' . $douban_user['link'][1]['@href'] . '" title="' . $douban_user['title']['$t'] . '" target="_blank">'
                    . '<img class="dc_user_image" src="' . $douban_user['link'][2]['@href'] . '" alt="' . $douban_user['title']['$t'] . '" />'
                    . '</a></div>'
                    . '<ul id="dc_user_info">'
                    . '<li class="dc_user_info_title">'
                    . '<a href="' . $douban_user['link'][1]['@href'] . '" title="' . $douban_user['title']['$t'] . '" target="_blank">' . $douban_user['title']['$t'] . '</a>';
                if(!empty($douban_user['db:signature']['$t'])){
                    $html .= '<span class="dc_user_signature"> “' . $douban_user['db:signature']['$t'] . '”</span>';
                }
                $html .= '</li>'
                    . '<li class="dc_user_info_item">Id: ' . $douban_user['db:uid']['$t'] . '</li>'
                    . '<li class="dc_user_info_item">Home Page: <a href="' . $douban_user['link'][3]['@href']. '" title="' . $douban_user['link'][3]['@href'] . '" target="_blank">' . $douban_user['link'][3]['@href'] . '</a></li>'
                    . '<li class="dc_user_info_item">Location: ' . $douban_user['db:location']['$t'] . '</li>'
                    . '<li class="dc_user_info_item">' . $douban_user['content']['$t'] . '</li>'
                    . '</ul>'
                    . '</div>';
            }
            // Never trust user input
            switch ($category) {
                case 'book':
                    $category = 'book';
                    break;
                case 'movie':
                    $category = 'movie';
                    break;
                case 'music':
                    //TODO add music category, now default to book
                    $category = 'book';
                    break;
                default:
                    $category = 'book';
                    break;
            }
            $collections = $this->get_collections($this->options['douban_user_id'], $category);
            $html .= '<ul>';
            foreach($collections as $status => $status_collections){
                $html .= '<li class="dc_status">' . $this->options['status_text'][$category][$status] . '</li>';
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
                        . '<li class="dc_info_item">' . $this->get_authors($entry) . '</li>'
                        . '<li class="dc_info_item">' . $this->get_attribute($entry['db:attribute'], 'publisher') . '</li>'
                        . '<li class="dc_info_item">' . $this->get_attribute($entry['db:attribute'], 'pubdate') . '</li>'
                        . '</ul>';
                    $movie_imdb = $this->get_attribute($entry['db:attribute'], 'imdb');
                    if(!empty($movie_imdb)){
                        $html .= '<p class="dc_info_footer">'
                            . '<a href="' . $movie_imdb . '" title="IMDB" target="_blank">Link to IMDB'
                            . '</a></p>';
                    }
                    $html .= '</div>'
                        . '<p class="dc_updated">' . $status . ' at ' . mysql2date('j M Y', $entry['updated']) . '</p>'
                        . '</li>';
                }
            }
            $html .= '</ul>' . '</div>';
            return $html;
        }

        function display_collections($atts){
            extract( shortcode_atts( array(
                'category' => 'book',
                'with_user_info' => 'true',
                ), $atts ) );
            return $this->compose_html($category, $with_user_info);
        }
        
        private function get_options() {
            $options = array('douban_user_id' => 'samsonw', 'status_text' => array('book' => array('reading' => '在读 ...', 'read' => '读过 ...', 'wish' => '想读 ...'), 'movie' => array('watched' => '看过 ...', 'wish' => '想看 ...')), 'status_max_results' => array('book' => array('reading' => 25, 'read' => 50, 'wish' => 50), 'movie' => array('watched' => 50, 'wish' => 50)), 'custom_css_styles' => '');
            
            $saved_options = get_option(DOUBAN_COLLECTIONS_OPTION_NAME);
            
            if (!empty($saved_options)) {
                foreach ($saved_options as $key => $option) {
                    if (is_array($option)) {
                        foreach ($option as $ar_key => $ar_option) {
                            $options[$key][$ar_key] = $ar_option;
                        }
                    } else {
                        $options[$key] = $option;
                    }
                }
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
                
                $options['status_text']['book']['reading'] = stripslashes($_POST['book_status_reading_text']);
                $options['status_text']['book']['read'] = stripslashes($_POST['book_status_read_text']);
                $options['status_text']['book']['wish'] = stripslashes($_POST['book_status_wish_text']);
                $options['status_max_results']['book']['reading'] = (int)$_POST['book_status_reading_max_results'];
                $options['status_max_results']['book']['read'] = (int)$_POST['book_status_read_max_results'];
                $options['status_max_results']['book']['wish'] = (int)$_POST['book_status_wish_max_results'];
                
                $options['status_text']['movie']['watched'] = stripslashes($_POST['movie_status_watched_text']);
                $options['status_text']['movie']['wish'] = stripslashes($_POST['movie_status_wish_text']);
                $options['status_max_results']['movie']['watched'] = (int)$_POST['movie_status_watched_max_results'];
                $options['status_max_results']['movie']['wish'] = (int)$_POST['movie_status_wish_max_results'];
                
                $options['custom_css_styles'] = stripslashes($_POST['custom_css_styles']);
        
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
            $this->options = $this->get_options();
            
            $css_url = $this->plugin_url . '/douban-collections.css';
            wp_register_style('douban_collections', $css_url, array(), DOUBAN_COLLECTIONS_VERSION, 'screen');
            wp_enqueue_style('douban_collections');
            
            $custom_css_styles = trim($this->options['custom_css_styles']);
            if(!empty($custom_css_styles)) {
                $custom_css_url = $this->plugin_url . '/douban-collections-custom-css.php';
                wp_register_style('douban_collections_custom', $custom_css_url, array(), DOUBAN_COLLECTIONS_VERSION, 'screen');
                wp_enqueue_style('douban_collections_custom');
            }
        }

        function delete_cache() {
            $categories = array('', 'book', 'movie', 'music');
            foreach($categories as $category) {
                delete_transient(DOUBAN_COLLECTIONS_TRANSIENT_KEY . $category);
            }
            delete_transient(DOUBAN_COLLECTIONS_USER_TRANSIENT_KEY);
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
