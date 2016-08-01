jQuery(function( $ ) {

    $( '#admin-notes' ).draggable();

    $( '#toggle-editor' ).on( 'click.admin-sticky-notes', function() {
        var id = $( this ).siblings( '#tj-admin-note-editor' );
        $( id ).toggleClass( 'hidden' );
    } );
    
} );
