(function ($, Drupal, once) {
    Drupal.behaviors.mosaic = {
        attach: function (context, settings) {

            setHToMosaicemptyThumb();

            $(window).resize(function() {
                setHToMosaicemptyThumb();
            });

            function setHToMosaicemptyThumb() {
                if($('.stv-mosaic-tpl .videoscollec-list > li').length > 0) {
                    var imgHeight = 0;
    
                    //once('mosaic_empty_thumb', '.stv-mosaic-tpl .videoscollec-list > li').forEach(function (element) {
                    $('.stv-mosaic-tpl .videoscollec-list > li').each(function (e) {
                        if($(this).find('.video-thumb:not(.empty_thumb)').length > 0) {
                            imgHeight = $(this).find('.video-thumb:not(.empty_thumb)').height();
    
                            return false; // breaks
                        }
                        else {
                            return true; // go to next iteration
                        }
                    });
    
                    if(imgHeight > 0) {
                        $('.stv-mosaic-tpl .videoscollec-list > li .video-thumb.empty_thumb').css({
                            'height' : imgHeight + 'px',
                        });
                    }
                }
            }
        }
    };
})(jQuery, Drupal, once);

