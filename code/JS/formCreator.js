// Script to show formular edit page
function showEditForm(id) {

}

//https://stackoverflow.com/a/901144
function getParameterByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, '\\$&');
    var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, ' '));
}

// Script to submit form based on id
function submitForm(id) {
    document.getElementById(id).submit();
}

function HTXJS_deleteForm(formid) {
    var confirmDelete = confirm("Er du sikker på at du vil slette denne formular?\nDette er en permanent handling!");
    if (confirmDelete == true) {
        var id = informationwindowInsert(2, "Arbejder på det...");
        $.post(ajaxurl, {
            formid: formid,
            action: "htx_delete_form"
        }, function(data) {
            informationwindowremove(id);
            if(data.success) {
                informationwindowInsert(1, "Formularen blev slettet.");
                location.search = "page=" + getParameterByName("page");
            } else {
                informationwindowInsert(3, "Kunne ikke slette formularen.");
                console.error(data.error);
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
        registration: $('#registrationInput').is(":checked") ? 1 : 0,
        arrived: $('#arrivedInput').is(":checked") ? 1 : 0,
        crew: $('#crewInput').is(":checked") ? 1 : 0,
        pizza: $('#pizzaInput').is(":checked") ? 1 : 0,
        action: "htx_update_form"
    }, function(data) {
        informationwindowremove(id);
        if(data.success) {
            //Update values
            $('#form-tableOfContent-' + formid + ' a').text($('#tableName').val());
            $('#formSettings h2 a').text($('#tableName').val());

            informationwindowInsert(1, "Formularen blev opdateret.");
        } else {
            informationwindowInsert(3, "Kunne ikke opdatere formularen.");
            console.error(data.error);
        }
    });
}

function HTXJS_dublicateForm(formid) {
    var id = informationwindowInsert(2, "Arbejder på det...");
    $.post(ajaxurl, {
        formid: formid,
        action: "htx_dublicate_form"
    }, function(data) {
        informationwindowremove(id);
        console.log(data);
        if(data.success) {
            informationwindowInsert(1, "Formularen blev dublikeret.<br>Det nye navn er:<br>"+data.newName);
        } else {
            informationwindowInsert(3, "Kunne ikke dublikere formularen.");
            console.error(data.error);
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
            $('.highlighted').each(function() {
                $(this).removeClass('highlighted');
            });
            $("#formCreator_tableOfContent").append("<form id='form-tableOfContent-" + data.id + "' action='admin.php' method=\"get\"><input name='page' value='" + getParameterByName('page') + "' type='hidden'><input name='form' value='" + data.id + "' type='hidden'><a onclick='submitForm(\"form-tableOfContent-" + data.id + "\")' class='highlighted'>" + data.name + "</a><br></form>");
            submitForm("form-tableOfContent-" + data.id);
            informationwindowInsert(1, "Formularen blev oprettet.");
        } else {
            informationwindowInsert(3, "Kunne ikke oprette en ny formular.");
            console.error(data.error);
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
            informationwindowInsert(1, "Ny række blev oprettet.");
            location.reload();
        } else {
            informationwindowInsert(3, "Kunne ikke oprette ny række.");
            console.error(data.error);
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
            informationwindowInsert(1, "Sorteringen blev opdateret");
            location.reload();
        } else {
            informationwindowInsert(3, "Kunne ikke opdatere sorteringen.");
            console.error(data.error);
        }
    });
}

function HTXJS_updateColumn(setting, formid) {
    //TODO: Optimize this function
    var id = informationwindowInsert(2, "Arbejder på det...");
    var form = {
        formid: formid,
        action: "htx_update_column",
        name: $('#settingName').val(),
        format: $('#settingFormat option:selected').val(),
        placeholder: $('#settingPlaceholder').val(),
        formatExtra: $('#settingTelformat').val(),
        specialNameExtra: $('#settingshow1').val(),
        specialNameExtra3: $('#settingSpecial3').val(),
        teams: $('#settingTeams').val(),
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

    if ($('#settingShowValueKind').val() == '1') {
        // Inputbox
        form.settingShowValue = $('#settingShowValue').val()
        form.settingShowValueKind = $('#settingShowValueKind').val()
        
    } else if ($('#settingShowValueKind').val() == '2') {
        // Checkbox
        var settingShowValues = [];
        form.settingShowValueKind = $('#settingShowValueKind').val()
        $('.settingShowValue').each(function() {
            if($(this).is(":checked")) {
                settingShowValues.push($(this).val());
            }
        });
        form.settingShowValue = settingShowValues;
    } else {
        form.settingShowValue = "";
    }

    if($('#settingsTrue').length)
        form.settingsTrue = $('#settingsTrue').val();
    if($('#settingsAmount').length)
        form.settingsAmount = $('#settingsAmount').val();
    $(".settingId").each(function() {
        form[$(this).attr('name')] = $(this).val();
    });
    $(".settingActive").each(function() {
        form[$(this).attr('name')] = $(this).is(":checked") ? 0 : 1;
    });
    $(".settingName").each(function() {
        form[$(this).attr('name')] = $(this).val();
    });
    $(".settingValue").each(function() {
        form[$(this).attr('name')] = $(this).val();
    });
    $(".settingExpence").each(function() {
        form[$(this).attr('name')] = $(this).val();
    });
    $(".settingSorting").each(function() {
        form[$(this).attr('name')] = $(this).val();
    });
    $.post(ajaxurl, form, function(data) {
        informationwindowremove(id);
        if(data.success) {
            location.reload();
            informationwindowInsert(1, "Rækken blev opdateret");
        } else {
            informationwindowInsert(3, "Kunne ikke opdatere rækken.");
            console.error(data.error);
        }
    });
}

function HTXJS_deleteColumn(setting) {
    var confirmDelete = confirm("Er du sikker på at du vil slette dette element?\nDette er en permanent handling!");
    if (confirmDelete == true) {
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
                informationwindowInsert(1, "Rækken blev slettet");
                location.search = "page=" + getParameterByName("page") + "&form=" + getParameterByName("form");
            } else {
                informationwindowInsert(3, "Kunne ikke slette rækken.");
                console.error(data.error);
            }
        });
    }
}

function HTXJS_deleteSetting(setting) {
    var id = informationwindowInsert(2, "Arbejder på det...");
    $.post(ajaxurl, {
        action: "htx_delete_setting",
        setting: setting
    }, function(data) {
        informationwindowremove(id);
        if(data.success) {
            location.reload();
            informationwindowInsert(1, "Valgmuligheden blev slettet");
        } else {
            informationwindowInsert(3, "Valgmuligheden kunne ikke slettes.");
            console.error(data.error);
        }
    });
}

function HTXJS_addSetting(setting, type, formid) {
    var id = informationwindowInsert(2, "Arbejder på det...");
    $.post(ajaxurl, {
        action: "htx_add_setting",
        setting: setting,
        columnType: type,
        tableId: formid
    }, function(data) {
        informationwindowremove(id);
        if(data.success) {
            location.reload();
            informationwindowInsert(1, "Valgmuligheden blev tilføjet");
        } else {
            informationwindowInsert(3, "Valgmuligheden kunne ikke tilføjes.");
            console.error(data.error);
        }
    });
}

// Function to uncheck the other function box
function HTXJS_unCheckFunctionCheckbox(id) {
    document.getElementById('function-'+id).checked = false;
}

// Disable required checkbox, if disable is checked
function HTXJS_settingDisabledCheckbox(param) {
    if (param == 'enable') {
        if (document.getElementById('settingDisabled').checked == true) document.getElementById('settingRequired').checked = false;
    } else {
        if (document.getElementById('settingRequired').checked == true) document.getElementById('settingDisabled').checked = false;
    }

}