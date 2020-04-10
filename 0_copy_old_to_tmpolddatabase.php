<?php
require_once 'config.php';
shell_exec("$whereis_mysqldump -u $mysql_user -p\"$mysql_pass\" --no-data --skip-add-drop-table  $old_db > tmp.old.sql");

$sql = file_get_contents('tmp.old.sql');
$sql = "

DROP DATABASE IF EXISTS tmpolddatabase;
CREATE DATABASE tmpolddatabase CHARSET=utf8 COLLATE=utf8_unicode_ci;
USE tmpolddatabase;

".$sql;
file_put_contents('tmp.old.sql', $sql);
shell_exec("$whereis_mysql -u $mysql_user -p\"$mysql_pass\" < tmp.old.sql");
shell_exec("rm tmp.old.sql");

