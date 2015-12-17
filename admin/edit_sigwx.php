<?php
include "../include/config.php";
mysql_connect($mysql_host, $mysql_user, $mysql_password);
mysql_select_db($mysql_db);
$sql = 'UPDATE `german_wings_settings` SET `value` = \''.strtoupper(trim($_POST['value'])).'\' WHERE `setting` = \''.$_POST['setting'].'\' LIMIT 1;';
mysql_query($sql);
echo $sql;
mysql_close();

