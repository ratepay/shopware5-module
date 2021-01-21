//{namespace name="backend/ratepay/profile_config/detail"}

Ext.define('Shopware.apps.RatepayProfileConfig.view.detail.ProfileConfig', {
    extend: 'Shopware.model.Container',
    padding: 20,

    configure: function () {
        return {
            controller: 'RatepayProfileConfig',
            fieldSets: [{
                title: '',
                fields: {
                    // active: {
                    //     fieldLabel: '{s name="field_active"}{/s}',
                    //     translatable: false,
                    //     allowBlank: false,
                    //     readOnly: true, // prevent editing
                    //     disabled: true // prevent posting
                    // },
                    profileId: {
                        fieldLabel: '{s name="field_profile_id"}{/s}',
                        translatable: false,
                        allowBlank: false,
                    },
                    securityCode: {
                        fieldLabel: '{s name="field_security_code"}{/s}',
                        translatable: false,
                        allowBlank: false,
                    },
                    sandbox: {
                        fieldLabel: '{s name="field_sandbox"}{/s}',
                        translatable: false,
                        allowBlank: false,
                    },
                    backend: {
                        fieldLabel: '{s name="field_backend"}{/s}',
                        translatable: false,
                        allowBlank: false,
                    },
                    shopId: {
                        fieldLabel: '{s name="field_shop"}{/s}',
                        translatable: false,
                        allowBlank: false,
                    }
                }
            }]
        };
    }
});

Shopware.app.Application.on('profileconfig-save-successfully', function (view, result, window, record, form, operation) {
    var responseData = JSON.parse(operation.response.responseText);
    if (responseData.message) {
        Shopware.Notification.createGrowlMessage('', responseData.message);
    }
});