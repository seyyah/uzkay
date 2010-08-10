<?php

include 'lib/F1.php';

// FIXME yavrum sen niye buradasın?
function iller() {
	return array(
		'',
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

function gunler() {
	$ret = range(1, 31); array_unshift($ret, '');
	return $ret;
}
function aylar() {
	$ret = range(1, 12); array_unshift($ret, '');
	return $ret;
}
function yillar() {
	$busene = date('Y');
	// sorarım size insan kaç sene yaşar?
	$ret = range($busene, $busene - 100); array_unshift($ret, '');
	return $ret;
}

F3::clear('SESSION.captcha');
F3::call(':db');

F3::set('gunler', gunler());
F3::set('aylar', aylar());
F3::set('yillar', yillar());
F3::set('iller', iller());

F3::set('template', 'goster');
F3::call('render');

?>
