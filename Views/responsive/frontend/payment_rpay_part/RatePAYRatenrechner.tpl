<link type="text/css" rel="stylesheet" href="{link file='frontend/installment/css/style.css' fullPath}"/>
<script type="text/javascript">
    pi_ratepay_rate_calc_path = "{link file='frontend/installment/' fullPath}";
    {if $smarty.server.HTTPS eq '' || $smarty.server.HTTPS eq 'off'}
        pi_ratepay_rate_ajax_path = "{url controller="RpayRatepay" action=""}";
    {else}
        pi_ratepay_rate_ajax_path = "{url controller="RpayRatepay" action="" forceSecure}";
    {/if}
</script>
<script type="text/javascript" src="{link file='frontend/installment/js/mouseaction.js' fullPath}"></script>
<script type="text/javascript" src="{link file='frontend/installment/js/layout.js' fullPath}"></script>
<script type="text/javascript" src="{link file='frontend/installment/js/ajax.js' fullPath}"></script>
<script type="text/javascript" src="{link file='frontend/installment/js/rateswitch.js' fullPath}"></script>
<div id="pirpmain-cont" name="pirpratenrechnerContent"></div>
<script type="text/javascript">
    if (document.getElementById('pirpmain-cont')) {
        piLoadrateCalculator();
    }
</script>
