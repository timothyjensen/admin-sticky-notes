<?php
/**
 * Plugin Name: Admin Notes
 * Plugin URI: https://github.com/timothyjensen/admin-sticky-notes
 * Description: Adds a metabox for Admin Notes that appear on the front end when an admin is logged in.  This is helpful for keeping track of edits that need to be made on each page when building a site.
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

//* Plugin Directory
define( 'ASN_DIR', __DIR__ );

//* Plugin URL
define( 'ASN_URL', plugin_dir_url(__FILE__) );

//* Initialize CMB2
if ( file_exists( ASN_DIR . '/lib/CMB2/init.php' ) ) {
	require_once ASN_DIR . '/lib/CMB2/init.php';
} elseif ( file_exists( ASN_DIR . '/lib/cmb2/init.php' ) ) {
	require_once ASN_DIR . '/lib/cmb2/init.php';
}

//* Registers admin notes metabox
add_action( 'cmb2_init', 'tj_register_admin_notes_metabox' );

//* Renders notes on the front end only when an Admin is logged in
add_action( 'pre_get_posts', 'tj_admin_notes' );

/**
 * Registers admin notes metabox
 *
 */
function tj_register_admin_notes_metabox() {
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
function tj_admin_notes() {

    if ( ! current_user_can( 'edit_dashboard' ) || is_search() ) return;

    //* Enqueue required Javascript
    add_action( 'wp_enqueue_scripts', 'tj_enqueue_admin_scripts' );

    //* Add Admin Notes to front end
    add_action( 'wp_footer', 'tj_render_admin_notes', 13 );

}

/**
 * Enqueue necessary scripts and styles
 *
 */
function tj_enqueue_admin_scripts() {

    wp_enqueue_script( 'admin-sticky-notes', ASN_URL . 'lib/js/admin-sticky-notes.js', array( 'jquery', 'jquery-ui-draggable' ), '1.0.0', false );

    wp_enqueue_style( 'admin-notes', ASN_URL . 'lib/css/admin-notes-style.css', array(), '1.0.0', false );

}

/**
 * Renders Admin Notes on the appropriate pages.
 *
 */
function tj_render_admin_notes() {

    //* Get the note content
    $admin_notes = get_post_meta( get_the_ID(), 'tj_admin_notes', true );

    //* Return if there are no notes
    if ( empty ( $admin_notes ) )
		return;

    //* Get the note label
    $admin_notes_label = get_post_meta( get_the_ID(), 'tj_admin_notes_label', true );

    //* Render the note
    echo '<div id="admin-notes" class="entry-content"><div id="tj-admin-note">';

        if ( $admin_notes_label) printf( '<h2>%s</h2>', $admin_notes_label );

        echo apply_filters( 'the_content', $admin_notes);

	echo '</div>'; //* End #tj-admin-note

    //* Button toggles the note editor
	echo '<button id="toggle-editor">edit note</button>';

        //* WYSIWYG editor
        $object_id = get_the_ID();
    	$metabox_id = 'tj_admin_notes_metabox';

    	$form = cmb2_get_metabox_form( $metabox_id, $object_id );

    	printf( '<div id="tj-admin-note-editor" class="hidden">%s</div>', $form );

    echo '</div>'; //* End #admin-notes

}
