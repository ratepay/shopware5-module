<?php

namespace Shopware\Components\Migrations;
if(class_exists(AbstractPluginMigration::class) === false) {

    abstract class AbstractPluginMigration extends AbstractMigration
    {
        const MODUS_UNINSTALL = 'uninstall';

        abstract public function down($keepUserData);
    }

}
