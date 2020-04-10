<?php
use iamcal\SQLParser;
include "SQLParser/SQLParser.php";
require_once 'config.php';
require_once 'functions.php';

if (file_exists('patch.sql')) {
	$result = shell_exec("$whereis_mysql -u $mysql_user -p\"$mysql_pass\" tmpolddatabase < patch.sql");
	echo $result."\n";
	echo "Your old database was copied to 'tmpolddatabase' and patched\n";
}
else {
	echo "file patch.sql does not exist.\n";
}
