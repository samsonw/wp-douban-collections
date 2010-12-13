<?php
header("Content-type: text/css; charset: UTF-8");

require_once('../../../wp-load.php');
$options = get_option('douban_collections_options');
echo trim($options['custom_css_styles']);
?>
