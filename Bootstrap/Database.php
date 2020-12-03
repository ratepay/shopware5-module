<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Bootstrap;


use DirectoryIterator;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\ORM\Tools\SchemaTool;
use Exception;
use PDO;
use RecursiveRegexIterator;
use RegexIterator;
use Shopware\Components\Migrations\AbstractMigration;
use Shopware\Components\Migrations\AbstractPluginMigration;

class Database extends AbstractBootstrap
{
    /** @var SchemaTool */
    protected $schemaTool;

    /** @var AbstractSchemaManager */
    protected $schemaManager;

    public function setContainer($container)
    {
        parent::setContainer($container);
        $this->schemaTool = new SchemaTool($this->modelManager);
        $this->schemaManager = $this->modelManager->getConnection()->getSchemaManager();
    }

    public function update()
    {
        require_once $this->pluginDir . '/lib/shopware/migrations/AbstractPluginMigration.php';
        $this->applyMigrations(AbstractMigration::MODUS_UPDATE);
    }

    private function applyMigrations($mode)
    {
        if ($this->installContext->assertMinimumVersion("5.6") === false) {
            $this->createMigrationSchema();

            /** @var PDO $connection */
            $connection = $this->container->get('db_connection');
            $migrationPath = $this->pluginDir . '/Resources/migrations/';
            $directoryIterator = new DirectoryIterator($migrationPath);
            $regex = new RegexIterator($directoryIterator, '/^([0-9]*)-(.*)\.php$/i', RecursiveRegexIterator::GET_MATCH);

            foreach ($regex as $result) {
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
        }
    }

    private function createMigrationSchema()
    {
        // this is the shopware schema of SW 5.6+
        $connection = $this->container->get('db_connection');
        $sql = '
            CREATE TABLE IF NOT EXISTS `s_plugin_schema_version` (
    `plugin_name` VARCHAR(255) NOT NULL COLLATE \'utf8_unicode_ci\',
    `version` INT(11) NOT NULL,
    `start_date` DATETIME NOT NULL,
    `complete_date` DATETIME NULL DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL COLLATE \'utf8_unicode_ci\',
    `error_msg` VARCHAR(255) NULL DEFAULT NULL COLLATE \'utf8_unicode_ci\',
    PRIMARY KEY (`plugin_name`, `version`)
)
COLLATE=\'utf8_unicode_ci\'
ENGINE=InnoDB
        ';
        $connection->exec($sql);
        $connection->commit();
    }

    private function isMigrationAlreadyExecuted($version)
    {
        $connection = $this->container->get('db_connection');
        $sql = "
            SELECT 
                1 
            FROM `s_plugin_schema_version` 
            WHERE 
                `plugin_name` LIKE 'RpayRatePay' AND 
                `version` = " . $version . " AND 
                `complete_date` IS NOT NULL
        ";
        $query = $connection->query($sql);
        return $query->rowCount() > 0;
    }

    private function insertMigration(PDO $connection, $migrationVersion, $migrationName, $errorMessage = null)
    {
        $migrationQuery = $connection->prepare("INSERT INTO `s_plugin_schema_version` VALUES (?, ?, NOW(), " . ($errorMessage ? 'NULL' : 'NOW()') . ",?,?)");
        $migrationQuery->bindValue(1, 'RpayRatePay');
        $migrationQuery->bindValue(2, $migrationVersion);
        $migrationQuery->bindValue(3, $migrationName);
        $migrationQuery->bindValue(4, $errorMessage);
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
                DROP TABLE IF EXISTS rpay_ratepay_schema_version;
                DROP TABLE IF EXISTS rpay_ratepay_config;
                DROP TABLE IF EXISTS rpay_ratepay_config_installment;
                DROP TABLE IF EXISTS rpay_ratepay_config_payment;
                DROP TABLE IF EXISTS rpay_ratepay_logging;
                DROP TABLE IF EXISTS rpay_ratepay_order_discount;
                DROP TABLE IF EXISTS rpay_ratepay_order_history;
                DROP TABLE IF EXISTS rpay_ratepay_order_positions;
                DROP TABLE IF EXISTS rpay_ratepay_order_shipping;
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
