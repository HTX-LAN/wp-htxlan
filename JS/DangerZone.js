// Danger zone Javascript scripts


// Create all databases
function HTXJS_createDatabases(url) {
    var confirmCreate = confirm("Er du sikker på at du vil oprette databaser?");
    id = informationwindowInsert(2,"Arbejder på det...");
    if (confirmCreate == true) {
        $.post(url, {
            postType: "createDatabases"
        }, function(data) {informationwindowremove(id); informationwindowInsert(1,"Databaser er oprettet");});
    } 
    
}
// Drop all databases
function HTXJS_dropDatabases(url) {
    var confirmCreate = confirm("Er du sikker på at du vil slette databaserne?");
    if (confirmCreate == true) {
        id = informationwindowInsert(2,"Arbejder på det...");
        $.post(url, {
            postType: "dropDatabases"
        }, function(data) {informationwindowremove(id); informationwindowInsert(1,"Databaser er slettet")});
    } 
    
}