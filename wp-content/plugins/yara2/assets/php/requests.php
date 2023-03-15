<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header("Access-Control-Allow-Headers: X-Requested-With");

$page = $_GET['p'];
$list = [];

include($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');


if (current_user_can('administrator')) {   
    if($page){
        include 'templates/' . $page . '.php';
        echo json_encode($list, JSON_PRETTY_PRINT);
    }
} else {
    //return error.php data
    //echo ". . ."; 
}