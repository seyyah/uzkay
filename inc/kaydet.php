<?php

F3::clear('message');
//echo F3::get('SESSION.captcha');
F3::call(':denetle');


if (! F3::exists('message')) {
	$kul = new Axon('kul');
	$kul->tarih = date("h:i / d-m-Y");
	$kul->save();
	// TODO: bir özet ver
	echo "Başarıyla kaydedildi.";
}
else {
	// Hata var, dön başa ve tekrar kayıt al.
	F3::call(':al');
}

?>
