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
		if ( ! $("input:checkbox[name='pdtr_send_mail_get_update']" ).is( ':checked' ) && ! $( "input:checkbox[name='pdtr_send_mail_after_update']" ).is( ':checked' ) ) {
			$( '.pdtr_email_settings, .pdtr_email_settings_new_update, .pdtr_email_settings_updated_completed' ).addClass( 'hidden' );
		};
		$( "input:checkbox[name='pdtr_send_mail_get_update']" ).change( function() {
			if ( ! $("input:checkbox[name='pdtr_send_mail_get_update']" ).is( ':checked' ) && ! $( "input:checkbox[name='pdtr_send_mail_after_update']" ).is( ':checked' ) ) {
				$('.pdtr_email_settings, .pdtr_email_settings_new_update').addClass( 'hidden' );
			} else if ( ! $("input:checkbox[name='pdtr_send_mail_get_update']" ).is( ':checked' ) &&  $( "input:checkbox[name='pdtr_send_mail_after_update']" ).is( ':checked' ) ) {
				$( '.pdtr_email_settings_new_update' ).addClass( 'hidden' );
			} else {
				$( '.pdtr_email_settings, .pdtr_email_settings_new_update' ).removeClass( 'hidden' );
			};
		});
		$( "input:checkbox[name='pdtr_send_mail_after_update']" ).change( function() {
			if ( ! $( "input:checkbox[name='pdtr_send_mail_get_update']" ).is( ':checked' ) && ! $( "input:checkbox[name='pdtr_send_mail_after_update']" ).is( ':checked' ) ) {
				$( '.pdtr_email_settings, .pdtr_email_settings_updated_completed' ).addClass( 'hidden' );
			} else if (  $("input:checkbox[name='pdtr_send_mail_get_update']" ).is( ':checked' ) && ! $( "input:checkbox[name='pdtr_send_mail_after_update']" ).is( ':checked' ) ) {
				$( '.pdtr_email_settings_updated_completed' ).addClass( 'hidden' );
			} else {
				$( '.pdtr_email_settings, .pdtr_email_settings_updated_completed' ).removeClass( 'hidden' );
			};
		});
	});
})(jQuery);
