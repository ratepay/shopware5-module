<?php
    /**
     * This program is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
     *
     * @package pi_ratepay_rate_calculator
     * Code by Ratepay GmbH  <http://www.ratepay.com/>
     */
    use RpayRatePay\Component\Service\ShopwareUtil;

    $pi_calculator = new PiRatepayRateCalc();

    $pi_calculator->unsetData();
    $pi_config = $pi_calculator->getRatepayRateConfig();
    $pi_monthAllowedArray = $pi_config['month_allowed'];

    $pi_amount = $pi_calculator->getRequestAmount();
    $pi_language = $pi_calculator->getLanguage();
    $pi_firstday = $pi_calculator->getRequestFirstday();

    if ($pi_config['payment_firstday'] && ! empty($pi_config['payment_firstday'])) {
        $serviceUtil = new ShopwareUtil();
        $debitPayType = $serviceUtil->getDebitPayType($pi_config['payment_firstday']);
    } else {
        $pi_config['payment_firstday'] = 28;
    }

    
    if ($pi_language == "DE") {
        require_once $calcPath . '/languages/german.php';
        $pi_currency = 'EUR';
        $pi_decimalSeperator = ',';
        $pi_thousandSeperator = '.';
    }
    else {
        require_once $calcPath . '/languages/english.php';
        $pi_currency = 'EUR';
        $pi_decimalSeperator = '.';
        $pi_thousandSeperator = ',';
    }

    $pi_amount = number_format($pi_amount, 2, $pi_decimalSeperator, $pi_thousandSeperator);

    if ($pi_calculator->getErrorMsg() != '') {
        if ($pi_calculator->getErrorMsg() == 'serveroff') {
            echo "<div>" . $pi_lang_server_off . "</div>";
        }
        else {
            echo "<div>" . $pi_lang_config_error_else . "</div>";
        }
    }
    else {
?>
    <div class="rpContainer">
    <?php if (count($pi_monthAllowedArray) > 1) { ?>
        <div class="row">
            <div class="col-md-10">
                <?php
                echo $rp_calculation_intro_part1;
                echo $rp_calculation_intro_part2;
                echo $rp_calculation_intro_part3;
                ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-5">
                <div class="panel panel-default">
                    <div class="panel-heading text-center" id="firstInput">
                        <h2><?php echo $rp_runtime_title; ?></h2>
                        <?php echo $rp_runtime_description; ?>
                    </div>
                    <input type="hidden" id="rate_elv" name="rate_elv" value="<?php echo $pi_rate_elv ?>">
                    <input type="hidden" id="rate" name="rate" value="<?php echo $pi_rate ?>">
                    <input type="hidden" id="month" name="month" value="">
                    <input type="hidden" id="mode" name="mode" value="">
                    <div class="panel-body">
                        <div class="btn-group btn-group-justified" role="group" aria-label="...">
                            <?php
                            foreach ($pi_monthAllowedArray AS $month) {
                                ?>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button class="btn btn-default rp-btn-runtime" type="button" onclick="piRatepayRateCalculatorAction('runtime', <?php echo $month; ?>);" id="piRpInput-buttonMonth-<?php echo $month; ?>" role="group"><?php echo $month; ?></button>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
            <div class="panel panel-default">
                <div class="panel-heading text-center" id="secondInput">
                    <h2><?php echo $rp_rate_title; ?></h2>
                    <?php echo $rp_rate_description; ?>
                </div>

                <div class="panel-body">
                    <div class="input-group input-group-sm">
                        <span class="input-group-addon">&euro;</span>
                        <input type="text" id="rp-rate-value" class="form-control" aria-label="Amount" />
                        <span class="input-group-btn">
                            <button class="btn btn-default rp-btn-rate" onclick="piRatepayRateCalculatorAction('rate', 0);" type="button" id="piRpInput-buttonRuntime"><?php echo $rp_calculate_rate; ?></button>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <?php } else { ?>
        <div class="row">
            <div class="col-md-10">
                <input type="hidden" id="rate_elv" name="rate_elv" value="<?php echo $pi_rate_elv ?>">
                <input type="hidden" id="rate" name="rate" value="<?php echo $pi_rate ?>">
                <input type="hidden" id="month" name="month" value="<?php echo $pi_monthAllowedArray[0]; ?>">
                <input type="hidden" id="mode" name="mode" value="runtime">
                <input type="hidden" id="piRpInput-buttonMonth-<?php echo $pi_monthAllowedArray[0]; ?>" role="group">
            </div>
            <br/>
         </div>
        <?php }  ?>
    </div>
    <br style="clear: both"/>
    <div class="row">
        <div class="col-md-11" id="piRpResultContainer"></div>
    </div>
    <br style="clear: both"/>
<?php
        if ($debitPayType == 'FIRSTDAY-SWITCH') {
?>
            <input type="hidden" id="paymentFirstday" name="paymentFirstday" value="2">
            <input type="hidden" id="firstdaySwitch" value="1">
<?php
        } else {
?>
            <input type="hidden" id="paymentFirstday" value="<?php echo $pi_config['payment_firstday']; ?>">
<?php
        }
    }
?>