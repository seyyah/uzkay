<?php

// Reset session variables in case someone else is logged in
F3::clear('SESSION.user');
F3::clear('SESSION.captcha');

F3::set('pagetitle','Sorgu');
F3::set('template','sorguok');
F3::call('render');

?>
