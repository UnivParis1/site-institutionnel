(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.pages_perso_unsupported_browsers = {
    attach: function (context, settings) {
      window.onload = function(){
        if (window.NodeList && !NodeList.prototype.forEach) {
          document.location.href = "https://pantheonsorbonne.fr/pages-persos-unsupported-browsers";
          console.log('unsupported browser');
          var x = document.getElementById('node-page-personnelle-edit-form');
          x.setAttribute('style', 'display:block');
          alert('Votre navigateur ne prend pas en charge certaines fonctionnalités du site. Veuillez le mettre à jour ou changer de navigateur. ');
        }
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
