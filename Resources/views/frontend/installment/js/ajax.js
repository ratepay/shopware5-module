/**
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @package pi_ratepay_rate_calculator
 * Code by Ratepay GmbH  <http://www.ratepay.com/>
 */

document.onreadystatechange = function () {
    if (document.readyState == "complete") {
        if (document.getElementById('month').value !== "" && document.getElementById('mode').value == "runtime") {
            piRatepayRateCalculatorAction('runtime', document.getElementById('month').value);
        }
    }
}

function piRatepayRateCalculatorAction(mode, month) {
    var calcValue;
    var calcMethod;
    var paymentFirstday;

    var html;

    if (document.getElementById('paymentFirstday') && document.getElementById('paymentFirstday').value == 2) {
        document.getElementById('debitDetails').style.display = 'block';
    }

    if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
        xmlhttp = new XMLHttpRequest();
    } else {// code for IE6, IE5
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }

    if (mode == 'rate') {
        calcValue = document.getElementById('rp-rate-value').value;
        calcMethod = 'calculation-by-rate';
        paymentFirstday = document.getElementById('paymentFirstday').value;

    } else if (mode == 'runtime') {
        calcValue = month;
        document.getElementById('month').value = month;
        calcMethod = 'calculation-by-time';
        paymentFirstday = document.getElementById('paymentFirstday').value;
    }

    var getParams = "?"
        + "calcValue=" + calcValue
        + "&calcMethod=" + calcMethod
        + "&paymentFirstday=" + paymentFirstday;

    xmlhttp.open("GET", pi_ratepay_rate_ajax_path + "calcRequest" + getParams, false);

    xmlhttp.setRequestHeader("Content-Type",
        "application/x-www-form-urlencoded");

    xmlhttp.send();

    if (xmlhttp.responseText != null) {
        html = xmlhttp.responseText;
        document.getElementById('piRpResultContainer').innerHTML = html;
        document.getElementById('piRpResultContainer').style.display = 'block';
        document.getElementById('piRpResultContainer').style.padding = '3px 0 0 0';
        //document.getElementById('piRpSwitchToTerm').style.display = 'none';
        setTimeout("piSetLoaderBack()", 300);

    }
}

function piLoadrateCalculator() {
    var html;

    if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
        xmlhttp = new XMLHttpRequest();
    } else {// code for IE6, IE5
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.open("GET", pi_ratepay_rate_ajax_path + "calcDesign", false);

    xmlhttp.setRequestHeader("Content-Type",
        "application/x-www-form-urlencoded");

    xmlhttp.send();

    if (xmlhttp.responseText != null) {
        html = xmlhttp.responseText;
        document.getElementById('pirpmain-cont').innerHTML = html;
    }
}

function piSetLoaderBack() {
    /*document.getElementById('piRpInput-buttonRuntime').className = 'piRpInput-button';
    document.getElementById('piRpInput-button').className = 'piRpInput-button';
    document.getElementById('piRpInput-buttonRuntime').value = piRpInputValueRuntime;
    document.getElementById('piRpInput-button').value = piRpInputValue;*/
}

function piLoadrateResult() {
    var html;

    if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
        xmlhttp = new XMLHttpRequest();
    } else {// code for IE6, IE5
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.open("GET", pi_ratepay_rate_ajax_path + "calcRequest", false);

    xmlhttp.setRequestHeader("Content-Type",
        "application/x-www-form-urlencoded");

    xmlhttp.send();

    if (xmlhttp.responseText != null) {
        html = xmlhttp.responseText;
        document.getElementById('pirpmain-cont').innerHTML = html;
    }
}
