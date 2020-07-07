/*Column hover effect - from http://jsfiddle.net/q3HHt/123/*/
/*For td*/
$('td').hover(function() {
    var t = parseInt($(this).index()) + 1;
    if (t > 1) {
        $(this).parents('table').find('td:nth-child(' + t + ')').addClass('highlighted');
        $(this).parents('table').find('th:nth-child(' + t + ')').addClass('highlighted');
    }
},
function() {
    var t = parseInt($(this).index()) + 1;
    if (t > 1) {
        $(this).parents('table').find('td:nth-child(' + t + ')').removeClass('highlighted');
        $(this).parents('table').find('th:nth-child(' + t + ')').removeClass('highlighted');
    }
});
/*for th*/
$('th').hover(function() {
    var t = parseInt($(this).index()) + 1;
    if (t > 1) {
        $(this).parents('table').find('td:nth-child(' + t + ')').addClass('highlighted');
        $(this).parents('table').find('th:nth-child(' + t + ')').addClass('highlighted');
    }
},
function() {
    var t = parseInt($(this).index()) + 1;
    if (t > 1) {
        $(this).parents('table').find('td:nth-child(' + t + ')').removeClass('highlighted');
        $(this).parents('table').find('th:nth-child(' + t + ')').removeClass('highlighted');
    }
});
