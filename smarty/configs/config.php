<?php
use Smarty\Smarty;

// put full path to Smarty.class.php
$SMARTY_BASE = $_SERVER['DOCUMENT_ROOT'];
require($SMARTY_BASE . '/smarty/libs/Smarty.class.php');
$smarty = new Smarty();

$config = require($SMARTY_BASE . '/config.php');

// Ustawienie czasu życia cache na 1 sekunde - na wszelki wypadek
$smarty->cache_lifetime = 1;
// Wyłączenie cache
$smarty->setCaching(Smarty::CACHING_OFF);

$smarty->setTemplateDir($SMARTY_BASE . '/templates');
$smarty->setCompileDir($SMARTY_BASE . '/smarty/templates_c');
$smarty->setCacheDir($SMARTY_BASE . '/smarty/cache');
$smarty->setConfigDir($SMARTY_BASE . '/smarty/configs');

// Modyfikator do dodawania wersji JS
function smarty_modifier_add_js_version($path) {
    global $config;
    $version = isset($config['app']['js_version']) ? $config['app']['js_version'] : '1.0';
    return $path . '?v=' . $version;
}

// Rejestracja modyfikatora
$smarty->registerPlugin('modifier', 'add_js_version', 'smarty_modifier_add_js_version');
