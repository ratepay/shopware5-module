<?php
    /**
     * This program is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
     *
     * @package pi_ratepay_rate_calculator
     * Code by Ratepay GmbH  <http://www.ratepay.com/>
     */
    $pi_calculator = new PiRatepayRateCalc();

    $pi_calculator->unsetData();
    $pi_config = $pi_calculator->getRatepayRateConfig();
    $pi_monthAllowed = $pi_config['month_allowed'];
    $pi_monthAllowedArray = explode(',', $pi_monthAllowed);

    $pi_amount = $pi_calculator->getRequestAmount();
    $pi_language = $pi_calculator->getLanguage();
    $pi_firstday = $pi_calculator->getRequestFirstday();

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

        <div id="piRpHeader">
            <div class="piRpFullWidth">
                <h2 class="piRpH2"><?php echo $pi_lang_calculate_rates_now; ?></h2>
            </div>
            <br class="piRpClearFix"/>
        </div>

        <div id="piRpContentSwitch">
            <div class="piRpChooseRuntime">
                <?php echo $pi_lang_cash_payment_price_part_one; ?>:
                <span><b><?php echo $pi_amount; ?> &euro;</b></span>
                <?php echo $pi_lang_cash_payment_price_part_two; ?>
                <br/>
                <label for="firstInput" style='width: 440px;'>
                    <div class="piRpChooseInput" id="piRpChooseInputRate">
                        <input id="firstInput" class="piRpFloatLeft" type="radio" name="Zahlmethode" value="wishrate"
                               onClick="switchRateOrRuntime('rate');">
                    </div>
                    <div class="piRpNintyPercentWidth piRpFloatLeft"><?php echo $pi_lang_payment_text_wishrate; ?></div>
                </label>

                <div id="piRpContentTerm" class="piRpContent" style="display: none;">
                    <div class="piRpMarginTop">
                        <span
                            class="piRpVertAlignMiddle">
                            <?php echo $pi_lang_please . " " . $pi_lang_insert_wishrate; ?>:
                        </span>
                        <input name="" id="rate" class="piRpInput-amount" type="text">
                        <span class="piRpCurrency"> &euro;</span>
                        <input name="" onclick="piRatepayRateCalculatorAction('rate');"
                               value="<?php echo $pi_lang_calculate_runtime; ?>" id="piRpInput-button"
                               class="piRpInput-button" type="button">
                    </div>
                </div>
                <br class="piRpClearFix"/>
                <label for="secondInput" style='width: 440px;'>
                    <div class="piRpChooseInput" id="piRpChooseInputRuntime">
                        <input id="secondInput" class="piRpFloatLeft" type="radio" name="Zahlmethode" value="runtime"
                               onClick="switchRateOrRuntime('runtime');">
                    </div>
                    <div class="piRpNintyPercentWidth piRpFloatLeft"><?php echo $pi_lang_payment_text_runtime; ?></div>
                </label>

                <div id="piRpContentRuntime" class="piRpContent" style="display: none;">
                    <div class="piRpMarginTop">
                        <span class="piRpVertAlignMiddle" style="float: left;">
                            <?php echo $pi_lang_please . " " . $pi_lang_insert_runtime; ?>:
                        </span>
                        <select id="runtime" style='position: absolute; top: -4px; width: 277px; height: 23px;'>
                            <?php
                                foreach ($pi_monthAllowedArray as $pi_month) {
                                    echo '<option value="' . $pi_month . '">';
                                    if ($pi_month < 10) echo '&nbsp;';
                                    echo $pi_month . ' ' . $pi_lang_months . '</option>';
                                }
                            ?>
                        </select>
                        <input name="" onclick="piRatepayRateCalculatorAction('runtime');"
                               value="<?php echo $pi_lang_calculate_rate; ?>" type="button" id="piRpInput-buttonRuntime"
                               class="piRpInput-button2">
                    </div>
                </div>
                <br class="piRpClearFix"/>

                <div class="piRpContentSwitchDiv" id="piRpSwitchToTerm" class="piRpActive" style="display: none">
                    <span id="pirpspanrate">
                        <?php echo $pi_lang_insert_wishrate; ?> <?php echo $pi_lang_calculate_runtime; ?>
                    </span>
                    <input name="" value="<?php echo $pi_lang_calculate_runtime; ?>" type="button"
                           class="piRpInput-button piRpContentSwitchInput ">
                </div>
                <div class="piRpContentSwitchDiv" id="piRpSwitchToRuntime" style="display: none">
                    <span id="pirpspanruntime" class="pirpactive">
                        <?php echo $pi_lang_choose_runtime; ?> <?php echo $pi_lang_calculate_rate; ?>
                    </span>
                    <input name="" value="<?php echo $pi_lang_calculate_rate; ?>" type="button"
                           class="piRpInput-button piRpContentSwitchInput ">
                </div>
                <div id="piRpResultContainer"></div>
            </div>
        </div>
        <br class="piRpClearFix"/>
<?php
        if ($pi_config['payment_firstday'] == '2,28') {
?>
            <input type="hidden" id="paymentFirstday" name="paymentFirstday" value="2">
            <input type="hidden" id="firstdaySwitch" value="1">
<?php
        } else {
            if (!$pi_config['payment_firstday'] || empty($pi_config['payment_firstday'])) {
                $pi_config['payment_firstday'] = 28;
            }
?>
            <input type="hidden" id="paymentFirstday" value="<?php echo $pi_config['payment_firstday']; ?>">
<?php
        }
    }
?>