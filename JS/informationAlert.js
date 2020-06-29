/*informationwindow inserter - by Mikkel Albrechtsen */
informationwindowInsertIDDONOTCHANGE = 0;
function informationwindowInsert(cat,text,speciel) {
    informationwindowInsertIDDONOTCHANGE +=1;
    id = informationwindowInsertIDDONOTCHANGE;
    if (cat == 1) {
        cat = "succes"
        cattext = "Success";
    } else if (cat == 2) {
        cat = "warning";
        cattext = "Advarsel";
    } else if (cat == 3) {
        cat = "error"
        cattext = "Error!";
    } else {
        return 0;
    }
    if (speciel != "") {
        element = "<div id='IW"+id+"' class='succesWindows Windows "+cat+"' onclick='("+speciel+");informationwindowremove("+id+")'>";
    } else {
        element = "<div id='IW"+id+"' class='succesWindows Windows "+cat+"' onclick='(informationwindowremove("+id+"))'>";
    }
    element += "<p id='IWTH"+id+"' class='infoText infoTextHeader'>"+cattext+"</p>";
    element += "<p id='IWT"+id+"' class='infoText'>"+text+"</p>";
    element += "<div id='IWS"+id+"' class='statusBar'>";
    element += "</div></div>";
    $( "#informationwindow" ).append( element );
    setTimeout(function(){ document.getElementById("IWS"+id).classList.add('statusClosing');  removeDocInfo(id)}, 10);
    function removeDocInfo(id) {setTimeout(function(){ document.getElementById(('IW'+id)).remove(); }, 5000);}
    return(id);
}
function informationwindowremove(id) {
    document.getElementById("IW"+id).remove();
}