<!--
  ~ Copyright (c) 2020 RatePAY GmbH
  ~
  ~ For the full copyright and license information, please view the LICENSE
  ~ file that was distributed with this source code.
  -->

<table class="table table-striped">
    <tr>
        <td class="text-center text-uppercase headline" colspan="2">
            {$ratepay.translations.rp_personal_calculation}
        </td>
    </tr>

    <tr>
        <td class="warning small text-center" colspan="2">
            {$ratepay.plan.responseText}
            <br/>
            {$ratepay.translations.rp_calulation_example}
        </td>
    </tr>

    <tr>
        <td colspan="2" class="small text-right">
            <a class="rp-link" id="rp-show-installment-plan-details">{$ratepay.translations.rp_showInstallmentPlanDetails} <i class="small icon--arrow-down"></i></a>
            <a class="rp-link" id="rp-hide-installment-plan-details">{$ratepay.translations.rp_hideInstallmentPlanDetails} <i class="small icon--arrow-up"></i></a>
        </td>
    </tr>

    <tr class="rp-installment-plan-details">
        <td class="rp-installment-plan-title">
            {$ratepay.translations.rp_cash_payment_price}
            <p class="rp-installment-plan-description small">{$ratepay.translations.rp_mouseover_cash_payment_price}</p>
        </td>
        <td class="text-right">
            {$ratepay.plan.amount} &euro;
        </td>
    </tr>

    <tr class="rp-installment-plan-details">
        <td class="rp-installment-plan-title">
            {$ratepay.translations.rp_service_charge}
            <p class="rp-installment-plan-description small">{$ratepay.translations.rp_mouseover_service_charge}</p>
        </td>
        <td class="text-right">
            {$ratepay.plan.serviceCharge} &euro;
        </td>
    </tr>

    <tr class="rp-installment-plan-details">
        <td class="rp-installment-plan-title">
            {$ratepay.translations.rp_effective_rate}
            <p class="rp-installment-plan-description small">{$ratepay.translations.rp_mouseover_effective_rate}</p>
        </td>
        <td class="text-right">
            {$ratepay.plan.annualPercentageRate} %
        </td>
    </tr>

    <tr class="rp-installment-plan-details">
        <td class="rp-installment-plan-title">
            {$ratepay.translations.rp_debit_rate}
            <p class="rp-installment-plan-description small">{$ratepay.translations.rp_mouseover_debit_rate}</p>
        </td>
        <td class="text-right">
            {$ratepay.plan.monthlyDebitInterest} %
        </td>
    </tr>
    <tr class="rp-installment-plan-details">
        <td class="rp-installment-plan-title">
            {$ratepay.plan.translations.rp_interest_amount}
            <p class="rp-installment-plan-description small">{$ratepay.translations.rp_mouseover_interest_amount}</p>
        </td>
        <td class="text-right">
            {$ratepay.plan.interestAmount} &euro;
        </td>
    </tr>

    <tr class="rp-installment-plan-details separator">
        <td colspan="2"></td>
    </tr>

    <tr class="rp-installment-plan-no-details">
        <td class="rp-installment-plan-title">
            {$ratepay.plan.numberOfRatesFull} {$ratepay.translations.rp_monthly_installment_pl} {$ratepay.translations.rp_each}
            <p class="rp-installment-plan-description small">{$ratepay.translations.rp_mouseover_duration_month}</p>
        </td>
        <td class="text-right">
            {$ratepay.plan.rate} &euro;
        </td>
    </tr>

    <tr class="rp-installment-plan-details">
        <td class="rp-installment-plan-title">
            1.-{$ratepay.plan.numberOfRatesFull - 1}. {$ratepay.translations.rp_monthly_installment_sg} {$ratepay.translations.rp_each}
            <p class="rp-installment-plan-description small">{$ratepay.translations.rp_mouseover_duration_month}</p>
        </td>
        <td class="text-right">
            {$ratepay.plan.rate} &euro;
        </td>
    </tr>

    <tr class="rp-installment-plan-details">
        <td class="rp-installment-plan-title">
            {$ratepay.plan.numberOfRatesFull}. {$ratepay.translations.rp_monthly_installment_sg}
            <p class="rp-installment-plan-description small">{$ratepay.translations.rp_mouseover_last_rate}</p>
        </td>
        <td class="text-right">
            {$ratepay.plan.lastRate} &euro;
        </td>
    </tr>

    <tr>
        <td class="rp-installment-plan-title">
            {$ratepay.translations.rp_total_amount}
            <p class="rp-installment-plan-description small">{$ratepay.translations.rp_mouseover_total_amount}</p>
        </td>
        <td class="text-right">
            {$ratepay.plan.totalAmount} &euro;
        </td>
    </tr>

</table>
