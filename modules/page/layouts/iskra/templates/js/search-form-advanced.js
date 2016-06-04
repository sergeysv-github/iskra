$(function(){
    $('.search-form').find('.dropdown-menu input, .dropdown-menu select, .dropdown-menu label, .select-dynamic').click(function(e) {
        // So that the form wouldn't close if you click on an input.
        e.stopPropagation();
    });
    $('.search-form').find('.dropdown').on('show.bs.dropdown', function () {
        // Move the dropdown to the left.
        var pos = $('#search-buttons').position();
        $(this).find('.dropdown-menu').css('left', '-'+pos.left+'px');
    });
});
