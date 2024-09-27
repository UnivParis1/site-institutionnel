(function ($, Drupal, drupalSettings, once) {
	Drupal.behaviors.ProgramsFiltersForm = {
		attach: function (context, settings) {
			once('select-day', $('.sorbonne-tv-programs-filters-form .form-item-day #edit-day'), context).forEach(
				function (item) {
					$(item).on('change', function () {
						$(item).parents('form.sorbonne-tv-programs-filters-form').submit();
					});
				}
			);

			once('select-period', $('.sorbonne-tv-programs-filters-form #edit-period .form-checkbox'), context).forEach(
				function (item) {
					$(item).on('change', function () {
						$(item).parents('form.sorbonne-tv-programs-filters-form').submit();
					});
				}
			);

			once('select-period-radio', $('.sorbonne-tv-programs-filters-form #edit-period .form-radio'), context).forEach(
				function (item) {
					$(item).on('change', function () {
						$(item).parents('form.sorbonne-tv-programs-filters-form').submit();
					});
				}
			);
		}
	}
})(jQuery, Drupal, drupalSettings, once);
