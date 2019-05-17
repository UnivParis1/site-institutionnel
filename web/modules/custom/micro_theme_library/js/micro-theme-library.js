(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.micro_theme_library_selector= {
    attach: function() {
      $('img').on('error', function() {
        $(this).hide();
      });
    }
  }
})(jQuery, Drupal, drupalSettings);