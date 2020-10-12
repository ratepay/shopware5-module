<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Bootstrapping\Events;


use BogxProductConfigurator\Subscriber\BogxFrontendSubscriber;
use Doctrine\DBAL\Query\QueryBuilder;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Enlight_Event_EventManager;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Attribute\OrderDetail as OrderDetailAttribute;

class BogxProductConfiguratorSubscriber implements SubscriberInterface
{
    /**
     * @var ModelManager
     */
    private $modelManager;
    /**
     * @var Enlight_Event_EventManager
     */
    private $eventManager;


    public function __construct()
    {
        $this->modelManager = Shopware()->Models();
        $this->eventManager = Shopware()->Events();
    }

    public static function getSubscribedEvents()
    {
        if (self::isBogxPluginInstalled()) {
            return [
                'Enlight_Controller_Front_RouteStartup' => 'unregisterPluginEvent',
                'Shopware_Modules_Basket_AddArticle_CheckBasketForArticle' => 'checkBasketForArticle',
                'RatePAY_filter_order_items' => 'onFilterOrderItems'
            ];
        }
        return [];
    }

    protected static function isBogxPluginInstalled()
    {
        return Shopware()->Db()->query("SELECT 1 FROM s_core_plugins WHERE name = 'BogxProductConfigurator' AND active = 1")->fetchColumn() !== false;
    }

    public function unregisterPluginEvent(Enlight_Event_EventArgs $args)
    {
        $handlers = $this->eventManager->getListeners('Shopware_Modules_Basket_AddArticle_CheckBasketForArticle');
        foreach ($handlers as $handler) {
            if (isset($handler->getListener()[0]) && $handler->getListener()[0] instanceof BogxFrontendSubscriber) {
                $this->eventManager->removeListener($handler);
            }
        }
    }

    public function checkBasketForArticle(Enlight_Event_EventArgs $args)
    {
        /** @var QueryBuilder $qb */
        $qb = $args->get('queryBuilder');
        $configuration = Shopware()->Front()->Request()->getParam('bogxProductConfiguratorSelection');

        if ($configuration) {
            $qb->resetQueryPart('select');
            $qb->select(['basket.id', 'quantity']);
            $qb->innerJoin('basket', 's_order_basket_attributes', 'attribute', 'basket.id = attribute.basketID')
                ->andWhere($qb->expr()->eq('attribute.bogx_productconfigurator', ':configuration'))
                ->setParameter('configuration', $configuration);
        }
    }

    public function onFilterOrderItems(Enlight_Event_EventArgs $args)
    {
        $data = $args->getReturn();

        if (isset($data['ordernumber'])) {
            $orderNumberKey = 'ordernumber';
        } else if (isset($data['articleordernumber'])) {
            $orderNumberKey = 'articleordernumber';
        } else {
            //original orderNumber can not be found
            return;
        }

        if (isset($data['articlename'])) {
            $productNameKey = 'articlename';
        } else if (isset($data['name'])) {
            $productNameKey = 'name';
        } else {
            //original name can not be found - we don't care
            $productNameKey = null;
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
        $config = json_decode($config, true);
        if ($productNameKey) {
            $productConfigName = str_replace(" \n", ', ', trim($config['additionaltext'], "\n"));
            $data[$productNameKey] = $data[$productNameKey] . ' (' . $productConfigName . ')';
        }
        $args->setReturn($data);
    }

}
