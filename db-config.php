<?php

/*--db configurations--*/
$offlineHosts = array("127.0.0.1", "localhost", "192.168.1.18");

//check if online or offline then update the settings accordingly
if(in_array($_SERVER['HTTP_HOST'], $offlineHosts)){
	define("DB_HOST", "localhost");
	define("DB_NAME", "class.db");
	define("DB_USER", "root");
	define("DB_PASSWORD", "password");
}
//online DB config
else{
	define("DB_HOST","online-host.com");
	define("DB_NAME","db-name");
	define("DB_USER","db-user");
	define("DB_PASSWORD","db-password");
}

//include class
require_once("DB.php");

//initialize connection with the database
$db = new DB(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

