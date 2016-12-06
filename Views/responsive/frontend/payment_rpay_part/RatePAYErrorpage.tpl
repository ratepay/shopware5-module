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
                    {$rpCustomerMsg}
                </span>

            </p>
        </div>
        <div class="actions">
            <a class="btn is--center is--large" href="{url controller=checkout action=cart}">
                {s namespace=RatePAY name=errorpagecart}Warenkorb anzeigen{/s}
            </a>
            <a class="btn is--primary is--center is--large" href="{url controller=account action=payment sTarget=checkout}">
                {s namespace=RatePAY name=errorpagepayment}Zahlungsart ändern{/s}
            </a>
        </div>
    </div>
{/block}
