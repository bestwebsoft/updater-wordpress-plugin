(function($) {
	$(document).ready( function() {
		/* add notice about changing in the settings page */
		$( '#pdtr_settings_form input' ).bind( "change click select", function() {
			if ( $( this ).attr( 'type' ) != 'submit' ) {
				$( '.updated.fade' ).css( 'display', 'none' );
				$( '#pdtr_settings_notice' ).css( 'display', 'block' );
			};
		});
	});
})(jQuery);