{* Ratepay error container *}
{block name="ratepay_frontend_error"}
    <div class="error alert is--error is--rounded is--hidden" style="margin-top: 20px;">

        <div class="alert--icon">
            <i class="icon--element icon--cross"></i>
        </div>

        {block name="frontend_global_messages_content"}
            <div class="alert--content is--strong" id="ratepay_error">
            </div>
        {/block}
    </div>
{/block}
