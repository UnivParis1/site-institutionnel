/**
 FullCalendar override for event calendar.
*/

(function($) {
  
  
  Drupal.behaviors.event_calendar = {
    attach: function (context, settings) {

      if(typeof drupalSettings.calendar != "undefined"){
        if(typeof drupalSettings.calendar[0] != "undefined"){

          /** BEGIN : override calendar Month grid */
          // Force lang = fr
          drupalSettings.calendar[0].setOption('locale', 'fr');
          // Define custom prev next button.
          drupalSettings.calendar[0].setOption('customButtons', {
            customprev: {
              text: '<',
              click: function() {
                drupalSettings.calendar[0].prev();
                // refresh event dot display when view month change.
                refresh_event_display();
              }
            },
            customnext: {
              text: '>',
              click: function() {
                drupalSettings.calendar[0].next();
                // refresh event dot display when view month change.
                refresh_event_display();
              }
            }
          });

          // Display custom buttons in header toolbar instead of native prev next.
          drupalSettings.calendar[0].setOption('header',{
            left: 'customprev',
            center: 'title',
            right: 'customnext'
          } );
          /** END : override calendar Month grid */
        }
        if(typeof drupalSettings.calendar[1] != "undefined"){
          // Force lang = fr
          drupalSettings.calendar[1].setOption('locale', 'fr');
        }
      }

      //Set today day as default.
      $('.fc-today').addClass('fc-current');
  
  
      $(document).on('click.fc', 'a[data-goto]', function (ev) {
        var anchorEl = $(ev.currentTarget);
  
        // Remove blue bg for old date details.
        var oldCurrent = $('.fc-current');
        oldCurrent.each(function(){
          $(this).removeClass('fc-current');
        });
  
        // Add blue bg for new date details.
        var newCurrent = anchorEl.parent('.fc-day-top').attr('data-date');
        $('[data-date="'+newCurrent+'"]').each(function(){
          $(this).addClass('fc-current');
        })
  
        // Refresh calendar + list with new date.
        var gotoOptions = anchorEl.data('goto');
        drupalSettings.calendar[1].changeView('listDay', gotoOptions.date);
        drupalSettings.calendar[0].changeView('dayGridMonth', gotoOptions.date);
        refresh_event_display();
      });
  
      refresh_event_display();
    }
  };
  
  
  function refresh_event_display(){
    // split td colspan to have as many dot as days.
    $('.fc-day-grid .fc-content-skeleton').each(function() {
      var count = 0;
      $(this).find('table tbody td.fc-event-container').each(function() {
        var td = $(this).attr('colspan');
        if (td > 0) {
          var tdVal = $(this).html();
          for (var j = 0; j < td; j++) {
            var newTd = $(this).clone();
            newTd.removeAttr('colspan');
            newTd.html(tdVal);
            $(this).after(newTd);
          }
          $(this).remove();
          count++;
        }
      });
    });
  }
})(jQuery,Drupal);