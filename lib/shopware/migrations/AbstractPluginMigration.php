<?php
/**
 * Copyright (c) 2020 RatePAY GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\Migrations;
if(class_exists(AbstractPluginMigration::class) === false) {

    abstract class AbstractPluginMigration extends AbstractMigration
    {
        const MODUS_UNINSTALL = 'uninstall';

        abstract public function down($keepUserData);
    }

}
