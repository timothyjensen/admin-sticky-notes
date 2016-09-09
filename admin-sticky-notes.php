<?php
/**
 * Plugin Name: Admin Sticky Notes
 * Plugin URI: https://github.com/timothyjensen/admin-sticky-notes
 * Description: Adds a metabox for Admin Notes that appear on the front end when an admin is logged in.  This is helpful for keeping track of edits that need to be made on each post/page.
 * Version: 1.0.0
 * Author: Tim Jensen
 * Author URI: http://www.timjensen.us
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License version 2, as published by the Free Software Foundation.  You may NOT assume
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 */


if ( ! defined( 'WPINC' ) ) {
    die;
}

// Plugin Directory
define( 'ASN_DIR', __DIR__ );

// Plugin URL
define( 'ASN_URL', plugin_dir_url(__FILE__) );

// Initialize CMB2
if ( file_exists( ASN_DIR . '/lib/CMB2/init.php' ) ) {
	require_once ASN_DIR . '/lib/CMB2/init.php';
} elseif ( file_exists( ASN_DIR . '/lib/cmb2/init.php' ) ) {
	require_once ASN_DIR . '/lib/cmb2/init.php';
}

// Registers admin notes metabox
add_action( 'cmb2_init', 'asn_register_admin_notes_metabox' );

// Renders notes on the front end only when an Admin is logged in
add_action( 'cmb2_init', 'asn_admin_notes' );

// Shortcode to list all page titles with their associated sticky note content.
add_shortcode( 'list-admin-sticky-notes', 'asn_list_admin_sticky_notes' );

/**
 * Registers admin notes metabox.
 *
 */
function asn_register_admin_notes_metabox() {
	$prefix = 'tj_';

	$cmb = new_cmb2_box( array(
		'id'            => $prefix . 'admin_notes_metabox',
		'title'         => __( 'Admin Notes', 'cmb2' ),
		'object_types'  => array( 'page', 'post' ), // Post type
		'context'    => 'normal',
		'priority'   => 'high',
		'show_names' => true, // Show field names on the left

	) );

	$cmb->add_field( array(
		'name'             => __( 'Heading', 'cmb2' ),
		'description'      => 'Include a heading for the notes section below (Optional)',
		'id'               => $prefix . 'admin_notes_label',
		'type'             => 'text_medium',
		) );

	$cmb->add_field( array(
		'name'             => __( 'Notes', 'cmb2' ),
		'id'               => $prefix . 'admin_notes',
		'type'             => 'wysiwyg',
        'options' => array(
                'media_buttons' => false,
            ),
		) );

}

/**
 * Adds notes on the front end when Admin is logged in
 * Helps with the revisions process
 *
 */
function asn_admin_notes() {

    if ( ! current_user_can( 'edit_dashboard' ) ) return;

    // Enqueue required Javascript
    add_action( 'wp_enqueue_scripts', 'asn_enqueue_admin_scripts' );

    // Add Admin Notes to front end
    add_action( 'wp_head', 'asn_render_admin_notes', 13 );

}

/**
 * Enqueue necessary scripts and styles
 *
 */
function asn_enqueue_admin_scripts() {

    wp_enqueue_script( 'admin-sticky-notes', ASN_URL . 'lib/js/admin-sticky-notes.js', array( 'jquery', 'jquery-ui-draggable' ), '1.0.0', false );

    wp_enqueue_style( 'admin-notes', ASN_URL . 'lib/css/admin-notes-style.css', array(), '1.0.0', false );

}

/**
 * Renders Admin Notes on the appropriate pages.
 *
 */
function asn_render_admin_notes() {

    $post_ID = get_the_ID();

    // Get the note content
    $admin_notes = get_post_meta( $post_ID, 'tj_admin_notes', true );

    // Return if there are no notes
    if ( empty ( $admin_notes ) )
		return;

    // Get the note label
    $admin_notes_label = get_post_meta( $post_ID, 'tj_admin_notes_label', true );

    // Render the note
    echo '<div id="admin-notes" class="entry-content"><div id="tj-admin-note">';

        if ( $admin_notes_label) printf( '<h2>%s</h2>', $admin_notes_label );

        echo apply_filters( 'the_content', $admin_notes);

	echo '</div>'; // End #tj-admin-note

    // Button toggles the note editor
	echo '<button id="toggle-editor">edit note</button>';

        // Render the WYSIWYG editor form
        $metabox_id = 'tj_admin_notes_metabox';
        $form = asn_cmb2_get_metabox_form( $metabox_id, $post_ID );
    	printf( '<div id="tj-admin-note-editor" class="hidden">%s</div>', $form );

    echo '</div>'; // End #admin-notes

}

/**
 * Retrieve a metabox form
 * @since  2.0.0
 * @param  mixed   $meta_box  Metabox config array or Metabox ID
 * @param  int     $object_id Object ID
 * @param  array   $args      Optional arguments array
 * @return string             CMB2 html form markup
 */
function asn_cmb2_get_metabox_form( $meta_box, $object_id = 0, $args = array() ) {

    $object_id = $object_id ? $object_id : get_the_ID();
    $cmb       = cmb2_get_metabox( $meta_box, $object_id );

    ob_start();
    // Get cmb form
    asn_cmb2_print_metabox_form( $cmb, $object_id, $args );
    $form = ob_get_contents();
    ob_end_clean();

    return apply_filters( 'cmb2_get_metabox_form', $form, $object_id, $cmb );
}

/**
 * Display a metabox form & save it on submission
 * @since  1.0.0
 * @param  mixed   $meta_box  Metabox config array or Metabox ID
 * @param  int     $object_id Object ID
 * @param  array   $args      Optional arguments array
 */
function asn_cmb2_print_metabox_form( $meta_box, $object_id = 0, $args = array() ) {

    $object_id = $object_id ? $object_id : get_the_ID();
    $cmb = cmb2_get_metabox( $meta_box, $object_id );

    // if passing a metabox ID, and that ID was not found
    if ( ! $cmb ) {
        return;
    }

    $args = wp_parse_args( $args, array(
        'form_format' => '<form class="cmb-form" method="post" id="%1$s" enctype="multipart/form-data" encoding="multipart/form-data"><input type="hidden" name="object_id" value="%2$s">%3$s<input type="submit" name="submit-cmb" value="%4$s" class="button-primary"></form>',
        'save_button' => __( 'Save Note', 'cmb2' ),
        'object_type' => $cmb->mb_object_type(),
        'cmb_styles'  => $cmb->prop( 'cmb_styles' ),
        'enqueue_js'  => $cmb->prop( 'enqueue_js' ),
    ) );

    // Set object type explicitly (rather than trying to guess from context)
    $cmb->object_type( $args['object_type'] );

    // Save the metabox if it's been submitted
    // check permissions
    // @todo more hardening?
    if (
        $cmb->prop( 'save_fields' )
        // check nonce
        && isset( $_POST['submit-cmb'], $_POST['object_id'], $_POST[ $cmb->nonce() ] )
        && wp_verify_nonce( $_POST[ $cmb->nonce() ], $cmb->nonce() )
        && $object_id && $_POST['object_id'] == $object_id
    ) {
        $cmb->save_fields( $object_id, $cmb->object_type(), $_POST );
    }

    // Enqueue JS/CSS
    if ( $args['cmb_styles'] ) {
        CMB2_hookup::enqueue_cmb_css();
    }

    if ( $args['enqueue_js'] ) {
        CMB2_hookup::enqueue_cmb_js();
    }

    $form_format = apply_filters( 'cmb2_get_metabox_form_format', $args['form_format'], $object_id, $cmb );

    $format_parts = explode( '%3$s', $form_format );

    // Show cmb form
    printf( $format_parts[0], $cmb->cmb_id, $object_id );
    $cmb->show_form();

    if ( isset( $format_parts[1] ) && $format_parts[1] ) {
        printf( str_ireplace( '%4$s', '%1$s', $format_parts[1] ), $args['save_button'] );
    }

}


/**
 * Creates shortcode to list all pages that have a sticky note as well as the content of the note
 *
 * @return string, the list of pages along with their note
 */
function asn_list_admin_sticky_notes() {

    $args = array(
        'posts_per_page' 	=> -1,
        'order' 		=> 'ASC',
        'orderby' 		=> 'title',
        'post_type'     => 'any',
        'meta_key'      => 'tj_admin_notes_label',
        'meta_value'    => 'Admin Notes',
    );

    $wp_query = new WP_Query($args);

    $list_sticky_notes = '<ol>';

    if ( $wp_query->have_posts() ) :

		while ( $wp_query->have_posts() ) : $wp_query->the_post();

            $list_sticky_notes .= sprintf( '<li><a href=%s>%s</a>', get_the_permalink(), get_the_title() );
            $list_sticky_notes .= sprintf( '%s</li>', apply_filters( 'the_content', get_post_meta( get_the_ID(), 'tj_admin_notes', true ) ) );

		endwhile; // end of one post

	endif; // end loop

    $list_sticky_notes .= '</ol>';

    return $list_sticky_notes;

}
