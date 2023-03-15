<?php

/**
 * Plugin Name: Yara 2
 * Plugin URI: 
 * Description: Yara Code Challenge
 * Version: 1.0.0
 * Author: Vicho Vichev
 * Author URI: https://vichev.art
 * Requires PHP: 7.4
 *
 * @package YaraPlugin
 */

//ini_set('display_errors','On');
//ini_set('error_reporting', E_ALL );
//define('WP_DEBUG', true);
//define('WP_DEBUG_DISPLAY', true);



defined('ABSPATH') || die;
define('YARA_CC_VERSION', '1.0.0');
define('YARA_CC_PATH', plugin_dir_url(__FILE__));



function yara_create_db()
{

    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'yara_products';

    $isYaraProductTable = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table_name));

    if (!$wpdb->get_var($isYaraProductTable) == $table_name) {
        $yaraProductTable = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            views smallint(5) NOT NULL,
            clicks smallint(5) NOT NULL,
            title varchar(200) NOT NULL,
            category varchar(200) NOT NULL,
            description longtext NOT NULL,
            price mediumint(9) NOT NULL,
            image_link longtext NOT NULL,
            image_att_id mediumint(9) NOT NULL,
            yara_type varchar(50) NOT NULL,
            wp_id mediumint(9) NOT NULL,
            souce_id mediumint(9) NOT NULL,
            related_id mediumint(9) NOT NULL,
            UNIQUE KEY id (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($yaraProductTable);
    }

    //just ping to init plugin start
    $wpdb->INSERT($table_name, array('clicks' => 1));  //     (`object_id`,`term_taxonomy_id`) values (5,5))";

}
register_activation_hook(__FILE__, 'yara_create_db');


// CRON JOB 123

// add custom interval
function cron_add_minute($schedules)
{
    // Adds once every 5 minutes 'interval' => 300 cant be less than 1min 60 to the existing schedules.
    $schedules['everyfiveminutes'] = array(
        'interval' => 60,
        'display' => __('Once Every 5 Minute')
    );
    return $schedules;
}
add_filter('cron_schedules', 'cron_add_minute');

// create a scheduled event (if it does not exist already)
function cronstarter_activation()
{
    if (!wp_next_scheduled('yara_mycronjob')) {
        wp_schedule_event(time(), 'everyfiveminutes', 'yara_mycronjob');
    }
}
// and make sure it's called whenever WordPress loads
add_action('wp', 'cronstarter_activation');


// unschedule event upon plugin deactivation
function cronstarter_deactivate()
{
    // find out when the last event was scheduled
    $timestamp = wp_next_scheduled('yara_mycronjob');
    // unschedule previous event if any
    wp_unschedule_event($timestamp, 'yara_mycronjob');
}
register_deactivation_hook(__FILE__, 'cronstarter_deactivate');


// CRON JOB 123

// DB Fields title category description price image_link image_att_id yara_type wp_id souce_id related_id
// if runs everything at once plus images generator you will slow down server (also can crash it)
// so we will prepare an database will put all data here then slowly will process it
// on next cron run we just will check for new items that are not procesed and create it
// also will check for updates on old ones (faster and secure)






function yara_code_challenge_setup_menu()
{
    add_menu_page('Yara Code Challenge', 'Yara Plugin', 'manage_options', 'yara-plugin', 'yara_init');
}
add_action('admin_menu', 'yara_code_challenge_setup_menu');

function yara_scrap_data()
{
    $apiUrl = 'https://dummyjson.com/products';   // Source URL  
    $jsonData = file_get_contents($apiUrl); // Read JSON file    
    $responseData = json_decode($jsonData); // Decode JSON data into PHP array   
    $productsData = $responseData->products; // All user data products in 'data' object       
    return $productsData;
}

function yara_js_functions()
{
    $yaraData['pluginDirUrl'] = YARA_CC_PATH;
    $yaraData['itemsData'] =  yara_scrap_data();
    wp_enqueue_style('yara-my-style',  YARA_CC_PATH . 'assets/SCSS/main.css', false, '1.0', 'all');
    wp_enqueue_script('advanced-script', YARA_CC_PATH . 'assets/apps/app.js', NULL, NULL, true);
    wp_localize_script(
        'advanced-script',
        'advanced_script_vars',
        $yaraData
    );
}

add_action('admin_enqueue_scripts', 'yara_js_functions');

function yara_init()
{
    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        //yara_get_data();
        yara_page_bilder();
    } else {
        echo "You need to install woocomerce!";
    }
}

function yara_page_bilder()
{
    echo '<div class="yaraMainHolder" id="yaraMainHolder"></div>';
    //cron debug
   // yara_repeat_function();
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

$yaraMainProduct = array("ID" => 0, "title" => "none");
$yaraMainWooProduct;
$yaraVariationWooProduct = 0;
$yaraTotalProducts = 0;

// here's the function we'd like to call with our cron job  !!allow_url_fopen = On in php.ini or htaccess
function yara_repeat_function()
{

    global $wpdb, $yaraMainWooProduct, $yaraVariationWooProduct;
    // ping to DB to be sure that cron works
    $current_datetime = current_datetime()->format('Y-m-d H:i:s');
    $table_name = $wpdb->prefix . 'yara_products';
    $wpdb->INSERT($table_name, array('clicks' => 8, 'time' => $current_datetime));


    $getSourceData = yara_scrap_data();


    // DB Fields title category description price image_link image_att_id yara_type wp_id souce_id related_id
    $yaraNewProducts = 0;
    foreach ($getSourceData as $index => $product) {
        $productImage = 'empty';
        if ($product->images[0]) {
            $productImage = htmlentities($product->images[0], ENT_COMPAT, 'UTF-8', true);
        }
        $ifPostExist = $wpdb->get_results("SELECT * FROM wp_yara_products WHERE title = '$product->title'");
        if (!$ifPostExist) {
            $wpdb->INSERT($table_name, array(
                'clicks' => 200,
                'time' => $current_datetime,
                'title' => $product->title,
                'category' => $product->category,
                'description' => $product->description,
                'price' => $product->price,
                'image_link' => $productImage,
                'souce_id' => $product->id,
            ));
            $yaraNewProducts++;
        } else {
            //check for updates ETC
        }
    }

    $wpdb->INSERT($table_name, array('views' => $yaraNewProducts, 'clicks' => 9, 'time' => $current_datetime));
   $getYaraPosts = $wpdb->get_results("SELECT * FROM  $table_name  WHERE clicks = 200 AND wp_id = 0 ORDER BY souce_id ASC LIMIT 9");

   // $query = $wpdb->query("SELECT * FROM  $table_name  WHERE clicks = 200 AND wp_id = 0 ORDER BY souce_id ASC LIMIT 9");
   // $getYaraPosts = $wpdb->get_results($query);

  //  $query = $wpdb->query( 
     //   $wpdb->prepare( 
      //      "SELECT * FROM  $table_name  WHERE clicks = 200 AND wp_id = 0 ORDER BY souce_id ASC LIMIT 9"
      //  )
   // );
//
    //$getYaraPosts = $wpdb->get_results($query);

    
   // $getYaraPosts = $wpdb->query("SELECT * FROM  $table_name WHERE clicks = 200 AND wp_id = 0 ORDER BY souce_id ASC LIMIT 9"); 
       

  // echo("REST - - - - - 00 ");
   // if ($getYaraPosts) {  
        $wpdb->INSERT($table_name, array('views' => 777, 'clicks' => 777, 'time' => $current_datetime));      
       // $yara_post_parent = $yara_post_type = $yara_post_parent = $yaraMainProduct = NULL;
        $atts = $names = $images = $prices = $souceID = [];
       // echo("REST - - - - - 01 ");
        foreach ($getYaraPosts as $details) {
           // echo("REST - - - - - ");
            print_r($details);
           
            $productCategory = category_exists($details->category);
            $names[] = $details->title;
            $images[] = $details->image_link;
            $prices[] = $details->price;
            $souceID[] = $details->souce_id;

            if (!$productCategory) {
                //BUG - 1 //The category needs more final touch !!!! Not Complete yet !!!
                wp_insert_term($details->category, 'product_cat', array(
                    'description' => $details->category
                ));
                $productCategory = category_exists($details->category);
                add_term_meta($productCategory, 'display_type', 'products');
            }

            $wpdb->INSERT($table_name, array('views' => $yaraVariationWooProduct, 'clicks' => 66, 'time' => $current_datetime));
            

            if ($yaraVariationWooProduct == 0) {
                $yara_post_type = 'product';
                $yara_post_parent = 0;
                $my_yara_product_post = array(
                    'post_title'    => $details->title,
                    'post_content'  =>  $details->description,
                    'post_status'   => 'publish',
                    'post_type'   => $yara_post_type,
                    'post_parent'   => $yara_post_parent,
                    'post_category' => array($productCategory),
                    'post_price' => $details->price,
                    'post_author'   => 1
                );
                $yaraMainWooProduct = yara_create_product($my_yara_product_post);
                $newProduct = $yaraMainWooProduct->get_id();
                $wpdb->update($table_name, array('image_att_id' => 1, 'wp_id' => $newProduct), array('souce_id' => $details->souce_id));
                $wpdb->INSERT($table_name, array('views' => $details->souce_id, 'wp_id' => $newProduct, 'clicks' => 42, 'time' => $current_datetime));

                if ($details->image_link) {
                    $myProductImageId = bg_image_upload($details->image_link, $details->title);
                    if (!add_post_meta($newProduct, '_thumbnail_id', $myProductImageId, true)) {
                        update_post_meta($newProduct, '_thumbnail_id', $myProductImageId);
                    }
                    $wpdb->update($table_name, array('image_att_id' => $myProductImageId), array('souce_id' => $details->souce_id));
                } 
                $yaraVariationWooProduct++;
            } else if ($yaraVariationWooProduct == 1) {
                $relatedParent = $yaraMainWooProduct->get_id();
                $wpdb->update($table_name, array('image_att_id' => 1, 'wp_id' => 1, 'related_id' => $relatedParent), array('souce_id' => $details->souce_id));
                $wpdb->INSERT($table_name, array('views' => $details->souce_id, 'related_id' => $relatedParent, 'clicks' => 43, 'time' => $current_datetime));
                $yaraVariationWooProduct++;
            } else if ($yaraVariationWooProduct == 2) {
                $relatedParent = $yaraMainWooProduct->get_id();
                $wpdb->update($table_name, array('image_att_id' => 1, 'wp_id' => 1, 'related_id' => $relatedParent), array('souce_id' => $details->souce_id));
                $wpdb->INSERT($table_name, array('views' => $details->souce_id, 'related_id' => $relatedParent, 'clicks' => 44, 'time' => $current_datetime));
                $yaraVariationWooProduct == 0;

                
                if ($yaraMainWooProduct) {
                    // BUG 2 function need lowercase string without whitespaces .. name can be updated after options was created by ID
                    $anOption = strtolower(str_replace(array('\'', '"', ',', ';', '<', '>', '.', ' '), '_',  $names[0]));
                    $atts[] = yara_create_attributes($anOption, $names);

                    $yaraMainWooProduct->set_attributes($atts);
                    $yaraMainWooProduct->save();
                    
                    foreach ($names as $index => $name) {
                        $options_data = new stdClass();
                        $options_data->sku = $name;
                        $options_data->price = $prices[$index];
                        //Create variations
                        $yaraNewVariation = yara_create_variations($yaraMainWooProduct->get_id(), [$anOption => $name], $options_data);
                        $newProduct = $yaraNewVariation->get_id();

                        $wpdb->update($table_name, array('image_att_id' => 1, 'wp_id' => $newProduct), array('souce_id' => $souceID[$index]));
                        $wpdb->INSERT($table_name, array('views' => $details->souce_id, 'related_id' => 16, 'clicks' => 44, 'time' => $current_datetime));
                        
                        if ($images[$index]) {
                            $myProductImageId = bg_image_upload($images[$index], $product->title);
                            if (!add_post_meta($yaraNewVariation->get_id(), '_thumbnail_id', $myProductImageId, true)) {
                                update_post_meta($yaraNewVariation->get_id(), '_thumbnail_id', $myProductImageId);
                            }
                            $wpdb->update($table_name, array('image_att_id' => $myProductImageId), array('souce_id' => $souceID[$index]));
                        }                        
                    }
                    $yaraVariationWooProduct = 0;
                    $atts = $names = $images = $prices = $souceID = [];
                }
            }
        }
   // }
}

// hook that function onto our scheduled event:
add_action('yara_mycronjob', 'yara_repeat_function');

