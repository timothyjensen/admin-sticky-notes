jQuery(document).ready(function($) {

    $( "#admin-notes" ).draggable();

    $( "#toggle-editor" ).click(function(){
        var id = $(this).siblings( "#tj-admin-note-editor" );
        $(id).toggleClass( "hidden" );
    });

});
