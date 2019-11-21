<?php


namespace RpayRatePay\Bootstrap;


use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use RpayRatePay\Models\ConfigInstallment;
use RpayRatePay\Models\ConfigPayment;
use RpayRatePay\Models\Log;
use RpayRatePay\Models\OrderHistory;
use RpayRatePay\Models\Position\Discount;
use RpayRatePay\Models\Position\Product;
use RpayRatePay\Models\Position\Shipping;
use RpayRatePay\Models\ProfileConfig;
use RpayRatePay\Services\Config\ProfileConfigService;
use RpayRatePay\Services\Config\WriterService;
use Shopware\Components\Migrations\AbstractMigration;

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
        $this->install();
    }

    public function install()
    {
        $this->applyMigrations(AbstractMigration::MODUS_INSTALL);
        $this->renameOldColumns();

        $install = [];
        $update = [];

        foreach ($this->getClassMetas() as $meta) {
            if ($this->schemaManager->tablesExist([$meta->getTableName()])) {
                $update[] = $meta;
            } else {
                $install[] = $meta;
            }
        }

        if (count($install)) {
            $this->schemaTool->createSchema($install);
        }
        if (count($update)) {
            $this->schemaTool->updateSchema($update, true);
        }
    }

    protected function renameOldColumns()
    {
        $renames = [
            ProfileConfig::class => [
                'country-code-billing' => [
                    'country_code_billing',
                    'varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL'
                ],
                'country-code-delivery' => [
                    'country_code_delivery',
                    'varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL'
                ],
                'error-default' => [
                    'error_default',
                    'varchar(535) COLLATE utf8_unicode_ci DEFAULT NULL'
                ],
            ],
            ConfigInstallment::class => [
                'month-allowed' => [
                    'month_allowed',
                    'varchar(255) COLLATE utf8_unicode_ci NOT NULL'
                ],
                'payment-firstday' => [
                    'payment_firstday',
                    'varchar(10) COLLATE utf8_unicode_ci NOT NULL'
                ],
                'interestrate-default' => [
                    'interestrate_default',
                    'float NOT NULL'
                ],
                'rate-min-normal' => [
                    'rate_min_normal',
                    'float NOT NULL'
                ],
            ]
        ];
        foreach ($renames as $class => $columns) {
            $classMeta = $this->modelManager->getClassMetadata($class);
            if ($this->schemaManager->tablesExist([$classMeta->getTableName()])) {
                $columnList = $this->schemaManager->listTableColumns($classMeta->getTableName());
                $toRename = [];
                foreach ($columns as $oldName => $definition) {
                    if (array_key_exists($oldName, $columnList)) {
                        $toRename[] = ' CHANGE COLUMN `' . $oldName . '` ' . $definition[0] . ' ' . $definition[1];
                    }
                }
                if (count($toRename)) {
                    $sql = 'ALTER TABLE ' . $classMeta->getTableName() . ' ' . implode(',', $toRename);
                    $this->modelManager->getConnection()->executeQuery($sql);
                }
            }
        }
    }

    /**
     * @return ClassMetadata[]
     */
    protected function getClassMetas()
    {
        return [
            $this->modelManager->getClassMetadata(ProfileConfig::class),
            $this->modelManager->getClassMetadata(ConfigInstallment::class),
            $this->modelManager->getClassMetadata(ConfigPayment::class),
            $this->modelManager->getClassMetadata(Discount::class),
            $this->modelManager->getClassMetadata(OrderHistory::class),
            $this->modelManager->getClassMetadata(Product::class),
            $this->modelManager->getClassMetadata(Shipping::class),
            $this->modelManager->getClassMetadata(Log::class),
        ];
    }

    public function uninstall($keepUserData = false)
    {
        if ($keepUserData === false) {
            $remove = [];
            foreach ($this->getClassMetas() as $meta) {
                if ($this->schemaManager->tablesExist([$meta->getTableName()])) {
                    $remove[] = $meta;
                }
            }
            if (count($remove)) {
                $this->schemaTool->dropSchema($remove);
            }
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

    private function applyMigrations(string $mode)
    {
        if($this->installContext->assertMinimumVersion("5.6") === false) {
            /** @var \PDO $connection */
            $connection = $this->container->get('db_connection');
            $migrationPath = $this->pluginDir.'/Resources/migrations/';
            $directoryIterator = new \DirectoryIterator($migrationPath);
            $regex = new \RegexIterator($directoryIterator, '/^([0-9]*)-.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);

            foreach ($regex as $result) {
                $migrationVersion = $result[1];
                if ($migrationVersion <= $this->getOldVersion()) {
                    continue;
                }
                require_once $migrationPath.$result[0];
                $className = "\RpayRatePay\Migrations\Migration".$migrationVersion;
                /** @var AbstractMigration $migrationClass */
                $migrationClass = new $className($connection);
                $migrationClass->up($mode);

                foreach($migrationClass->getSql() as $sql) {
                    $connection->exec($sql);
                }
            }

        }
    }
}
