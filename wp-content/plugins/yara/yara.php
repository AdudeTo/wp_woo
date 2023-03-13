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


/**
 * Register an attribute taxonomy.
 */
function so_29549525_create_attribute_taxonomies()
{

    $attributes = wc_get_attribute_taxonomies();

    $slugs = wp_list_pluck($attributes, 'attribute_name');

    if (!in_array('my_PPcolor', $slugs)) {

        $args = array(
            'slug'    => 'my_PPcolor',
            'name'   => __('My PPColor', 'your-textdomain'),
            'type'    => 'select',
            'orderby' => 'menu_order',
            'has_archives'  => false,
        );

        $result = wc_create_attribute($args);
    }
}
//add_action( 'admin_init', 'so_29549525_create_attribute_taxonomies' );





//FIX - 1 // Images can have more metadata for ALT and <CAPTION> !!!
function bg_image_upload($url, $productTitle)
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


$yaraMainProduct = array("ID" => 0, "title" => "none");
$testPost = get_post(1);
$testPostMeta = get_post_meta(1);
$testPostMetaB = wc_get_order(1);



function pricode_create_product()
{
    $product = new WC_Product_Variable();
    $product->set_description('T-shirt variable description');
    $product->set_name('T-shirt variable');
    $product->set_sku('test-shirt');
    $product->set_price(1);
    $product->set_regular_price(1);
    $product->set_stock_status();
    $product->save();
    return $product;
}

/**
 * Create Product Attributes 
 * @param  string $name    Attribute name
 * @param  array $options Options values
 * @return Object          WC_Product_Attribute 
 */
function pricode_create_attributes($name, $options)
{
    $attribute = new WC_Product_Attribute();
    $attribute->set_id(0);
    $attribute->set_name($name);
    $attribute->set_options($options);
    $attribute->set_visible(true);
    $attribute->set_variation(true);
    return $attribute;
}

/**
 * [pricode_create_variations description]
 * @param  [type] $product_id [description]
 * @param  [type] $values     [description]
 * @return [type]             [description]
 */
function pricode_create_variations($product_id, $values, $data)
{
    $variation = new WC_Product_Variation();
    $variation->set_parent_id($product_id);
    $variation->set_attributes($values);
    $variation->set_status('publish');
    $variation->set_sku($data->sku);
    $variation->set_price($data->price);
    $variation->set_regular_price($data->price);
    $variation->set_stock_status();
    $variation->save();
    $product = wc_get_product($product_id);
    $product->save();
}









function yara_get_data()
{
    
    //Adding product
    $product = pricode_create_product();

    //Creating Attributes 
    $atts = [];
    $atts[] = pricode_create_attributes('color', ['red', 'green']);
    $atts[] = pricode_create_attributes('size', ['S', 'M']);

    //Adding attributes to the created product
    $product->set_attributes($atts);
    $product->save();

    //Setting data (following Alexander's rec
    $data = new stdClass();
    $data->sku = 'sku-123';
    $data->price = '10';
    //Create variations
    pricode_create_variations($product->get_id(), ['color' => 'red', 'size' => 'M'], $data);

    $dataB = new stdClass();
    $dataB->sku = 'sku-1235';
    $dataB->price = '15';
    pricode_create_variations($product->get_id(), ['color' => 'green', 'size' => 'S'], $dataB);
}



function yara_get_data555()
{

    global $wpdb;
    $yara_term_variable = term_exists('variable');

    if (!$yara_term_variable) {
        $yara_term_variable = wp_create_term('variable')['term_id'];
    }
    echo "</br>";
    echo "------------------------------------------------------------------";
    echo $yara_term_variable;
    echo "------------------------------------------------------------------";
    echo "</br>";


    //updateProductAttributes();
    //so_29549525_create_attribute_taxonomies();

    global $yaraMainProduct, $testPost, $testPostMeta, $testPostMetaB;
    echo "<pre>";
    echo "</br>";
    echo "------------------------------------------------------------------";


    print_r($testPostMetaB);
    print_r($testPostMeta['_product_attributes'][0]);
    $dataTest = $testPostMeta['_product_attributes'][0];
    echo "</br>";
    $newData = json_decode($dataTest);

    echo ($newData);




    echo "</br>";
    echo "------------------------------------------------------------------";
    echo "</pre>";



    $api_url = 'https://dummyjson.com/products';   // Source URL  
    $json_data = file_get_contents($api_url); // Read JSON file    
    $response_data = json_decode($json_data); // Decode JSON data into PHP array   
    $products_data = $response_data->products; // All user data products in 'data' object    
    //$products_data = array_slice($products_data, 0, 3); // FOR DEBUG !!! Cut long data into small & select only first few records

    // Print data if need to debug
    //echo "<pre>";
    //print_r($products_data);
    //echo "</pre>";

    echo "<pre>";
    // Traverse array and display data
    foreach ($products_data as $index => $product) {
        //print_r($product);
        $mainProduct = 0;
        if ($index % 3 == 0) {
            $mainProduct = 1;
        }

        echo "yaraMainProduct ID: " . $yaraMainProduct['ID'];
        echo "<br />";
        echo "Count: " . $index . " mainProduct: " . $mainProduct;
        echo "<br />";
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
        if ($mainProduct) {
            $yara_post_type = 'product';
            $yara_post_parent = 0;
        } else {
            $yara_post_type = 'product_variation';
            $yara_post_parent = $yaraMainProduct['ID'];
        }

        
        $my_yara_product_post = array(
            'post_title'    => $product->title,
            'post_content'  =>  $product->description,
            'post_status'   => 'publish',
            'post_type'   => $yara_post_type,
            'post_parent'   => $yara_post_parent,
            'post_category' => array($productCategory),
            'post_author'   => 1
        );

        if ($mypostExist == NULL) {
            $myNewPost = wp_insert_post($my_yara_product_post);
            if ($mainProduct) {
                $yaraMainProduct['ID'] = $myNewPost;
            }


            // add_post_meta( int $post_id, string $meta_key, mixed $meta_value, bool $unique = false ): int|false
            //FIX - 1 // - scrap/send more data Alternative Text,Caption,Description//
            if ($product->images[0]) {
                //FIX - 2 // - check if already Exist !!! //
                //FIX - 3 // - Set Some !!!DELAY!!! (write in loop) //
                $myProductImageId = bg_image_upload($product->images[0], $product->title);
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
        if ($mainProduct) {
            do_action('add_term_relationship',  $yaraMainProduct['ID'], $yara_term_variable);
            //$sql = $wpdb->INSERT('wp_term_relationships', array('object_id' => $yaraMainProduct['ID'], 'term_taxonomy_id' => $yara_term_variable));  //     (`object_id`,`term_taxonomy_id`) values (5,5))";
            // $wpdb->query($sql);

            $createVariableProduct = 'a:1:{s:3:"new";a:6:{s:4:"name";s:3:"new";s:5:"value";s:9:"1 | 2 | 3";s:8:"position";i:0;s:10:"is_visible";i:1;s:12:"is_variation";i:1;s:11:"is_taxonomy";i:0;}}';

            $sql = $wpdb->INSERT('wp_postmeta', array('post_id' => $yaraMainProduct['ID'], 'meta_key' => '_product_attributes', 'meta_value' => $createVariableProduct));  //     (`object_id`,`term_taxonomy_id`) values (5,5))";
            $wpdb->query($sql);
            do_action('add_term_relationship', 5, 3);
            // $createVariableProduct = "a:1:{s:3:old;a:6:{s:4:name;s:3:old;s:5:value;s:9:4 | 5 | 6;s:8:position;i:0;s:10:is_visible;i:1;s:12:is_variation;i:1;s:11:is_taxonomy;i:0;}}";
            // if (!add_post_meta($myNewPost, '_product_attributes', $createVariableProduct, true)) {
            //     update_post_meta($myNewPost, '_price', $createVariableProduct);
            // }
        }



        echo "----------------------------------------------------------------";
        echo "<br /><br />";
        // Insert the post into the database

    }
    echo "</pre>";
}
