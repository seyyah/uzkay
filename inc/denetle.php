<?php

function is_tc($tc) {
	// Kaynak: is_tc(): http://www.kodaman.org/yazi/t-c-kimlik-no-algoritmasi
	preg_replace(
		'/([1-9]{1})([0-9]{1})([0-9]{1})([0-9]{1})([0-9]{1})([0-9]{1})([0-9]{1})([0-9]{1})([0-9]{1}).*$/e',
		"eval('\$on=((((\\1+\\3+\\5+\\7+\\9)*7)-(\\2+\\4+\\6+\\8))%10); \$onbir=(\\1+\\2+\\3+\\4+\\5+\\6+\\7+\\8+\\9+\$on)%10; \$sonIki = \$on.\$onbir;')",
		$tc
	);

	return (substr($tc, -2) == $sonIki);
}

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

F3::clear('message');

if (! F3::exists('SESSION.captcha')) {
	F3::set('message', 'Oturum Güvenlik Kodu eksik');
}

F3::input($alan='captcha',
	function($value) use($alan) {
		$ne = "Güvenlik Kodu";
		$captcha = F3::get('SESSION.captcha');
		if ($hata = denetle(strtolower($value), array(
			'dolu'   => array(true,                 "$ne boş bırakılamaz"),
			'enaz'   => array(strlen($captcha),     "$ne çok kısa"),
			'degeri' => array(strtolower($captcha), "Yanlış $ne"),
		))) { F3::set('message', $hata); return; }
	}
);

foreach (array('ad', 'soyad') as $alan) {
	F3::input($alan,
		function($value) use($alan) {
			$ne = ucfirst($alan);
			if ($hata = denetle($value, array(
				'dolu'    => array(true, "$ne boş bırakılamaz"),
				'enaz'    => array(1,    "$ne çok kısa"),
				'enfazla' => array(127,  "$ne çok uzun"),
			))) { F3::set('message', $hata); return; }
			F3::set("REQUEST.$alan", ucfirst($value));
		}
	);
}

F3::input($alan='tc',
	function($value) use($alan) {
		$ne = ucfirst($alan);

		// TODO Bu kısmı test etmedim --roktas
		F3::sql('SELECT * from kul ','DB');
		foreach (F3::get('DB.result') as $row) {
			if ($row['tc'] == $value) {
				F3::set('message', $value. 'Bu kayıt daha önceden eklendi');
				return;
			}
		}

		if ($hata = denetle($value, array(
			'dolu'    => array(true, 'TC No boş bırakılamaz'),
			'esit'    => array(11,   'TC No 11 haneli olmalıdır'),
			'tamsayi' => array(true, 'Tc no sadece rakam içermeli'),
			'ozel'    => array(function($value) { return ! is_tc($value); },
					'Geçerli bir TC no değil'),
		))) { F3::set('message', $hata); return; }
		F3::set("REQUEST.$alan", ucfirst($value));
	}
);

F3::set('REQUEST.tarih', date("h:i / d-m-Y"));

?>
