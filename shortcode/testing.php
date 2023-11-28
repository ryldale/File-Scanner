<?php

function wpdatabase()
{
    $asset = "placeholder.png";
    global $wpdb;
    $mytables = $wpdb->get_results("SHOW TABLES");
    foreach ($mytables as $mytable) {
        foreach ($mytable as $t) {
            echo "<br>" . $t . "<br>";
            $table = $wpdb->get_results("desc " . $t);

            foreach ($table as $tables) {
                $field = $tables->Field;
                echo $field . '</br>';
                $post_tables = $wpdb->get_results("SELECT * FROM $t WHERE $field LIKE '%".$asset."%'");

                // echo $field;

                if (!empty($post_tables)) {
                    echo '<pre>';
                    echo print_r($post_tables, true);
                    echo '</pre>';
                }
            }
        }
    }
}

add_shortcode("wp_database", "wpdatabase");