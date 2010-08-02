<?php

// F3'te her tablo bir klas, tablodaki her kayıt da bu klas'tan çıkan bir 
// nesne.  Tablo'yu, dolayısıyla klas'ı burada tanımlıyoruz

// FIXME istediğim gibi çalışmıyor, sonra bakacağım
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

// FIXME init.sql'deki tabloyu buraya aktar
F3::sql(
	array(
		'CREATE TABLE IF NOT EXISTS kul ('.
			'id INT(11) NOT NULL auto_increment,'.
			'tc VARCHAR(11) NOT NULL,'.
			'ad CHAR (15),'.
			'soyad CHAR (20),'.
			'tarih varchar(100),'.
			'PRIMARY KEY(id)'.
		');' 
	)
);

?>
