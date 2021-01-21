Ext.define('Shopware.apps.RatepayProfileConfig.model.ProfileConfig', {
    extend: 'Shopware.data.Model',

    configure: function () {
        return {
            controller: 'RatepayProfileConfig',
            detail: 'Shopware.apps.RatepayProfileConfig.view.detail.ProfileConfig'
        };
    },

    fields: [
        { name: 'id', type: 'int', useNull: true },
        { name: 'active', type: 'boolean' },
        { name: 'profileId', type: 'string' },
        { name: 'securityCode', type: 'string' },
        { name: 'sandbox', type: 'boolean' },
        { name: 'currencies', type: 'auto' },
        { name: 'countryCodesBilling', type: 'auto' },
        { name: 'countryCodesDelivery', type: 'auto' },
        { name: 'backend', type: 'boolean' },
        { name: 'shopId', type: 'int' }
    ],

    associations: [{
        relation: 'ManyToOne',
        field: 'shopId',
        type: 'hasMany',
        model: 'Shopware.apps.Base.model.Shop',
        name: 'getShop',
        associationKey: 'shop'
    }]
});
