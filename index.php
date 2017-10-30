<?php
/**
 * Created by PhpStorm.
 * User: techbeast
 * Date: 27/12/15
 * Time: 4:46 PM
 * Description: Examples of how this micro-framework can be used.
 */

/*-- this is a database test file --*/

//include your db config file, and initialize the DB connection
require_once("db-config.php");

/*-- insert --*/
$query = "INSERT INTO my_table (name, email) VALUES ('techbeast', 'techbeast@example.com')";
//$lastInsertId = $db->query($query);

/*-- update --*/
$query = "UPDATE my_table SET name = 'TECHBEAST' WHERE id = 1";
$rowsAffected = $db->query($query);

/*-- delete --*/
$query = "DELETE FROM my_table WHERE id = 2";
$rowsAffected = $db->query($query);

/*-- select all records and return result in OBJECT form --*/
$query = "SELECT * FROM my_table";
$result = $db->getResults($query);
#echo "<pre>"; print_r($result); echo "</pre>";

/*-- select all records and return result in ASSOCIATIVE ARRAY --*/
$query = "SELECT * FROM my_table";
$result = $db->getResults($query, ARRAY_A);
#echo "<pre>"; print_r($result); echo "</pre>";

/*-- select all records and return result in NUMERICAL ARRAY --*/
$query = "SELECT * FROM my_table";
$result = $db->getResults($query, ARRAY_N);
#echo "<pre>"; print_r($result); echo "</pre>";

/*-- select first row from the result --*/
$query = "SELECT * FROM my_table";
$result = $db->getRow($query);
#echo "<pre>"; print_r($result); echo "</pre>";

/*-- select first value from the result --*/
$query = "SELECT * FROM my_table";
$result = $db->getVar($query);
#echo "<pre>"; print_r($result); echo "</pre>";

/*-- var dump --*/
#$db->varDump($result);

/*-- get column info of last query --*/
$colInfo = $db->getColInfo();
#echo "<pre>"; print_r($colInfo); echo "</pre>";

$firstColumns = $db->getCol();
echo "<pre>"; print_r($firstColumns); echo "</pre>";


//debug the last query
$db->debug();
