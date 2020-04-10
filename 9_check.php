<?php
use iamcal\SQLParser;
include "SQLParser/SQLParser.php";
require_once 'config.php';
require_once 'functions.php';

$ddl_new = shell_exec("$whereis_mysqldump -u $mysql_user -p\"$mysql_pass\" --no-data --skip-add-drop-table  $new_db");
$ddl_old_org = shell_exec("$whereis_mysqldump -u $mysql_user -p\"$mysql_pass\" --no-data --skip-add-drop-table  $old_db");
$ddl_old = shell_exec("$whereis_mysqldump -u $mysql_user -p\"$mysql_pass\" --no-data --skip-add-drop-table  tmpolddatabase");

$pattern = "/AUTO_INCREMENT=\d+\s/m";

$newNew = preg_replace($pattern, '', $ddl_new);
file_put_contents("Z_diff_new.sql", $newNew);

$oldOld = preg_replace($pattern, '', $ddl_old);
file_put_contents("Z_diff_old_patched.sql", $oldOld);

$oldOldOrg = preg_replace($pattern, '', $ddl_old_org);
file_put_contents("Z_diff_old_not_patched.sql", $oldOldOrg);

