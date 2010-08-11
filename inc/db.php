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
			'tc varchar(11) NOT NULL,'.
			'ad varchar(32),'.
			'soyad varchar(32),'.
			'kizliksoyad varchar(32),'.
			'babaad varchar(32),'.
			'anaad varchar(32),'.
			'dogumgun varchar(2),'.
			'dogumay varchar(2),'.
			'dogumyil varchar(4),'.
			'dogumil varchar(32),'.
			'dogumilce varchar(32),'.
			'dogumulke varchar(64),'.
			'ceptel varchar(10),'.
			'evtel varchar(10),'.
			'email varchar(100),'.
			'evadres varchar(100),'.
			'il varchar(32),'.
			'ilce varchar(32),'.
			'uni varchar(100),'.
			'yokul varchar(100),'.
			'calismakurum varchar(100),'.
			'calismabirim varchar(100),'.
			'isadres varchar(100),'.
			'isil varchar(32),'.
			'isilce varchar(32),'.
			'onkayit varchar(1),'.
			'tarih varchar(100),'.
			'PRIMARY KEY(id)'.
		');' 
	)
);

?>
