<?php


namespace RpayRatePay\Bootstrap;


use DateTime;
use DirectoryIterator;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\ORM\Tools\SchemaTool;
use Exception;
use PDO;
use RecursiveRegexIterator;
use RegexIterator;
use RpayRatePay\Models\Migration;
use RpayRatePay\Models\MigrationRepository;
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
        $this->applyMigrations(AbstractMigration::MODUS_UPDATE);
    }

    private function applyMigrations($mode)
    {
        if ($this->installContext->assertMinimumVersion("5.6") === false) {

            require_once $this->pluginDir . '/lib/shopware/migrations/AbstractPluginMigration.php';

            $migrationTable = $this->modelManager->getClassMetadata(Migration::class);
            if ($this->schemaManager->tablesExist([$migrationTable->getTableName()]) === false) {
                $this->schemaTool->createSchema([$migrationTable]);
            }
            /** @var MigrationRepository $migrationRepo */
            $migrationRepo = $this->modelManager->getRepository(Migration::class);


            /** @var PDO $connection */
            $connection = $this->container->get('db_connection');
            $migrationPath = $this->pluginDir . '/Resources/migrations/';
            $directoryIterator = new DirectoryIterator($migrationPath);
            $regex = new RegexIterator($directoryIterator, '/^([0-9]*)-(.*)\.php$/i', RecursiveRegexIterator::GET_MATCH);

            foreach ($regex as $result) {
                $migrationVersion = $result[1];
                $migrationName = $result[2];

                $migrationModel = $migrationRepo->findMigrationByNumber($migrationVersion);
                if ($migrationModel == null) {
                    $migration = new Migration();
                    $migration->setVersion($migrationVersion);
                    $migration->setName($migrationName);
                    $migration->setStartDate(new DateTime());

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
                    } catch (Exception $e) {
                        $connection->rollBack();
                        $migration->setErrorMsg($e->getMessage());
                        $exception = $e;
                    }
                    $migration->setCompleteDate(new DateTime());
                    $this->modelManager->persist($migration);
                    $this->modelManager->flush($migration);
                    if ($exception) {
                        throw $exception;
                    }
                }
            }
        }
    }

    public function install()
    {
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
