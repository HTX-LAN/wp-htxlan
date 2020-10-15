// Function for downloading data
function downloadData() {

    var csv = Papa.unparse(tableCSVContent);

    var csvData = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
    var csvURL =  null;
    if (navigator.msSaveBlob)
    {
        csvURL = navigator.msSaveBlob(csvData, 'Tilmeldings formular - Download.csv');
    }
    else
    {
        csvURL = window.URL.createObjectURL(csvData);
    }

    var tempLink = document.createElement('a');
    tempLink.href = csvURL;
    tempLink.setAttribute('Download', 'Tilmeldings formular - Download.csv');
    tempLink.click();
}

function participantUpdate(cell,rowId,formId,userId,price) {
    var form = {
        formid: formId,
        userId: userId,
        paymentOption: $('#paymentOption-'+rowId).val(),
        arrived: $('#arrived-'+rowId).is(":checked") ? 1 : 0,
        arrivedAtDoor: $('#arrivedAtDoor-'+rowId).is(":checked") ? 1 : 0,
        crew: $('#crew-'+rowId).is(":checked") ? 1 : 0,
        pizza: $('#pizza-'+rowId).is(":checked") ? 1 : 0,
        postType: 'update',
        type: 'POST',
        action: "htx_participant_update"
    };
    $.post(ajaxurl, form, function(data) {
        if(data.success == true) {
            if (cell == 'crewUpdate') {
                currentPrice = parseFloat(document.getElementById('price-'+rowId).innerHTML);
                if ($('#crew-'+rowId).is(":checked")){
                    document.getElementById('price-'+rowId).innerHTML = (currentPrice - parseFloat(price));
                } else {
                    document.getElementById('price-'+rowId).innerHTML = (currentPrice + parseFloat(price));
                }
            }
        } else {
            informationwindowInsert(3, "Rækken blev ikke opdateret");
            console.log(data.error);
        }
    });
}


function participantOpenUpdate(submissionId) {
    
}

users = [];
function HTX_participant_massAction(id,userid) {
    // Get if checkbox is checked
    checked = $('#'+id).is(":checked") ? 1 : 0;
    // Edit array for user id
    if (checked == 1) {
        // Add user  id
        users.push(`${userid}`);
    } else {
        // Remove user id
        const index = users.indexOf(`${userid}`);
        if (index > -1) {
            users.splice(index, 1);
        }
    }
    $('.massAction_users').each(function() {
        $(this).val(users);
    });
    
    if (users.length < 1) { // hide mass action
        document.getElementById('massAction').classList.add('hidden')
    } else { // Show mass action
        document.getElementById('massAction').classList.remove('hidden')
    }
}
function HTX_participant_massAction_master() {
    // Get if checkbox is checked
    checked = $('#userid-checkbox-master').is(":checked") ? 1 : 0;

    // Check checkboxes
    if (checked == 1) { // Check every checkbox
        $('.userid-checkbox').prop('checked', true);

        // Edit users array
        users = [];
        $('.userid-checkbox').each(function() {
            if($(this).is(":checked")) {
                users.push($(this).val());
            }
        });
    } else { // Uncheck every checkbox
        $('.userid-checkbox').prop('checked', false);
        users = [];
    }
    $('.massAction_users').each(function() {
        $(this).val(users);
    });

    if (users.length < 1) { // hide mass action
        document.getElementById('massAction').classList.add('hidden')
    } else { // Show mass action
        document.getElementById('massAction').classList.remove('hidden')
    }
}