{*
 we need this file to make sure that the prems-osc will recognise, that we have neccessary field, which the user has to fill.
 cause the field are not available during the state when the Ratepay methods has not been selected, we need to simulate
 a input field.
 The OSC is looking for an element with the selector `.method--bankdata` and searchs for an input-element which does not
 have the type `hidden`.
 Only when this requirements matches the OSC will not submit the payment-form. Then it will load the edit-page (internal)

 So this file does not contain real template data, only a template which triggers the correct functionallity of OSC.
 *}
{if $useOnePageCheckout}
    <input type="text" style="display: none!important;"/>
{/if}
