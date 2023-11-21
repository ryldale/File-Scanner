<?php


function Images(){
    ob_start();

    printf('
                <img src="/wp-content/uploads/2023/11/VAPT.png" >
                <img src="/wp-content/uploads/2023/11/Threat-Intelligence.png" >
                <img src="/wp-content/uploads/2023/11/Source-Code.png" >
                <img src="/wp-content/uploads/2023/11/RTE.png" >
                ');
    
    return ob_get_clean();

}

add_shortcode( 'Image', 'Images' );