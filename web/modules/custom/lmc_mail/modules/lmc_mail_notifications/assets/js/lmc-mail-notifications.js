(function ($, Drupal, once) {
  Drupal.behaviors.lmcMailNotifications = {
    attach: function (context, settings) {

      $(once('lmc-mail-notif', '.lmc-mail-notifications-blk .notifs-toggle', context)).on('click', function () {
        $(this).parents('.blk-content').find('.notis-container').toggleClass('active');
        console.log('Lalala');
      });
    }
  };

})(jQuery, Drupal, once);
