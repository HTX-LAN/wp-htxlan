// Function for downloading data
function downloadData()
{
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

function participantUpdate(cell,rowId,formId,userId) {
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
        if(data.success == false) {
            informationwindowInsert(3, "Rækken blev ikke opdateret");
            console.log(data.error);
        }
    });
}