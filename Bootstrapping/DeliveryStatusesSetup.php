<?php

namespace RpayRatePay\Bootstrapping;

use Exception;
use RpayRatePay\Component\Service\Logger;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Status;

class DeliveryStatusesSetup extends Bootstrapper
{

    /**
     * @var ModelManager
     */
    private $modelManager;

    public function __construct($bootstrap)
    {
        parent::__construct($bootstrap);
        $this->modelManager = Shopware()->Container()->get('models');
    }

    /**
     * @throws Exception
     */
    public function install()
    {
        $sql = 'INSERT IGNORE INTO `s_core_states` SET `id` =?, `description` =?, `position` =?, `group` =?, `mail`=?, `name`=?';
        try {
            Shopware()->Db()->query($sql, [
                255, 'Teil-(Retoure)', 255, 'state', 0, 'ratepay_partly_return'
            ]);
        } catch (Exception $exception) {
            Logger::singleton()->addNotice($exception->getMessage());
        }
        try {
            Shopware()->Db()->query($sql, [
                265, 'Teil-(Storno)', 265, 'state', 0, 'ratepay_partly_cancel'
            ]);
        } catch (Exception $exception) {
            Logger::singleton()->addNotice($exception->getMessage());
        }
    }

    /**
     * @return mixed|void
     * @throws Exception
     */
    public function update()
    {
        $flush = [];
        /** @var Status $status */
        $status = $this->modelManager->find(Status::class, 255);
        if ($status) {
            $status->setName('ratepay_partly_return');
            $flush[] = $status;
        }

        /** @var Status $status */
        $status = $this->modelManager->find(Status::class, 265);
        if ($status) {
            $status->setName('ratepay_partly_cancel');
            $flush[] = $status;
        }

        if (count($flush)) {
            $this->modelManager->flush($flush);
        }
    }

    /**
     * @return mixed|void
     */
    public function uninstall()
    {
    }
}
