<?php

function is_tc($tc) {
	// Kaynak: is_tc(): http://www.kodaman.org/yazi/t-c-kimlik-no-algoritmasi
	preg_replace(
		'/([1-9]{1})([0-9]{1})([0-9]{1})([0-9]{1})([0-9]{1})([0-9]{1})([0-9]{1})([0-9]{1})([0-9]{1}).*$/e',
		"eval('
			\$on=((((\\1+\\3+\\5+\\7+\\9)*7)-(\\2+\\4+\\6+\\8))%10);
			\$onbir=(\\1+\\2+\\3+\\4+\\5+\\6+\\7+\\8+\\9+\$on)%10;
		')",
		$tc
	);
	// son iki haneyi (on ve onbirinci) kontrol et
	return substr($tc, -2) == ($on < 0 ? 10 + $on : $on) . $onbir;
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

// ad ve soyad şart
foreach (array('ad', 'soyad') as $alan) {
	F3::input($alan,
		function($value) use($alan) {
			$ne = ucfirst($alan);
			if ($hata = denetle($value, array(
				'dolu'    => array(true, "$ne boş bırakılamaz"),
				'enaz'    => array(2,    "$ne çok kısa"),
				'enfazla' => array(127,  "$ne çok uzun"),
			))) { F3::set('error', $hata); return; }
			F3::set("REQUEST.$alan", ucfirst($value));
		}
	);
}

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

		$kul = new Axon('kul');
		if ($kul->found("tc=$value")) {
			F3::set('error', "$ne $value daha önceden eklendi");
			return;
		}
	}
);

?>
