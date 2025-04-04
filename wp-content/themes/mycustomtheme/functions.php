<?php

function followandrew_theme_support()
{
    // add dynamic title tag support
    add_theme_support("title-tag");
    add_theme_support('custom-logo');
    add_theme_support('post-thumbnails');
}
add_action('after_setup_theme', 'followandrew_theme_support');


function followandrew_menus()
{
    $locations = array(
        'primary' => "Main Navigation Menu",
        'footer' => "Footer Navigation Menu"
    );

    register_nav_menus($locations);
}
add_action('init', 'followandrew_menus');


function followandrew_register_styles()
{
    $version = wp_get_theme()->get("Version");

    wp_enqueue_style("followandrew-style", get_template_directory_uri() . '/style.css', array('followandrew-bootstrap'), $version, 'all');
    wp_enqueue_style("followandrew-bootstrap", 'https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css', array(), '4.4.1', 'all');
    wp_enqueue_style("followandrew-fontawesome", 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css', array(), '5.13.0', 'all');
}

add_action('wp_enqueue_scripts', 'followandrew_register_styles');

function followandrew_register_scripts()
{
    wp_enqueue_script("followandrew-jquery", 'https://code.jquery.com/jquery-3.4.1.slim.min.js', array(), '3.4.1', true);
    wp_enqueue_script("followandrew-bootstrap", 'https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js', array(), '4.4.1', true);
    wp_enqueue_script("followandrew-popper", 'https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js', array(), '1.16.0', true);
    wp_enqueue_script("followandrew-main", get_template_directory_uri() . '/assets/js/main.js', array(), '1.0', true);
    wp_enqueue_script("followandrew-fontawesome", get_template_directory_uri() . '/assets/js/fontawesome.js', array(), '1.0', true);
}

add_action('wp_enqueue_scripts', 'followandrew_register_scripts');


function followandrew_widget_areas()
{
    register_sidebar(
        array(
            'before_title' => '',
            'after_title' => '',
            'before_widget' => '<ul class="social-list list-inline py-3 mx-auto">',
            'after_widget' => '</ul>',
            'name' => 'Sidebar Area',
            'id' => 'sidebar-1',
            'description' => 'Sidebar Widget Area'
        )
    );

    register_sidebar(
        array(
            'before_title' => '<h2 style="color: white;">',
            'after_title' => '</h2>',
            'before_widget' => '',
            'after_widget' => '',
            'name' => 'Footer Area',
            'id' => 'footer-1',
            'description' => 'Footer Widget Area'
        )
    );
}

add_action('widgets_init', 'followandrew_widget_areas');


function register_my_menus()
{
    register_nav_menus(
        array(
            'main-menu' => __('Main Menu')
        )
    );
}
add_action('after_setup_theme', 'register_my_menus');

function custom_breadcrumbs()
{
    if (!is_home()) {
        echo '<nav class="breadcrumbs">';
        echo '<a href="' . home_url() . '">Acceuil</a> » ';

        if (is_category() || is_single()) {
            the_category(' » ');
            if (is_single()) {
                echo ' » <span>' . get_the_title() . '</span>';
            }
        } elseif (is_page()) {
            global $post;
            if ($post->post_parent) {
                $parent_id = $post->post_parent;
                $breadcrumbs = array();

                while ($parent_id) {
                    $page = get_post($parent_id);
                    $breadcrumbs[] = '<a href="' . get_permalink($page->ID) . '">' . get_the_title($page->ID) . '</a>';
                    $parent_id = $page->post_parent;
                }

                echo implode(' » ', array_reverse($breadcrumbs)) . ' » ';
            }
            echo '<span>' . get_the_title() . '</span>';
        }
        echo '</nav>';
    }
}

?>