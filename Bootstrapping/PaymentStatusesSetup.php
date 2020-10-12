<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Bootstrapping;

use Exception;
use RpayRatePay\Component\Service\Logger;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Status;

class PaymentStatusesSetup extends Bootstrapper
{

    /**
     * @var object|ModelManager
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
                155, 'Zahlungsabwicklung durch RatePAY', 155, 'payment', 0, 'ratepay_payment_via_ratepay'
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
        $status = $this->modelManager->find(Status::class, 155);
        if ($status) {
            $status->setName('ratepay_payment_via_ratepay');
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
