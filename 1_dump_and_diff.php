<?php
use iamcal\SQLParser;
include "SQLParser/SQLParser.php";
require_once 'config.php';
require_once 'functions.php';

$ddl_new = shell_exec("$whereis_mysqldump -u $mysql_user -p\"$mysql_pass\" --no-data --skip-add-drop-table  $new_db");
$ddl_old = shell_exec("$whereis_mysqldump -u $mysql_user -p\"$mysql_pass\" --no-data --skip-add-drop-table  $old_db");

$newParsed = new SQLParser();
$newParsed->parse($ddl_new);
$oldParsed = new SQLParser();
$oldParsed->parse($ddl_old);

$mergedTableNames = [];
foreach ($newParsed->tables as $tableName => $unused) {
	$mergedTableNames[$tableName] = $tableName;
}
foreach ($oldParsed->tables as $tableName => $unused) {
	$mergedTableNames[$tableName] = $tableName;
}


$INDEX_TYPES = ['PRIMARY', 'UNIQUE', 'INDEX', ];
$FK_NAMES = ['FOREIGN', ];

// $diff_old = "use `tmpolddatabase`;\n";
$diff_old = "";
$diff_new = '';
$diff_additional_info = '';

foreach ($mergedTableNames as $tableName) {


	// TABLE MISSING from OLD
	if (!isset($oldParsed->tables[$tableName])) {
		$diff_old .= $newParsed->tables[$tableName]['sql']."\n";
		if (
			isset($new[$tableName]['props']['AUTO_INCREMENT']) &&
			$new[$tableName]['props']['AUTO_INCREMENT'] > 0
		) {
			$diff_additional_info .= "CHECK DATA FOR (AUTO_INCREMENT > 0): $tableName\n";
		}
	}

	// TABLE MISSING from NEW
	elseif (!isset($newParsed->tables[$tableName])) {
		$diff_old .= "DROP TABLE `{$tableName}`;\n";
		$diff_additional_info .= "MISSING db.new TABLE `{$tableName}`\n";
	}

	// TABLE PRESENT in BOTH
	else {

		$addIndexes = '';


		// INDEXES - MISSING IN OLD or NEW
		// ======================================

		$bothIndexes = [];
		foreach ($newParsed->tables[$tableName]['indexes'] as $index) {
			$indexSer = serialize_index($index/*, 'n'*/);
			$bothIndexes[$indexSer] = $indexSer;
			$newParsed->tables[$tableName]['indexes_by_name'][ $indexSer ] = $index;
			// $diff_debug[$tableName][ $indexSer ] = $index;
		}
		foreach ($oldParsed->tables[$tableName]['indexes'] as $index) {
			$indexSer = serialize_index($index/*, 'o'*/);
			$bothIndexes[$indexSer] = $indexSer;
			$oldParsed->tables[$tableName]['indexes_by_name'][ $indexSer ] = $index;
			// $diff_debug[$tableName][ $indexSer ] = $index;
		}
		foreach ($bothIndexes as $indexSer) {

			// DROP EXTRA INDEXES

			if (!isset($newParsed->tables[$tableName]['indexes_by_name'][$indexSer])) {
				$ind = $oldParsed->tables[$tableName]['indexes_by_name'][$indexSer];
				if (in_array($ind['type'], $INDEX_TYPES)) {
					$diff_old .= "ALTER TABLE `{$tableName}` DROP INDEX `{$ind['name']}`;\n";
				}
				elseif (in_array($ind['type'], $FK_NAMES)) {
					$diff_old .= "ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$ind['constraint_name']}`;\n";
				}
			}

			// ADD MISSING INDEXES

			if (!isset($oldParsed->tables[$tableName]['indexes_by_name'][$indexSer])) {
				$ind = $newParsed->tables[$tableName]['indexes_by_name'][$indexSer];
				$constraint = '';
				if (isset($ind['constraint_name'])) {
					$constraint =  " CONSTRAINT `{$ind['constraint_name']}` ";
				}

				$cols = glue_col_names($ind['cols']);
				if ($ind['type'] == 'PRIMARY') {
					$addIndexes .= "ALTER TABLE `{$tableName}` ADD {$constraint} PRIMARY KEY ($cols);\n";
				}
				elseif ($ind['type'] == 'INDEX') {
					$addIndexes .= "CREATE INDEX `{$ind['name']}` ON `{$tableName}` ({$cols});\n";
				}
				elseif ($ind['type'] == 'UNIQUE') {
					$addIndexes .= "CREATE UNIQUE INDEX `{$ind['name']}` ON `{$tableName}` ({$cols});\n";
				}
				elseif ($ind['type'] == 'FOREIGN') {
					$ref_cols = glue_col_names($ind['ref_cols']);

					$addIndexes .= "ALTER TABLE `{$tableName}` ADD {$constraint} FOREIGN KEY ({$cols}) REFERENCES `{$ind['ref_table']}` ({$ref_cols});";
				}

			}
		}





		// COLUMNS - MISSING IN OLD or NEW
		// ======================================

		$bothColumns = [];
		$colPositionNew = [];
		$colPositionOld = [];
		$lastCol = 0;

		// get all new.table.colNames
		foreach ($newParsed->tables[$tableName]['fields'] as $column) {
			$bothColumns[$column['name']] = $column['name'];
			$newParsed->tables[$tableName]['fields_by_name'][ $column['name'] ] = $column;
			$colPositionNew[ $column['name'] ] = $lastCol;
			$lastCol = $column['name'];
		}
		// get all old.table.colNames
		foreach ($oldParsed->tables[$tableName]['fields'] as $column) {
			$bothColumns[$column['name']] = $column['name'];
			$oldParsed->tables[$tableName]['fields_by_name'][ $column['name'] ] = $column;
			$colPositionOld[ $column['name'] ] = $lastCol;
			$lastCol = $column['name'];
		}

		// loop trough the mixed list
		$moveColPosition = '';
		foreach ($bothColumns as $colName) {

			// new.table.colName IS MISSING
			if (!isset($newParsed->tables[$tableName]['fields_by_name'][$colName])) {
				$diff_old .= "ALTER TABLE `{$tableName}` DROP COLUMN `{$colName}`;\n";
			}

			// old.table.colName IS MISSING
			elseif (!isset($oldParsed->tables[$tableName]['fields_by_name'][$colName])) {
				$cDef = $newParsed->tables[$tableName]['fields_by_name'][$colName];
				$colRendered = render_column_definition($cDef);
				if ($colPositionNew[ $colName ] === 0) {$poz = ' FIRST';}
				else {$poz = " AFTER `{$colPositionNew[ $colName ]}` ";}
				$diff_old .= "ALTER TABLE `{$tableName}` ADD COLUMN `{$colName}` {$colRendered}{$poz};\n";
			}

			// new.table.colName.definition ?= old.table.colName.definition

			else {
				if ($newParsed->tables[$tableName]['fields_by_name'][$colName]
					!== $oldParsed->tables[$tableName]['fields_by_name'][$colName]
				) {
					// new.table.colName != old.table.colName
					$cDef = $newParsed->tables[$tableName]['fields_by_name'][$colName];
					$colRendered = render_column_definition($cDef);
					$diff_old .= "ALTER TABLE `{$tableName}` MODIFY `{$colName}` {$colRendered};\n";
				}

				// old col is out of order
				if ($colPositionNew[$colName] != $colPositionOld[$colName]) {
					$cDef = $newParsed->tables[$tableName]['fields_by_name'][$colName];
					$colRendered = render_column_definition($cDef);
					if ($colPositionNew[ $colName ] === 0) {$poz = ' FIRST';}
					else {$poz = " AFTER `{$colPositionNew[ $colName ]}` ";}
					$moveColPosition .= "ALTER TABLE `{$tableName}` MODIFY `{$colName}` {$colRendered}{$poz};\n";
				}
			}
		}

		$diff_old .= $moveColPosition;
		$diff_old .= $addIndexes;

	}
}

file_put_contents("patch.sql", $diff_old);
file_put_contents("patch_additional_info.txt", $diff_additional_info);
