<?php

// Burada tüylerimizi diken diken eden berbat kod tekrarları var.
// Lütfen yeni kod yazarken bu tür "günü kurtaran hareketler"den kacının.

function is_tc($tc) {
	// Kaynak: is_tc(): http://www.kodaman.org/yazi/t-c-kimlik-no-algoritmasi
	preg_replace(
		'/([1-9]{1})([0-9]{1})([0-9]{1})([0-9]{1})([0-9]{1})([0-9]{1})([0-9]{1})([0-9]{1})([0-9]{1}).*$/e',
		"eval('\$on=((((\\1+\\3+\\5+\\7+\\9)*7)-(\\2+\\4+\\6+\\8))%10); \$onbir=(\\1+\\2+\\3+\\4+\\5+\\6+\\7+\\8+\\9+\$on)%10; \$sonIki = \$on.\$onbir;')",
		$tc
	);

	return (substr($tc, -2) == $sonIki);
}

function strtolower_turkish($string) {
	$lower = array(
		'İ' => 'i', 'I' => 'ı', 'Ğ' => 'ğ', 'Ü' => 'ü',
		'Ş' => 'ş', 'Ö' => 'ö', 'Ç' => 'ç',
	);
	return strtolower(strtr($string, $lower));
}

function streq_turkish($string1, $string2) {
	return strtolower_turkish($string1) == strtolower_turkish($string2);
}

// FIXME: bunu bir işlev tablosuna dönüştür
function denetle($verilen, $tarif) {
	foreach ($tarif as $ne => $bilgi) {
		$kosul = array_shift($bilgi);
		switch ($ne) {
		case 'dolu':
			$hata = $kosul && empty($verilen);
			break;
		case 'esit':
			$hata = $kosul != strlen($verilen);
			break;
		case 'enfazla':
			$hata = strlen($verilen) > $kosul;
			break;
		case 'enaz':
			$hata = strlen($verilen) < $kosul;
			break;
		case 'degeri':
			$hata = $kosul != $verilen;
			break;
		case 'tamsayi':
			$hata = $kosul && ! ctype_digit($verilen);
			break;
		case 'ozel':
			$hata = $kosul && $kosul($verilen);
			break;
		}
		if ($hata) {
			return array_shift($bilgi);
		}
	}
}

// temiz bir sayfa açalım!
F3::clear('error');

// captcha'sız maça çıkmayız, sağlam gidelim
if (! F3::exists('SESSION.captcha')) {
	F3::set('error', 'Oturum Güvenlik Kodu eksik');
	return;
}

// captcha tamam mı?
F3::input($alan='captcha',
	function($value) use($alan) {
		$ne = "Güvenlik Kodu";
		$captcha = F3::get('SESSION.captcha');
		if ($hata = denetle(strtolower($value), array(
			'dolu'   => array(true,                 "$ne boş bırakılamaz"),
			'enaz'   => array(strlen($captcha),     "$ne çok kısa"),
			'degeri' => array(strtolower($captcha), "Yanlış $ne"),
		))) { F3::set('error', $hata); return; }
	}
);

// tc numara geçerli olmalı
F3::input($alan='tc',
	function($value) use($alan) {
		$ne = "Tc No";
		if ($hata = denetle($value, array(
			'dolu'    => array(true, "$ne boş bırakılamaz"),
			'esit'    => array(11,   "$ne 11 haneli olmalıdır"),
			'tamsayi' => array(true, "$ne sadece rakam içermeli"),
			'ozel'    => array(function($value) { return ! is_tc($value); },
					"Geçerli bir $ne değil"),
		))) { F3::set('error', $hata); return; }
	}
);

F3::input($alan='kizliksoyad',
	function($value) use($alan) {
		$ne = "Kızlık Soyadı";
		if ($hata = denetle($value, array(
			'dolu'    => array(true, "$ne boş bırakılamaz"),
		))) { F3::set('error', $hata); return; }
	}
);

if (! F3::exists('error')) {
	$tc = F3::get('REQUEST.tc');
	$kizliksoyad = F3::get('REQUEST.kizliksoyad');

	$kul = new Axon('kul');
	$kul->load("tc=$tc");

	if (!$kul->dry() && streq_turkish($kul->kizliksoyad, $kizliksoyad)) {
		// tc no'yu oturuma gömelim ve oradan alalım
		F3::set('SESSION.sorgutc', $tc);
		F3::set('SESSION.sorgukizliksoyad', $kizliksoyad);
		return F3::call(':sorguok');
	}

	F3::set('error', "Girdiğiniz bilgilere uygun bir kayıt bulunamadı.  Lütfen verdiğiniz bilgileri kontrol edin.");
}

// hata var, dön başa ve tekrar sorgu al.
// error alanı dolu ve layout.htm'de görüntülenecek
F3::call(':sorgual');

?>
