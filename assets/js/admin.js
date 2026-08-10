( function () {
	document.addEventListener( 'DOMContentLoaded', function () {
		var wrap = document.querySelector( '.amw-settings' );
		if ( ! wrap ) {
			return;
		}

		/* ── Tabs ── */
		var tabs   = Array.prototype.slice.call( wrap.querySelectorAll( '.amw-tabs .nav-tab' ) );
		var panels = wrap.querySelectorAll( '.amw-tab-panel' );

		function activate( tab, focus ) {
			var target = tab.getAttribute( 'data-amw-tab' );

			tabs.forEach( function ( t ) {
				var on = ( t === tab );
				t.classList.toggle( 'nav-tab-active', on );
				t.setAttribute( 'aria-selected', on ? 'true' : 'false' );
				t.setAttribute( 'tabindex', on ? '0' : '-1' );
			} );

			panels.forEach( function ( panel ) {
				panel.classList.toggle( 'amw-active', panel.getAttribute( 'data-amw-panel' ) === target );
			} );

			if ( focus ) {
				tab.focus();
			}
		}

		tabs.forEach( function ( tab, index ) {
			tab.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				activate( tab, false );
			} );

			tab.addEventListener( 'keydown', function ( event ) {
				var next = null;

				if ( 'ArrowRight' === event.key || 'ArrowDown' === event.key ) {
					next = ( index + 1 ) % tabs.length;
				} else if ( 'ArrowLeft' === event.key || 'ArrowUp' === event.key ) {
					next = ( index - 1 + tabs.length ) % tabs.length;
				} else if ( 'Home' === event.key ) {
					next = 0;
				} else if ( 'End' === event.key ) {
					next = tabs.length - 1;
				}

				if ( null !== next ) {
					event.preventDefault();
					activate( tabs[ next ], true );
				}
			} );
		} );

		/* ── Revisions: show the limit field only in "limit" mode ── */
		var revMode  = document.getElementById( 'amw-revisions-mode' );
		var revLimit = document.getElementById( 'amw-revisions-limit' );

		if ( revMode && revLimit ) {
			var syncRevLimit = function () {
				revLimit.style.display = ( 'limit' === revMode.value ) ? '' : 'none';
			};
			syncRevLimit();
			revMode.addEventListener( 'change', syncRevLimit );
		}

		/* ── Purge revisions: confirmation modal + AJAX ── */
		var purgeBtn = document.getElementById( 'amw-purge-revisions' );
		var modal    = document.getElementById( 'amw-purge-modal' );

		if ( purgeBtn && modal && window.amwToolbox ) {
			var cancelBtn  = document.getElementById( 'amw-purge-cancel' );
			var confirmBtn = document.getElementById( 'amw-purge-confirm' );
			var countEl    = document.getElementById( 'amw-revision-count' );
			var modalCount = document.getElementById( 'amw-purge-count' );

			var closeModal = function () {
				modal.hidden = true;
			};

			purgeBtn.addEventListener( 'click', function () {
				if ( modalCount && countEl ) {
					modalCount.textContent = countEl.textContent;
				}
				modal.hidden = false;
			} );

			cancelBtn.addEventListener( 'click', closeModal );

			modal.addEventListener( 'click', function ( event ) {
				if ( event.target === modal ) {
					closeModal();
				}
			} );

			confirmBtn.addEventListener( 'click', function () {
				confirmBtn.disabled = true;
				confirmBtn.textContent = amwToolbox.i18n.deleting;

				var body = new FormData();
				body.append( 'action', 'amw_toolbox_purge_revisions' );
				body.append( 'nonce', amwToolbox.purgeNonce );

				fetch( amwToolbox.ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					body: body
				} )
					.then( function ( response ) {
						return response.json();
					} )
					.then( function ( result ) {
						if ( result && result.success ) {
							var deleted = result.data.deleted;
							if ( countEl ) {
								countEl.textContent = '0';
							}
							purgeBtn.textContent = amwToolbox.i18n.deletedTpl.replace( '%d', deleted );
							purgeBtn.disabled = true;
						} else {
							window.alert( amwToolbox.i18n.error );
						}
					} )
					.catch( function () {
						window.alert( amwToolbox.i18n.error );
					} )
					.finally( function () {
						closeModal();
						confirmBtn.disabled = false;
						confirmBtn.textContent = amwToolbox.i18n.confirmLabel;
					} );
			} );
		}
	} );
} )();