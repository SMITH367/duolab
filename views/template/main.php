<?php

include_once dirname(__DIR__, 2) . '/global/config.php';

// Get the project root directory (physical path)
$web_root_dir = str_replace('\\', '/', dirname(__DIR__, 2));
$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);

// Calculate the web directory name by comparing the physical path with the document root
$web_dir_name = str_ireplace($doc_root, '', $web_root_dir);

// Ensure it starts with a / and has no trailing /
$web_dir_name = '/' . ltrim($web_dir_name, '/');
if ($web_dir_name === '/') {
    $web_dir_name = '';
} else {
    $web_dir_name = rtrim($web_dir_name, '/');
}

// Set the default timezone
// This is important for date and time functions to work correctly
date_default_timezone_set("America/Lima");

// Load Web Functions
include_once dirname(__DIR__, 2) . "/modules/web_functions.php";
$functions = new WebFunctions();

if (isset($_GET["url"])) {

    // Initialize and check session
    include_once $web_root_dir.'/global/session.php';

    // Web Header (Navbar)
    include $web_root_dir."/views/template/header.php";

    // Web Sidebar
    include $web_root_dir."/views/template/sidebar.php";

    // Web View (Content)
    $url = explode("/", $_GET["url"]);
    
    // If the URL starts with 'views/', we ignore that first part for routing purposes
    if ($url[0] == 'views') {
        array_shift($url);
    }

    // Join the remaining parts to form the path to the view file
    $url_complete = implode("/", $url);
    $page_name = end($url);
    
    $view_file = $web_root_dir . "/views/" . $url_complete . ".php";

    if (file_exists($view_file)) {
        include $view_file;
    } else {
        echo "<h1>Error 404</h1>";
        echo "La vista <b>" . $url_complete . "</b> no existe.";
    }

    // Web Footer
    include $web_root_dir."/views/template/footer.php";

    // AJAX Directory Path
    $ajax_dir_path = $functions->direct_sistema()."/ajax/".$page_name.".js?v=".SCRIPT_VER;

    // AJAX Directory Relative Path
    $ajax_dir_rel_path = $functions->directorio_carpetas()."/ajax/".$page_name.".js";

    // Check if an AJAX file for requested view exists 
    if (file_exists($ajax_dir_rel_path)) {
        echo '<script src="' . $ajax_dir_path . '"></script>';
    }

} else {
    // Show login page
    include_once $web_root_dir . "/views/login.php";
}

?>