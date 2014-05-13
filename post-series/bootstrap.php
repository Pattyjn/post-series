<?php
/**
 * Plugin Name: Post Series
 * Plugin URI: https://github.com/Pattyjn/post-series
 * Description: Add series taxonomy to posts and displays posts in the same series at the start of each post
 * Version: 1.0
 * Author: Patrick Neary
 * Author URI: http://www.patrickneary.co
 */

// Add actions
add_action( 'init', 'pjn_ps_register_series_taxonomy', 10 );
add_action( 'wp_enqueue_scripts', 'pjn_ps_enqueue_style', 10 );

// Add filter
add_filter( 'the_content', 'pjn_ps_add_series_links', 10 );

/**
 * Registers the series taxonomy
 */
function pjn_ps_register_series_taxonomy() {

    $labels = array(
        'name'              => _x( 'Series', 'taxonomy general name' ),
        'singular_name'     => _x( 'Series', 'taxonomy singular name' ),
        'search_items'      => __( 'Search Series' ),
        'all_items'         => __( 'Series' ),
        'parent_item'       => __( 'Parent Series' ),
        'parent_item_colon' => __( 'Parent Series:' ),
        'edit_item'         => __( 'Edit Series' ),
        'update_item'       => __( 'Update Series' ),
        'add_new_item'      => __( 'Add New Series' ),
        'new_item_name'     => __( 'New Series Name' ),
        'menu_name'         => __( 'Series' ),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'series' ),
    );

    register_taxonomy( 'series', array( 'post' ), $args );
}

/**
 * Add series to content
 *
 * Generates the content for our page. First of all establishing current series terms, ensuring that it is the only series
 * but changing the ID to 0. We then proceed to build the arguments for our query. Before we run a query that selects all the posts
 * in the current series. We then open a list before looping through each post, checking for the current.
 *
 * @param $content string the current content
 * @return string modified content
 */
function pjn_ps_add_series_links( $content ) {

    global $post;

    // get the current series terms
    $series = wp_get_post_terms($post->ID, 'series', array());

    // If the series for the post is empty return the original content
    if(empty($series)) {
        return $content;
    }

    // Set series to the first series - we don't want to handle multiple series at this point
    $series = $series[0];

    // Build the arguments for the query
    $args = array(
        'post_type' => 'post',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'order' => 'ASC',
        'orderby' => 'date',
        'tax_query' => array(
            array(
                'taxonomy' => 'series',
                'field' => 'slug',
                'terms' => $series->slug
            )
        )
    );

    // Runs the query that gets the posts in the series
    $query = new WP_Query( $args );
    $posts = $query->posts;

    // Open a list
    $additional = '<div class="series-container"><p>This post is part of the series: <a href="' . get_term_link( $series, 'series' ) . '" title="' . $series->name . '" >' . $series->name . '</a></p><ul>';

    // Prepare counter
    $counter = 1;

    // Loop through each post
    foreach ($posts as $relatedPost) {

        // Check if post is current
        if($post->ID == $relatedPost->ID) {
            $class = "class='current'";
        } else {
            $class = '';
        }

        // Link to each post in a list item
        $additional .= '<li><a ' . $class . ' href="' . get_permalink( $relatedPost->ID ) . '" title ="' . $relatedPost->post_title . '">Part ' . $counter . ': ' . $relatedPost->post_title . '</a></li>';

        $counter++;
    }

    // Close the list
    $additional .= '</ul></div>';

    // Gluing additional(at the front) onto content
    $content = $additional . $content;

    // Returns the content.
    return $content;
}

/**
 * Add CSS to page
 *
 * Locates the appropriate css file and adds it to the page
 */
function pjn_ps_enqueue_style() {
    wp_enqueue_style( 'series-style', plugins_url() . '/post-series/css/style.css' );
}