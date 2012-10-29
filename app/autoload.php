<?php

spl_autoload_register(function ($className) {
  $classPath = str_replace('_', '/', $className);
  $dirs = array('', '/../lib');
  foreach ($dirs as $dir) {
    $file = dirname(__FILE__).$dir.'/'.$classPath.'.php';
    if (file_exists($file)) {
      require($file);
    }
  }
});

