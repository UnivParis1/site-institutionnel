(function ($, Drupal, once) {

  Drupal.behaviors.SorbonneTv = {
    attach: function(context, settings) {

      if($('.page_sorbonne_tv_video.full .btn-display-socials').length > 0) {
        once('open-socials', $('.page_sorbonne_tv_video.full .btn-display-socials'), context).forEach(
          function (item) {
            $(item).on('click', function(e) {
              e.preventDefault();
              $(this).addClass('active');
              $(this).parent().find('.social-buttons').addClass('open');
            });
          }
        );
      }

      if($('.page_sorbonne_tv_video.full .close-socials').length > 0) {
        once('close-socials', $('.page_sorbonne_tv_video.full .close-socials'), context).forEach(
          function (item) {
            $(item).on('click', function(e) {
              e.preventDefault();
              $(this).parent('.share-item').find('.btn-display-socials').removeClass('active');
              $(this).parent('.social-buttons').removeClass('open');
            });
          }
        );
      }

    }
  };

})(jQuery, Drupal, once);