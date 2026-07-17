/**
 * BuddyPress Birthdays — Admin JS (toast + confirm helpers + field toggles).
 *
 * Skill Part 6 rules 10 + 12: no browser alert()/confirm() anywhere.
 * Exposes window.bbdToast() and window.bbdConfirm() so this file and any
 * future Pro extension share the same feedback surface.
 *
 * @since 2.5.0
 */
( function ( $ ) {
	'use strict';

	var i18n = ( window.bbdAdmin && window.bbdAdmin.i18n ) || {};

	/* ─── Toast ─────────────────────────────────────────────── */

	function getToastHost() {
		var host = document.querySelector( '.bbd-toast-host' );
		if ( ! host ) {
			host = document.createElement( 'div' );
			host.className = 'bbd-toast-host';
			document.body.appendChild( host );
		}
		return host;
	}

	function toast( message, tone ) {
		tone = tone || 'info';
		var host = getToastHost();
		var el   = document.createElement( 'div' );
		el.className   = 'bbd-toast bbd-toast--' + tone;
		el.setAttribute( 'role', 'status' );
		el.textContent = String( message );
		host.appendChild( el );

		requestAnimationFrame( function () {
			el.classList.add( 'bbd-toast--visible' );
		} );

		window.setTimeout( function () {
			el.classList.remove( 'bbd-toast--visible' );
			window.setTimeout( function () {
				if ( el.parentNode ) {
					el.parentNode.removeChild( el );
				}
			}, 250 );
		}, 3600 );
	}

	window.bbdToast = toast;

	/* ─── Confirm modal (returns a Promise) ──────────────────── */

	function confirmModal( opts ) {
		opts = opts || {};
		return new Promise( function ( resolve ) {
			var backdrop = document.createElement( 'div' );
			backdrop.className = 'bbd-confirm-backdrop';

			var card = document.createElement( 'div' );
			card.className = 'bbd-confirm';
			card.setAttribute( 'role', 'dialog' );
			card.setAttribute( 'aria-modal', 'true' );

			var title = document.createElement( 'h2' );
			title.className = 'bbd-confirm__title';
			title.textContent = opts.title || '';
			if ( opts.title ) { card.appendChild( title ); }

			var desc = document.createElement( 'p' );
			desc.className = 'bbd-confirm__desc';
			desc.textContent = opts.message || i18n.confirmDanger || '';
			if ( opts.message || i18n.confirmDanger ) { card.appendChild( desc ); }

			var actions = document.createElement( 'div' );
			actions.className = 'bbd-confirm__actions';

			var cancelBtn = document.createElement( 'button' );
			cancelBtn.type = 'button';
			cancelBtn.className = 'bbd-btn bbd-btn-secondary';
			cancelBtn.textContent = opts.cancelLabel || i18n.confirmCancel || 'Cancel';

			var confirmBtn = document.createElement( 'button' );
			confirmBtn.type = 'button';
			confirmBtn.className = 'bbd-btn ' + ( 'danger' === opts.tone ? 'bbd-btn-danger' : 'bbd-btn-primary' );
			confirmBtn.textContent = opts.confirmLabel || i18n.confirmContinue || 'Continue';

			actions.appendChild( cancelBtn );
			actions.appendChild( confirmBtn );
			card.appendChild( actions );
			backdrop.appendChild( card );
			document.body.appendChild( backdrop );

			function cleanup( result ) {
				document.removeEventListener( 'keydown', onKey );
				if ( backdrop.parentNode ) {
					backdrop.parentNode.removeChild( backdrop );
				}
				resolve( result );
			}

			function onKey( e ) {
				if ( 'Escape' === e.key ) { cleanup( false ); }
				if ( 'Enter' === e.key ) { cleanup( true ); }
			}

			cancelBtn.addEventListener( 'click', function () { cleanup( false ); } );
			confirmBtn.addEventListener( 'click', function () { cleanup( true ); } );
			backdrop.addEventListener( 'click', function ( e ) {
				if ( e.target === backdrop ) { cleanup( false ); }
			} );
			document.addEventListener( 'keydown', onKey );
			confirmBtn.focus();
		} );
	}

	window.bbdConfirm = confirmModal;

	/* ─── Dependent-field toggles ────────────────────────────── */

	/**
	 * Enable/disable a group of dependent rows based on a checkbox.
	 *
	 * @param {jQuery} $checkbox      The controlling checkbox.
	 * @param {string} dependentClass Class on the dependent <tr> rows.
	 */
	function toggleDependentFields( $checkbox, dependentClass ) {
		var isChecked = $checkbox.is( ':checked' );
		var $rows     = $( '.' + dependentClass );
		if ( isChecked ) {
			$rows.removeClass( 'disabled' );
			$rows.find( 'input, select, textarea' ).prop( 'disabled', false );
		} else {
			$rows.addClass( 'disabled' );
			$rows.find( 'input, select, textarea' ).prop( 'disabled', true );
		}
	}

	function wireToggle( name, dependentClass ) {
		var $toggle = $( 'input[name="' + name + '"]' );
		if ( ! $toggle.length ) {
			return;
		}
		toggleDependentFields( $toggle, dependentClass );
		$toggle.on( 'change', function () {
			toggleDependentFields( $( this ), dependentClass );
		} );
	}

	$( function () {
		wireToggle( 'bp_birthdays_settings[email_enabled]', 'email-dependent' );
		wireToggle( 'bp_birthdays_settings[admin_email_enabled]', 'admin-email-dependent' );
		wireToggle( 'bp_birthdays_settings[activity_enabled]', 'activity-dependent' );
		wireToggle( 'bp_birthdays_settings[notification_enabled]', 'notification-dependent' );

		// Generic destructive-action confirm (opt-in via data-bbd-confirm).
		$( document ).on( 'click', '[data-bbd-confirm]', function ( e ) {
			var $el = $( this );
			if ( $el.attr( 'data-action' ) ) { return; }
			if ( $el.data( 'bbd-confirm-ok' ) ) { return; }
			e.preventDefault();
			var message = $el.data( 'bbd-confirm' ) || i18n.confirmDanger;
			var tone    = $el.data( 'bbd-confirm-tone' ) || 'danger';
			confirmModal( { message: message, tone: tone } ).then( function ( ok ) {
				if ( ! ok ) { return; }
				$el.data( 'bbd-confirm-ok', true );
				if ( $el.is( 'a' ) ) {
					window.location.href = $el.attr( 'href' );
				} else if ( $el.is( 'button' ) || $el.is( 'input' ) ) {
					var form = $el.closest( 'form' ).get( 0 );
					if ( form ) { form.submit(); }
				}
			} );
		} );
	} );

} )( jQuery );
