//{namespace name="backend/ratepay/profile_config/list"}

Ext.define('Shopware.apps.RatepayProfileConfig.view.list.ProfileConfig', {
    extend: 'Shopware.grid.Panel',
    alias: 'widget.ratepay-profile-config-listing-grid',
    region: 'center',

    configure: function () {
        return {
            detailWindow: 'Shopware.apps.RatepayProfileConfig.view.detail.Window',
            columns: {
                active: {
                    header: '{s name=column_active}{/s}',
                    dataIndex: 'active'
                },
                profileId: {
                    header: '{s name=column_profile_id}{/s}',
                    dataIndex: 'profileId'
                },
                shopId: {
                    header: '{s name=column_shop}{/s}',
                    // dataIndex: 'shopId',
                    xtype: 'templatecolumn',
                    tpl: '{ shop.name }'
                },
                backend: {
                    header: '{s name=column_backend}{/s}',
                    dataIndex: 'backend'
                },
                currencies: {
                    header: '{s name=column_currency}{/s}',
                    dataIndex: 'currencies',
                    renderer: function (v) {
                        if (Ext.isArray(v)) {
                            return v.join(', ');
                        } else {
                            return v;
                        }
                    }
                },
                countryCodesBilling: {
                    header: '{s name=column_country_billing}{/s}',
                    dataIndex: 'countryCodesBilling',
                    renderer: function (v) {
                        if (Ext.isArray(v)) {
                            return v.join(', ');
                        } else {
                            return v;
                        }
                    }
                },
                countryCodesDelivery: {
                    header: '{s name=column_country_shipping}{/s}',
                    dataIndex: 'countryCodesDelivery',
                    renderer: function (v) {
                        if (Ext.isArray(v)) {
                            return v.join(', ');
                        } else {
                            return v;
                        }
                    }
                }
            }
        };
    },

});
