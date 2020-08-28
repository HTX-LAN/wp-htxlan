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