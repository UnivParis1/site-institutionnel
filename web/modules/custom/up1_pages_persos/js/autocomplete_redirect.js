(($, once) => {
  Drupal.behaviors.pagesPersosAutocompleteRedirect = {
    attach: function (context) {
      once('autocompleteRedirect', '.pages-persos-autocomplete', context)
        .forEach((element) => {
          $(element).on('autocompleteselect', function () {
            let $form = $(this).closest('form'); // 'this' refers to the autocomplete input element
            setTimeout(() => {
              $form.submit(); // Submits the form after a slight delay
            }, 500); // 500ms delay
          });
        });
    }
  };
})(jQuery, window.once);
