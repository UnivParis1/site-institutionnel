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

		//language-switcher set current lang
		if( $('.language-switcher-language-url').length > 0){
			$('.language-switcher-language-url .block-title')
			.wrapInner('<span class="visually-hidden"></span>')
			.append($('.language-switcher-language-url .language-link.is-active').html())
			.click(function(){
				$(this).next('ul').toggleClass('show');
			});
		}

    //menulevel2
    if($('.secondlevel').length > 0){$('.secondlevel').parent('li').parent('ul').parent('li').addClass('has-dropdown');}
    if($('li.has-dropdown').length > 0){
      $('li.has-dropdown').click(function() {
        $('li.has-dropdown.expanded').removeClass('expanded');
        if($(this).hasClass('expanded')){
          console.log('close');
          $(this).removeClass('expanded');
        }
        else{$(this).toggleClass('expanded');}
      });
    }

    if($('#navbar-header').length > 0 && $('.toggle-container').length > 0){
      $('.toggle-container').click(function() {
        $('#navbar-header, .icon-menu').toggleClass('menu-expanded');
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
  }//close myBehavior
})(jQuery);
