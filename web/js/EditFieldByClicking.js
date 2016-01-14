function onChange(data, fieldName, e) {
    if(e.type === 'keypress' && e.keyCode !== 13) return;
    if(data) {
        $.ajax({
            type: 'get',
            url: Routing.generate('editGegevens', {fieldName: fieldName, data: data}),
            success: function (data) {
                $('#' + fieldName).text(data.data);
                var melding;
                if (data.error) {
                    melding = '<div id="error">' + data.error + '</div>';
                    $('#error_container').html(melding);
                } else {
                    melding = '<div id="error_success">De gegevens zijn succesvol opgeslagen</div>';
                    $('#error_success_container').html(melding);
                }
                console.log(melding);
            }
        });
    } else {
        $.ajax({
            type: 'get',
            url: Routing.generate('removeGegevens', {fieldName: fieldName}),
            success: function (data) {
                $('#' + fieldName).text(data);
            }
        });
    }
}

function onClick(data, fieldName) {
    if ($('#txt_' + fieldName).length) return;
    $('#' + fieldName).html('');
    $('<input> </input>')
        .attr({
            'type': 'text',
            'name': 'fname',
            'id': 'txt_' + fieldName,
            'class': 'txt_edit',
            'size': '30',
            'value': data
        })
        .appendTo('#' + fieldName);
    $('#txt_' + fieldName).focus();
    var tmpStr = $('#txt_' + fieldName).val();
    $('#txt_' + fieldName).val('');
    $('#txt_' + fieldName).val(tmpStr);
    $('#txt_' + fieldName).focus();
}