// Danger zone Javascript scripts


// Reset all databases
function HTXJS_resetDatabases() {
    var confirmCreate = confirm("Er du sikker på at du vil nulstille databaser?");
    if (confirmCreate == true) {
        var id = informationwindowInsert(2, "Arbejder på det...");
        $.post(ajaxurl, {
            postType: "resetDB",
            action: "htx_parse_dangerzone_request"
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
function HTXJS_downloadData() {
    var id = informationwindowInsert(2, "Arbejder på det...");
    $.post(ajaxurl, {
        postType: "downloadParticipants",
        action: "htx_parse_dangerzone_request"
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
