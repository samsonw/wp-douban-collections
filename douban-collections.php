<?php
/* 
Plugin Name: Douban Collections
Plugin URI: http://blog.samsonis.me/tag/douban-collections
Version: 1.0.0
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

define('DOUBAN_COLLECTIONS_VERSION', '1.0.0');

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

if (!class_exists("Douban")) {
    class Douban {
        public static function get_categories() {
            return array('book', 'music', 'movie');
        }

        public static function get_category_status_list($category) {
            switch ($category) {
                case 'book':
                    return array('read', 'reading', 'wish');
                
                case 'music':
                    return array('listened', 'listening', 'wish');

                case 'movie':
                    return array('watched',  'watching',  'wish');

                default:
                    return array();
            }
        }

        public static function get_user($user_id = '', $api_key = '00b80c3a9c5d966d022824afd518c347') {
            // If we have a non-expire cached copy, use that instead
            if ($douban_user = get_transient(DOUBAN_COLLECTIONS_USER_TRANSIENT_KEY)){
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
            if (strpos($headers[0], '404') === false){
                $douban_user['link'][2]['@href'] = $big_icon_url;
            }

            // Store the results into the WordPress transient, expires in 24 hours
            set_transient(DOUBAN_COLLECTIONS_USER_TRANSIENT_KEY, $douban_user, 60 * 60 * 24);
            
            return $douban_user;
        }

        public static function get_collections($user_id, $category, $max_retrieve_items_list, $start_index = 1){
            if (empty($user_id) || empty($category) || empty($max_retrieve_items_list))
                return array();

            // If we have a non-expire cached copy, use that instead
            if ($collections = get_transient(DOUBAN_COLLECTIONS_TRANSIENT_KEY . $category)) {
                return $collections;
            }
            
            $max_retrive_items = array_sum($max_retrieve_items_list);
            $items_per_request = 50;
            
            $request_count = 0;
            while ($items_per_request * $request_count < $max_retrive_items) {
                $raw_collections = Douban::retrive_raw_collections($user_id, $category, $current_index, $items_per_request);
                foreach ($raw_collections['entry'] as $entry) {
                    $subject_entry = $entry['db:subject'];
                    $subject_entry['updated'] = $entry['updated']['$t'];
                    $collections[ $entry['db:status']['$t'] ][] = $subject_entry;
                    $subject_entry = null;
                    $entry = null;
                }
                $raw_collections = null;
                $request_count++;
            }
            
            if (! empty($collections)) {
                foreach (Douban::get_category_status_list($category) as $status)
                    if (! empty($collections[$status]))
                        $collections[$status] = array_slice($collections[$status], 0, $max_retrieve_items_list[$status]);

                uksort($collections, 'Douban::cmp_collections_status_order');
            } else {
                $collections = array();
            }
            
            // Store the results into the WordPress transient, expires in 30 mins
            set_transient(DOUBAN_COLLECTIONS_TRANSIENT_KEY . $category, $collections, 60 * 30);
            
            return $collections;
        }

        private static function retrive_raw_collections($user_id, $category, $start_index, $max_results = 50, $api_key = '00b80c3a9c5d966d022824afd518c347'){
            if (empty($user_id) || empty($category))
                return "";

            $url = 'http://api.douban.com/people/'.$user_id.'/collection?cat='.$category.'&start-index='.$start_index.'&max-results=' . $max_results . '&alt=json';
            if (!empty($api_key)){
                $url .= '&apikey=' . $api_key;
            }
            // TODO: exception handling
            $raw_collections = json_decode(file_get_contents($url), true);
            
            return $raw_collections;
        }

        private static function cmp_collections_status_order($status_a, $status_b){
            // TODO: make $collections_status_order an opition
            $collections_status_order = array('wish' => '0', 
                                              'reading' => '1', 
                                              'listening' => '1',
                                              'watching' => '1',
                                              'read' => '2',
                                              'listend' => '2',
                                              'watched' => '2');
            return strcmp($collections_status_order[$status_a], $collections_status_order[$status_b]);
        }
    }
}

if (!class_exists("DoubanCollections")) {
    class DoubanCollections {
        var $options;

        function DoubanCollections() {
            $this->plugin_url = WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__));

            add_action('wp_print_styles', array(&$this, 'load_styles'));
            add_action('admin_print_scripts', array(&$this, 'load_admin_scripts'));
            add_shortcode('douban_collections', array(&$this, 'display_collections'));

            // admin menu
            add_action('admin_menu', array(&$this, 'douban_collections_settings'));

            register_activation_hook(__FILE__, array(&$this, 'install'));
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

        private function compose_userinfo_html() {
            $douban_user = Douban::get_douban_user($this->options['douban_user_id']);
            $user_part_html = '<div id="dc_user">'
                . '<div id="dc_user_pic">'
                . '<a href="' . $douban_user['link'][1]['@href'] . '" title="' . $douban_user['title']['$t'] . '" target="_blank">'
                . '<img class="dc_user_image" src="' . $douban_user['link'][2]['@href'] . '" alt="' . $douban_user['title']['$t'] . '" />'
                . '</a></div>'
                . '<ul id="dc_user_info">'
                . '<li class="dc_user_info_title">'
                . '<a href="' . $douban_user['link'][1]['@href'] . '" title="' . $douban_user['title']['$t'] . '" target="_blank">' . $douban_user['title']['$t'] . '</a>';

            if (!empty($douban_user['db:signature']['$t'])) {
                $user_part_html .= '<span class="dc_user_signature"> “' . $douban_user['db:signature']['$t'] . '”</span>';
            }

            $user_part_html .= '</li>'
                . '<li class="dc_user_info_item">Id: ' . $douban_user['db:uid']['$t'] . '</li>'
                . '<li class="dc_user_info_item">Home Page: <a href="' . $douban_user['link'][3]['@href']. '" title="' . $douban_user['link'][3]['@href'] . '" target="_blank">' . $douban_user['link'][3]['@href'] . '</a></li>'
                . '<li class="dc_user_info_item">Location: ' . $douban_user['db:location']['$t'] . '</li>'
                . '<li class="dc_user_info_item">' . $douban_user['content']['$t'] . '</li>'
                . '</ul>'
                . '</div>';
            return $user_part_html;
        }

        private function compose_category_html($category, $max_retrieve_items_list) {
            if ($category == 'none')
                return '';

            $collections = Douban::get_collections($this->options['douban_user_id'], $category, $max_retrieve_items_list);
            $category_html .= '<ul>';
            foreach($collections as $status => $status_collections) {
                if ($max_retrieve_items_list[$status] == 0)
                    continue;
                
                $category_html .= '<li class="dc_status">' . $this->options['status_text'][$category][$status] . '</li>';
                foreach($status_collections as $entry){
                    $category_html .= '<li class="dc_list">'
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
                        $category_html .= '<p class="dc_info_footer">'
                            . '<a href="' . $movie_imdb . '" title="IMDB" target="_blank">Link to IMDB'
                            . '</a></p>';
                    }
                    $category_html .= '</div>'
                        // . '<p class="dc_updated">' . $status . ' at ' . mysql2date('j M Y', $entry['updated']) . '</p>'
                        . '</li>';
                }
            }
            $category_html .= '</ul>';
            return $category_html;
        }
        
        private function compose_html($category, $with_user_info, $max_retrieve_items_list) {
            $html = '<div id="douban_collections" class="douban_collections_column">';
            if('true' === strtolower($with_user_info)) {
                $html .= $this->compose_userinfo_html();
            }

            // Never trust user input
            if (! in_array($category, Douban::get_categories()))
                $category = 'none';

            $html .= $this->compose_category_html($category, $max_retrieve_items_list);
            $html .= '</div>';
            return $html;
        }

        function display_collections($atts){
            extract( shortcode_atts( array('category'      => 'none',
                                           'with_user_info' => 'false',
                                           'reading_num'   => '0',
                                           'read_num'      => '10',
                                           'listened_num'  => '10',
                                           'listening_num' => '0',
                                           'watched_num'   => '10',
                                           'watching_num'  => '0',
                                           'wish_num'      => '0'), $atts ) );
            $max_retrieve_items_list = array();
            foreach (Douban::get_category_status_list($category) as $media_status)
                $max_retrieve_items_list[$media_status] = ${$media_status.'_num'};

            return $this->compose_html($category, $with_user_info, $max_retrieve_items_list);
        }
        
        private function get_options() {
            $options = array('douban_user_id' => 'samsonw', 
                             'status_text' => array('book'  => array('reading'   => '在读 ...', 
                                                                     'read'      => '读过 ...', 
                                                                     'wish'      => '想读 ...'), 
                                                    'music' => array('listened'  => '听过 ...', 
                                                                     'listening' => '在听 ...', 
                                                                     'wish'      => '想听 ...'), 
                                                    'movie' => array('watched'   => '看过 ...', 
                                                                     'watching'  => '在看 ...', 
                                                                     'wish'      => '想看 ...')), 
                             'custom_css_styles' => '', 
                             'load_resources_only_in_douban_collections_page' => false, 
                             'douban_collections_page_names' => 'douban, books, movies, reads');
            
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

                $orig_options = $options;
                $options = array();

                $options['douban_user_id'] = $_POST['douban_user_id'];

                foreach (Douban::get_categories() as $media_category)
                    foreach (Douban::get_category_status_list($media_category) as $media_status)
                        $options['status_text'][$media_category][$media_status] = stripslashes($_POST[$media_category.'_status_'.$media_status.'_text']);

                $options['custom_css_styles'] = stripslashes($_POST['custom_css_styles']);

                $options['load_resources_only_in_douban_collections_page'] = isset($_POST['load_resources_only_in_douban_collections_page']) ? (boolean)$_POST['load_resources_only_in_douban_collections_page'] : false;
                $options['douban_collections_page_names'] = $options['load_resources_only_in_douban_collections_page'] ? stripslashes($_POST['douban_collections_page_names']) : $orig_options['douban_collections_page_names'];

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

            if($this->options['load_resources_only_in_douban_collections_page']){
                $load_extra = false;
                // if enabled, only load css in those specific files
                foreach(array_map('trim', explode(",", $this->options['douban_collections_page_names'])) as $page_name){
                   $load_extra = is_page($page_name);
                   if($load_extra) break;
               }
            }else{
                // disabled, load css
               $load_extra = true;
            }

            if($load_extra){
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
        }

        function load_admin_scripts(){
            $admin_script_url = $this->plugin_url . '/douban-collections-options.js';
            wp_register_script('douban_collections_admin_script', $admin_script_url, 'jquery', DOUBAN_COLLECTIONS_VERSION);
            wp_enqueue_script('douban_collections_admin_script');
        }

        function delete_cache() {
            $categories = array('') + Douban::get_categories();
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
