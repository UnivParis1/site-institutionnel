/*
  Scripts jQuery et Javascript
  Par Ebizproduction
*/

(function($) {
  $(document).foundation();
    //accessibility toggler
    if($('#accessibility-toggle').length > 0){
      $('#accessibility-toggle').click(function(){
        if($('.accessibility-wrapper.overlay-show').length > 0){
          overlayClose();
            console.log('removed');
        }
        else{
          overlayClose();
          $('.accessibility-wrapper').addClass('overlay-show');
            console.log('clicked');
        }
      });
    }

    if($('.path-recherche #search-toggle').length > 0){
      $('#search-toggle').click(function(){
        $('#edit-homepage-search').focus();
      });
    }

     //search toggle
    if($('#search-toggle').length > 0){
      $('#search-toggle').click(function(){
        var scrollTop = $(window).scrollTop();
        if($('.block-views-exposed-filter-blockrecherche-de-contenu-page-1.overlay-show').length > 0){
          overlayClose();
          if(scrollTop <= 0 || $(window).width() < 1024){
            $('body').removeClass('scroll');
          }
        }
        else{
          overlayClose();
          $('.block-views-exposed-filter-blockrecherche-de-contenu-page-1, #search-toggle').addClass('overlay-show');
          if(scrollTop <= 0 || $(window).width() < 1024){
            $('body').addClass('scroll');
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

     if($('#contraste-normal').length > 0){
      $('#contraste-normal').click(function(){
        $('.accessibility-wrapper').removeClass('overlay-show');
        if($('html.accessibility-mode').length > 0){
          $('html.accessibility-mode').removeClass('accessibility-mode');
          if($('#contraste-normal.is-active').length <= 0){
            $('#contraste-normal').addClass('is-active');
            $('#contraste-renforce').removeClass('is-active');
          }
        }
      });
    }
    if($('#contraste-renforce').length > 0){
      $('#contraste-renforce').click(function(){
        $('.accessibility-wrapper').removeClass('overlay-show');
        if($('html.accessibility-mode').length <= 0){
          $('html').addClass('accessibility-mode');
          if($('#contraste-renforce.is-active').length <= 0){
            $('#contraste-renforce').addClass('is-active');
            $('#contraste-normal').removeClass('is-active');
          }
        }
      });
    }

     if($('#navbar-header').length > 0 && $('.icon-menu').length > 0){
      $('.icon-menu').click(function() {
        var scrollTop = $(window).scrollTop();
        if($('#navbar-header.overlay-show').length > 0){
          overlayClose();
          $('body').removeClass('noscroll');
          $('.expanded').removeClass('expanded');
          if(scrollTop <= 0 || $(window.width() < 1024)){
            $('body').removeClass('scroll');
          }
        }
        else{
          overlayClose();
          $('#navbar-header, .icon-menu').addClass('overlay-show');
          if($(window).width() < 1024){
            $('.block-views-exposed-filter-blockrecherche-de-contenu-page-1').addClass('overlay-show');
          }
          $('body').addClass('noscroll');
          if(scrollTop <= 0 || $(window).width() < 1024){
            $('body').addClass('scroll');
          }
        }
      });
    }

  Drupal.behaviors.myBehavior = {
    attach: function (context, settings) {
    	//ADD SCROLL CLASS
    	if ($(window).width() > 1024){
			$(document).scroll(function() {
				var top=$(this).scrollTop();
				if(top>1) {
					setTimeout(function(){ $("body").addClass("scroll"); }, 100);
				} if(top < 1 && $('.block-views-exposed-filter-blockrecherche-de-contenu-page-1.overlay-show').length <= 0) {
					setTimeout(function(){ $("body").removeClass("scroll"); }, 100);
				}
			});
		}//close scroll

    BrowserDetection();

    if($('.play-pause-button').length > 0){
      console.log('found');
      $( ".play-pause-button" ).click(function() {
        console.log('clicked');
        $('.play-pause-button').toggleClass('paused');
      });
    }

    // Chosen touch support.
    if ($('.chosen-container').length > 0) {
      $('.chosen-container').on('touchstart', function(e){
        e.stopPropagation(); e.preventDefault();
        // Trigger the mousedown event.
        $(this).trigger('mousedown');
      });
    }


    //map submit button
    if($('.center-form #edit-submit').length > 0){
      $('.center-form #edit-submit').addClass('reset-btn circle white').removeClass('button button--primary js-form-submit form-submit');
    }

    //swiper-buttons
    if($('.swiper-button-next').length > 0){
      $('.swiper-button-next').addClass('fa fa-chevron-right');
    }
    if($('.swiper-button-prev').length > 0){
      $('.swiper-button-prev').addClass('fa fa-chevron-left');
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



		//social media footer
		if( $('.block-system-menu-blockreseaux-sociaux').length > 0){
			$('.block-system-menu-blockreseaux-sociaux li').each(function(){
				$(this).find('a').wrapInner('<span class="visually-hidden"></span>');
			});
		}

      $(window).resize(function(e) {
        if($(window).width() < 1024 && $('#navbar-header.overlay-show').length > 0){
          $('.block-views-exposed-filter-blockrecherche-de-contenu-page-1').addClass('overlay-show');
        }
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

  function BrowserDetection() {
     var ua = navigator.userAgent.match(/(opera|chrome|safari|firefox|msie)\/?\s*(\.?\d+(\.\d+)*)/i),
      browser;
    if (navigator.userAgent.match(/Edge/i) || navigator.userAgent.match(/Trident.*rv[ :]*11\./i)) {
      browser = "msie";
    }
    else {
      browser = ua[1].toLowerCase();
    }

    if(browser == 'msie') $('body').addClass('browser-ie');
    else $('body').addClass('browser-'+browser);
  }

})(jQuery);
