(function ($, Drupal, once) {

  Drupal.behaviors.slickSliders = {
    attach: function(context, settings) {

      // Slider Parcourir la collection sur les notices video
      if($('.browse_collection_blk .videoscollec-item-list .videoscollec-list .video-item-row').length > 1) {

        if($('.browse_collection_blk .videoscollec-slider-arrows').length <= 0) {
          //$('.browse_collection_blk .blk-content').before('<div class="videoscollec-slider-arrows"></div>');
          $('.browse_collection_blk .blk-content').prepend('<div class="videoscollec-slider-arrows"></div>');
        }

        if($('.browse_collection_blk .videoscollec-item-list .videoscollec-slider-controls').length <= 0) {
          $('.browse_collection_blk .videoscollec-item-list').append('<div class="videoscollec-slider-controls"></div>');
        }

        $('.browse_collection_blk .videoscollec-item-list .videoscollec-list').slick({
          slidesToShow: 1,
          //slidesToScroll: 1,
          centerMode: false,
          variableWidth: true,
          accessibility: true,
          autoplay: false,
          autoplaySpeed: 5000,
          infinite: false,
          equalizeHeight: true,
          centerPadding: '0',
          arrows: true,
          appendArrows: $('.videoscollec-slider-arrows'),
          prevArrow: '<button class="custo-prev-slide" aria-label="Précédent" type="button"><span class="bi bi-chevron-left"></span></button>',
          nextArrow: '<button class="custo-next-slide" aria-label="Suivant" type="button"><span class="bi bi-chevron-right"></span></button>',
          dots: false,
          //appendDots: $('.videoscollec-slider-controls'),
          //customPaging: function(slider, i) {
            // This example would render "tabs" with titles
            //return '<button class="dot-btn"></button>';
          //},
        });
      }

      var numberedLists = [];
      // Slider bloc liste num
      if($('.paragraph--type--sorbonne-tv-prg-numbered-list .prg_stv_numbered_list').length > 0) {
        $('.paragraph--type--sorbonne-tv-prg-numbered-list .prg_stv_numbered_list').each(function(index) {
          // https://github.com/kenwheeler/slick/issues/2123
          let ssContainer = $(this);

          if($(this).find('> .field--item').length > 1) {
            var responsiveOptions = [
              {
                breakpoint: 992,
                settings: 'unslick'
              }
            ];

            const slickOptions = {
              mobileFirst: 1,
              slidesToShow: 1,
              //slidesToScroll: 1,
              centerMode: false,
              variableWidth: true,
              accessibility: true,
              autoplay: false,
              autoplaySpeed: 5000,
              infinite: false,
              equalizeHeight: true,
              centerPadding: '0',
              arrows: false,
              //appendArrows: $('#filters-bytypes-'+ index +'-slider-arrows'),
              //prevArrow: '<button class="custo-prev-slide" aria-label="Précédent" type="button"><span class="bi bi-chevron-left"></span></button>',
              //nextArrow: '<button class="custo-next-slide" aria-label="Suivant" type="button"><span class="bi bi-chevron-right"></span></button>',
              dots: true,
              responsive: responsiveOptions,
              /*
              responsive: [
                {
                  breakpoint: 768,
                  settings: {
                    arrows: true,
                  }
                }
              ]*/
            };

            // check in a condition, similar to a debounce
            //$(this).slick(slickOptions);
            const ss = $(ssContainer).slick(slickOptions);

            numberedLists[index] = {
              'ssContainer' : ssContainer,
              'ss' : ss,
              'slickOptions' : slickOptions,
            };
          }
        });
      }

      // https://github.com/kenwheeler/slick/issues/2123 => Corrige soucis rezise et breakpoints
      $(window).on('resize', function() {
        $.each(numberedLists, function(key, numberedListElmt) {
          // on resize, if the width is less than 768, and has no init class, re initialize.
          if( $(window).width() < 992 &&  !numberedListElmt.ss.hasClass('slick-initialized')) {
            $(numberedListElmt.ssContainer).slick(numberedListElmt.slickOptions);
          }
        });
      });

      // Slider bloc recommande pour vous des notices collection
      if($('.collection_recommanded_videos_blk .videoscollec-item-list .videoscollec-list .video-item-row').length > 1) {

        if($('.collection_recommanded_videos_blk .videoscollec-slider-arrows').length <= 0) {
          //$('.collection_recommanded_videos_blk .blk-content').before('<div class="videoscollec-slider-arrows"></div>');
          $('.collection_recommanded_videos_blk .videoscollec-item-list').prepend('<div class="videoscollec-slider-arrows"></div>');
        }

        if($('.collection_recommanded_videos_blk .videoscollec-item-list .videoscollec-slider-controls').length <= 0) {
          $('.collection_recommanded_videos_blk .videoscollec-item-list').append('<div class="videoscollec-slider-controls"></div>');
        }

        $('.collection_recommanded_videos_blk .videoscollec-item-list .videoscollec-list').slick({
          slidesToShow: 1,
          //slidesToScroll: 1,
          centerMode: false,
          variableWidth: true,
          accessibility: true,
          autoplay: false,
          autoplaySpeed: 5000,
          infinite: false,
          equalizeHeight: true,
          centerPadding: '0',
          arrows: true,
          appendArrows: $('.videoscollec-slider-arrows'),
          prevArrow: '<button class="custo-prev-slide" aria-label="Précédent" type="button"><span class="bi bi-chevron-left"></span></button>',
          nextArrow: '<button class="custo-next-slide" aria-label="Suivant" type="button"><span class="bi bi-chevron-right"></span></button>',
          dots: false,
          //appendDots: $('.videoscollec-slider-controls'),
          //customPaging: function(slider, i) {
            // This example would render "tabs" with titles
            //return '<button class="dot-btn"></button>';
          //},
        });
      }

      var prgPlaylists = [];
      // Slider prg playlist
      if ($('.prg-stv-playlist-wrapper').length > 0) {
        $('.prg-stv-playlist-wrapper').each(function(index) {

          if($(this).find('> .field--item').length > 0) {
            
            // Si prg 2 lignes on laisse les fleches au dessus, sinon, sur les côtés
            /*
            if($(this).find('> .item-wrapper-2lines').length > 0) {
              if( $(this).parents('.paragraph--type--sorbonne-tv-playlist').find('#playlist-'+ index +'-slider-arrows').length <= 0 ) {
                $(this).parents('.paragraph--type--sorbonne-tv-playlist').find('.prg_stv_title').after('<div id="playlist-'+ index +'-slider-arrows" class="playlist-slider-arrows"></div>');
              }
            }
            else {
            */
              if( $(this).find('#playlist-'+ index +'-slider-arrows').length <= 0 ) {
                $(this).wrap('<div class="playlist-and-arrows container"></div>');
                $(this).before('<div id="playlist-'+ index +'-slider-arrows" class="playlist-slider-arrows"></div>');
              }
            /*} */

            let ssPlaylistContainer = $(this);

            var slickOptions = {
              mobileFirst: 1,
              slidesToShow: 1,
              //slidesToScroll: 1,
              centerMode: false,
              variableWidth: true,
              accessibility: true,
              autoplay: false,
              autoplaySpeed: 5000,
              infinite: false,
              equalizeHeight: true,
              centerPadding: '0',
              arrows: false,
              appendArrows: $('#playlist-'+ index +'-slider-arrows'),
              prevArrow: '<button class="custo-prev-slide" aria-label="Précédent" type="button"><span class="bi bi-chevron-left"></span></button>',
              nextArrow: '<button class="custo-next-slide" aria-label="Suivant" type="button"><span class="bi bi-chevron-right"></span></button>',
              dots: false,
              responsive: [
                {
                  breakpoint: 768,
                  settings: {
                    arrows: true,
                  }
                }
              ]
            };

            // Alignement d'item pour les playlists "2 lignes"
            if($(this).find('> .item-wrapper-2lines').length > 0) {
              // Bind init event listener function
              $(this).on('init', function (event, slick) {
                var tallest = 0;

                $(this).find('.slick-track > .item-wrapper-2lines').each(function(index_2l) {

                  //if($(this).find('> .field--item:first') > 0) {
                    var item_2l_heaight = $(this).find('> .field--item:first').innerHeight();

                    if(item_2l_heaight > tallest) {
                      tallest = item_2l_heaight;
                    }
                  //}
                });

                $(this).find('.slick-track > .item-wrapper-2lines').each(function(index_2l) {
                  $(this).find('> .field--item:first').css({
                    'height' : tallest + 'px',
                  });
                });
              });
            }

            //$(this).slick(slickOptions);
            const ssPlaylist = $(ssPlaylistContainer).slick(slickOptions);

            prgPlaylists[index] = {
              'ssContainer' : ssPlaylistContainer,
              'ss' : ssPlaylist,
              'slickOptions' : slickOptions,
            };

            $(window).on('resize', function() {
              $.each(prgPlaylists, function(key, filterTypeElmt) {
              // on resize, if the width is up than 992, and has no init class, re initialize.
              if( $(window).width() > 992 && !filterTypeElmt.ss.hasClass('slick-initialized')) {
                $(filterTypeElmt.ssContainer).slick(filterTypeElmt.slickOptions);
              }
              });
            });

          }

        });
      }

      // Slider prg Filtres "Liens"
      var filterLinks = [];
      if ($('.prg_stv_cta_filters_wrapper.prg_stv_cta_filter_links_wrapper').length > 0) {
        $('.prg_stv_cta_filters_wrapper.prg_stv_cta_filter_links_wrapper').each(function(index) {
          let ssFilterLinksContainer = $(this);

          if($(this).find('> .field--item').length > 0) {
            if( $(this).parents('.paragraph--type--sorbonne-tv-filters').find('#filters-links-'+ index +'-slider-arrows').length <= 0 ) {
              $(this).wrap('<div class="filters-and-arrows container"></div>');
              $(this).before('<div id="filters-links-'+ index +'-slider-arrows" class="filters-links-slider-arrows"></div>');
            }

            var responsiveOptions = [];

            var slickOptions = {
              mobileFirst: false,
              slidesToShow: 1,
              //slidesToScroll: 1,
              centerMode: false,
              variableWidth: true,
              accessibility: true,
              autoplay: false,
              autoplaySpeed: 5000,
              infinite: false,
              equalizeHeight: true,
              centerPadding: '0',
              arrows: true,
              appendArrows: $('#filters-links-'+ index +'-slider-arrows'),
              prevArrow: '<button class="custo-prev-slide" aria-label="Précédent" type="button"><span class="bi bi-chevron-left"></span></button>',
              nextArrow: '<button class="custo-next-slide" aria-label="Suivant" type="button"><span class="bi bi-chevron-right"></span></button>',
              dots: false,
              responsive: [
                {
                  breakpoint: 992,
                  settings: 'unslick',
                }
              ]
            };

            const ssFilterLinks = $(ssFilterLinksContainer).slick(slickOptions);

            filterLinks[index] = {
              'ssContainer' : ssFilterLinksContainer,
              'ss' : ssFilterLinks,
              'slickOptions' : slickOptions,
            };

            $(window).on('resize', function() {
              $.each(filterLinks, function(key, filterLinksElmt) {
              // on resize, if the width is up than 992, and has no init class, re initialize.
              if( $(window).width() > 992 && !filterLinksElmt.ss.hasClass('slick-initialized')) {
                $(filterLinksElmt.ssContainer).slick(filterLinksElmt.slickOptions);
              }
              });
            });

          }
        });
      }
      
      var filterTypes = [];
      // Slider prg Filtres par type
      if ($('.prg_stv_cta_filters_wrapper.prg_stv_cta_filter_types').length > 0) {
        $('.prg_stv_cta_filters_wrapper.prg_stv_cta_filter_types').each(function(index) {
          let ssFilterTypesContainer = $(this);

          if($(this).find('> .field--item').length > 0) {

            if( $(this).parents('.paragraph--type--sorbonne-tv-filters').find('#filters-bytypes-'+ index +'-slider-arrows').length <= 0 ) {
              //$(this).parents('.paragraph--type--sorbonne-tv-filters').find('.prg_stv_title').after('<div id="filters-bytypes-'+ index +'-slider-arrows" class="filters-bytypes-slider-arrows"></div>');

              $(this).wrap('<div class="filters-and-arrows container"></div>');
              $(this).before('<div id="filters-bytypes-'+ index +'-slider-arrows" class="filters-bytypes-slider-arrows"></div>');
            }

            var responsiveOptions = [];

            var slickOptions = {
              mobileFirst: false,
              slidesToShow: 1,
              //slidesToScroll: 1,
              centerMode: false,
              variableWidth: true,
              accessibility: true,
              autoplay: false,
              autoplaySpeed: 5000,
              infinite: false,
              equalizeHeight: true,
              centerPadding: '0',
              arrows: true,
              appendArrows: $('#filters-bytypes-'+ index +'-slider-arrows'),
              prevArrow: '<button class="custo-prev-slide" aria-label="Précédent" type="button"><span class="bi bi-chevron-left"></span></button>',
              nextArrow: '<button class="custo-next-slide" aria-label="Suivant" type="button"><span class="bi bi-chevron-right"></span></button>',
              dots: false,
              responsive: [
                {
                  breakpoint: 992,
                  settings: 'unslick',
                }
              ]
            };

            //$(this).slick(slickOptions);
            const ssFilterTypes = $(ssFilterTypesContainer).slick(slickOptions);

            filterTypes[index] = {
              'ssContainer' : ssFilterTypesContainer,
              'ss' : ssFilterTypes,
              'slickOptions' : slickOptions,
            };

            $(window).on('resize', function() {
              $.each(filterTypes, function(key, filterTypeElmt) {
              // on resize, if the width is up than 992, and has no init class, re initialize.
              if( $(window).width() > 992 && !filterTypeElmt.ss.hasClass('slick-initialized')) {
                $(filterTypeElmt.ssContainer).slick(filterTypeElmt.slickOptions);
              }
              });
            });

          }
        });
      }
      
    }
  };

})(jQuery, Drupal, once);
