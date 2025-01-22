;(function(W, D, $, once, Drupal) {
  'use strict';
  var $D = $(D),
    $W = $(W),
    $html = $('html');
  $D.ready(function() {
    // Par défaut, afficher le contenu français et masquer l'anglais
    $('.callFR').show();
    $('.callEN').hide();
    $('.en').show();
    $('.fr').hide();
    if ($('#call-toggle-language').length > 0) {
      // Gestion du changement d'état du toggle
      $('#call-toggle-language').change(function () {
        if ($(this).is(':checked')) {
          // Si le toggle est activé, afficher le contenu en anglais
          $('.callFR').hide();
          $('.callEN').show();
          $('.fr').show();
          $('.en').hide();
          $html.attr('lang', 'en');
        } else {
          // Sinon, afficher le contenu en français
          $('.callFR').show();
          $html.attr('lang', 'fr');
          $('.callEN').hide();
          $('.en').show();
          $('.fr').hide();
        }
      });
    }
  });
})(window, document, window.jQuery, window.once, window.Drupal);
