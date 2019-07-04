(function($) {
  $( 'ul.nav.nav-tabs  a' ).click( function ( e ) {
    e.preventDefault();
  } );

  // changement des Tabs en Accordions
  fakewaffle.responsiveTabs( [ 'xs', 'sm' ] );
} )( jQuery );

