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
    var element =  document.getElementById('priceLine');
    if (typeof(element) != 'undefined' && element != null)
    {
        document.getElementById('priceLine').innerHTML = totalPrice;
    }
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

function HTX_charAmount(i, id) {
    // Getting input length
    input = document.getElementById(id).value;
    inputLength = input.length;

    // Setting element for alert
    alertElement = document.getElementById('charAmount-'+i);

    // Getting char type
    type = document.getElementById('char-'+i).value;
    
    switch (type) {
        case 'both':
            // Getting values
            min = document.getElementById('minChar-'+i).value;
            max = document.getElementById('maxChar-'+i).value;

            // Checking length
            if (inputLength < min) {
                alertElement.innerHTML = `venligst indtast et svar længere end eller lig med ${min} tegn. ${inputLength}/${max}`;
                alertElement.classList.add('charAmountWarning');
            } else if (inputLength > max) {
                alertElement.innerHTML = `venligst indtast et svar kortere end eller lig med ${max} tegn. ${inputLength}/${max}`;
                alertElement.classList.add('charAmountWarning');
            } else {
                alertElement.innerHTML = `${inputLength}/${max}`
                alertElement.classList.remove('charAmountWarning')
            }
        break;
        case 'min':
            // Getting min value
            min = document.getElementById('minChar-'+i).value;
            
            // Checking length
            if (inputLength < min) {
                alertElement.innerHTML = `venligst indtast et svar længere end eller lig med ${min} tegn.`;
                alertElement.classList.add('charAmountWarning');
            }  else {
                alertElement.innerHTML = ""
                alertElement.classList.remove('charAmountWarning')
            }
        break;
        case 'max':
            // Getting max value
            max = document.getElementById('maxChar-'+i).value;

            // Checking length
            if (inputLength > max) {
                alertElement.innerHTML = `venligst indtast et svar kortere end eller lig med ${max} tegn. ${inputLength}/${max}`;
                alertElement.classList.add('charAmountWarning');
            } else {
                alertElement.innerHTML = `${inputLength}/${max}`
                alertElement.classList.remove('charAmountWarning')
            }
        break;
        default: // none

        break;
    }
}