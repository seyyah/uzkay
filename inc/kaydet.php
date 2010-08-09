<?php

include 'lib/F1.php';

// denetleme yapalım
F3::call(':denetle');

// FIXME bunu biraz daha genelleştir, PNG falan yönetsin
function yukle($hedef=NULL, $alan='file') {
	$yuklenen = F3::get("FILES.$alan.tmp_name");

	// hedef ve yüklenen dosyanın boş olmasına izin veriyoruz
	// herhangi biri boşsa mesele yok, çağırana dön
	if (empty($hedef) || empty($yuklenen)) {
		return true;
	}

	// bu bir uploaded dosya olmalı, fake dosyalara izin yok
	if (is_uploaded_file($yuklenen)) {
		// boyutu sınırla, değeri öylesine seçtim
		if (filesize($yuklenen) > 600000) {
			F3::set('error', 'Resim çok büyük');
		}
		// şimdilik sadece JPEG, dosya tipini içine bakarak tespit ediyoruz
		else if (exif_imagetype($yuklenen) != IMAGETYPE_JPEG) {
			F3::set('error', 'Resim JPEG değil');
		}
		// dosyanın üzerine yazmayalım, ekstra güvenlik
		else if (file_exists($hedef)) {
			F3::set('error', 'Resim zaten kaydedilmiş');
		}
		// tamamdır, kalıcı kayıt yapalım
		else if (!move_uploaded_file($yuklenen, $hedef)) {
			F3::set('error', 'Dosya yükleme hatası');
		}
		// yok başka bir ihtimal!
	}
	else {
		// bu aslında bir atak işareti
		F3::set('error', 'Dosya geçerli bir yükleme değil');
	}

	return false;
}

// denetleme sırasında hata oluşmamışsa kayıt yapacağız
// hata olmadığını nereden anlıyoruz?  "error"a bakarak
if (! F3::exists('error')) {
	$kul = new Axon('kul');
	$kul->copyFrom('REQUEST');
	$kul->tarih = date("d-m-Y h:i");

	// artık elimizde temiz bir tc no var, resmi kaydedelim
	// ilk kurulum sırasında bu <uploaddir> dizinini oluştur
	// php prosesi yazacağına göre izinleri doğru ayarla
	// 	chgrp -R www-data <uploaddir> && chmod g+w <uploaddir>

	$tc = $kul->tc;
	F3::set('tc', $tc);

	if (! empty($tc)) {
		$resim = F3::get('uploaddir') . $kul->tc . '.jpg';
		yukle($resim);
	}

	if (! F3::exists('error')) {
		// here we go!
		$kul->save();
		// TODO: burada bir özet verelim
		F3::set('message', 'Kaydınız başarıyla yapıldı.');
		return F3::call(':ok');
	}
	// hatalı bir resim kaydı varsa çöp bırakmamaya çalış
	// FIXME: bu mantık üzerinde biraz daha çalış
	else if (file_exists($yeni) && ! unlink($yeni)) {
		// TODO ne yazayım ben şimdi buraya
		;
	}
}

// hata var, dön başa ve tekrar kayıt al.
// error alanı dolu ve layout.htm'de görüntülenecek
F3::call(':goster');

?>
