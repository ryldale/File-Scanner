<?php

// INCLUDE CONTENTS include(get_stylesheet_directory() . 'FILE_PATH');

function my_theme_enqueue_styles() { 
 wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );

if(is_admin()){
	$mainFolderName = 'php';
	include $mainFolderName.'/file-scanner.php';
	
    function filescanneradmin($hook){
        wp_enqueue_style( 'filescanner-style', get_stylesheet_directory_uri().'/css/file-scanner.css',array(),'1.0.0');
   }
	
   add_action( 'admin_enqueue_scripts', 'filescanneradmin' );
}


