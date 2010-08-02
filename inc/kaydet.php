<?php

// denetleme yapalım
F3::call(':denetle');

function yukle($hedef=NULL) {
	$yuklenen = F3::get('FILES.file.tmp_name');

	// hedef ve yüklenen dosyanın boş olmasına izin veriyoruz
	// herhangi biri boşsa mesele yok, çağırana dön
	if (empty($hedef) || empty($yuklenen)) {
		return true;
	}

	// bu bir uploaded dosya olmalı, fake dosyalara izin yok
	if (is_uploaded_file($yuklenen)) {
		// boyutu sınırla, değeri öylesine seçtim
		if (filesize($yuklenen) > 350000) {
			F3::set('message', 'Resim çok büyük');
		}
		// şimdilik sadece JPEG, dosya tipini içine bakarak tespit ediyoruz
		else if (exif_imagetype($yuklenen) != IMAGETYPE_JPEG) {
			F3::set('message', 'Resim JPEG değil');
		}
		// dosyanın üzerine yazmayalım, ekstra güvenlik
		else if (file_exists($hedef)) {
			F3::set('message', 'Resim zaten kaydedilmiş');
		}
		// tamamdır, kalıcı kayıt yapalım
		else if (!move_uploaded_file($yuklenen, $hedef)) {
			F3::set('message', 'Dosya yükleme hatası');
		}
		else {
			return true;
		}
	}
	else {
		// bu aslında bir atak işareti
		F3::set('message', 'Dosya geçerli bir yükleme değil');
	}

	return false;
}

// denetleme sırasında hata oluşmamışsa kayıt yapacağız
// hata olmadığını nereden anlıyoruz?  "message"a bakarak
if (! F3::exists('message')) {
	$kul = new Axon('kul');
	$kul->copyFrom('REQUEST');
	$kul->tarih = date("h:i / d-m-Y");

	$resim = 'resim/' . $kul->tc . '.jpg';

	// here we go!
	yukle($resim);

	if (! F3::exists('message')) {
		$kul->save();
		// TODO: burada bir özet verelim
		echo "Başarıyla kaydedildi.";
	}
	// hatalı bir resim kaydı varsa çöp bırakmamaya çalış
	// FIXME: bu mantık üzerinde biraz daha çalış
	else if (file_exists($yeni) && ! unlink($yeni)) {
		// TODO ne yazayım ben şimdi buraya
		;
	}
}
else {
	// hata var, dön başa ve tekrar kayıt al.
	// message alanı dolu ve layout.htm'de görüntülenecek
	F3::call(':goster');
}

?>
