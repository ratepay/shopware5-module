<?php


namespace RpayRatePay\Bootstrap;


use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use RpayRatePay\Models\ProfileConfig;
use RpayRatePay\Models\ConfigInstallment;
use RpayRatePay\Models\ConfigPayment;
use RpayRatePay\Models\Log;
use RpayRatePay\Models\OrderDiscount;
use RpayRatePay\Models\OrderHistory;
use RpayRatePay\Models\OrderPositions;
use RpayRatePay\Models\OrderShipping;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin\Context\InstallContext;

class Database extends AbstractBootstrap
{

    /** @var ModelManager */
    protected $entityManager;

    /** @var SchemaTool  */
    protected $schemaTool;

    /** @var AbstractSchemaManager  */
    protected $schemaManager;

    public function __construct(
        InstallContext $context,
        ModelManager $entityManager
    )
    {
        parent::__construct($context);
        $this->entityManager = $entityManager;
        $this->schemaTool = new SchemaTool($this->entityManager);
        $this->schemaManager = $this->entityManager->getConnection()->getSchemaManager();
    }

    public function install() {
        $this->renameOldColumns();

        $install = [];
        $update = [];

        foreach($this->getClassMetas() as $meta) {
            if($this->schemaManager->tablesExist([$meta->getTableName()])) {
                $update[] = $meta;
            } else {
                $install[] = $meta;
            }
        }

        if(count($install)) {
            $this->schemaTool->createSchema($install);
        }
        if(count($update)) {
            $this->schemaTool->updateSchema($update, true);
        }
    }

    public function update() {
        $this->install();
    }

    public function uninstall($keepUserData = false) {
        if($keepUserData === false) {
            $remove = [];
            foreach ($this->getClassMetas() as $meta) {
                if ($this->entityManager->getConnection()->getSchemaManager()->tablesExist([$meta->getTableName()])) {
                    $remove[] = $meta;
                }
            }
            if (count($remove)) {
                $this->schemaTool->dropSchema($remove);
            }
        }
    }

    /**
     * @return ClassMetadata[]
     */
    protected function getClassMetas() {
        return [
            $this->entityManager->getClassMetadata(ProfileConfig::class),
            $this->entityManager->getClassMetadata(ConfigInstallment::class),
            $this->entityManager->getClassMetadata(ConfigPayment::class),
            $this->entityManager->getClassMetadata(OrderDiscount::class),
            $this->entityManager->getClassMetadata(OrderHistory::class),
            $this->entityManager->getClassMetadata(OrderPositions::class),
            $this->entityManager->getClassMetadata(OrderShipping::class),
            $this->entityManager->getClassMetadata(Log::class),
        ];
    }

    protected function renameOldColumns()
    {
        $renames = [
            ProfileConfig::class => [
                'country-code-billing'  => [
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
        foreach($renames as $class => $columns) {
            $classMeta = $this->entityManager->getClassMetadata($class);
            if($this->schemaManager->tablesExist([$classMeta->getTableName()])) {
                $columnList = $this->schemaManager->listTableColumns($classMeta->getTableName());
                $toRename = [];
                foreach($columns as $oldName => $definition) {
                    if (array_key_exists($oldName, $columnList)) {
                        $toRename[] = ' CHANGE COLUMN `' . $oldName . '` ' . $definition[0] . ' ' . $definition[1];
                    }
                }
                if(count($toRename)) {
                    $sql = 'ALTER TABLE '.$classMeta->getTableName().' '.implode(',', $toRename);
                    $this->entityManager->getConnection()->executeQuery($sql);
                }
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
}
