<?php 
global $wpdb;
// exclude * pick up only what you need !!!
$getYaraPosts = $wpdb->get_results("SELECT * FROM wp_yara_products WHERE clicks = 200");
foreach ($getYaraPosts as $details) {
   // for debug 
   // echo "title:" . $details->title . "\n";    
   // echo "souce_id:" . $details->souce_id . "\n";
   // echo "wp_id:" . $details->wp_id . "\n";
   // echo "image_att_id:" . $details->image_att_id . "\n \n";
    $list[] = (object) [
        'title' => $details->title,
        'souce_id' => $details->souce_id,
        'wp_id' => $details->wp_id,
        'image_att_id' => $details->image_att_id
      ];    
}
?>
