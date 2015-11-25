{extends file="frontend/checkout/confirm.tpl"}
{block name="frontend_index_content"}
    <style>
        .content-main {
            min-height: 0rem;
        }*/
    </style>
    <div class="container block-group">
        <div>
            <p style="margin-top: 3rem;" class="center">
                <span style="color: #999;">
                    {s namespace=RatePAY name=errorpagetext}
                        Leider ist eine Bezahlung mit RatePAY nicht möglich. Diese Entscheidung ist auf Grundlage einer automatisierten
                        Datenverarbeitung getroffen worden. Einzelheiten hierzu finden Sie in der
                    {/s}
                </span>

                {if $sUserData.additional.country.countryiso == 'DE'}
                    <a href='http://www.ratepay.com/zusaetzliche-geschaeftsbedingungen-und-datenschutzhinweis' target="_blank">RatePAY-Datenschutzerklärung</a>
                {elseif $sUserData.additional.country.countryiso == 'AT'}
                    <a href='http://www.ratepay.com/zusaetzliche-geschaeftsbedingungen-und-datenschutzhinweis-at' target="_blank">RatePAY-Datenschutzerklärung</a>
                {else}
                    <a href='http://www.ratepay.com/zusaetzliche-geschaeftsbedingungen-und-datenschutzhinweis' target="_blank">RatePAY-Datenschutzerklärung</a>
                {/if}

            </p>
        </div>
        <div class="actions">
            <a class="btn is--center is--large" href="{url controller=checkout action=cart}">
                {s namespace=RatePAY name=errorpagecart}Warenkorb anzeigen{/s}
            </a>
            <a class="btn is--primary is--center is--large" href="{url controller=account action=payment sTarget=checkout}">
                {s namespace=RatePAY name=errorpagepayment}Zahlart ändern{/s}
            </a>
        </div>
    </div>
{/block}
