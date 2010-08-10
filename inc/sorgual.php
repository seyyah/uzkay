<?php

F3::clear('SESSION.captcha');
F3::clear('SESSION.sorgutc');
F3::clear('SESSION.sorgukizliksoyad');
F3::set('template', 'sorgual');
F3::call('render');

?>
