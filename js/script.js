(function($) {
	$(document).ready(function(){
		$( document ).on( 'click', '#plugin_update_from_iframe', function( event ) {
			if ( window.parent === window ) {
				return;
			}
			var target = window.parent.document.body;

			if ( ! $( target ).hasClass( 'toplevel_page_updater' ) ) {
				return;
			}
			var update_link = $( target ).find( 'tr[data-key="' + $( this ).data( 'plugin' ) + '"]' ).find( '.pdtr-update-now' );
			
			$( this ).attr( 'disabled', 'disabled' );

			if ( update_link.length > 0 ) {
			  	window.parent.location = $( update_link ).attr( 'href' );
			} else {
				window.parent.location = $( this ).attr( 'href' );
			}
		} );
	});
})(jQuery);
