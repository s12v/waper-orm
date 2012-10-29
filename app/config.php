<?php
mb_internal_encoding("UTF-8");

// This is intended to be outside of source tree, because it's host-related

Waper_Config::$ENV = array(
  'dev' => true,		// Версия разработчика
  'debug' => true,		// Дебаг воможен для некторых пользователей
);

Waper_Config::$DATABASES = array(
  'main' => array(
    'username' =>'root',
    'password' => 'root',
    'host' => 'localhost',
    'port' => 3306,
    'dbname' => 'sandbox',
    'charset' => 'utf8'
  ),
);

