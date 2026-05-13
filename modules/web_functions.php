<?php

class WebFunctions {
  function redireccionar($ruta){
    echo '<script type="text/javascript">';
    echo 'location.href ="'.$this->direct_sistema().'/'.$ruta.'";';
    echo '</script>';
  }

  function direct_sistema(){
    if (defined("BASE_URL")) {
        return BASE_URL;
    }
    $web_root_dir = str_replace('\\', '/', dirname(__DIR__));
    $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    
    $directorio_sistema = str_ireplace($doc_root, '', $web_root_dir);
    $directorio_sistema = '/' . ltrim($directorio_sistema, '/');
    
    if ($directorio_sistema === '/') {
        $directorio_sistema = '';
    } else {
        $directorio_sistema = rtrim($directorio_sistema, '/');
    }
    return $directorio_sistema;
  }

  function directorio_carpetas(){
    $directorio_folder = dirname(__DIR__);
    return $directorio_folder;
  }

  function direct_paginas(){
    $directorio = $this->direct_sistema()."/views/";
    return $directorio;
  }
  
}
?>