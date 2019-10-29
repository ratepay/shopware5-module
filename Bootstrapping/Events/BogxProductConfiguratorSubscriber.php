<?php


namespace RpayRatePay\Bootstrapping\Events;


use Enlight\Event\SubscriberInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Attribute\OrderDetail as OrderDetailAttribute;

class BogxProductConfiguratorSubscriber implements SubscriberInterface
{
    /**
     * @var ModelManager
     */
    protected $modelManager;

    public function __construct()
    {
        $this->modelManager = Shopware()->Models();
    }

    public static function getSubscribedEvents()
    {
        return [
            'RatePAY_filter_order_items' => 'onFilterOrderItems'
        ];
    }

    public function onFilterOrderItems(\Enlight_Event_EventArgs $args)
    {
        if ($this->isBogsPluginInstalled() === false) {
            return;
        }
        $data = $args->getReturn();

        if (isset($data['ordernumber'])) {
            $orderNumberKey = 'ordernumber';
        } else if (isset($data['articleordernumber'])) {
            $orderNumberKey = 'articleordernumber';
        } else {
            //original orderNumber can not be found
            return;
        }

        if (isset($data['ob_bogx_configurator'])) {
            $config = $data['ob_bogx_configurator'];
        } else {
            //try to find the order detail attribute
            $config = null;
            $orderDetailAttribute = null;
            if (isset($data['orderDetailId'])) {
                /** @var OrderDetailAttribute $orderDetailAttribute */
                $orderDetailAttribute = $this->modelManager->find(OrderDetailAttribute::class, $data['orderDetailId']);
            }

            if ($orderDetailAttribute && method_exists($orderDetailAttribute, 'getBogxProductconfigurator')) {
                $config = $orderDetailAttribute->getBogxProductconfigurator();
            }
        }
        if (empty($config)) {
            //no configuration given.
            return;
        }

        $data[$orderNumberKey] = $data[$orderNumberKey] . '-' . md5($config);
        $args->setReturn($data);
    }

    protected function isBogsPluginInstalled()
    {
        return $this->modelManager->getConnection()->query("SELECT 1 FROM s_core_plugins WHERE name = 'BogxProductConfigurator' AND active = 1")->fetchColumn() !== false;
    }

}
