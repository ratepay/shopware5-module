//{namespace name="backend/ratepay/profile_config/list"}

Ext.define('Shopware.apps.RatepayProfileConfig.view.list.Window', {
    extend: 'Shopware.window.Listing',
    alias: 'widget.ratepay-profile-config-list-window',
    height: 450,
    title: '{s name=window_title}{/s}',

    configure: function () {
        return {
            listingGrid: 'Shopware.apps.RatepayProfileConfig.view.list.ProfileConfig',
            listingStore: 'Shopware.apps.RatepayProfileConfig.store.ProfileConfig'
        };
    }
});
