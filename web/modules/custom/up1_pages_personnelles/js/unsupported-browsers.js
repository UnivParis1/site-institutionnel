(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.pages_perso_unsupported_browsers = {
    attach: function (context, settings) {
      if (window.NodeList && !NodeList.prototype.forEach) {
        document.location.href = "https://pantheonsorbonne.fr/pages-persos-unsupported-browsers";
        console.log('unsupported browser');
        alert('Votre navigateur ne prend pas en charge certaines fonctionnalités du site. Veuillez le mettre à jour ou changer de navigateur. ');
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
