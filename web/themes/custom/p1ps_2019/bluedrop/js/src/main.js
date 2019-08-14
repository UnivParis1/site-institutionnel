/*
  Scripts jQuery et Javascript
  Par Ebizproduction
*/

(function($) {
  $(document).foundation();

  Drupal.behaviors.myBehavior = {
    attach: function (context, settings) {
      $(window).resize(function(e) {

      });//close resize
    }
  }//close myBehavior
})(jQuery);
