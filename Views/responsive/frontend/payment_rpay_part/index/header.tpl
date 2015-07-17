{extends file="parent:frontend/index/header.tpl"}

{block name="frontend_index_header_css_print" append}
    <link type="text/css" media="screen, projection" rel="stylesheet"
          href="{link file='engine/Shopware/Plugins/Community/Frontend/RpayRatePay/Views/responsive/frontend/_public/src/styles/ratepay.css' fullPath}"/>
{/block}

{block name="frontend_index_header_javascript" append}

    {if $sUserData.additional.payment.name == 'rpayratepaydebit' }
    {* Javascript for SepaDirectDebit *}
        <script language='javascript'>
            {* Disable confirmation button if sepadirectdebit tac are not checked *}
            $(document).ready(function () {
                var button = $('button[type=submit]');

                button.attr('disabled', 'disabled');
                button.css({ opacity: 0.5 });
                button.attr('title', '{s namespace=RatePAY name="ratepayAgbMouseover"}Um RatePAY nutzen zu können müssen sie den AGBs von RatePAY zustimmen{/s}');
                $('#ratepay_agb').click(function () {
                    if ($(this).prop('checked')) {
                        button.removeAttr('disabled');
                        button.removeAttr('title');
                        button.css({ opacity: 1.0 });
                    } else {
                        button.attr('disabled', 'disabled');
                        button.attr('title', '{s namespace=RatePAY name="ratepayAgbMouseover"}Um RatePAY nutzen zu können müssen sie den AGBs von RatePAY zustimmen{/s}');
                        button.css({ opacity: 0.5 });
                    }
                });
            });

            {* Handle BIC Field ( only fade in for AT ) *}
            $(document).ready(function () {

                var blzInput       = $(":input#ratepay_debit_bankcode");
                var blzInputLabel  = $("label[for='ratepay_debit_bankcode']");
                var accNumberInput = $(":input#ratepay_debit_accountnumber");

                blzInput.prop('disabled', true);
                blzInput.hide();
                blzInputLabel.hide();

                accNumberInput.keyup(function () {
                    if ($(this).val().match(/^\d+$/)) {
                        blzInput.prop('disabled', false);
                        blzInput.show();
                        blzInputLabel.show();
                    } else if ($(this).val().match(/at/i)) {
                        blzInput.prop('disabled', false);
                        blzInput.show();
                        blzInputLabel.show();
                    }
                    else {
                        blzInput.prop('disabled', true);
                        blzInput.hide();
                        blzInputLabel.hide();
                    }
                })
            });

        </script>

    {/if}

    <script language='javascript'>

        $(document).ready(function () {

            {if $ratepayValidateisAgeValid != 'true'}
            $("#ratepay_error").text("{s namespace=RatePAY name=invalidAge}Bitte überprüfen sie die Eingabe ihres Geburtstdatums. Sie müssen mindestens 18 Jahre alt sein!{/s}");
            $("#ratepay_error").parent().removeClass("is--hidden");
            {/if}
            {if $ratepayErrorRatenrechner == 'true'}
            $("#ratepay_error").text("{s namespace=RatePAY name=errorRatenrechner}Bitte lassen Sie sich den Ratenplan berechnen!{/s}");
            $("#ratepay_error").parent().removeClass("is--hidden");
            {/if}

            /* returns correct YYYY-MM-dd dob */
            Date.prototype.yyyymmdd = function () {
                var yyyy = this.getFullYear().toString();
                var mm = (this.getMonth() + 1).toString();
                var dd = this.getDate().toString();
                return yyyy + '-' + (mm[1] ? mm : "0" + mm[0]) + '-' + (dd[1] ? dd : "0" + dd[0]);
            };

            /* returns age */
            function getAge(dateString) {
                var today = new Date();
                var birthDate = new Date(dateString);
                var age = today.getFullYear() - birthDate.getFullYear();
                var m = today.getMonth() - birthDate.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                return age;
            }

            /* proceed if tac are checked and button clicked */
            $("button[type=submit]").click(function () {
                if($('input#sAGB').is(':checked')) {

                    var requestParams = 'userid=' + "{$sUserData.billingaddress.userID}";
                    var dob = false;
                    var userUpdate = true;
                    var error = false;
                    var errorMessage = '{s namespace=RatePAY name=invaliddata}Bitte vervollständigen Sie die Daten.{/s}';
                    /** show the modal window */
                    $("div.ratepay-overlay").show();

                    /* handle all normal inputs */
                    $('input[id^="ratepay_"]').each(function () {
                        requestParams += '&' + $(this).attr('id') + '=' + $(this).val();
                        if ($(this).val() == '') {
                            error = true;
                            userUpdate = false;
                        }

                        /* validate sepa direct debit - no error if no blz is net @toDo: fix for international direct debits */

                        if ($(this).attr('id') == 'ratepay_debit_bankcode' && !$(":input#ratepay_debit_accountnumber").val().match(/^\d+$/)) {
                            error = false;
                            userUpdate = true;
                        }

                    });

                    /* dob validation */
                    if ($('#ratepay_birthyear').val() != '' && $('#ratepay_birthmonth').val() != '' && $('#ratepay_birthday').val() != '') {
                        dob = new Date($('#ratepay_birthyear').val() + '-' + $('#ratepay_birthmonth').val() + '-' + $('#ratepay_birthday').val());

                        /* validate age */
                        if (getAge(dob) < 18 || getAge(dob) > 120) {
                            error = true;
                            userUpdate = false;
                            errorMessage = '{s namespace=RatePAY name=dobtooyoung}Für eine Bezahlung mit RatePAY müssen Sie mindestens 18 Jahre alt sein.{/s}'
                        }

                        requestParams += '&ratepay_dob=' + dob.yyyymmdd();
                    } else {
                        error = true;
                        userUpdate = false;
                    }

                    /* phone number validation */
                    if($('#ratepay_phone').val() != '') {
                        {literal}
                        var regex = /[0-9-()+]{3,20}/;
                        {/literal}

                        var phoneNumber = $('#ratepay_phone').val().replace(/ /g,'');

                        if($('#ratepay_phone').val().length < 6 ||  ( phoneNumber.match(regex) != phoneNumber ) )
                        {
                            error = true;
                            userUpdate = false;
                            errorMessage = '{s namespace=RatePAY name=phonenumbernotvalid}Für eine Bezahlung mit RatePay müssen Sie eine gültige Telefonnummer angeben. Die Nummer muss mindestens 6 Zeichen lang sein und darf Sonderzeichen wie - und + enthalten.{/s}'
                        }
                    } else {
                        error = true;
                        userUpdate = false;
                    }

                    {if $sUserData.additional.payment.name == 'rpayratepaydebit' }
                    if($('#ratepay_debit_accountnumber').val() == '' || $('#ratepay_debit_accountholder').val() == '') {
                        error = true;
                        userUpdate = false;
                        errorMessage = '{s namespace=RatePAY name=bankdatanotvalid}Für eine Bezahlung mit RatePay müssen Sie gültige Bankverbindung angeben.{/s}'
                    }
                    {/if}

                    /* error handler */
                    if (error) {

                        /** hide the modal window */
                        $("div.ratepay-overlay").hide();

                        $("#ratepay_error").text(errorMessage);
                        $("#ratepay_error").parent().removeClass("is--hidden");
                        $('html, body').animate({
                            scrollTop: $("#ratepay_error").offset().top - 100
                        }, 1000);
                        return false;

                    } else {
                        $("#ratepay_error").parent().hide();
                    }

                    /* update user */
                    if (userUpdate) {
                        $.ajax({
                            type: "POST",
                            async: false,
                            {if $smarty.server.HTTPS eq '' || $smarty.server.HTTPS eq 'off'}
                            url: "{url controller='RpayRatepay' action='saveUserData'}",
                            {else}
                            url: "{url controller='RpayRatepay' action='saveUserData' forceSecure}",
                            {/if}
                            data: requestParams
                        }).done(function (msg) {
                            if (msg == 'OK') {
                                console.log('{s namespace=RatePAY name=updateUserSuccess}UserDaten erfolgreich aktualisiert.{/s}');
                            } else {
                                console.log('{s namespace=RatePAY name=updateUserSuccess}Fehler beim Aktualisieren der UserDaten. Return: {/s}' + msg);
                            }
                        });
                    }

                }


            });
        });
    </script>

{/block}