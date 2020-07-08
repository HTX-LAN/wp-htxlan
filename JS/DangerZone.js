// Danger zone Javascript scripts


// Reset all databases
function HTXJS_resetDatabases(url) {
    var confirmCreate = confirm("Er du sikker på at du vil nulstille databaser?");
    if (confirmCreate == true) {
        var id = informationwindowInsert(2, "Arbejder på det...");
        $.post(url, {
            postType: "resetDB"
        }, function(data) {
            informationwindowremove(id);
            if(data.success)
                informationwindowInsert(1, "Databaser er nulstillet");
            else {
                informationwindowInsert(3, "Kunne ikke nulstille databaser.");
                console.log(data.error);
            }
        });
    }

}

// Reset all databases
function HTXJS_downloadData(url) {
    var id = informationwindowInsert(2, "Arbejder på det...");
    $.post(url, {
        postType: "downloadParticipants"
    }, function(data) {
        informationwindowremove(id);
        if(data.success) {
            informationwindowInsert(1, "Data er klar til download");
            var link = document.createElement('a');
            link.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(data.csv));
            link.setAttribute('download', data.filename);
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } else {
            informationwindowInsert(3, "Kunne ikke downloade data.");
            console.log(data.error);
        }
    });
}
