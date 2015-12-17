<?php
include "../include/config.php";
mysql_connect($mysql_host, $mysql_user, $mysql_password);
mysql_select_db($mysql_db);
$sql = 'INSERT INTO `german_wings_stations` (`station`) VALUES (\''.strtoupper($_POST['station']).'\');';
mysql_query($sql);
mysql_close();

header("Location: index.php");
