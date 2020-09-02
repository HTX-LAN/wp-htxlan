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

function liveParticipantCount(tableId,countDown,countDownFrom,id) {
    function liveParticipantCountLoop() { 
        $.post(widgetAjax.ajaxurl, {
            action: "htx_live_participant_count",
            security : widgetAjax.security,
            formid: tableId
        }, function(data) {
            if(data.success) {
                if (data.number >= 0) {
                    number = data.number;
                    if (countDown == 'true') number = countDownFrom - number;
                    if (number <= 0) number = 0;
                    document.getElementById(id).innerHTML = number;
                }
            }
        });
        setTimeout(liveParticipantCountLoop, 5000);
    }
    liveParticipantCountLoop()
}