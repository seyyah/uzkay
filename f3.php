<?php

include 'lib/F1.php';

function render() { echo F3::serve('layout.htm'); }

F3::config(".f3.ini");

F3::route("GET $F3/captcha",     ':captcha');
F3::route("GET $F3/",            ':goster');
F3::route("GET $F3/cikti",       ':cikti');
F3::route("GET $F3/kubalak/@tc", ':kubalak');
F3::route("POST $F3/kaydet",     ':kaydet');

F3::run();

?>

