function aantal_plekken() {
    $.ajax({
        type: 'get',
        url: Routing.generate('aantalVrijePlekkenAjaxCall'),
        success: function (data) {
            document.getElementById("aantal_vrije_plekken").innerHTML = data;
        }
    });
}

// FUNCTIE: IF VERENIGING SELECTED AND CHECKBOX CHECKED -> FOUTMELDING

function vereniging_bestaat_niet() {
    aantal_plekken();
    var check = document.getElementById("verenigingnaam").value;
    if (check != "") {
        document.getElementById("error_container").innerHTML = '<div id="error"><span id="vereniging_error"><b>FOUTMELDING:</b> Je hebt wel een' +
            ' vereniging geselecteerd. Als je vereniging er inderdaad niet tussen staat, deselecteer dan eerst de vereniging!</span></div>';
        document.getElementById("verenigingstaaternietbijikbenzozielig").checked = false;
    }
    else {
        if (document.getElementById("general_contact_error")) {
            document.getElementById("error_container").innerHTML = '';
        }
        document.getElementById("inschrijven_contactpersoon").style.display = 'none';
        var check1 = document.getElementById("verenigingstaaternietbijikbenzozielig").checked;
        var x = document.getElementById("inschrijven_nieuwe_vereniging").innerHTML;
        if (check1 == true) {
            document.getElementById('inschrijven_nieuwe_vereniging').style.display = '';
            document.getElementById('inschrijven_nieuwe_vereniging').innerHTML = '<div class="fadein">' + x + '</div>';
        }
        else if (check1 == false) {
            document.getElementById('inschrijven_nieuwe_vereniging').style.display = 'none';
            document.getElementById('inschrijven_vereniging_header').className = '';
        }
    }
}

// FUNCTIES VOOR VERENIGING FORMULIER

function check_vereniging() {
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

function show_contactpersoon() {
    aantal_plekken();
    var z = document.getElementById('inschrijven_contactpersoon').style.display;
    if (z !== '') {
        var x = document.getElementById('inschrijven_contactpersoon').innerHTML;
        document.getElementById('inschrijven_contactpersoon').style.display = '';
        document.getElementById('inschrijven_contactpersoon').innerHTML = '<div class="appear">' + x + '</div>';
    }
}

function update_vereningsnaam() {
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

function check_contactpersoon() {
    aantal_plekken();
    var theForm = document.forms["inschrijven_contactpersoon"];
    var voornaam = theForm.elements["voornaam"];
    var achternaam = theForm.elements["achternaam"];
    var email = theForm.elements["email"];
    var telefoonnummer = theForm.elements["telefoonnummer"];
    var username = theForm.elements["username"];
    var wachtwoord = theForm.elements["wachtwoord"];
    var wachtwoord2 = theForm.elements["wachtwoord2"];
    if (voornaam.value !== "" && achternaam.value !== "" && email.value !== "" && username.value !== "" && wachtwoord.value !== "" && wachtwoord2.value !== "" && wachtwoord.value == wachtwoord2.value) {
        if (validate_contact_fields()) {
            show_reserveren();
            document.getElementById('inschrijven_contactpersoon_header').className = 'success';
            if (document.getElementById("general_contact_error")) {
                document.getElementById("error_container").innerHTML = '';
            }
        } else {
            document.getElementById("error_container").innerHTML = '<div id="error"><span id="general_contact_error"><b>FOUTMELDING:</b> Niet alle' +
                ' velden zijn correct ingevoerd!</span></div>';
        }
    }
    else {
        document.getElementById("error_container").innerHTML = '<div id="error"><span id="general_contact_error"><b>FOUTMELDING:</b> Nog niet alle' +
            ' velden zijn ingevoerd!</span></div>';
        document.getElementById('inschrijven_reserveren').style.display = 'none';
        document.getElementById('inschrijven_contactpersoon_header').className = '';
    }
}

function validate_contact_fields() {

    return (validate_email(false))
}

function validate_email(show_error_messages) {
    var validated = false;
    var email = document.getElementById("email");
    var re = /^((?:[a-zA-Z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&'*+/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-zA_Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-zA-Z0-9-]*[a-zA-Z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\]))$/;
    if (!email.value) {
        email.className = 'error';
        if (show_error_messages) {
            document.getElementById("error_container").innerHTML = '<div id="error"><span' +
                ' id="email_error" class="ddd"><b>FOUTMELDING:</b> Je hebt' +
                ' geen emailadres ingevoerd!</span></div>';
        }
    } else if (re.test(email.value)) {
        email.className = 'succesIngevuld';
        validated = true;
        if (document.getElementById("email_error")) {
            document.getElementById("error_container").innerHTML = '';
        }
    } else {
        email.className = 'error';
        if (show_error_messages) {
            document.getElementById("error_container").innerHTML = '<div id="error"><span id="email_error" class="' + Date.now() + '"><b>FOUTMELDING:</b> Je hebt geen' +
                ' geldig emailadres ingevoerd!</span></div>';
        }
    }
    return validated;
}

function show_reserveren() {
    aantal_plekken();
    var z = document.getElementById('inschrijven_reserveren').style.display;
    if (z !== '') {
        var x = document.getElementById('inschrijven_reserveren').innerHTML;
        document.getElementById('inschrijven_reserveren').style.display = '';
        document.getElementById('inschrijven_reserveren').innerHTML = '<div class="appear">' + x + '</div>';
    }
}

// FUNCTIES VOOR HET RESERVEREN

function update_reserveer_display() {
    aantal_plekken();
    var z = document.getElementById('reserveer_aantal_invoer').value;
    if (z !== '') {
        if (z == 1) {
            document.getElementById('reserveer_aantal').innerHTML = '1 plek reserveren!';
        }
        else {
            document.getElementById('reserveer_aantal').innerHTML = z + ' plekken reserveren!';
        }
    }
    else {
        document.getElementById('reserveer_aantal').innerHTML = '0 plekken reserveren!';
    }
}