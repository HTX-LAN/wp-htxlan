// Script to show formular edit page
function showEditForm(id) {

}

// Script to submit form based on id
function submitForm(id) {
    document.getElementById(id).submit();
}

function HTXJS_deleteForm(formid) {
    var confirmDelete = confirm("Er du sikker på at du vil slette denne formular?");
    if (confirmDelete == true) {
        var id = informationwindowInsert(2, "Arbejder på det...");
        $.post(ajaxurl, {
            formid: formid,
            action: "htx_delete_form"
        }, function(data) {
            informationwindowremove(id);
            if(data.success) {
                //TODO: Update and remove necessary values
                informationwindowInsert(1, "Formularen blev slettet.");
            } else {
                informationwindowInsert(3, "Kunne ikke slette formularen.");
                console.log(data.error);
            }
        });
    }
}

function HTXJS_updateForm(formid) {
    var id = informationwindowInsert(2, "Arbejder på det...");
    $.post(ajaxurl, {
        formid: formid,
        tableName: $('#tableName').val(),
        tableDescription: $('#tableDescription').val(),
        action: "htx_update_form"
    }, function(data) {
        informationwindowremove(id);
        if(data.success) {
            //TODO: Update and remove necessary values
            informationwindowInsert(1, "Formularen blev opdateret.");
        } else {
            informationwindowInsert(3, "Kunne ikke opdatere formularen.");
            console.log(data.error);
        }
    });
}

function HTXJS_createForm() {
    var id = informationwindowInsert(2, "Arbejder på det...");
    $.post(ajaxurl, {
        action: "htx_create_form"
    }, function(data) {
        informationwindowremove(id);
        if(data.success) {
            //TODO: Update necessary values
            informationwindowInsert(1, "Formularen blev oprettet. ID " + data.id);
        } else {
            informationwindowInsert(3, "Kunne ikke oprette en ny formular.");
            console.log(data.error);
        }
    });
}

function HTXJS_addColumn(formid) {
    var id = informationwindowInsert(2, "Arbejder på det...");
    $.post(ajaxurl, {
        action: "htx_new_column",
        inputType: $('#inputType option:selected').val(),
        tableId: formid
    }, function(data) {
        informationwindowremove(id);
        if(data.success) {
            //TODO: Update necessary values
            informationwindowInsert(1, "Ny række blev oprettet. ID " + data.id);
        } else {
            informationwindowInsert(3, "Kunne ikke oprette ny række.");
            console.log(data.error);
        }
    });
}

function HTXJS_updateSorting(setting) {
    var id = informationwindowInsert(2, "Arbejder på det...");
    $.post(ajaxurl, {
        action: "htx_update_sorting",
        setting: setting,
        sorting: $('#settingSorting').val()
    }, function(data) {
        informationwindowremove(id);
        if(data.success) {
            //TODO: Update necessary values
            informationwindowInsert(1, "Sorteringen blev opdateret");
        } else {
            informationwindowInsert(3, "Kunne ikke opdatere sorteringen.");
            console.log(data.error);
        }
    });
}

function HTXJS_updateColumn(setting) {
    //TODO: Optimize this function
    var id = informationwindowInsert(2, "Arbejder på det...");
    var form = {
        action: "htx_update_column",
        name: $('#settingName').val(),
        format: $('#settingFormat option:selected').val(),
        placeholder: $('#settingPlaceholder').val(),
        required: $('#settingRequired').is(":checked") ? 1 : 0,
        disabled: $('#settingDisabled').is(":checked") ? 1 : 0,
        setting: setting,
        sorting: $('#settingSorting').val()
    };
    var specials = [];
    $('.special').each(function() {
        if($(this).is(":checked")) {
            specials.push($(this).val());
        }
    });
    form.specialName = specials;
    if($('#settingsTrue').length)
        form.settingsTrue = $('#settingsTrue').val();
    if($('#settingsAmount').length)
        form.settingsAmount = $('#settingsAmount').val();
    $(".settingId").each(function() {
        form[$(this).attr('id')] = $(this).val();
    });
    $(".settingActive").each(function() {
        form[$(this).attr('id')] = $(this).val();
    });
    $(".settingName").each(function() {
        form[$(this).attr('id')] = $(this).val();
    });
    $(".settingValue").each(function() {
        form[$(this).attr('id')] = $(this).val();
    });
    $(".settingSorting").each(function() {
        form[$(this).attr('id')] = $(this).val();
    });
    $.post(ajaxurl, form, function(data) {
        informationwindowremove(id);
        if(data.success) {
            //TODO: Update necessary values
            informationwindowInsert(1, "Rækken blev opdateret");
        } else {
            informationwindowInsert(3, "Kunne ikke opdatere rækken.");
            console.log(data.error);
        }
    });
}

function HTXJS_deleteColumn(setting) {
    var id = informationwindowInsert(2, "Arbejder på det...");
    var form = {
        action: "htx_delete_column",
        setting: setting
    };
    if($('#settingsTrue').length)
        form.settingsTrue = $('#settingsTrue').val();
    if($('#settingsAmount').length)
        form.settingsAmount = $('#settingsAmount').val();
    $(".settingId").each(function() {
        form[$(this).attr('id')] = $(this).val();
    });
    $.post(ajaxurl, form, function(data) {
        informationwindowremove(id);
        if(data.success) {
            //TODO: Update necessary values
            informationwindowInsert(1, "Rækken blev slettet");
        } else {
            informationwindowInsert(3, "Kunne ikke slette rækken.");
            console.log(data.error);
        }
    });
}
