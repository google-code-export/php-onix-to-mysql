<?php
##########################################################################
##########################################################################
##                                                                      ##
##  This script processes ONIX xml files and inserts them into a        ##
##  database. This database does not need to have any pre-existing      ##
##  tables or collums, these will be automatically created by the       ##
##  script.                                                             ##
##                                                                      ##
##                                                                      ##
##                                                                      ##
##  AFTER RUNNING THIS SCRIPT:                                          ##
##  update table collums etc. to match content type.                    ##
##  Also you might want to ad some primary keys and indexes afterwards  ##
##                                                                      ##
##  Author: Jonathan van Bochove                                        ##
##  Author url: www.johannes-multimedia.nl                              ##
##  Author e-mail: webmaster@johannes-multimedia.nl                     ##
##  Licence: Copyright (c) 2011 Johannes Multimedia                     ##
##  Released under the GNU General Public License                       ##
##  Version 1.0 (2011-10-11)                                            ##
##                                                                      ##
##                                                                      ##
##                                                                      ##
##  If you make any alterations to this script to make it more use-     ##
##  full, faster or more efficient, please send a copy of the updated   ##
##  script to the author, and mention what was updated/changed.         ##
##                                                                      ##
##                                                                      ##
##########################################################################
##                                                                      ##
##  edit settings below:                                                ##
##                                                                      ##
##########################################################################
##########################################################################

$mem = 1000000; // Onix chunk size (script won't process more then this at once)
$file = "~/onix.xml"; // Location of onix file
$dbhost = "localhost"; // mysql host
$dbuser = "my_username"; //mysql username
$dbpw = "my_password"; // mysql user password
$db = "my_database"; // mysql database name
$uri = "http://". $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF']; // scriptlocation e.g. "http://www.example.com/onix/script.php";

##########################################################################
##########################################################################
##                                                                      ##
##                           end of settings                            ##
##                                                                      ##
##########################################################################
##########################################################################

function ti() { // function to calculate time used
﻿  $t = microtime();
﻿  $t = explode(' ', $t);
﻿  return $t[1] + $t[0];
}
$start = max((int)$_GET['start'], 0); // set startpoint of xml file (must be an integer greater then 0)
$st = ($start==0?ti():(int)$_GET['st']); // remember when we started with the first chunk of data to show total processing time
$totaal = max((int)$_GET['totaal'], 0); // remember the total number of records we processed from the start of the first chunk
$size = filesize($file);
if(!isset($_GET['start'])) $_GET['start'] = 0; // if the startpoint still is not set, set it at 0
$end = min($size, ($start+$mem)); // if xml file smaller then chunksize, then don't try and do too much

if($start < $size) { // are we not already done?
﻿  $p = file_get_contents($file, NULL, NULL, $start, $end); // load the chunk of xml into memory
﻿  $p = preg_replace("/(.*?)<Product>(.*)/s", "<Product>\\2", $p); // strip the "useless" header and stuff
﻿  $product = '';
﻿  $conn = mysql_connect($dbhost, $dbuser, $dbpw);
﻿  mysql_select_db($db);
﻿  $pos = strrpos($p, '</Product>')+10; // find the end of the last record of this chunk of data
﻿  $deleted = strlen($p) - $pos; // help to figure out where to start processing the next chunk of data
﻿  $products = simplexml_load_string("<xml>".substr($p, 0, $pos)."</xml>"); // do the magic, turn the xml into an xml object that we can process
﻿  unset($p); // clear the memory of the xml string
﻿  $totaal = $totaal + sizeof($products); // how many records to process?

﻿  // Fetch existing tables and collumns
﻿  ﻿  $tbls = mysql_query("SHOW TABLES");
﻿  ﻿  while($temp = mysql_fetch_array($tbls)) {
﻿  ﻿  ﻿  $tbl[strtolower($temp[0])] = array();
﻿  ﻿  }
﻿  ﻿  foreach($tbl as $key => $value) {
﻿  ﻿  ﻿  $collumns = mysql_query("SHOW COLUMNS FROM `".mysql_real_escape_string($key)."`");
﻿  ﻿  ﻿  while($temp = mysql_fetch_array($collumns)) {
﻿  ﻿  ﻿  ﻿  $tbl[strtolower($key)][$temp['Field']] = " ";
﻿  ﻿  ﻿  }
﻿  ﻿  }
﻿  
﻿  // if it does not exist, create the first table
﻿  if(!isset($tbl['product'])) {
﻿  ﻿  mysql_query("CREATE TABLE IF NOT EXISTS `product` (`RecordReference` varchar(15) NOT NULL, PRIMARY KEY (`RecordReference`)) ENGINE=InnoDB DEFAULT CHARSET=utf8");
﻿  ﻿  $tbl['product']['RecordReference'] = " ";
﻿  }
﻿  
﻿  // loop through the chunk of xml to process
﻿  foreach ($products as $produc) {
﻿  ﻿  //check that all used tables and collumns exist, and if not create and update these tables
﻿  ﻿  $current = ti();
﻿  ﻿  $id = mysql_real_escape_string($produc->RecordReference);
﻿  ﻿  foreach($produc as $key => $value) {
﻿  ﻿  ﻿  $vars = get_object_vars($value);
﻿  ﻿  ﻿  if(is_array($vars)&&sizeof($vars)>0){
﻿  ﻿  ﻿  ﻿  $key = strtolower($key);
﻿  ﻿  ﻿  ﻿  if(!isset($tbl[$key])) {
﻿  ﻿  ﻿  ﻿  ﻿  mysql_query("CREATE TABLE IF NOT EXISTS `".mysql_real_escape_string($key)."` (`id` varchar(15) NOT NULL, INDEX (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8");
﻿  ﻿  ﻿  ﻿  ﻿  $tbl[$key] = array('id' => 'varchar(15)');
﻿  ﻿  ﻿  ﻿  ﻿  foreach($value as $key2 => $value2) {
﻿  ﻿  ﻿  ﻿  ﻿  ﻿  $vars2 = get_object_vars($value2);
﻿  ﻿  ﻿  ﻿  ﻿  ﻿  if(is_array($vars2)&&sizeof($vars2)>0){
﻿  ﻿  ﻿  ﻿  ﻿  ﻿  ﻿  foreach($value2 as $key3 => $value3) {
﻿  ﻿  ﻿  ﻿  ﻿  ﻿  ﻿  ﻿  if(!isset($tbl[$key][$key3])) {
﻿  ﻿  ﻿  ﻿  ﻿  ﻿  ﻿  ﻿  ﻿  mysql_query("ALTER TABLE `".mysql_real_escape_string($key)."` ADD `".mysql_real_escape_string($key3)."` longtext");
﻿  ﻿  ﻿  ﻿  ﻿  ﻿  ﻿  ﻿  ﻿  $tbl[$key][$key3] = 'longtext';
﻿  ﻿  ﻿  ﻿  ﻿  ﻿  ﻿  ﻿  }
﻿  ﻿  ﻿  ﻿  ﻿  ﻿  ﻿  }
﻿  ﻿  ﻿  ﻿  ﻿  ﻿  } else {
﻿  ﻿  ﻿  ﻿  ﻿  ﻿  ﻿  if(!isset($tbl[$key][$key2])) {
﻿  ﻿  ﻿  ﻿  ﻿  ﻿  ﻿  ﻿  mysql_query("ALTER TABLE `".mysql_real_escape_string($key)."` ADD `".mysql_real_escape_string($key2)."` longtext");
﻿  ﻿  ﻿  ﻿  ﻿  ﻿  ﻿  ﻿  $tbl[$key][$key2] = 'longtext';
﻿  ﻿  ﻿  ﻿  ﻿  ﻿  ﻿  }
﻿  ﻿  ﻿  ﻿  ﻿  ﻿  }
﻿  ﻿  ﻿  ﻿  ﻿  }
﻿  ﻿  ﻿  ﻿  }
﻿  ﻿  ﻿  } else {
﻿  ﻿  ﻿  ﻿  if(!isset($tbl['product'][$key])) {
﻿  ﻿  ﻿  ﻿  ﻿  mysql_query("ALTER TABLE product ADD ".mysql_real_escape_string($key)." VARCHAR(128)");
﻿  ﻿  ﻿  ﻿  ﻿  $tbl['product'][$key] = 'varchar(128)';
﻿  ﻿  ﻿  ﻿  }
﻿  ﻿  ﻿  }
﻿  ﻿   }
﻿  
﻿  ﻿  // seperate the big xml chunk into arrays that match tables
﻿  ﻿  foreach($produc as $key => $value) {
﻿  ﻿  ﻿  $vars = get_object_vars($value);
﻿  ﻿  ﻿  if(is_array($vars)&&sizeof($vars)>0){
﻿  ﻿  ﻿  ﻿  foreach($value as $key2 => $value2) {
﻿  ﻿  ﻿  ﻿  ﻿  $temp = strtolower($key);
﻿  ﻿  ﻿  ﻿  ﻿  $vars2 = get_object_vars($value2);
﻿  ﻿  ﻿  ﻿  ﻿  if(is_array($vars2) && sizeof($vars2)>0){
﻿  ﻿  ﻿  ﻿  ﻿  ﻿  $i = 0;
﻿  ﻿  ﻿  ﻿  ﻿  ﻿  foreach($value2 as $key3 => $value3) {
﻿  ﻿  ﻿  ﻿  ﻿  ﻿  ﻿  ${$temp}[$id][$i][$key3] = (string)$value3;
﻿  ﻿  ﻿  ﻿  ﻿  ﻿  ﻿  $i++;
﻿  ﻿  ﻿  ﻿  ﻿  ﻿  }
﻿  ﻿  ﻿  ﻿  ﻿  } else {
﻿  ﻿  ﻿  ﻿  ﻿  ﻿  ${$temp}[$id][0][$key2] = (string)$value2;
﻿  ﻿  ﻿  ﻿  ﻿  }
﻿  ﻿  ﻿  ﻿  }
﻿  ﻿  ﻿  } else {
﻿  ﻿  ﻿  ﻿  $product[$id][0][$key] = (string)$value;
﻿  ﻿  ﻿  }
﻿  ﻿  }
 ﻿  }
﻿  
﻿  // insert each array of data into its own table
﻿  foreach($tbl as $table => $array) {
﻿  ﻿  $query = "insert into `".mysql_real_escape_string($table)."` (";
﻿  ﻿  foreach($array as $key => $useless) {
﻿  ﻿  ﻿  $query .= "`".mysql_real_escape_string($key)."`, ";
﻿  ﻿  }
﻿  ﻿  $query = substr($query, 0, -2) . ") values ";
﻿  ﻿  $rows = "";
﻿  ﻿  foreach(${$table} as $key => $value) {
﻿  ﻿  ﻿  foreach($value as $key2 => $value2) {
﻿  ﻿  ﻿  ﻿  # $key = isbn
﻿  ﻿  ﻿  ﻿  # $value2 = array with data to be inserted
﻿  ﻿  ﻿  ﻿  $rows .= "(";
﻿  ﻿  ﻿  ﻿  foreach($array as $k => $v){
﻿  ﻿  ﻿  ﻿  ﻿  $rows .= "'".($k=='id'?mysql_real_escape_string($key):(isset($value2[$k])?mysql_real_escape_string($value2[$k]):'')) . "', ";
﻿  ﻿  ﻿  ﻿  }
﻿  ﻿  ﻿  ﻿  $rows = substr($rows, 0, -2) . "), ";
﻿  ﻿  ﻿  }﻿  
﻿  ﻿  }
﻿  ﻿  $rows = substr($rows, 0, -2);
﻿  ﻿  mysql_query($query . $rows);
﻿  }
﻿  
﻿  
﻿  if(($end+$start-($deleted+1))<$size) {
﻿  ﻿  header("Location: ".$uri."?start=".($end+$start-($deleted+1))."&st=".$st."&totaal=".$totaal); // continue with the next chunk of xml
﻿  } else { // finished and show total number of inserted records and processing time
﻿  ﻿  echo date("Y-m-d H:i:s") . " records: " . $totaal . " time: " .number_format((ti()-$st), 2, '.', ',')." seconds";
﻿  }
mysql_close($conn);
}
?>