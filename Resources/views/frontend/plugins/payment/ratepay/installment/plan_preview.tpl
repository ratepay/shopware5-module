{*
Copyright (c) Ratepay GmbH

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*}
{namespace name="frontend/plugins/payment/ratepay"}
<input type="hidden" name="ratepay[installment][payment_type]" id="rp-payment-type" value="{$ratepay.defaults.paymentType}">

{block name="ratepay_payment_method__ic_plan_preview"}
    <div class="preview-heading">{$ratepay.translations.rp_personal_calculation}</div>
    <table class="preview-table ratepay-installment_plan-table">
        <tbody>
        <tr>
            <td>
                <span>{$ratepay.translations.rp_cash_payment_price}</span><br />
                <span class="small">{$ratepay.translations.rp_mouseover_cash_payment_price}</span>
            </td>
            <td>{$ratepay.plan.amount} &euro;</td>
        </tr>
        <tr>
            <td>
                <span>{$ratepay.plan.numberOfRatesFull} {$ratepay.translations.rp_monthly_installment_pl} {$ratepay.translations.rp_each}</span><br />
                <span class="small">{$ratepay.translations.rp_mouseover_duration_month}</span>
            </td>
            <td>{$ratepay.plan.rate} &euro;</td>
        </tr>
        <tr>
            <td>
                <span>{$ratepay.translations.rp_total_amount}</span><br />
                <span class="small">{$ratepay.translations.rp_mouseover_total_amount}</span>
            </td>
            <td>{$ratepay.plan.totalAmount} &euro;</td>
        </tr>
        </tbody>
    </table>
    <div class="preview-after-table">
        <a href="#" id="rpChangeInstallmentDetails" class="preview-display-more">
            {s name="instalmentShowDetails"}Display details about installment / Change monthly rate{/s}&nbsp;<span class="icon--arrow-right"></span>
        </a>
    </div>
{/block}
