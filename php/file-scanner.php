<?php

// Add action to create admin menu
add_action('admin_menu', 'addFileScannerAdminMenu');

function addFileScannerAdminMenu()
{
    add_menu_page('FILE SCANNER', 'FILE SCANNER', 'manage_options', 'file_scanner_menu', 'FileScannerAdminPage', 'dashicons-wordpress');
}

// File Scanner Admin Page NOT WORKING
function FileScannerAdminPage()
{
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
function file_scanner_action()
{
    $theme_folder = get_stylesheet_directory();
    $uploads_folder = wp_upload_dir()['basedir'];

    $theme_files = scan_directory($theme_folder, array('php', 'css', 'js'));
    $uploads_files = scan_directory($uploads_folder, array('png', 'jpg', 'jpeg', 'pdf'));

    // Extract basenames before comparison
    $theme_files_basenames = array_map('basename', $theme_files);
    $uploads_files_basenames = array_map('basename', $uploads_files);


    // Find files used only in uploads
    $uploads_only_files = array_diff($uploads_files_basenames, $theme_files_basenames);

    // Display the content of files and compare images
    display_file_content($theme_files, $uploads_files_basenames, $uploads_only_files);


    wp_die();
}


// Helper function to scan a folder for specific file types (FOR IMAGES INSIDE THE FOLDER)
function scan_directory($folder, $allowed_file_types)
{
    // $allowed_file_types = array('png', 'jpg', 'jpeg', 'pdf');
    $scanned_folder = array();
    $scanned_directory = array($folder);

    $dir = new DirectoryIterator($folder);

    foreach ($dir as $file) {
        if ($file != '.' && $file != '..' && $file != 'screenshot.png') {
            if ($file->isFile() && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $allowed_file_types)) {
                $scanned_folder[] = $file->getPathname();
            } elseif (is_dir($file->getPathname()) && !in_array($file->getPathname(), $scanned_directory)) {
                $scanned_directory[] = $file->getPathname();
                $scan = scan_directory($file->getPathname(), $allowed_file_types);
                $scanned_folder = array_merge($scan, $scanned_folder);
            }
        }
    }

    return $scanned_folder;
}

// Helper function to display file content and compare images
function display_file_content($theme_files, $uploads_files_basenames, $uploads_only_files)
{
    echo '<table border="1">';
    echo '<tr><th>Used Images</th><th>Not Used Images</th><th>Files</th></tr>';

    // Arrays to store used and not used images across all theme files
    $all_used_images = [];
    $all_not_used_images = [];

    foreach ($theme_files as $theme_file) {
        $content = file_get_contents($theme_file);
        
        // Parenthesis ()
        $content = preg_replace('/\(\s*([\'"])?([^\'")\s]+)\1?\s*\)/', '("$2")', $content);

        // Extract image filenames from the content
        $image_references = [];

        // Inside the Parenthesis ""
        preg_match_all('/\/wp-content\/uploads\/\d+\/\d+\/([^\'"]+\.(?:png|jpeg|jpg|pdf))/', $content, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $image) {
                $image_parts = pathinfo($image);
                $image_references[] = $image_parts['basename'];
            }
        }


        // Compare with the images found in $uploads_files
        $used_images = array_intersect($uploads_files_basenames, $image_references);

        $theme_file_images = array_diff($image_references, $uploads_files_basenames); // Images inside the File
        $upload_file_images = array_diff($uploads_only_files, $theme_file_images); // Images inside the folder Uploads
        
        // Merge the Theme File and Upload File Images within a file 
        $all_images = array_merge($theme_file_images, $upload_file_images);
        $not_used_images = array_diff($all_images, $used_images);


        // Accumulate used and not used images across all files
        $all_used_images = array_unique(array_merge($all_used_images, $used_images));
        $all_not_used_images = array_unique(array_merge($all_not_used_images, $not_used_images));
        $all_not_used_images = array_diff($all_not_used_images, $all_used_images);


        // // Display the results for each file in a table row
        echo '<tr>';
        echo '<td>' . implode(', ', $used_images) . '</td>';
        echo '<td>' . implode(', ', $not_used_images) . '</td>';
        echo '<td>' . basename($theme_file) . '</td>';
        echo '</tr>';
    }

    // Display a row for all used and not used images across all files
    echo '<tr>';
    echo '<td>' . implode('</br>', $all_used_images) . '</td>';
    echo '<td>' . implode('</br>', $all_not_used_images) . '</td>';
    echo '<td> ALL FILES </td>';
    echo '</tr>';

    echo '</table>';
}
