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
    $page .= '<div id="fileScannerResults" class="list-con"></div>';

    $page .= '<div id="pagination-container"></div>';
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

    echo '<table border="1" id="fileScannerTable">';
    echo '<tr><th>Images</th><th>File (css, php & js)</th><th>Database</th></tr>';

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
        // echo '<tr>';
        // echo '<td>' . implode('</br>', $used_images) . '</td>';
        // echo '<td>' . basename($theme_file) . '</td>';
        // echo '<td>';

        // foreach ($used_images as $image) {
        //     $database_path = get_database_path($image);
        //     if (!empty($database_path)) {
        //         echo $database_path . '/' . $image . '<br>';
        //     }
        // }

        // echo '</td>';
        // echo '</tr>';

        // Display the results for each file in separate columns
        foreach ($used_images as $image) {
            echo '<tr class="all-images">';
            // IMAGES
            echo '<td>' . $image . '</td>';

            //FILE TYPE
            echo '<td>' . basename($theme_file) . '</td>';

            // DATABASE
            echo '<td>';

            $database_path = get_database_path($image);
            if (!empty($database_path)) {
                echo $database_path . '/' . $image;
            }



            echo '</td>';
            '</tr>';
        }

    }

    // Display a row for all used and not used images across all files
    foreach ($all_not_used_images as $image) {
        echo '<tr class="all-images">';
        // IMAGES
        echo '<td>' . $image . '</td>';

        //FILE TYPE
        echo '<td>NA</td>';

        // DATABASE
        echo '<td>';

        $database_path = get_database_path($image);
        if (!empty($database_path)) {
            echo $database_path . '/' . $image . '<br>';
        }
        // DELETE BUTTON
        echo '<td><button class="delete-image" data-image="' . $image . '">Delete</button></td>';

        echo '</td>';
        '</tr>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</table>';

}

function get_database_path($image)
{
    global $wpdb;
    $database_path = '';

    $mytables = $wpdb->get_results("SHOW TABLES");

    foreach ($mytables as $mytable) {
        foreach ($mytable as $t) {
            $table = $wpdb->get_results("DESC " . $t);

            foreach ($table as $tables) {
                $field = $tables->Field;

                // Check if the field is "_wp_attachment_metadata"
                if ($field !== '_wp_attachment_metadata') {
                    $post_tables = $wpdb->get_results("SELECT * FROM $t WHERE $field LIKE '%" . $image . "%'");

                    // Check if the row contains "_wp_attachment_metadata" and skip further processing
                    $post_tables = array_filter($post_tables, function ($row) {
                        return !in_array('_wp_attachment_metadata', (array) $row, true);
                    });

                    if (!empty($post_tables)) {
                        $database_path = $t . '/' . $field;
                        break 3; // break out of all loops
                    }
                }
            }
        }
    }

    return $database_path;
}

function delete_image_from_database($image) {
    global $wpdb;

    // Remove the image reference from all tables in the database
    $mytables = $wpdb->get_results("SHOW TABLES");

    foreach ($mytables as $mytable) {
        foreach ($mytable as $t) {
            $field = null;

            // Check if the table has a column that references the image
            $table = $wpdb->get_results("DESC " . $t);
            foreach ($table as $tables) {
                $field = $tables->Field;

                // Check if the field is "_wp_attachment_metadata"
                if ($field !== '_wp_attachment_metadata') {
                    // Remove the image reference from the table
                    $wpdb->query($wpdb->prepare("UPDATE $t SET $field = REPLACE($field, %s, '')", $image));
                }
            }
        }
    }
}