<?php

namespace RpayRatePay\Bootstrap;

use Exception;
use RpayRatePay\Enum\OrderStatus as OrderStatusEnum;
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

    public function install()
    {
        foreach (OrderStatusEnum::STATUS as $type => $status) {
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
