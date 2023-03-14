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
                //FIX - 4 // - Avoid Long Names  // 
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
$yaraMainWooProduct;
$yaraVariationWooProduct = 0;
$yaraTotalProducts = 0;


function yara_create_product($data)
{
    $product = new WC_Product_Variable();
    $product->set_description($data['post_content']);
    $product->set_name($data['post_title']);
    $product->set_sku($data['post_content']);
    $product->set_price($data['post_price']);
    $product->set_regular_price($data['post_price']);
    $product->set_stock_status();
    $product->save();
    return $product;
}

function yara_create_attributes($name, $options)
{
    $attribute = new WC_Product_Attribute();
    $attribute->set_id(0);
    $attribute->set_name($name);
    $attribute->set_options($options);
    $attribute->set_visible(true);
    $attribute->set_variation(true);
    return $attribute;
}

function yara_create_variations($product_id, $values, $data)
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
    return $variation;
}

function yara_get_data()
{

    global $yaraMainProduct, $yaraMainWooProduct, $yaraVariationWooProduct, $yaraTotalProducts;


    $api_url = 'https://dummyjson.com/products';   // Source URL  
    $json_data = file_get_contents($api_url); // Read JSON file    
    $response_data = json_decode($json_data); // Decode JSON data into PHP array   
    $products_data = $response_data->products; // All user data products in 'data' object    
    //$products_data = array_slice($products_data, 0, 3); // FOR DEBUG !!! Cut long data into small & select only first few records

    $atts = $names = $images = $prices = [];
    foreach ($products_data as $index => $product) {
        $mainProduct = 0;
        if ($index % 3 == 0) {
            $mainProduct = 1;
        }
        $productCategory = category_exists($product->category);
        $names[] = $product->title;
        $images[] = $product->images[0];
        $prices[] = $product->price;

        if (!$productCategory) {
            //BUG - 1 //The category needs more final touch !!!! Not Complete yet !!!
            wp_insert_term($product->category, 'product_cat', array(
                'description' => $product->category
            ));
            $productCategory = category_exists($product->category);
            add_term_meta($productCategory, 'display_type', 'products');
        }

        //BUG - 1 // post_category WOO products do not use default categories !!!
        if ($mainProduct) {
            $yara_post_type = 'product';
            $yara_post_parent = 0;
            $my_yara_product_post = array(
                'post_title'    => $product->title,
                'post_content'  =>  $product->description,
                'post_status'   => 'publish',
                'post_type'   => $yara_post_type,
                'post_parent'   => $yara_post_parent,
                'post_category' => array($productCategory),
                'post_price' => $product->price,
                'post_author'   => 1
            );

            $mypostExist = post_exists($product->title);
            if ($mypostExist == NULL) {
                $yaraMainWooProduct = yara_create_product($my_yara_product_post);
                $myNewPost = $yaraMainWooProduct->get_id();
                //FIX - 1 // - scrap/send more data Alternative Text,Caption,Description//
                if ($product->images[0]) {
                    //FIX - 2 // - check if already Exist !!! //
                    //FIX - 3 // - Set Some !!!DELAY!!! (write in loop) //
                    $myProductImageId = bg_image_upload($product->images[0], $product->title);
                    if (!add_post_meta($myNewPost, '_thumbnail_id', $myProductImageId, true)) {
                        update_post_meta($myNewPost, '_thumbnail_id', $myProductImageId);
                    }
                }
                $yaraTotalProducts++;
            }
        } else {
            $yara_post_type = 'product_variation';
            $yara_post_parent = $yaraMainProduct['ID'];

            //create variations
            if ($yaraVariationWooProduct == 0) {
                $yaraVariationWooProduct++;
            } else if ($yaraVariationWooProduct == 1) {
                
                if ($yaraMainWooProduct) {

                    // BUG 2 function need lowercase string without whitespaces .. name can be updated after options was created by ID
                    $anOption = strtolower(str_replace(array( '\'', '"', ',' , ';', '<', '>', '.', ' ' ), '_',  $names[0]));
                    $atts[] = yara_create_attributes($anOption, $names);

                    $yaraMainWooProduct->set_attributes($atts);
                    $yaraMainWooProduct->save();
                    foreach ($names as $index => $name) {

                        $options_data = new stdClass();
                        $options_data->sku = $name;
                        $options_data->price = $prices[$index];
                        //Create variations
                        $yaraNewVariation = yara_create_variations($yaraMainWooProduct->get_id(), [$anOption => $name], $options_data);
                        if ($images[$index]) {
                            $myProductImageId = bg_image_upload($images[$index], $product->title);
                            if (!add_post_meta($yaraNewVariation->get_id(), '_thumbnail_id', $myProductImageId, true)) {
                                update_post_meta($yaraNewVariation->get_id(), '_thumbnail_id', $myProductImageId);
                            }
                        }
                    }
                }
                $yaraVariationWooProduct = 0;
                $atts = $names = $images = $prices = [];
            }
        }
    }
    if($yaraTotalProducts > 0) {
    echo "<h3> " , $yaraTotalProducts , " Product/s was successfully created</h3>";
    }else {
        echo "<h3>Nothing to update</h3>";
    }
}
