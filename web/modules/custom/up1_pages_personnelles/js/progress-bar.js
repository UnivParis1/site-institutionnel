(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.pages_perso_progress_bar = {
    attach: function() {
      var wrapper_top = $(".progress .wrapper").offset().top;
      $(window).scroll(function (){
        var wrapper_height = $(".progress .wrapper").height();

        // Affixes Progress Bars
        var top = $(this).scrollTop();
        if (top > wrapper_top - 234) {
          $(".progress .wrapper").addClass("affix");
        }
        else {
          $(".progress .wrapper").removeClass("affix");
        }

        // Calculate each progress section
        $(".colonnecentrale .new-section").each(function(i){
          var this_top = $(this).offset().top;
          var height = $(this).height();
          var this_bottom = this_top + height;
          var percent = 0;

          // Scrolled within current section
          if (top >= this_top && top <= this_bottom) {
            percent = ((top - this_top) / (height - wrapper_height)) * 100;
            if (percent >= 100) {
              percent = 100;
              $(".progress .wrapper .bar:eq("+i+") i").css("color", "#fff");
            }
            else {
              $(".progress .wrapper .bar:eq("+i+") i").css("color", "#36a7f3");
            }
          }
          else if (top > this_bottom) {
            percent = 100;
            $(".progress .wrapper .bar:eq("+i+") i").css("color", "#fff");
          }
          $(".progress .wrapper .bar:eq("+i+") span").css("width", percent + "%");
        });
      });
    }
  }
})(jQuery, Drupal, drupalSettings);
