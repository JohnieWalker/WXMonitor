<?php
include "../include/config.php";
mysql_connect($mysql_host, $mysql_user, $mysql_password);
mysql_select_db($mysql_db);
$sql = 'DELETE FROM `german_wings_stations` WHERE `station` = \''.$_GET['station'].'\' LIMIT 1;';
mysql_query($sql) or die("Error: ".mysql_error());
mysql_close();
//echo $sql;
header("Location: index.php");