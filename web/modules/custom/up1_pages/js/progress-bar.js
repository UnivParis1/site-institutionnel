/**
 * @file
 * Barres de progression de lecture des sections d'une page personnelle.
 */
((Drupal, $, once) => {
  Drupal.behaviors.up1PagesProgressBar = {
    attach(context) {
      once('up1-pages-progress', '.progress .wrapper', context).forEach((element) => {
        const $wrapper = $(element);
        const $bars = $wrapper.find('.bar');
        const wrapperTop = $wrapper.offset().top;

        $(window).on('scroll', () => {
          const top = $(window).scrollTop();
          const wrapperHeight = $wrapper.height();
          $wrapper.toggleClass('affix', top > wrapperTop - 234);

          $('.colonnecentrale .new-section').each((i, section) => {
            const sectionTop = $(section).offset().top;
            const height = $(section).height();
            const $bar = $bars.eq(i);
            let percent = 0;
            if (top >= sectionTop && top <= sectionTop + height) {
              percent = Math.min(100, ((top - sectionTop) / (height - wrapperHeight)) * 100);
              $bar.find('i').css('color', percent >= 100 ? '#fff' : '#36a7f3');
            }
            else if (top > sectionTop + height) {
              percent = 100;
              $bar.find('i').css('color', '#fff');
            }
            $bar.find('span').css('width', `${percent}%`);
          });
        });
      });
    },
  };
})(Drupal, jQuery, once);
