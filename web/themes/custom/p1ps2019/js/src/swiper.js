(function(W, $, Drupal) {
  let $W = $(W);

  Drupal.behaviors.bluedropSwiper = {
    attach: function (context, settings) {
      if($('.swiper-container').length > 0){
        var carouselSwiper = new Swiper('.swiper-container', {
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
            if($('.play-pause-button.paused').length > 0){
              if (!carouselSwiper.autoplay.paused) {
                carouselSwiper.autoplay.paused = true;
                return true;
              }
            }
            else{
              carouselSwiper.autoplay.paused = false;
              carouselSwiper.autoplay.run();
              return true;
            }
          });
        }
      }


      $W.resize(function() {
        //  setTimeout(checkOnSwipers, 2000);
        noSwiping();
        reinitSwiper(carouselSwiper);
      });//close resize

      if($('.tabs, .accordion').length > 0){
        $(".tabs li a, .accordion li a").on("click",function(){
          if($('.tabs-content .swiper-container, .accordion .swiper-container').length > 0){
            reinitSwiper(carouselSwiper);
          }
        });
      }
    }
  }

  //setTimeout(checkOnSwipers, 2000);

  function noSwiping(){
    if($W.width() > 1024){
      if($('.swiper-tiles.swiper-no-swiping').length <= 0){
        $('.swiper-tiles').addClass('swiper-no-swiping');
      }
    }
    else{
      if($('.swiper-tiles.swiper-no-swiping').length > 0){
        $('.swiper-tiles').removeClass('swiper-no-swiping');
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
})(window, window.$,window.Drupal);
