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

// Delete submission
function confirmDelete(id) {
    var userConfirm = confirm("Er du sikker på at du vil slette denne tilmelding. Dette er en permanent handling");
    if (userConfirm == true) {
        document.forms[id].submit();
    }
}

// Sort table (edited https://stackoverflow.com/questions/24033294/sorting-table-rows-according-to-table-header-column-using-javascript-or-jquery)
//  sortTable(f,n)
//  f : 1 ascending order, -1 descending order
//  n : n-th child(<td>) of <tr>
function sortTable(f,n, tableId){
    var rows = $('#'+tableId+' tbody  tr').get();

    rows.sort(function(a, b) {

        var A = getVal(a);
        var B = getVal(b);

        if(A < B) {
            return -1*f;
        }
        if(A > B) {
            return 1*f;
        }
        return 0;
    });

    function getVal(elm){
        var v = $(elm).children('td').eq(n).text().toUpperCase();
        if($.isNumeric(v)){
            v = parseInt(v,10);
        }
        return v;
    }

    $.each(rows, function(index, row) {
        $('#'+tableId).children('tbody').append(row);
    });
}
var f_sl = 1; // flag to toggle the sorting order
var f_nm = 1; // flag to toggle the sorting order
$("#sl").click(function(){
    f_sl *= -1; // toggle the sorting order
    var n = $(this).prevAll().length;
    sortTable(f_sl,n);
});
$("#nm").click(function(){
    f_nm *= -1; // toggle the sorting order
    var n = $(this).prevAll().length;
    sortTable(f_nm,n);
});