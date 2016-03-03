function aantal_plekken()
{
    $.ajax({
        type: 'get',
        url: Routing.generate('aantalVrijePlekkenAjaxCall'),
        success: function (data) {
            document.getElementById("aantal_vrije_plekken").innerHTML = data;
        }
    });
}

// FUNCTIE: IF VERENIGING SELECTED AND CHECKBOX CHECKED -> FOUTMELDING

function vereniging_bestaat_niet()
{
    aantal_plekken();
    var check = document.getElementById("verenigingnaam").value;
    if (check != "") {
        document.getElementById("error_container").innerHTML = '<div id="error"><b>FOUTMELDING:</b> Je hebt wel een vereniging geselecteerd. Als je vereniging er inderdaad niet tussen staat, deselecteer dan eerst de vereniging!</div>';
        document.getElementById("verenigingstaaternietbijikbenzozielig").checked = false;
    }
    else {
        document.getElementById("inschrijven_contactpersoon").style.display = 'none';
        var check1 = document.getElementById("verenigingstaaternietbijikbenzozielig").checked;
        var x = document.getElementById("inschrijven_nieuwe_vereniging").innerHTML;
        if (check1 == true) {
            document.getElementById('inschrijven_nieuwe_vereniging').style.display = '';
            document.getElementById('inschrijven_nieuwe_vereniging').innerHTML = '<div class="fadein">'+x+'</div>';
        }
        else if (check1 == false) {
            document.getElementById('inschrijven_nieuwe_vereniging').style.display = 'none';
            document.getElementById('inschrijven_vereniging_header').className = '';
        }
    }
}

// FUNCTIES VOOR VERENIGING FORMULIER

function check_vereniging()
{
    aantal_plekken();
    var theForm = document.forms["vereniging"];
    var check1 = document.getElementById('verenigingsnaam').value;
    var check2 = document.getElementById('verenigingsplaats').value;
    var check3 = theForm.elements["verenigingnaam"].value;
    if ((check3 === "" || check3 === null) && check1 !== "" && check2 !== "" && check1 !== null && check2 !== null) {
        show_contactpersoon();
        document.getElementById('inschrijven_vereniging_header').className = 'success';
    }
    else {
        if (check3 !== "") {
            document.getElementById("verenigingstaaternietbijikbenzozielig").checked = false;
            show_contactpersoon();
            document.getElementById('verenigingstaaternietbijikbenzozielig').checked = false;
            document.getElementById('inschrijven_nieuwe_vereniging').style.display = 'none';
            document.getElementById('inschrijven_vereniging_header').className = 'success';
        }
        else {
            document.getElementById('inschrijven_contactpersoon').style.display = 'none';
            document.getElementById('inschrijven_reserveren').style.display = 'none';
            document.getElementById('inschrijven_vereniging_header').className = '';
            document.getElementById('inschrijven_contactpersoon_header').className = '';
        }
    }
}

function show_contactpersoon()
{
    aantal_plekken();
    var z = document.getElementById('inschrijven_contactpersoon').style.display;
    if (z !== '') {
        var x = document.getElementById('inschrijven_contactpersoon').innerHTML;
        document.getElementById('inschrijven_contactpersoon').style.display='';
        document.getElementById('inschrijven_contactpersoon').innerHTML = '<div class="appear">'+x+'</div>';
    }
}

function update_vereningsnaam()
{
    aantal_plekken();
    var a = (document.getElementById('verenigingnaam').value.split("_"))[1];
    var b = document.getElementById("verenigingsnaam");
    var c = document.getElementById("verenigingsplaats");
    var d = b.value + ', ' + c.value;

    if (a) {
        var vereniging = a;
    }
    else {
        var vereniging = d.toUpperCase();
    }
    document.getElementById('inschrijven_verenigingsnaam').innerHTML = vereniging;
}

// FUNCTIES VOOR CONTACTPERSOON FORMULIER

function check_contactpersoon()
{
    aantal_plekken();
    var theForm = document.forms["inschrijven_contactpersoon"];
    var voornaam = theForm.elements["voornaam"];
    var achternaam = theForm.elements["achternaam"];
    var email = theForm.elements["email"];
    var username = theForm.elements["username"];
    var wachtwoord = theForm.elements["wachtwoord"];
    var wachtwoord2 = theForm.elements["wachtwoord2"];
    if (voornaam.value !== "" && achternaam.value !== "" && email.value !== "" && username.value !== "" && wachtwoord.value !== "" && wachtwoord2.value !== "" && wachtwoord.value == wachtwoord2.value) {
        show_reserveren();
        document.getElementById('inschrijven_contactpersoon_header').className = 'success';
    }
    else {
        document.getElementById('inschrijven_reserveren').style.display = 'none';
        document.getElementById('inschrijven_contactpersoon_header').className = '';
    }
}

function show_reserveren()
{
    aantal_plekken();
    var z = document.getElementById('inschrijven_reserveren').style.display;
    if (z !== '') {
        var x = document.getElementById('inschrijven_reserveren').innerHTML;
        document.getElementById('inschrijven_reserveren').style.display='';
        document.getElementById('inschrijven_reserveren').innerHTML = '<div class="appear">'+x+'</div>';
    }
}

// FUNCTIES VOOR HET RESERVEREN

function update_reserveer_display()
{
    aantal_plekken();
    var z = document.getElementById('reserveer_aantal_invoer').value;
    if (z !== '') {
        if (z == 1) {
            document.getElementById('reserveer_aantal').innerHTML = '1 plek reserveren!';
        }
        else {
            document.getElementById('reserveer_aantal').innerHTML = z+' plekken reserveren!';
        }
    }
    else {
        document.getElementById('reserveer_aantal').innerHTML = '0 plekken reserveren!';
    }
}