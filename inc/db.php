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

F3::sql(
	array(
		'CREATE TABLE IF NOT EXISTS kul ('.
			'id INT(11) NOT NULL auto_increment,'.
			'tc VARCHAR(11) NOT NULL,'.
			'ad CHAR (32),'.
			'soyad CHAR (32),'.
			'kizliksoyad varchar(32),'.
			'babaad varchar(32),'.
			'anaad varchar(32),'.
			'ceptel varchar(11),'.
			'evtel varchar(11),'.
			'email varchar(100),'.
			'evadres varchar(100),'.
			'il varchar(32),'.
			'ilce varchar(32),'.
			'uni varchar(100),'.
			'bolum varchar(100),'.
			'calismakurum varchar(100),'.
			'calismabirim varchar(100),'.
			'isadres varchar(100),'.
			'isil varchar(32),'.
			'onkayit varchar(1),'.
			'tarih varchar(100),'.
			'PRIMARY KEY(id)'.
		');' 
	)
);

?>
