(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.pages_perso_unsupported_browsers = {
    attach: function (context, settings) {
      window.onload = function(){
        if (window.NodeList && !NodeList.prototype.forEach) {
          console.log('unsupported browser');
          var x = document.getElementById('node-page-personnelle-edit-form');
          x.remove();
          var newDiv = document.createElement("div");
          // et lui donne un peu de contenu
          var newContent = document.createTextNode('Votre navigateur ne prend pas en charge certaines fonctionnalités du site. ' +
            'Veuillez mettre à jour de navigateur ou télécharger un navigateur plus récent. ');
          newDiv.setAttribute('class','unsupported-browser');
          // ajoute le nœud texte au nouveau div créé
          newDiv.appendChild(newContent);

          // ajoute le nouvel élément créé et son contenu dans le DOM
          var currentDiv = document.getElementById('block-adminimal-theme-content');
          currentDiv.appendChild(newDiv);
        }
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
