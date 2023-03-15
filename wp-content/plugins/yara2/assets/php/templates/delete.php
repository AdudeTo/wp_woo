<?php 
global $wpdb;
$wpdb->get_results("DELETE FROM wp_yara_products WHERE ID >-1");
$wpdb->get_results("DELETE FROM wp_posts WHERE ID >9");
?>