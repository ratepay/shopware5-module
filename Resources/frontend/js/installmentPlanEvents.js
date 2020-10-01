/*
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

;(function () {
    jQuery(document).on("click", "#rp-show-installment-plan-details", function (event) {
        event.preventDefault();
        jQuery('.rp-installment-plan-details').show();
        jQuery('.rp-installment-plan-no-details').hide();
        jQuery(this).hide();
        jQuery('#rp-hide-installment-plan-details').show();
    });

    jQuery(document).on("click", "#rp-hide-installment-plan-details", function (event) {
        event.preventDefault();
        jQuery('.rp-installment-plan-details').hide();
        jQuery('.rp-installment-plan-no-details').show();
        jQuery(this).hide();
        jQuery('#rp-show-installment-plan-details').show();
    });
})();

