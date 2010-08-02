<?php

include 'lib/F1.php';

function iller() {
	return array(
		'Adana',
		'Adıyaman',
		'Afyonkarahisar',
		'Ağrı',
		'Aksaray',
		'Amasya',
		'Ankara',
		'Antalya',
		'Ardahan',
		'Artvin',
		'Aydın',
		'Balıkesir',
		'Bartın',
		'Batman',
		'Bayburt',
		'Bilecik',
		'Bingöl',
		'Bitlis',
		'Bolu',
		'Burdur',
		'Bursa',
		'Çanakkale',
		'Çankırı',
		'Çorum',
		'Denizli',
		'Diyarbakır',
		'Düzce',
		'Edirne',
		'Elazığ',
		'Erzincan',
		'Erzurum',
		'Eskişehir',
		'Gaziantep',
		'Giresun',
		'Gümüşhane',
		'Hakkari',
		'Hatay',
		'Iğdır',
		'Isparta',
		'İstanbul',
		'İzmir',
		'Kahramanmaraş',
		'Karabük',
		'Karaman',
		'Kars',
		'Kastamonu',
		'Kayseri',
		'Kırıkkale',
		'Kırklareli',
		'Kırşehir',
		'Kilis',
		'Kocaeli',
		'Konya',
		'Kütahya',
		'Malatya',
		'Manisa',
		'Mardin',
		'Mersin',
		'Muğla',
		'Muş',
		'Nevşehir',
		'Niğde',
		'Ordu',
		'Osmaniye',
		'Rize',
		'Sakarya',
		'Samsun',
		'Siirt',
		'Sinop',
		'Sivas',
		'Şanlı urfa',
		'Şırnak',
		'Tekirdağ',
		'Tokat',
		'Trabzon',
		'Tunceli',
		'Uşak',
		'Van',
		'Yalova',
		'Yozgat',
		'Zonguldak',
	);
}

F3::clear('SESSION.captcha');
F3::set('iller', iller());

// FIXME init.sql'deki tabloyu buraya aktar
F3::sql(
	array(
		'CREATE TABLE IF NOT EXISTS kul ('.
			'tc INT (11) UNSIGNED NOT NULL,'.
			'ad CHAR (15),'.
			'soyad CHAR (20),'.
			'PRIMARY KEY(tc)'.
		');' 
	)
);

F3::call('render');

?>
