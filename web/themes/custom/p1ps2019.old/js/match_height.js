/**
 * @file
 * A JavaScript file for the site with globally used scripts.
 */
(function ($) {
  Drupal.behaviors.breakpoints = {
    attach: function (context, settings) {
      // Define breakpoints on the global window object so we can use them later.
      // This way we keep our breakpoints more defined.
      window.breakpoints = {
        bp0: '350',
        bp1: '750',
        bp_menu: '970',
        bp2: '1280',
        bp3: '1400'
      }
    }
  };
})(jQuery, Drupal);

/**
 * @file
 * A JavaScript file for the site, specifically for the match height plugin.
 */
(function ($) {
  Drupal.behaviors.match_height = {
    attach: function (context, settings) {
      // Match Height Scripts
      runMatchHeight();

      $(document).ready(function() {
        runMatchHeight();
      });

      $(window).resize(function() {
        runMatchHeight();
      });

      function runMatchHeight() {
        // Match height ordering for small teasers
        $('.sm-teaser__title').matchHeight();
        $('.sm-teaser__img-link').matchHeight();
        $('.sm-teaser').matchHeight();
      }
    }
  };
  Drupal.behaviors.match_height_search = {
    attach: function (context, settings) {
      // Match height for the search results page
      $('.sm-teaser__title').matchHeight();
      $('.sm-teaser__title').matchHeight();
      if ($(window).width() > window.breakpoints.bp1) {
        $('.matchheight-desktop').matchHeight({
          byRow: false
        });
      };

      if ($(window).width() < window.breakpoints.bp1) {
        $('.matchheight-mobile').matchHeight({
          byRow: false
        });
      }
    }
  };

})(jQuery, Drupal);
(function ($, Drupal) {

  matchHeight = function () {
    $('.sm-teaser__title').matchHeight();
    $('.sm-teaser__img-link').matchHeight();
    $('.sm-teaser').matchHeight();
  }

  Drupal.AjaxCommands.prototype.viewsScrollTop = function (ajax, response) {
    // Prevents the scroll to top behavior when a "View more" pager link was
    // utilized.
    if (ajax.element.className == 'results__link') {
      // Set temporary min-height on body to prevent page from jockeying around
      // when AJAX content is inserted.
      var body = $('body');
      var initHeight = body.css('min-height');
      body.css('min-height', body.height());
      setTimeout(function () {
        body.css('min-height', initHeight);
      }, 100);

      // Rerun the match height script
      setTimeout(matchHeight, 200);

      return;
    }

    // The following is Views' default behavior.
    // Scroll to the top of the view. This will allow users
    // to browse newly loaded content after e.g. clicking a pager
    // link.
    var offset = $(response.selector).offset();
    // We can't guarantee that the scrollable object should be
    // the body, as the view could be embedded in something
    // more complex such as a modal popup. Recurse up the DOM
    // and scroll the first element that has a non-zero top.
    var scrollTarget = response.selector;
    while ($(scrollTarget).scrollTop() === 0 && $(scrollTarget).parent()) {
      scrollTarget = $(scrollTarget).parent();
    }
    // Only scroll upward.
    if (offset.top - 10 < $(scrollTarget).scrollTop()) {
      $(scrollTarget).animate({scrollTop: (offset.top - 10)}, 500);
    }
  };
})(jQuery, Drupal);
