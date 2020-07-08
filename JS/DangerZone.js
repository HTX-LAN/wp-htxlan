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
