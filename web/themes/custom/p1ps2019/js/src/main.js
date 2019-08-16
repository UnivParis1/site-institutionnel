/*
  Scripts jQuery et Javascript
  Par Ebizproduction
*/

(function($) {
  $(document).foundation();

  Drupal.behaviors.myBehavior = {
    attach: function (context, settings) {
    	if ($(window).width() > 1024){
			$(document).scroll(function() {
				var top=$(this).scrollTop();
				if(top>1) {
					setTimeout(function(){ $("body").addClass("scroll"); }, 100);
				} else {
					setTimeout(function(){ $("body").removeClass("scroll"); }, 100);
				}
			});
		}

      $(window).resize(function(e) {

      });//close resize
    }
  }//close myBehavior
})(jQuery);
