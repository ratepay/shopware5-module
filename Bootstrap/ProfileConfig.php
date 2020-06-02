<?php
/**
 * Copyright (c) 2020 RatePAY GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Bootstrap;


use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use RpayRatePay\Models\ConfigInstallment;
use RpayRatePay\Models\ConfigPayment;
use RpayRatePay\Models\ProfileConfig as ProfileConfigModel;

class ProfileConfig extends AbstractBootstrap
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
        $this->recreateTables();
    }

    private function recreateTables()
    {
        $this->dropTables();
        $this->schemaTool->createSchema($this->getClassMetas());
    }

    private function dropTables()
    {
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

    /**
     * @return ClassMetadata[]
     */
    protected function getClassMetas()
    {
        return [
            $this->modelManager->getClassMetadata(ProfileConfigModel::class),
            $this->modelManager->getClassMetadata(ConfigInstallment::class),
            $this->modelManager->getClassMetadata(ConfigPayment::class)
        ];
    }

    public function install()
    {
        $this->recreateTables();
    }

    public function uninstall($keepUserData = false)
    {
        if ($keepUserData === false) {
            $this->dropTables();
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
