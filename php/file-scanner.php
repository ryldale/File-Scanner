<?php

add_action('admin_menu', 'addFileScannerAdminMenu');

function addFileScannerAdminMenu() {
    add_menu_page('FILE SCANNER', 'FILE SCANNER', 'manage_options', 'file_scanner_menu', 'FileScannerAdminPage', 'dashicons-wordpress');
}

function FileScannerAdminPage() {
    // cms frontend page
    $page_name = "File Scanner";
    $page = '';

    // Main Container Start
    $page .= '<div id="main-con">';

    // Header Options
    $page .= '
        <div id="header-con" class="con">
            <h1>' . esc_html($page_name) . '</h1>
            <a id="btnAdd" >Scan</a>
        </div>
    ';

    // Main Container End
    $page .= '</div>';

    echo $page;
}
