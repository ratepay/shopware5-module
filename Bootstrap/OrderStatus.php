<?php

namespace RpayRatePay\Bootstrap;

use Exception;
use Shopware\Models\Order\Status;

class OrderStatus extends AbstractBootstrap
{

    /**
     * @return mixed|void
     * @throws Exception
     */
    public function update()
    {
        $this->install();
    }

    /**
     * @throws Exception
     */
    public function install()
    {
        foreach (\RpayRatePay\Enum\OrderStatus::STATUS as $type => $status) {
            foreach ($status as $id => $options) {
                $entity = $this->modelManager->getRepository(Status::class)->findOneBy(['name' => $options['name']]);
                if ($entity) {
                    continue;
                }
                $this->modelManager->getConnection()->insert(
                    $this->modelManager->getClassMetadata(Status::class)->getTableName(),
                    [
                        'id' => $id,
                        'name' => $options['name'],
                        'description' => $options['description'],
                        'position' => $id,
                        '`group`' => $type, //group is a keyword - so we must escape it
                        'mail' => 0,
                    ]
                );

            }
        }
    }

    public function uninstall($keepUserData = false)
    {
        // do not remove the status
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
