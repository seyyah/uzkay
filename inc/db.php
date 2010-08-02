<?php

function is_table_exists($table, $db=NULL) {
	if (is_null($db))
		$db = F3::get('DB.name');
	return $db && F3::sql(
		array(
			"SELECT COUNT(*) AS found ".
			"FROM information_schema.tables ".
			"WHERE table_schema='$db' ".
			"AND table_name='$table';"
		)
	);
}

if (! is_table_exists('kul')) {
	// FIXME init.sql'deki tabloyu buraya aktar
	F3::sql(
		array(
			'CREATE TABLE kul ('.
				'tc INT (11) UNSIGNED NOT NULL,'.
				'ad CHAR (15),'.
				'soyad CHAR (20),'.
				'PRIMARY KEY(tc)'.
			');' 
		)
	);
}

?>
