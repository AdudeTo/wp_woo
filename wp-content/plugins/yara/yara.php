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

include('wp-load.php');
include_once(ABSPATH . '/wp-admin/includes/image.php');

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

//FIX - 1 // Images can have more metadata for ALT and <CAPTION> !!!
function bg_image_upload($url,$productTitle)
{
    $timeout_seconds = 10;
    $temp_file = download_url($url, $timeout_seconds);
    if (!is_wp_error($temp_file)) {
        $info = getimagesize($temp_file);
        $allImagesSizes = wp_get_registered_image_subsizes(); //; $_wp_additional_image_sizes; 
        $file = array(
            'width' => $info[0],
            'height' => $info[1],
            'hwstring_small' => "height='{$info[1]}' width='{$info[0]}'",
            'name'     => basename($url), 
            'type'     => 'image/png',
            'tmp_name' => $temp_file,
            'sizes' => $allImagesSizes,
            'error'    => 0,
            'size'     => filesize($temp_file),
        );
        $overrides = array(
            'test_form' => false,
            'test_size' => true,
        );        
        $results = wp_handle_sideload($file, $overrides); // Move the temporary file into the uploads directory

        if (!empty($results['error'])) {
            // Insert any error handling here
        } else {
            $filename  = $results['file']; // Full path to the file
            $local_url = $results['url'];  // URL to the file in the uploads dir
            $type = $results['type']; // MIME type of the file
            $wp_upload_dir = wp_upload_dir(); // Get the path to the upload directory.
            $attachment = array(
                //FIX - 4 // - Avoid Long Name  //
                'post_title' => preg_replace('/\.[^.]+$/', '', basename($productTitle)) . preg_replace('/\.[^.]+$/', '', basename($filename)),
                'post_mime_type' => $type,
                'post_status' => 'inherit',
                'post_content' => '',
            );

            $img_id = wp_insert_attachment($attachment, $filename);
            $attach_data = wp_generate_attachment_metadata($img_id, $filename);
            wp_update_attachment_metadata($img_id,  $attach_data);
            return $img_id;
        }
    }
}



function yara_get_data()
{

    $api_url = 'https://dummyjson.com/products';   // Source URL  
    $json_data = file_get_contents($api_url);// Read JSON file    
    $response_data = json_decode($json_data);// Decode JSON data into PHP array   
    $products_data = $response_data->products; // All user data products in 'data' object    
    $products_data = array_slice($products_data, 0, 2); // FOR DEBUG !!! Cut long data into small & select only first few records

    // Print data if need to debug
    //echo "<pre>";
    //print_r($products_data);
    //echo "</pre>";

    echo "<pre>";
    // Traverse array and display data
    foreach ($products_data as $product) {
        //print_r($product);	
        echo "title: " . $product->title;
        echo "<br />";
        echo "description: " . $product->description;
        echo "<br />";
        echo "category: " . $product->category;
        echo "<br />";
        echo "price: " . $product->price;
        echo "<br />";
        echo "image 1: " . $product->images[0];
        echo "<br />";


        //echo "----------------------------------------------------------------";
        //echo "<br /><br />";


        $productCategory = category_exists($product->category);

        echo "category exist ?: " . $productCategory;
        echo "<br />";

        if (!$productCategory) {
            echo "missing" . $productCategory;
            echo "<br />";
            //BUG - 1 //The category needs more final touch !!!! Not Complete yet !!!
            wp_insert_term($product->category, 'product_cat', array(
                'description' => $product->category
            ));
            $productCategory = category_exists($product->category);
            add_term_meta($productCategory, 'display_type', 'products');
        }


        $mypostExist = post_exists($product->title);
        //BUG - 1 // post_category WOO products do not use default categories !!!
        $my_yara_product_post = array(
            'post_title'    => $product->title,
            'post_content'  =>  $product->description,
            'post_status'   => 'publish',
            'post_type'   => 'product',
            'post_category' => array($productCategory),
            'post_author'   => 1
        );

        if ($mypostExist == NULL) {
            $myNewPost = wp_insert_post($my_yara_product_post);
            // add_post_meta( int $post_id, string $meta_key, mixed $meta_value, bool $unique = false ): int|false
            //FIX - 1 // - scrap/send more data Alternative Text,Caption,Description//
            if ($product->images[0]) {
                //FIX - 2 // - check if already Exist !!! //
                //FIX - 3 // - Set Some !!!DELAY!!! (write in loop) //
                $myProductImageId = bg_image_upload($product->images[0],$product->title);
                //print_r($myProductImageId);
                //echo "<br />";
                if (!add_post_meta($myNewPost, '_thumbnail_id', $myProductImageId, true)) {
                    update_post_meta($myNewPost, '_thumbnail_id', $myProductImageId);
                }
            }

            print_r($myNewPost);
            echo "<br />";
            print_r($productCategory);
            echo "<br />";

            //BUG - 1 // post_category WOO products do not use default categories !!!
            do_action('add_term_relationship', $myNewPost, $productCategory, 0);
        } else {
            
            //echo "POST WAS UPDATED";
            //print_r($updateThewPost);
            //echo "<br />";            
            
            //Update the post
        }

        // Insert/update Meta
        if (!add_post_meta($myNewPost, '_regular_price', $product->price, true)) {
            update_post_meta($myNewPost, '_regular_price', $product->price);
        }

        if (!add_post_meta($myNewPost, '_price', $product->price, true)) {
            update_post_meta($myNewPost, '_price', $product->price);
        }

        echo "----------------------------------------------------------------";
        echo "<br /><br />";
        // Insert the post into the database

    }
    echo "</pre>";
}
