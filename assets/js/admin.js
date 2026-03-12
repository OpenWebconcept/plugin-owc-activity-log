/* global owcalData */

document.addEventListener( 'DOMContentLoaded', function () {
	document.querySelectorAll( '.owcal-toggle' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			const meta = this.nextElementSibling;

			meta.classList.toggle( 'is-visible' );

			this.textContent = meta.classList.contains( 'is-visible' )
				? owcalData.hideDetails
				: owcalData.showDetails;
		} );
	} );
} );
