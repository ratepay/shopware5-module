Ext.define('Shopware.apps.RatepayProfileConfig.store.ProfileConfig', {
    extend: 'Shopware.store.Listing',

    configure: function () {
        return {
            controller: 'RatepayProfileConfig'
        };
    },

    model: 'Shopware.apps.RatepayProfileConfig.model.ProfileConfig'
});
