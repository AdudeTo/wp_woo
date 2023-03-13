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
$yaraMainWooProduct;
$yaraVariationWooProduct = 0;


function pricode_create_product($data)
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

    global $yaraMainProduct, $yaraMainWooProduct, $yaraVariationWooProduct;





    $api_url = 'https://dummyjson.com/products';   // Source URL  
    $json_data = file_get_contents($api_url); // Read JSON file    
    $response_data = json_decode($json_data); // Decode JSON data into PHP array   
    $products_data = $response_data->products; // All user data products in 'data' object    
    $products_data = array_slice($products_data, 0, 3); // FOR DEBUG !!! Cut long data into small & select only first few records

    // Print data if need to debug
    //echo "<pre>";
    //print_r($products_data);
    //echo "</pre>";

    echo "<pre>";
    // Traverse array and display data
    $atts = [];
    $names = [];
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
        $names[] = $product->title;

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

            //create variations
            if ($yaraVariationWooProduct == 0) {


                //$atts[] = pricode_create_attributes('color', ['red', 'green']);
                $yaraVariationWooProduct++;
                //Adding attributes to the created product




            } else if ($yaraVariationWooProduct == 1) {

                //Creating Attributes 
                /*
               
                    $atts = [];
                    $atts[] = pricode_create_attributes('col_or', ['red cato', 'green mnogo green po4ti 5']);
                   
                    //Adding attributes to the created product
                    $yaraMainWooProduct->set_attributes($atts);
                    $yaraMainWooProduct->save();

                    //Setting data (following Alexander's rec
                    $data = new stdClass();
                    $data->sku = 'sku-123';
                    $data->price = '10';
                    //Create variations
                    pricode_create_variations($yaraMainWooProduct->get_id(), ['col_or' => 'red cato'], $data);

                    $dataB = new stdClass();
                    $dataB->sku = 'sku-1235';
                    $dataB->price = '15';
                    pricode_create_variations($yaraMainWooProduct->get_id(), ['col_or' => 'green mnogo green po4ti 5',], $dataB);
                  */



                $anOption = strtolower(str_replace(' ', '_', $names[0]));


                $atts[] = pricode_create_attributes($anOption, $names);

                $yaraMainWooProduct->set_attributes($atts);
                $yaraMainWooProduct->save();


                echo "<pre>";
                print_r($yaraMainWooProduct);
                echo "</pre>";

               


                $options_data = new stdClass();
                $options_data->sku = $names[0];
                $options_data->price = '10';
                //Create variations
                pricode_create_variations($yaraMainWooProduct->get_id(), [$anOption => $names[0]], $options_data);

                $options_data = new stdClass();
                $options_data->sku = $names[1];
                $options_data->price = '15';
                //Create variations
                pricode_create_variations($yaraMainWooProduct->get_id(), [$anOption => $names[1]], $options_data);

                $options_data = new stdClass();
                $options_data->sku = $names[2];
                $options_data->price = '20';
                //Create variations
                pricode_create_variations($yaraMainWooProduct->get_id(), [$anOption => $names[2]], $options_data);




                $yaraVariationWooProduct = 0;
                $atts = [];
                $names = [];
            }
        }


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

        if ($mypostExist == NULL) {
            //$myNewPost = wp_insert_post($my_yara_product_post);
            if ($mainProduct) {
                //$yaraMainProduct['ID'] = $myNewPost;
                $yaraMainWooProduct = pricode_create_product($my_yara_product_post);


                echo "PPR:::";
            }
            $myNewPost = post_exists($product->title);


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
            // do_action('add_term_relationship', $myNewPost, $productCategory, 0);
        } 

    }
    echo "</pre>";
}
