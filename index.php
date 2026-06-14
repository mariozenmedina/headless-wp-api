<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: Content-Type");
    include get_stylesheet_directory() . '/class/wpjsonapi.class.php';
    if(
        $_POST
        && isset($_POST['token'])
        && $_POST['token'] == 'token'
    ){
        $wpJsonApi = new wpJsonApi($_POST);
    }
?>