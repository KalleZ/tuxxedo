<?php
define('REQUEST_METHOD',	strtolower($_SERVER['REQUEST_METHOD']));
define('REQUEST_URI',		$_SERVER['REQUEST_URI']);

header('Content-type: text/plain;charset:UTF-8');
require 'library/Tuxxedo/Mvc.php';
require 'library/Tuxxedo/Mvc/Url.php';
require 'App/Routes.php';
#var_dump($_SERVER['REQUEST_URI']);
//Mvc::init();