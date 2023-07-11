/**
 FullCalendar override for event calendar.
*/

(function($) {
  
  
  Drupal.behaviors.event_calendar = {
    attach: function (context, settings) {

      // Move pager in calendar div.
      var pager_elm = $('#calendar-month-pager').clone();
      $('.calendar-view-table caption').html(pager_elm.html());
      $('#calendar-month-pager').hide();

      // Display only day event rows.
      var today = $('.today.current-month');
      if (today.length){
        today.addClass('current-day');
        refresh_event_rows(today);
      }else {
        // Display first day no today date.
        refresh_event_rows($('.current-month').first());
      }

      // Change days name from LUNDI to LUN.
      $('.calendar-view-month th').each(function(){
        var dayname = $(this).html();
        $(this).html(dayname.substr(0, 3));
      });

      // Refresh event row and current date.
      $(document).on('click', '.calendar-view-day', function (ev) {
        
        $('.current-day').removeClass('current-day');
        var el = $(ev.currentTarget);
        el.parent('td').addClass('current-day');
        refresh_event_rows(el.parent('td'));
      });

      function refresh_event_rows( el ){
        $('[data-nid]').hide();
        
        var event_to_display = el.find('.calendar-view-day__rows').attr('data-nids-day');
        if (typeof event_to_display != "undefined") {
          if(event_to_display != ""){
            var events = event_to_display.split(",");
            $.each(events, function( index, value ) {
              $('[data-nid="'+value+'"]').show();
            });

            $('.no-event').hide();
          }else{
            // if no event, display empty message.
            $('.no-event').show();
          }
          
        }else{
          // if no event, display empty message.
          $('.no-event').show();
        }
      }
  
    }
  };
  
  

  Drupal.behaviors.agenda = {
    attach: function (context, settings) {


      function refresh_buttons_states(){
        var btn_type = $('[data-drupal-selector="edit-field-event-date-value"]').parents().find('body').attr('data-search');
        if( typeof btn_type != undefined && btn_type != ""){
          $('[data-drupal-selector="edit-field-event-date-value"]').parents().find('form').find('.btn').removeClass('active');
          $('.btn.'+btn_type).addClass('active');
        }
      }
      refresh_buttons_states();
    

      $('.today').click(function(){
        $(this).parents().find('body').attr('data-search','today');
        $('#agenda_datepicker').datepicker('setDate', '');
        $('[data-drupal-selector="edit-field-event-date-value"]').val(moment().format('YYYY-MM-DD') );
        $('[data-drupal-selector="edit-field-event-date-end-value"]').val(moment().format('YYYY-MM-DD') );
        refresh_buttons_states();
      });
    
      $('.tomorrow').click(function(){
        $(this).parents().find('body').attr('data-search','tomorrow');
        $('#agenda_datepicker').datepicker('setDate', '');
        $('[data-drupal-selector="edit-field-event-date-value"]').val(moment().add(1, 'days').format('YYYY-MM-DD'));
        $('[data-drupal-selector="edit-field-event-date-end-value"]').val(moment().add(1, 'days').format('YYYY-MM-DD')  );
        
        console.log($('[data-drupal-selector="edit-field-event-date-end-value"]'));
        refresh_buttons_states();
      });

      $('.week').click(function(){
        $(this).parents().find('body').attr('data-search','week');
        var next_monday = getNextDayOfWeek(new Date(), "1");
        $('[data-drupal-selector="edit-field-event-date-value"]').val(moment(next_monday).format('YYYY-MM-DD'));
        $('[data-drupal-selector="edit-field-event-date-end-value"]').val(moment(next_monday).add(6, 'days').format('YYYY-MM-DD')  );
        refresh_buttons_states();
      });

      $("#agenda_datepicker").datepicker({
        dateFormat: 'yy-mm-dd'
      });
      $('#agenda_datepicker').datepicker("option", "onSelect", function(dateText) {
          $(this).parents().find('body').attr('data-search','');
          $('[data-drupal-selector="edit-field-event-date-value"]').val(dateText);
          $('[data-drupal-selector="edit-field-event-date-end-value"]').val(dateText);
      });

      if( $('.btn.active').length == 0 && $('[data-drupal-selector="edit-field-event-date-value"]').val() != "" ) {
        $('#agenda_datepicker').datepicker('setDate', $('[data-drupal-selector="edit-field-event-date-value"]').val());
      }
    }
  };

  

  function getNextDayOfWeek(date, dayOfWeek) {
    var resultDate = new Date(date.getTime());
    resultDate.setDate(date.getDate() + (7 + dayOfWeek - date.getDay()) % 7);
    return resultDate;
  }
})(jQuery,Drupal);