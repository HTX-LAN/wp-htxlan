function HTXJS_price_update() {
    totalPrice = 0;
    $(".priceFunction").each(function() {
        totalPrice += parseFloat(price[$(this).val()]);
    });
    $(".priceFunctionRadio").each(function() {
        if ($(this).is(":checked")) {
            totalPrice += parseFloat(price[$(this).val()]);
        }
    });
    $(".priceFunctionCheckbox").each(function() {
        if ($(this).is(":checked")) {
            totalPrice += parseFloat(price[$(this).val()]);
        }
    });
    document.getElementById('priceLine').innerHTML = totalPrice;
}