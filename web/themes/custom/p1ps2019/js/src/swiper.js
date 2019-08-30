(function($) {

  Drupal.behaviors.bluedropSwiper = {
    attach: function (context, settings) {




        if($('.swiper-container').length > 0){
  				var mySwiper = new Swiper('.swiper-container', {
  					slidesPerView: 3,
            centeredSlides: true,
            spaceBetween: 20,
            navigation: {
              nextEl: '.swiper-button-next',
              prevEl: '.swiper-button-prev',
            },
  			  });
  			}

      $(window).resize(function() {
      //  setTimeout(checkOnSwipers, 2000);
        var windowW = $(window).outerWidth();
        if(windowW < 801 && tabsSwiper != null){
          tabsSwiper.params.slidesOffsetBefore = 48;
          tabsSwiper.update();
        }
      });//close resize



      if($('.tabs').length > 0){
        $(".tabs li a").on("click",function(){
          console.log('li a');
          if($('.tabs-content .tabs-panel .swiper-container').length > 0){
            console.log('swiper');
            reinitSwiper(mySwiper);
          }
        });
      }
    }
  }

  //setTimeout(checkOnSwipers, 2000);

//=============================================== FUNCTIONS =================================================
function reinitSwiper(swiper) {
    setTimeout(function () {
        swiper.update();
    }, 400);
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
