Ext.define('Shopware.apps.RatepayProfileConfig', {
    extend: 'Enlight.app.SubApplication',

    name: 'Shopware.apps.RatepayProfileConfig',

    loadPath: '{url action=load}',
    bulkLoad: true,

    controllers: ['Main'],

    views: [
        'list.Window',
        'list.ProfileConfig',

        'detail.ProfileConfig',
        'detail.Window'
    ],

    models: ['ProfileConfig'],
    stores: ['ProfileConfig'],

    launch: function () {
        return this.getController('Main').mainWindow;
    }
});
