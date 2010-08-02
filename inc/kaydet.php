<?php

F3::clear('message');
//echo F3::get('SESSION.captcha');
F3::call(':denetle');

function yukle($yuklenen, $yeni) {
	if (file_is_uploaded($yuklenen) {
		$filesize = filesize($yuklenen);
		$mimetype = exif_imagetype($yuklenen);

		if ($filesize > 350000) {
			F3::set('message', 'Resim çok büyük');
		}
		else if ($mimetype != IMAGETYPE_JPEG) {
			F3::set('message', 'Resim JPEG değil');
		}
		else if (! move_uploaded_file($yuklenen, $yeni)) {
			F3::set('message', 'Dosya yükleme hatası');
		}
		else {
			return true;
			; // ok
		}
	}
	else {
		F3::set('message', 'Dosya yüklenmemiş');
	}

	return false;
}

if (! F3::exists('message')) {
	$kul = new Axon('kul');
	$kul->copyFrom('REQUEST');
	$kul->tarih = date("h:i / d-m-Y");

	$yuklenen = $_FILES['file']['tmp_name'];
	if (! empty($yuklenen)) {
		// TODO: tc numara ile kayıt yapacağız, $kul->tc
		$yeni = "./resim/foo.jpg";
		yukle($yuklenen, $yeni); // dönüş değeri yerine message kontrolü yapacağım
	}

	if (! F3::exists('message')) {
		$kul->save();
		// TODO: burada bir özet verelim
		echo "Başarıyla kaydedildi.";
	}
}
else {
	// Hata var, dön başa ve tekrar kayıt al.
	F3::call(':goster');
}

?>
