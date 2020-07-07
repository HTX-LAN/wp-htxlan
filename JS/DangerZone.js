// Danger zone Javascript scripts


// Reset all databases
function HTXJS_resetDatabases(url) {
    var confirmCreate = confirm("Er du sikker på at du vil nulstille databaser?");
    id = informationwindowInsert(2,"Arbejder på det...");
    if (confirmCreate == true) {
        $.post(url, {
            postType: "resetDB"
        }, function(data) {informationwindowremove(id); informationwindowInsert(1,"Databaser er nulstillet");});
    }

}
