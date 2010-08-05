<?php

// Reset session variables in case someone else is logged in
F3::clear('SESSION.user');
F3::clear('SESSION.captcha');

// Render ok.htm template
F3::set('template','onkayitli');
F3::call('render');

?>
