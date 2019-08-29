/*
  Scripts jQuery et Javascript
  Par Ebizproduction
*/

(function($) {
  $(document).foundation();

  Drupal.behaviors.myBehavior = {
    attach: function (context, settings) {
    	//ADD SCROLL CLASS
    	if ($(window).width() > 1024){
			$(document).scroll(function() {
				var top=$(this).scrollTop();
				if(top>1) {
					setTimeout(function(){ $("body").addClass("scroll"); }, 100);
				} else {
					setTimeout(function(){ $("body").removeClass("scroll"); }, 100);
				}
			});
		}//close scroll

    $('body').click(function(event){
			if ($('.overlay-show').length > 0){
				var langClass = $(event.target).attr('class');
				if(typeof langClass != 'undefined'){
					if(langClass.toLowerCase().indexOf("overlay") <= 0){
						$(".overlay-show").removeClass('overlay-show');
            $('.overlay-show').removeClass('overlay-show');
					}
				}
				if(typeof langClass == 'undefined'){
					$(".overlay-show").removeClass('overlay-show');
          $('.overlay-show').removeClass('overlay-show');
				}
			}
		});

    //accessibility toggler
    if($('#accessibility-toggle').length > 0){
      $('#accessibility-toggle').click(function(){
        overlayClose();
        $('#accessibility-container').addClass('overlay-show');
      });
    }
    if($('#contraste-normal').length > 0){
      $('#contraste-normal').click(function(){
        if($('body.accessibility-mode').length > 0){
          $('body.accessibility-mode').removeClass('accessibility-mode');
          if($('#contraste-normal.is-active').length <= 0){
            $('#contraste-normal').addClass('is-active');
            $('#contraste-renforce').removeClass('is-active');
          }
        }
      });
    }
    if($('#contraste-renforce').length > 0){
      $('#contraste-renforce').click(function(){
        if($('body.accessibility-mode').length <= 0){
          $('body').addClass('accessibility-mode');
          if($('#contraste-renforce.is-active').length <= 0){
            $('#contraste-renforce').addClass('is-active');
            $('#contraste-normal').removeClass('is-active');
          }
        }
      });
    }

		//language-switcher set current lang
		if( $('.language-switcher-language-url').length > 0){
			$('.language-switcher-language-url .block-title')
      .addClass('circle')
			.wrapInner('<span class="visually-hidden"></span>')
			.append($('.language-switcher-language-url .language-link.is-active').attr('hreflang'))
			.click(function(){
        overlayClose();
				$(this).next('ul').toggleClass('overlay-show');
			});
		}

    //menulevel2
    if($('.secondlevel').length > 0){$('.secondlevel').parent('li').parent('ul').parent('li').addClass('has-dropdown');}
    if($('li.has-dropdown').length > 0){
      $('li.has-dropdown').click(function() {
        $('li.has-dropdown.expanded').not(this).removeClass('expanded');
        $(this).toggleClass('expanded');
      });
    }

    if($('#navbar-header').length > 0 && $('.icon-menu').length > 0){
      $('.icon-menu').click(function() {
        overlayClose();
        $('#navbar-header, .icon-menu').toggleClass('overlay-show');
        $('body').toggleClass('noscroll');
      });
    }
		//social media footer
		if( $('.block-system-menu-blockreseaux-sociaux').length > 0){
			$('.block-system-menu-blockreseaux-sociaux li').each(function(){
				$(this).find('a').wrapInner('<span class="visually-hidden"></span>');
			});
		}

      $(window).resize(function(e) {

      });//close resize
    }
  };//close myBehavior

  function overlayClose(){
    if($('.overlay-show').length > 0){
      $('.overlay-show').removeClass('overlay-show');
    }
  }
})(jQuery);
