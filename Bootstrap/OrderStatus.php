<?php
/**
 * Copyright (c) 2020 RatePAY GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Bootstrap;

use Exception;
use RpayRatePay\Enum\OrderStatus as OrderStatusEnum;
use Shopware\Models\Order\DetailStatus;
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
                if ($type === 'position') {
                    $entity = $this->modelManager->getRepository(DetailStatus::class)->find($id);
                    if (!$entity) {
                        $this->modelManager->getConnection()->insert(
                            $this->modelManager->getClassMetadata(DetailStatus::class)->getTableName(),
                            [
                                'id' => $id,
                                'description' => $options['description'],
                                'position' => $id,
                                'mail' => 0,
                            ]
                        );
                    }
                } else {
                    $entity = $this->modelManager->getRepository(Status::class)->find($id);
                    if ($entity) {
                        if ($entity->getName() == null) {
                            // issue RATEPLUG-73: in further versions the name was not set, so the status wasn't displayed
                            // correctly in the admin
                            $entity->setName($options['name']);
                            $this->modelManager->flush($entity);
                        }
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
