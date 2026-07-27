/**
 * Upgrades the Turnstile widget from the default "compact" size (150x140,
 * icon stacked above text - the right shape when it wraps onto its own
 * line on a narrow viewport) to "normal" (300x65, short and wide) once
 * there's enough width for it to sit inline with the form's submit button
 * without looking like an oddly tall block. Must run - and this script tag
 * must be placed - before the Turnstile API script tag, since Turnstile
 * only reads data-size at the moment it scans and renders each widget.
 */
document.querySelectorAll( '.cf-turnstile' ).forEach( function( p_element ) {
	if( window.matchMedia && window.matchMedia( '(min-width:768px)' ).matches ) {
		p_element.setAttribute( 'data-size', 'normal' );
	}
} );
