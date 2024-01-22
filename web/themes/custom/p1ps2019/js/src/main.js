/*
  Scripts jQuery et Javascript
  Par Ebizproduction
*/

(function($) {
  //accessibility toggler
  if($('#accessibility-toggle').length > 0){
    $('#accessibility-toggle').click(function(){
      if($('.accessibility-wrapper.overlay-show').length > 0){
        overlayClose();
      }
      else{
        overlayClose();
        $('.accessibility-wrapper').addClass('overlay-show');
      }
    });
  }

  //search toggle
  if($('#search-toggle').length > 0){
    $('#search-toggle').click(function(){
      var scrollTop = $(window).scrollTop();
      if ($('.block-views-exposed-filter-blockresultats-de-recherche-page-1.overlay-show').length > 0) {
        overlayClose();
        if (scrollTop <= 0 || $(window).width() < 1024) {
          $('body').removeClass('scroll');
        }
      } else {
        overlayClose();
        $('.block-views-exposed-filter-blockresultats-de-recherche-page-1, #search-toggle').addClass('overlay-show');
        if (scrollTop <= 0 || $(window).width() < 1024) {
          $('body').addClass('scroll');
        }
        setTimeout(function () {
          $('#views-exposed-form-resultats-de-recherche-page-1 #edit-text').focus()
        }, 30);
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
  //direct access toggle
  if( $('.direct-access-btn').length > 0){
    $('.direct-access-btn').click(function(){
      if($('.direct-access-wrapper.overlay-show').length > 0){
        overlayClose();
      }
      else{
        overlayClose();
        $('.direct-access-wrapper').addClass('overlay-show');
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
          $('.block-views-exposed-filter-blockresultats-de-recherche-page-1').addClass('overlay-show');
          setTimeout(function () {
            $('#views-exposed-form-resultats-de-recherche-page-1 #edit-text').focus()
          }, 30);
        }
        $('body').addClass('noscroll');
        if(scrollTop <= 0 || $(window).width() < 1024){
          $('body').addClass('scroll');
        }
      }
    });
  }

  // var replaceUrlIframe = false;


  Drupal.behaviors.myBehavior = {
    attach: function (context, settings) {

      $(document, context).once('foundation-init').each(function() {
        $(document).foundation();
      });
      //ADD SCROLL CLASS
      if ($(window).width() > 1024){
        $(document).scroll(function() {
          var top=$(this).scrollTop();
          if(top>90) {
            setTimeout(function(){ $("body").addClass("scroll"); }, 100);
          }
          if(top < 90 && $('.block-views-exposed-filter-blockresultats-de-recherche-page-1.overlay-show').length <= 0) {
            setTimeout(function(){ $("body").removeClass("scroll"); }, 100);
          }
        });
      }//close scroll

      if ($('.nod').length > 0 || $('.vid').length > 0) {
       $('#block-p1ps2019-motsclessaisonniers').hide();
      }

      BrowserDetection();

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

      //social media footer
      if( $('.block-system-menu-blockreseaux-sociaux').length > 0){
        $('.block-system-menu-blockreseaux-sociaux li').each(function(){
          $(this).find('a').wrapInner('<span class="visually-hidden"></span>');
        });
      }

      $(window).resize(function(e) {
        if($(window).width() < 1024 && $('#navbar-header.overlay-show').length > 0){
          $('.block-views-exposed-filter-blockresultats-de-recherche-page-1').addClass('overlay-show');
          //Ajouté après...
          setTimeout(function () {
            $('#views-exposed-form-resultats-de-recherche-page-1 #edit-text').focus()
          }, 30);
        }
      });//close resize
      if (($('.bp-columns-three-uneven').length != 0) &&
        ($('.bp-columns-three-uneven .paragraph--type--files-media').length != 0)) {
        $('.bp-columns-three-uneven .paragraph--type--files-media')
          .removeClass('medium-6')
          .removeClass('large-4')
          .addClass('medium-12');
      }
	    if (($('.path-frontpage').length != 0 || $('.path-site').length != 0) && 
		    ($('.nod').length != 0 || $('.vid').length != 0)) {
	    	$('#block-p1ps2019-motsclessaisonniers').hide();
	    }
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
