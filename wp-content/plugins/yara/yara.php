<?php

/**
 * Plugin Name: Yara
 * Plugin URI: 
 * Description: Yara Code Challenge
 * Version: 1.0.0
 * Author: Vicho Vichev
 * Author URI: https://vichev.art
 * Requires PHP: 7.1
 *
 * @package YaraPlugin
 */

function yara_code_challenge_setup_menu()
{
    add_menu_page('Yara Code Challenge', 'Yara Plugin', 'manage_options', 'yara-plugin', 'yara_init');
}

add_action('admin_menu', 'yara_code_challenge_setup_menu');

function yara_init()
{
    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        yara_get_data();
    } else {
        echo "You need to install woocomerce!";
    }
}

function yara_get_data()
{

    $api_url = 'http://dummyjson.com/products';
    // Read JSON file
    $json_data = file_get_contents($api_url);
    // Decode JSON data into PHP array
    $response_data = json_decode($json_data);
    // All user data exists in 'data' object
    $products_data = $response_data->products;
    // Cut long data into small & select only first 10 records
    $products_data = array_slice($products_data, 0, 2);

    // Print data if need to debug
    // echo "<pre>";
    // print_r($products_data);
    // echo "</pre>";

    echo "<pre>";
    // Traverse array and display user data
    foreach ($products_data as $product) {
        //print_r($product);	
        echo "title: " . $product->title;
        echo "<br />";
        //echo "description: " . $product->description;
        //echo "<br />";
        //echo "price: " . $product->price;
        //echo "<br />";
        //echo "----------------------------------------------------------------";
        //echo "<br /><br />";
        //echo "name: ".$product->employee_age;
        //echo "<br /> <br />";

        $mypostExist = post_exists($product->title);
        $my_yara_product_post = array(
            'post_title'    => $product->title,
            'post_content'  =>  $product->description,
            'post_status'   => 'publish',
            'post_type'   => 'product',
            'post_author'   => 1
        );

        if ($mypostExist == NULL) {            
            wp_insert_post($my_yara_product_post);
        } else {
            //Update the post
        }

        echo "----------------------------------------------------------------";
        echo "<br /><br />";
        // Insert the post into the database

    }
    echo "</pre>";
}

?>