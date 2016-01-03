function onChange(name, fieldName, e) {
    if(e.type === 'keypress' && e.keyCode !== 13) return;
    if(name) {
        $.ajax({
            type: 'get',
            url: Routing.generate('editGegevens', {fieldName: fieldName, data: name}),
            success: function (data) {
                $('#' + fieldName).text(data);
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

function onClick(name, fieldName) {
    if ($('#txt_' + fieldName).length) return;
    $('#' + fieldName).html('');
    $('<input></input>')
        .attr({
            'type': 'text',
            'name': 'fname',
            'id': 'txt_' + fieldName,
            'size': '30',
            'value': name
        })
        .appendTo('#' + fieldName);
    $('#txt_' + fieldName).focus();
    var tmpStr = $('#txt_' + fieldName).val();
    $('#txt_' + fieldName).val('');
    $('#txt_' + fieldName).val(tmpStr);
    $('#txt_' + fieldName).focus();
}