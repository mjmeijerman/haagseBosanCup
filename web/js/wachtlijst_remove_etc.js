function remove_turnster(id, naam, vereniging) {
    if (confirm("Weet je zeker dat je " + naam + " van " + vereniging + " wilt verwijderen?")) {
        var row = document.getElementById('turnster_row_' + id);
        var aantal_deelnemers = document.getElementById('turnsters_aantal');
        aantal_deelnemers.innerHTML = (parseInt(aantal_deelnemers.innerHTML) - 1);
        row.innerHTML = '';
        $.ajax({
            type: 'get',
            url: Routing.generate('removeOrganisatieTurnsterAjaxCall', {id: id})
        });
    }
}

function remove_turnster_wachtlijst(id, naam, vereniging) {
    if (confirm("Weet je zeker dat je " + naam + " van " + vereniging + " wilt verwijderen?")) {
        var row = document.getElementById('turnster_row_' + id);
        var aantal_deelnemers = document.getElementById('wachtlijst_aantal');
        aantal_deelnemers.innerHTML = (parseInt(aantal_deelnemers.innerHTML) - 1);
        row.innerHTML = '';
        $.ajax({
            type: 'get',
            url: Routing.generate('removeOrganisatieTurnsterAjaxCall', {id: id})
        });
    }
}

function remove_turnster_afgemeld(id, naam, vereniging) {
    if (confirm("Weet je zeker dat je " + naam + " van " + vereniging + " wilt verwijderen?")) {
        var row = document.getElementById('turnster_row_' + id);
        var aantal_deelnemers = document.getElementById('afgemeld_aantal');
        aantal_deelnemers.innerHTML = (parseInt(aantal_deelnemers.innerHTML) - 1);
        row.innerHTML = '';
        $.ajax({
            type: 'get',
            url: Routing.generate('removeOrganisatieTurnsterAjaxCall', {id: id})
        });
    }
}

function remove_jurylid(id, naam, vereniging) {
    if (confirm("Weet je zeker dat je " + naam + " van " + vereniging + " wilt verwijderen?")) {
        var row = document.getElementById('jurylid_row_' + id);
        var aantal_jury = document.getElementById('juryleden_aantal');
        aantal_jury.innerHTML = (parseInt(aantal_jury.innerHTML) - 1);
        row.innerHTML = '';
        $.ajax({
            type: 'get',
            url: Routing.generate('removeOrganisatieJuryAjaxCall', {id: id})
        });
    }
}

function naar_wachtlijst(id)
{
    var table = document.getElementById("wachtlijst_table");
    var new_row = table.insertRow(-1);
    var old_row = document.getElementById('turnster_row_' + id);
    var aantal_deelnemers = document.getElementById('turnsters_aantal');
    aantal_deelnemers.innerHTML = (parseInt(aantal_deelnemers.innerHTML) - 1);
    var aantal_deelnemers_wachtlijst = document.getElementById('wachtlijst_aantal');
    aantal_deelnemers_wachtlijst.innerHTML = (parseInt(aantal_deelnemers_wachtlijst.innerHTML) + 1);
    new_row.innerHTML = old_row.innerHTML;
    old_row.innerHTML = '';
    $.ajax({
        type: 'get',
        url: Routing.generate('moveTurnsterToWachtlijst', {id: id})
    });
}

function van_wachtlijst(id)
{
    var table = document.getElementById("turnster_table");
    var new_row = table.insertRow(-1);
    var old_row = document.getElementById('turnster_row_' + id);
    var aantal_deelnemers = document.getElementById('turnsters_aantal');
    aantal_deelnemers.innerHTML = (parseInt(aantal_deelnemers.innerHTML) + 1);
    var aantal_deelnemers_wachtlijst = document.getElementById('wachtlijst_aantal');
    aantal_deelnemers_wachtlijst.innerHTML = (parseInt(aantal_deelnemers_wachtlijst.innerHTML) - 1);
    new_row.innerHTML = old_row.innerHTML;
    old_row.innerHTML = '';
    $.ajax({
        type: 'get',
        url: Routing.generate('moveTurnsterFromWachtlijst', {id: id})
    });
}