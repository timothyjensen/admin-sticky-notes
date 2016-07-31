jQuery(document).ready(function($) {
                $("#toggle-editor").click(function(){
                    var id = $(this).siblings("#tj-admin-note-editor");
                    $(id).toggleClass("hidden");
                });
		});
