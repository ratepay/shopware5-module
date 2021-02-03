<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Bootstrap;


use DirectoryIterator;
use Exception;
use PDO;
use RecursiveRegexIterator;
use RegexIterator;
use Shopware\Components\Migrations\AbstractMigration;
use Shopware\Components\Migrations\AbstractPluginMigration;

class Database extends AbstractBootstrap
{

    public function update()
    {
        require_once $this->pluginDir . '/lib/shopware/migrations/AbstractPluginMigration.php';
        $this->applyMigrations(AbstractMigration::MODUS_UPDATE);
    }

    private function applyMigrations($mode)
    {
        /** @var PDO $connection */
        $connection = $this->container->get('db_connection');

        if ($this->installContext->assertMinimumVersion("5.6") === false) {

            $connection->exec("
                CREATE TABLE IF NOT EXISTS `ratepay_schema_version` (
                  `version` int(11) NOT NULL,
                  `start_date` datetime NOT NULL,
                  `complete_date` datetime DEFAULT NULL,
                  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                  `error_msg` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  PRIMARY KEY (`version`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
            ");

            $migrationPath = $this->pluginDir . '/Resources/migrations/';
            $directoryIterator = new DirectoryIterator($migrationPath);
            $regex = new RegexIterator($directoryIterator, '/^([0-9]*)-(.*)\.php$/i', RecursiveRegexIterator::GET_MATCH);

            // collect migrations
            $migrations = [];
            foreach ($regex as $result) {
                $migrationVersion = $result[1];
                $migrations[$migrationVersion] = $result;
            }
            ksort($migrations);

            // execute migrations
            foreach($migrations as $result) {
                $migrationVersion = $result[1];
                $migrationName = $result[2];

                if ($this->isMigrationAlreadyExecuted($migrationVersion) === false) {

                    require_once $migrationPath . $result[0];
                    $className = "\RpayRatePay\Migrations\Migration" . $migrationVersion;
                    /** @var AbstractMigration $migrationClass */
                    $migrationClass = new $className($connection);
                    $migrationClass->up($mode);

                    $exception = null;
                    try {
                        foreach ($migrationClass->getSql() as $sql) {
                            $connection->exec($sql);
                        }
                        $this->insertMigration($connection, $migrationVersion, $migrationName);
                    } catch (Exception $e) {
                        $connection->rollBack();
                        $this->insertMigration($connection, $migrationVersion, $migrationName, $e->getMessage());
                        throw $e;
                    }
                }
            }
        } else {
            $connection->exec("DROP TABLE IF EXISTS ratepay_schema_version;");
        }
    }

    private function isMigrationAlreadyExecuted($version)
    {
        $connection = $this->container->get('db_connection');
        $sql = "
            SELECT 
                1 
            FROM `ratepay_schema_version` 
            WHERE 
                `version` = " . $version . " AND 
                `complete_date` IS NOT NULL
        ";
        $query = $connection->query($sql);
        return $query->rowCount() > 0;
    }

    private function insertMigration(PDO $connection, $migrationVersion, $migrationName, $errorMessage = null)
    {
        $migrationQuery = $connection->prepare("INSERT INTO `ratepay_schema_version` VALUES (?, NOW(), " . ($errorMessage ? 'NULL' : 'NOW()') . ",?,?)");
        $migrationQuery->bindValue(1, $migrationVersion);
        $migrationQuery->bindValue(2, $migrationName);
        $migrationQuery->bindValue(3, $errorMessage);
        $migrationQuery->execute();
    }

    public function install()
    {
        require_once $this->pluginDir . '/lib/shopware/migrations/AbstractPluginMigration.php';
        $this->applyMigrations(AbstractPluginMigration::MODUS_INSTALL);
    }

    public function uninstall($keepUserData = false)
    {
        if ($keepUserData === false) {
            $this->modelManager->getConnection()->exec("
                SET FOREIGN_KEY_CHECKS=0;
                DROP TABLE IF EXISTS ratepay_schema_version;
                DROP TABLE IF EXISTS rpay_ratepay_config;
                DROP TABLE IF EXISTS rpay_ratepay_config_installment;
                DROP TABLE IF EXISTS rpay_ratepay_config_payment;
                DROP TABLE IF EXISTS rpay_ratepay_logging;
                DROP TABLE IF EXISTS rpay_ratepay_order_discount;
                DROP TABLE IF EXISTS rpay_ratepay_order_history;
                DROP TABLE IF EXISTS rpay_ratepay_order_positions;
                DROP TABLE IF EXISTS rpay_ratepay_order_shipping;
                DROP TABLE IF EXISTS ratepay_profile_config;
                DROP TABLE IF EXISTS ratepay_profile_config_method;
                DROP TABLE IF EXISTS ratepay_profile_config_method_installment;
                SET FOREIGN_KEY_CHECKS=1;
            ");
        }
    }

    public function activate()
    {
        // do nothing
    }

    public function deactivate()
    {
        // do nothing
    }
}
