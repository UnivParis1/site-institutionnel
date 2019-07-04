$(document).ready(function(){
  $(".search-toggle").click(function(){
    $(".recherche").fadeToggle();
  });
  $('#nav-icon').click(function(){
    $(this).toggleClass('open');
  });

  ///Filtrage
  filtresSelectionnes=[];
  $('#recherche_formation .dropdown-menu a').click(function(){
    var filterValue = $( this ).attr('data-filter');
    filtresSelectionnes.push(filterValue);
    Filtrer(filterValue);
    showFilters();
  });

  function Filtrer(value) {
    $('.formation-wrapper').isotope({
      filter: value,
      itemSelector: '.items',
      masonry: {
        columnWidth: '.grid-sizer'
      }
    });
  }

  function showFilters(){

    var showFilters="";
    showFilters+="<ul class='listedFilters'>";

    filtresSelectionnes.forEach(function(entry) {

      var theNewEntry=entry;
      if(entry!="*") {
        theNewEntry=theNewEntry.replace(".", " ");
        showFilters+="<li data-filter='"+entry+"'><div class='top_checkbox'></div><p>"+theNewEntry+"</p></li>";
      }
    });

    showFilters+="</ul>";

    $("#filtres-choisis").html(showFilters);
  }


});

