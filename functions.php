<?php


function glue_col_names ($arr, $close = false) {
	if (!isset($arr) || empty($arr)) {
		return '';
	}

	$cols = '';
	foreach ($arr as $col) {
		$cols.= ($cols?',':'')."`{$col['name']}`";
	}
	if ($close) {
		$cols = "({$cols})";
	}
	return $cols;
}

function serialize_index ($i, $suffix = '') {

	$prefix = substr($i['type'], 0, 1);
	$name = isset($i['name']) ? $i['name'] : '';
	$cols = '';
	if (isset($i['cols'])) {
		foreach ($i['cols'] as $col) {
			$cols.= ($cols?',':'').$col['name'];
		}
	}

	$constraint = isset($i['constraint_name']) ? $i['constraint_name'] : '';
	$ref_table = '';
	$ref_cols = '';

	if (isset($i['ref_table'])) {
		$ref_table = $i['ref_table'];
		$ref_cols = '';
		foreach ($i['ref_cols'] as $col) {
			$ref_cols.= ($ref_cols?',':'').$col['name'];
		}
	}


	$r = "{$prefix}:{$name}:{$cols}:{$constraint}:{$ref_table}:{$ref_cols}{$suffix}";

	return $r;

}

function render_column_definition($cDef) {

	$default = '';
	if (isset($cDef['default'])) {
		$default = 'DEFAULT ';
		if ($cDef['default'] == 'NULL') {
			$default .= 'NULL ';
		}
		elseif ($cDef['default'] === '') {
			$default .= "'' ";
		}
		else {
			$default .= '"'.$cDef['default'].'" ';
		}
	}

	if (!isset($cDef['null'])) {
		$notNull = '';
	}
	elseif ($cDef['null'] === false) {
		$notNull = ' NOT NULL';
	}
	else {
		$notNull = '';
	}

	if (isset($cDef['default']) && $cDef['default'] == 'NULL') {
		$notNull = '';
	}

	$type = $cDef['type'];

	$length = '';
	if (isset($cDef['length'])) {
		$length = $cDef['length'];
		if (isset($cDef['decimals'])) {
			$length .= ', '.$cDef['decimals'];
		}
		$length = "($length) ";
	}

	$collate = '';
	if (isset($cDef['collation'])) {
		$collate .= ' COLLATE '.$cDef['collation'];
	}

	return "{$type} {$length} {$collate} {$notNull} {$default}";

}
