<?php
/**
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Component\ThirdPartySupport\SwagPromotion\Subscriber;


use Enlight\Event\SubscriberInterface;
use RpayRatePay\Event\CreatePositionEntity;
use RpayRatePay\Event\FilterBasketItem;
use RpayRatePay\Models\Position\Discount;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Attribute\OrderBasket;
use Shopware\Models\Order\Basket;
use SwagPromotion\Models\Promotion;

class BasketSubscriber implements SubscriberInterface
{

    /**
     * @var \Shopware\Components\Model\ModelManager
     */
    private $modelManager;

    /** @var bool */
    private $_databaseCheck;

    public function __construct(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            FilterBasketItem::class => 'onFilterBasketItem',
            CreatePositionEntity::class => 'onCreatePositionEntity'
        ];
    }

    public function onFilterBasketItem(FilterBasketItem $args)
    {
        if (!$this->checkAttributeTables()) {
            return;
        }

        $item = $args->getReturn();
        if (($originalSourceItem = $args->getOriginalSourceItem()) && is_array($originalSourceItem) && isset($originalSourceItem['id'])) {
            // frontend order

            /** @var Basket $basket */
            $basket = $this->modelManager->find(Basket::class, $originalSourceItem['id']);

            $basketAttribute = $basket ? $basket->getAttribute() : null;
            if ($basketAttribute && $basketAttribute->getSwagPromotionId()) {
                $item['UniqueArticleNumber'] = 'adv-prom-' . $basketAttribute->getSwagPromotionId() . '-' . md5($basket->getArticleName());
            }
        }

        $args->setReturn($item);
    }

    public function onCreatePositionEntity(CreatePositionEntity $args)
    {
        if (!class_exists(Promotion::class)) {
            return;
        }

        $item = $args->getReturn();

        if ($item instanceof Discount === false) {
            return;
        }

        $detail = $args->getDetail();

        $promotion = $this->modelManager->getRepository(Promotion::class)
            ->findOneBy(['number' => $detail->getArticleNumber()]);

        if ($promotion) {
            $item->setUniqueNumber('adv-prom-' . $promotion->getId() . '-' . md5($detail->getArticleName()));
        }

        $args->setReturn($item);
    }

    private function checkAttributeTables()
    {
        if ($this->_databaseCheck === null) {
            $columns = ['swagPromotionId'];
            $classMeta = $this->modelManager->getClassMetadata(OrderBasket::class);
            $this->_databaseCheck = true;
            foreach ($columns as $field) {
                $this->_databaseCheck = $this->_databaseCheck && $classMeta->hasField($field);
            }
        }

        return $this->_databaseCheck;
    }

}
