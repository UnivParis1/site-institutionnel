(function ($, Drupal, once) {
	Drupal.behaviors.favorites = {
		attach: function (context, settings) {
			once('sorbonne-favorite', $('.add-to-favorites'), context).forEach(
				function (item) {
          $(item).click(function(e) {
            e.preventDefault();
            var link = $(this);
            $.get(link.attr('href'), function (data) {
              if (data.action == 'add') {
                link.find('span').removeClass('bi-heart');
                link.find('span').addClass('bi-heart-fill');
              }
              else if (data.action == 'remove') {
                link.find('span').removeClass('bi-heart-fill');
                link.find('span').addClass('bi-heart');
              }
              //alert(data.message);
            });
          });
				}
			);
		}
	}
})(jQuery, Drupal, once);
