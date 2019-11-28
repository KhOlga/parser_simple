<?php
##### required files #####
require_once('db_config.php');

function DB_connection(){
    $db_connection = new PDO(DB_DSN, DB_USER, DB_PASS);
    
    return $db_connection;
}
