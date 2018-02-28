jQuery( document ).ready(function( $ ) {
    var containerID;
});
function importCSV(e, importID, type){
    e.preventDefault();

    containerID = importID;

    var $form = jQuery('#' + containerID).find('.ait-csv-import-form');
    var fileSelect = $form.find('.file-select');
    var files = fileSelect[0].files;
    var file = files[0];

    var formData = new FormData();
    formData.append('file', file);

    var fileHandlerUrl = $form.find('.upload-handler').val();

    var importOptions = {
        formData: formData,
        adminAjaxUrl: $form.attr('action'),
        fileHandlerUrl: fileHandlerUrl,
        type: type,
        slug: jQuery('#'+containerID).attr('data-slug'),
        lang: jQuery('#ait-csv-import-export-import-panel ').find("[name='ait_csv_options[language]']").val()
    };



    jQuery('#' + containerID).find('.ait-csv-report').hide();


    if (fileSelect.val() !== '') {
        resetProgressBar();
        $form.find('.ait-button.upload').prop('disabled', true);
        handleCSVFile(importOptions);
    }
}


function handleCSVFile(importOptions){
    jQuery.ajax({
        type: "POST",
        url: importOptions.fileHandlerUrl,
        data: importOptions.formData,
        processData: false,
        contentType: false,

    }).done(function(xhr){
        if (xhr === null) {
            displayAlert("Unknown server error. See the documentation", "error");
        } else if (xhr.status === true) {
            var data = {
                rowsPerRequest: 30,
                offset: 0,
                type: importOptions.type,
                slug: importOptions.slug,
                lang: importOptions.lang,
                file: xhr.file,
                delimiter: xhr.delimiter,
                firstDataRow: xhr.firstDataRow,
                foundRows: xhr.foundRows,
            };
            initProgressBar(xhr.foundRows);
            csvAjaxImport(importOptions.adminAjaxUrl, data);
        } else {
            displayAlert(xhr.report.message, "error");
        }

    }).fail(function(xhr){
        console.log('fail');
    });
}


function csvAjaxImport(importUrl, data){
    jQuery.post(importUrl, {
        'action': 'csvImport',
        'data': data,
        'dataType': 'json',

    }).success(function(xhr){
        if(xhr.result == true){
            console.log('success');
            data.offset = data.offset + xhr.data.response.imported;
            updateProgressBar(data.offset);
            if (data.offset < data.foundRows) {
                csvAjaxImport(importUrl, data);
            } else {
                jQuery('#' + containerID).find('.ait-csv-import-form .ait-button.upload').prop('disabled', false);
                console.log(xhr.data);
                displayAlert(xhr.data.response.message, "success");
                // displayResult(1);
            }
        } else {
            console.log("not success");
            displayAlert(xhr.data.response.message, "error");
        }

    }).fail(function(xhr){
        console.log('fail');
        displayAlert(xhr.responseText, "error");
    });
}



function initProgressBar(max) {
    var $loader = jQuery('#' + containerID).find('.ait-loader');
    $loader.attr('data-max', max);
    $loader.find('.loader-max').text(max);
}



function updateProgressBar(count) {
    var $loader = jQuery('#' + containerID).find('.ait-loader');
    var max = $loader.attr('data-max');
    var progress = 100 / max * count;
    // var progress = 100 / rowsCount * imported;
    $loader.attr('data-current', count);
    $loader.find('.loader-value').text(count);
    $loader.find('.loader-bar').width(progress + '%');
}


function resetProgressBar() {
    var $loader = jQuery('#' + containerID).find('.ait-loader');
    // var progress = 100 / rowsCount * imported;
    $loader.attr('data-current', 0);
    $loader.find('.loader-value').text(0);
    $loader.find('.loader-bar').width(0 + '%');
}


function displayAlert(message, type) {
    if (type == "error") {
        jQuery('#' + containerID).find('.ait-csv-report').removeClass('alert-success')
        jQuery('#' + containerID).find('.ait-csv-report').addClass('alert-danger')
    } else {
        jQuery('#' + containerID).find('.ait-csv-report').removeClass('alert-danger')
        jQuery('#' + containerID).find('.ait-csv-report').addClass('alert-success')
    }
    jQuery('#' + containerID).find('.ait-csv-report').html("<pre>" + message + "</pre>");
    jQuery('#' + containerID).find('.ait-csv-report').slideDown();
    // var $progressBar = jQuery('#' + activeFormID + '-progress');
    // $progressBar.find('.loader-bar').css('background-color', 'green');
}