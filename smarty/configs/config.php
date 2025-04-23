<?php
use Smarty\Smarty;

// put full path to Smarty.class.php
$SMARTY_BASE = $_SERVER['DOCUMENT_ROOT'];
require($SMARTY_BASE . '/smarty/libs/Smarty.class.php');
$smarty = new Smarty();

// Ustawienie czasu ¿ycia cache na 1 sekunde - na wszelki wypadek
$smarty->cache_lifetime = 1;
// Wy³¹czenie cache
$smarty->setCaching(Smarty::CACHING_OFF);

$smarty->setTemplateDir($SMARTY_BASE . '/templates');
$smarty->setCompileDir($SMARTY_BASE . '/smarty/templates_c');
$smarty->setCacheDir($SMARTY_BASE . '/smarty/cache');
$smarty->setConfigDir($SMARTY_BASE . '/smarty/configs');
