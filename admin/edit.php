<?php
include "../include/config.php";
mysql_connect($mysql_host, $mysql_user, $mysql_password);
mysql_select_db($mysql_db);
$sql = 'UPDATE `german_wings_stations` SET `'.$_POST['column'].'` = \''.$_POST['value'].'\' WHERE `station` = \''.$_POST['station'].'\' LIMIT 1;';
mysql_query($sql);
echo $sql;
mysql_close();
