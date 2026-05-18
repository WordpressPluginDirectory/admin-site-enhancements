( function ( $ ) {
	'use strict';

	$( function () {
		if (
			typeof asenhaDisableUserAccountProfile === 'undefined' ||
			! asenhaDisableUserAccountProfile.rowHtml
		) {
			return;
		}

		var $table = $( 'table.form-table' )
			.filter( function () {
				return $( this ).find( '#password' ).length > 0;
			} )
			.first();

		if ( ! $table.length ) {
			return;
		}

		var $rows = $(
			$.parseHTML(
				asenhaDisableUserAccountProfile.rowHtml,
				document,
				true
			)
		);

		var $sessions = $table.find( 'tr.user-sessions-wrap' ).first();
		if ( $sessions.length ) {
			$sessions.before( $rows );
			return;
		}

		var $reset = $table.find( 'tr.user-generate-reset-link-wrap' ).first();
		if ( $reset.length ) {
			$reset.after( $rows );
			return;
		}

		var $pwWeak = $table.find( 'tr.pw-weak' ).first();
		if ( $pwWeak.length ) {
			$pwWeak.after( $rows );
		}
	} );
} )( jQuery );
