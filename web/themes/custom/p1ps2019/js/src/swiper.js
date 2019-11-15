(function($) {

  Drupal.behaviors.bluedropSwiper = {
    attach: function (context, settings) {


      noSwiping();

        if($('.swiper-container:not(#swiper-tiles):not(#swiper-carousel)').length > 0){
  				var mySwiper = new Swiper('.swiper-container:not(#swiper-tiles):not(#swiper-carousel)', {
  					slidesPerView: 3,
            spaceBetween: 24,
            navigation: {
              nextEl: '.swiper-button-next',
              prevEl: '.swiper-button-prev',
            },
            breakpoints: {
              1024: {
                slidesPerView: 1
              }
            }
  			  });
  			}
        if($('#swiper-tiles').length > 0){
  				var tilesSwiper = new Swiper('#swiper-tiles', {
  					slidesPerView: 1,
            pagination: {
  				    el: '.swiper-pagination',
  				    type: 'bullets',
  						clickable: true,
  				  },
  			  });
  			}

        if($('#swiper-carousel').length > 0){
  				var carouselSwiper = new Swiper('#swiper-carousel', {
  					slidesPerView: 1,
            loop: true,
            autoplay: true,
            pagination: {
  				    el: '.swiper-pagination',
  				    type: 'bullets',
  						clickable: true,
  				  },
            navigation: {
              nextEl: '.swiper-button-next',
              prevEl: '.swiper-button-prev',
            },
  			  });
          if($('.play-pause-button').length > 0){
            $('.play-pause-button span').click(function() {
              $('.play-pause-button').toggleClass('paused');
            });
          }
  			}


      $(window).resize(function() {
      //  setTimeout(checkOnSwipers, 2000);
        noSwiping();
        reinitSwiper(mySwiper);
        reinitSwiper(tilesSwiper);
        reinitSwiper(carouselSwiper);
      });//close resize



      if($('.tabs, .accordion').length > 0){
        $(".tabs li a, .accordion li a").on("click",function(){
          if($('.tabs-content .swiper-container, .accordion .swiper-container').length > 0){
            reinitSwiper(mySwiper);
            reinitSwiper(tilesSwiper);
          }
        });
      }
    }
  }

  //setTimeout(checkOnSwipers, 2000);

function noSwiping(){
  if($(window).width() > 1024){
    if($('#swiper-tiles.swiper-no-swiping').length <= 0){
      $('#swiper-tiles').addClass('swiper-no-swiping');
    }
  }
  else{
    if($('#swiper-tiles.swiper-no-swiping').length > 0){
      $('#swiper-tiles').removeClass('swiper-no-swiping');
    }
  }
}
//=============================================== FUNCTIONS =================================================
function reinitSwiper(swiper) {
    if (swiper !== undefined) {
      setTimeout(function () {
        swiper.update();
      });
    }
}
// function checkOnSwipers(){
//   if($('.swiper-container:not(.one-slide) .swiper-wrapper').length > 0){
//     $('.swiper-wrapper').each(function(){
//       var swiperContainer = $(this).parent();
//       var swiperContainerWidth = swiperContainer.outerWidth();
//       //var swiperWrapperWidth = $(this).outerWidth();
//
//       //console.log('============== swiperContainer: '+swiperContainer.attr('class'));
//
//       var swiperSlidesWidth = 0;
//       //console.log('==========> '+swiperContainer.attr('class'));
//       var slidesCount = $(this).children('.swiper-slide').length;
//       var sliderMargin = $(this).children('.swiper-slide:first-child').css('margin-right');
//       //console.log('sliderMargin: '+sliderMargin);
//       if(sliderMargin !== undefined) {
//       var slideMargin = parseInt(sliderMargin.replace('px', ''));
//       var slidesMargin = slideMargin * (slidesCount -1);
//
//       //console.log('slides COunt : '+slidesCount+' slideMargin: '+slideMargin+' Margins: '+slidesMargin);
//       swiperSlidesWidth += slidesMargin;}
//
//       $(this).children('.swiper-slide').each(function(){
//         //swiperSlidesWidth += $(this).outerWidth();
//         swiperSlidesWidth += $(this).width();
//         //swiperSlidesWidth += parseInt($(this).css('margin-right').replace('px', ''));
//         //console.log('SLIDE WIDTH: '+ $(this).outerWidth());
//       });
//
//
//       /*//if(swiperContainer.attr('class').indexOf('swiper-tarifs') != -1){
//         console.log('--------------->'+swiperContainer.attr('class'));
//         console.log('TOT SLIDES WIDTH: '+swiperSlidesWidth);
//         console.log('swiperContainerWidth: '+swiperContainerWidth);
//         console.log('----------');
//       //}*/
//
//       if(swiperSlidesWidth < swiperContainerWidth || $(window).width() > 1200){
//         if(!swiperContainer.hasClass('swiper-no-swiping')){
//           swiperContainer.addClass('swiper-no-swiping ');
//           if($('.tabs-style .swiper-wrapper.swiper-align').length > 0) $('.tabs-style .swiper-wrapper.swiper-align').removeClass('swiper-align');
//         }
//       }
//       else{
//         if(swiperContainer.hasClass('swiper-no-swiping')){
//           swiperContainer.removeClass('swiper-no-swiping');
//           $('.tabs-style .swiper-wrapper').addClass('swiper-align');
//         }
//       }
//       //console.log('ENDDD------> '+swiperContainer.attr('class'));
//     });
//   }
// }
})(jQuery);
