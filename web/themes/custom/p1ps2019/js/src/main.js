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

    //map submit button
    if($('.center-form #edit-submit').length > 0){
      $('.center-form #edit-submit').addClass('reset-btn search-btn white').removeClass('button button--primary js-form-submit form-submit');
    }

    //swiper-buttons
    if($('.swiper-button-next').length > 0){
      $('.swiper-button-next').addClass('fa fa-chevron-right');
    }
    if($('.swiper-button-prev').length > 0){
      $('.swiper-button-prev').addClass('fa fa-chevron-left');
    }

    //accessibility toggler
    if($('#accessibility-toggle').length > 0){
      $('#accessibility-toggle').click(function(){
        if($('#accessibility-container.overlay-show').length > 0){
          overlayClose();
        }
        else{
          overlayClose();
          $('#accessibility-container, #accessibility-toggle').addClass('overlay-show');
        }
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

    //search toggle
    if($('#search-toggle').length > 0){
      $('#search-toggle').click(function(){
        if($('.block-views-exposed-filter-blockrecherche-de-contenu-page-1.overlay-show').length > 0){
          overlayClose();
        }
        else{
          overlayClose();
          $('.block-views-exposed-filter-blockrecherche-de-contenu-page-1, #search-toggle').addClass('overlay-show');
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
        if($(this).next('ul.overlay-show').length > 0){
          overlayClose();
        }
        else{
          overlayClose();
          $(this).next('ul').addClass('overlay-show');
          $('.language-switcher-language-url .block-title').addClass('overlay-show');
        }
			});
		}

    //menulevel2
    // if($('.secondlevel').length > 0){$('.secondlevel').parent('li').parent('ul').parent('li').addClass('has-dropdown');}
    // if($('li.has-dropdown .fa').length > 0){
    //   $('li.has-dropdown .fa').click(function() {
    //     if($(this).closest('li.expanded').length > 0){
    //       $('.expanded').removeClass('expanded');
    //     }
    //     else{
    //       $('.expanded').removeClass('expanded');
    //       $(this).closest('li.has-dropdown').addClass('expanded');
    //     }
    //   });
    // }

    // if($('.has-dropdown').length > 0 && $(window).width() < 1024){
    //   $('.has-dropdown > span').click(function(){
    //     $(this).closest(".has-dropdown").toggleClass('expanded');
    //     var count = $(this).parent().siblings('ul').find('li').length;
    //     console.log(count);
    //     var li_height = $(this).parent().siblings('ul').find('li').outerHeight();
    //     console.log(li_height);
    //     var max_height = count*li_height;
    //     console.log(max_height);
    //     if($(this).closest(".has-dropdown.expanded").length > 0){
    //       $(this).parent().siblings('ul').css('max-height', max_height + 'px');
    //     }
    //     else{
    //       $(this).parent().siblings('ul').css('max-height', 0);
    //     }
    //   });
    // }

    if($('#navbar-header').length > 0 && $('.icon-menu').length > 0){
      $('.icon-menu').click(function() {
        var scrollTop = $(window).scrollTop();
        if($('#navbar-header.overlay-show').length > 0){
          overlayClose();
          $('body').removeClass('noscroll');
          $('.expanded').removeClass('expanded');
          if(scrollTop <= 0){
            $('body').removeClass('scroll');
          }
        }
        else{
          overlayClose();
          $('#navbar-header, .icon-menu').addClass('overlay-show');
          $('body').addClass('noscroll');
          if(scrollTop <= 0){
            $('body').addClass('scroll');
          }
        }
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
      if($('body.noscroll').length > 0){
        $('body').removeClass('noscroll');
      }
    }
  }
})(jQuery);
