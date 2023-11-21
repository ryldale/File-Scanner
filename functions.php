<?php

// INCLUDE CONTENTS include(get_stylesheet_directory() . 'FILE_PATH');
include(get_stylesheet_directory() . '/shortcode/home.php');

function my_theme_enqueue_styles() { 
 wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );

    if(is_page( 'home' )){
        wp_enqueue_style( 'home-style', get_stylesheet_directory_uri().'/css/home.css',array(),null);
    }

}
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );


//admin side
if(is_admin()){
	$mainFolderName = 'php';
	include $mainFolderName.'/file-scanner.php';
	
    function filescanneradmin($hook){
        //Styles
        wp_enqueue_style( 'filescanner-style', get_stylesheet_directory_uri().'/css/file-scanner.css',array(),null);

        //Scripts
        wp_enqueue_script( 'jquery3.7.1-script', 'https://code.jquery.com/jquery-3.7.1.min.js', array(),null);
        wp_enqueue_script( 'filescanner-script', get_stylesheet_directory_uri().'/js/filescanner.js',array(),null);
        wp_localize_script( 'filescanner-script', 'myAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
   }
	
   add_action( 'admin_enqueue_scripts', 'filescanneradmin' );
}


