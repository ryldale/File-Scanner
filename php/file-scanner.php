<?php

// Add action to create admin menu
add_action('admin_menu', 'addFileScannerAdminMenu');

function addFileScannerAdminMenu() {
    add_menu_page('FILE SCANNER', 'FILE SCANNER', 'manage_options', 'file_scanner_menu', 'FileScannerAdminPage', 'dashicons-wordpress');
}

// File Scanner Admin Page NOT WORKING
function FileScannerAdminPage() {
    $page_name = "File Scanner";
    $page = '';

    // Main Container Start
    $page .= '<div id="main-con">';

    // Header Options
    $page .= '
        <div id="header-con" class="con">
            <h1>' . $page_name . '</h1>
            <a id="scan">Scan</a>
        </div>
    ';

    // Container for file scanner results
    $page .= '<div class="list-con"></div>';

    // Main Container End
    $page .= '</div>';

    echo $page;
}


// File Scanner Action AJAX Callback
add_action('wp_ajax_file_scanner_action', 'file_scanner_action');
add_action('wp_ajax_nopriv_file_scanner_action', 'file_scanner_action');

// File Scanner Action Function
function file_scanner_action() {
    $theme_folder = get_stylesheet_directory(); 
    $uploads_folder = wp_upload_dir()['basedir'];

    $theme_files = scan_file($theme_folder);
    $uploads_files = scan_folder($uploads_folder);

    // Extract basenames before comparison
    $theme_files_basenames = array_map('basename', $theme_files);
    $uploads_files_basenames = array_map('basename', $uploads_files);

    // Find files used only in the theme
    $theme_only_files = array_diff($theme_files_basenames, $uploads_files_basenames);

    // Find files used only in uploads
    $uploads_only_files = array_diff($uploads_files_basenames, $theme_files_basenames);

    // Display the content of files and compare images
    display_file_content($theme_files, $uploads_files_basenames, $uploads_only_files);


    wp_die();
}


// Helper function to scan a folder for specific file types (FOR IMAGES INSIDE THE FOLDER)
function scan_folder($folder) {
    $allowed_file_types = array('png', 'jpg', 'jpeg', 'pdf');
    $scanned_folder = array();
    $scanned_directory = array($folder);

    $dir = new DirectoryIterator($folder);

    foreach ($dir as $file) {
        if ($file != '.' && $file != '..' && $file != 'screenshot.png'){
            if ($file->isFile() && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $allowed_file_types)) {
                $scanned_folder[] = $file->getPathname();
            } elseif(is_dir($file->getPathname()) && !in_array($file->getPathname(), $scanned_directory)) {
                $scanned_directory[] = $file->getPathname();
                $scan = scan_folder($file->getPathname());
                $scanned_folder = array_merge($scan, $scanned_folder);
            }
        }
    }

    return $scanned_folder;
}

// Helper function to scan a folder for specific file types (FOR IMAGES INSIDE THE FILES)
function scan_file($folder) {
    $allowed_file_types = array('php', 'css', 'js');
    $scanned_files = array();

    $dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder));

    foreach ($dir as $file) {
        if ($file->isFile() && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $allowed_file_types)) {
            $scanned_files[] = $file->getPathname();
        }
    }

    return $scanned_files;
}

// Helper function to display file content and compare images
function display_file_content($theme_files, $uploads_files_basenames, $uploads_only_files) {
    echo '<table border="1">';
    echo '<tr><th>Used Images</th><th>Not Used Images</th></tr>';

    // Arrays to store used and not used images across all theme files
    $all_used_images = [];
    $all_not_used_images = [];

    foreach ($theme_files as $theme_file) {
        $content = file_get_contents($theme_file);

        // Extract image filenames from the content
        $image_references = [];
        preg_match_all('/\/wp-content\/uploads\/\d+\/\d+\/([^\'"]+\.(?:png|jpeg|jpg|pdf))/', $content, $matches); // NOT SURE - Need links of all Images if this is the Default links for images

        if (!empty($matches[1])) {
            $image_references = $matches[1];
        }

        // Compare with the images found in $uploads_files
        $used_images = array_intersect($uploads_files_basenames, $image_references);
        $empty_images = array_diff($image_references, $uploads_files_basenames);
        $all_uploads = array_diff($uploads_only_files, $empty_images);
        $not_used_images = array_diff($all_uploads, $used_images);

        // Accumulate used and not used images across all theme files
        $all_used_images = array_merge($all_used_images, $used_images);
        $all_not_upload_images = array_merge($all_not_used_images, $not_used_images);

        // // Display the results for each file in a table row
        // echo '<tr>';
        // echo '<td>' . basename($theme_file) . '</td>';
        // echo '<td>' . implode(', ', $used_images) . '</td>';
        // echo '<td>' . implode(', ', $not_used_images) . '</td>';
        // echo '</tr>';
    }
        $all_not_used_images = array_diff($all_not_upload_images, $all_used_images);
        
        // Display a row for all used and not used images across theme files
        echo '<tr>';
        // echo '<td>All Files</td>';
        echo '<td>' . implode('</br>', $all_used_images) . '</td>';
        echo '<td>' . implode('</br>', $all_not_used_images) . '</td>';
        echo '</tr>';
        
    echo '</table>';
}
