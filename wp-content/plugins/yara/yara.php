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

function bg_image_upload($url) {

    // Gives us access to the download_url() and wp_handle_sideload() functions
   // require_once( ABSPATH . 'wp-admin/includes/file.php' );
  
    // Download file to temp dir
    $timeout_seconds = 10;
    $temp_file = download_url( $url, $timeout_seconds );
  
    if ( !is_wp_error( $temp_file ) ) {
        echo "TEMP FILE";
        print_r($temp_file);
        echo "</br>";
        echo "getimagesize";


        echo "getimagesize2";
        $info = getimagesize($temp_file);
        print_r($info);
        echo "</br>";
       $allImagesSizes = wp_get_registered_image_subsizes();; $_wp_additional_image_sizes; 
        print '<pre>'; 
        print_r( $allImagesSizes ); 
        print '</pre>';
  
        // Array based on $_FILE as seen in PHP file uploads
        $file = array(
            'width' => $info[0],
            'height' => $info[1],
            'hwstring_small' => "height='{$info[1]}' width='{$info[0]}'",
            'name'     => basename($url), // ex: wp-header-logo.png
            'type'     => 'image/png',
            'tmp_name' => $temp_file,
            'sizes' => $allImagesSizes,  
            'error'    => 0,
            'size'     => filesize($temp_file),
        );
  
        $overrides = array(
            // Tells WordPress to not look for the POST form
            // fields that would normally be present as
            // we downloaded the file from a remote server, so there
            // will be no form fields
            // Default is true
            'test_form' => false,
  
            // Setting this to false lets WordPress allow empty files, not recommended
            // Default is true
            'test_size' => true,
        );
  
        // Move the temporary file into the uploads directory
        $results = wp_handle_sideload( $file, $overrides );
  
        if ( !empty( $results['error'] ) ) {
            // Insert any error handling here
        } else {
  
            $filename  = $results['file']; // Full path to the file
            $local_url = $results['url'];  // URL to the file in the uploads dir
            $type = $results['type']; // MIME type of the file
            $wp_upload_dir = wp_upload_dir(); // Get the path to the upload directory.
            echo "try";
            echo "</br>";
            echo "basename( $filename ) - " . basename( $filename );
            echo "</br>";
            echo "filename - " .  $filename;
            echo "</br>";
            echo "local_url - " .  $local_url;
            echo "</br>";
            echo "wp_upload_dir - " .  $wp_upload_dir;
            echo "</br>";
            echo "basename(url) - " .  basename($url);


  
            $attachment = array (
              'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
              'post_mime_type' => $type,
              'post_status' => 'inherit',              
              'post_content' => '',
            );
  
            $img_id = wp_insert_attachment( $attachment, $filename  );



            $attach_data = wp_generate_attachment_metadata( $img_id, $filename );
            wp_update_attachment_metadata( $img_id,  $attach_data );

           // $resizedImages = wp_insert_attachment( $img_id, $filename);

  
            return $img_id;
        }
    }
  }



function yara_get_data()
{

    $api_url = 'https://dummyjson.com/products';
    // Read JSON file
    $json_data = file_get_contents($api_url);
    // Decode JSON data into PHP array
    $response_data = json_decode($json_data);
    // All user data exists in 'data' object
    $products_data = $response_data->products;
    // Cut long data into small & select only first 10 records
    //$products_data = array_slice($products_data, 0, 2);

    // Print data if need to debug
    echo "<pre>";
    print_r($products_data);
    echo "</pre>";

    echo "<pre>";
    // Traverse array and display user data
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

        

        // [category] => smartphones
        //echo "----------------------------------------------------------------";
        //echo "<br /><br />";
        //echo "name: ".$product->employee_age;
        //echo "<br /> <br />";

        $productCategory = category_exists($product->category);

        echo "category exist ?: " . $productCategory;
        echo "<br />";

        if (!$productCategory) {
            echo "missibg" . $productCategory;
            echo "<br />";
            wp_insert_term($product->category, 'product_cat', array(
                'description' => $product->category
            ));
            $productCategory = category_exists($product->category);
            add_term_meta($productCategory, 'display_type', 'products');
        }


        $mypostExist = post_exists($product->title);
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

            if (!add_post_meta($myNewPost, '_regular_price', $product->price, true)) {
                update_post_meta($myNewPost, '_regular_price', $product->price);
            }

            if (!add_post_meta($myNewPost, '_price', $product->price, true)) {
                update_post_meta($myNewPost, '_price', $product->price);
            }

           // imagesUpload($imgUrl);
           if($product->images[0]){
            $myProductImageId = bg_image_upload($product->images[0]);
            print_r($myProductImageId);
            echo "<br />";
            if (!add_post_meta($myNewPost, '_thumbnail_id', $myProductImageId, true)) {
                update_post_meta($myNewPost, '_thumbnail_id', $myProductImageId);
            }

           }



            print_r($myNewPost);
            echo "<br />";
            print_r($productCategory);
            echo "<br />";

            do_action('add_term_relationship', $myNewPost, $productCategory, 0);
        } else {
            //Update the post
        }

        echo "----------------------------------------------------------------";
        echo "<br /><br />";
        // Insert the post into the database

    }
    echo "</pre>";
}
