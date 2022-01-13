<?php
/**
 * Copyright (c) Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Component\ThirdPartySupport\SwagCustomProducts\Subscriber;


use Enlight\Event\SubscriberInterface;
use RpayRatePay\Event\AdminPositionManagementBasketLoaded;
use RpayRatePay\Event\CreatePositionEntity;
use RpayRatePay\Event\FilterBasketItem;
use RpayRatePay\Helper\TaxHelper;
use RpayRatePay\Models\Position\Product;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Attribute\OrderDetail;
use Shopware\Models\Order\Detail;
use SwagCustomProducts\Components\Services\BasketManagerInterface;
use SwagCustomProducts\Components\Services\HashManager;

class BasketSubscriber implements SubscriberInterface
{

    /**
     * @var \Shopware\Components\Model\ModelManager
     */
    private $modelManager;

    private $_databaseCheck;

    public function __construct(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            FilterBasketItem::class => 'onFilterBasketItem',
            CreatePositionEntity::class => 'onCreatePositionEntity',
            AdminPositionManagementBasketLoaded::class => 'onAdminPositionsLoaded'
        ];
    }

    public function onFilterBasketItem(FilterBasketItem $args)
    {
        $item = $args->getReturn();
        $originalSourceItem = $args->getOriginalSourceItem();

        if ($originalSourceItem) {
            if (is_array($originalSourceItem)) {
                // frontend order

                if (isset($originalSourceItem['customProductMode'])) {
                    switch ($originalSourceItem['customProductMode']) {
                        case BasketManagerInterface::MODE_OPTION:
                        case BasketManagerInterface::MODE_VALUE:
                            foreach ($args->getBuilder()->getBasketItems()['Items'] as &$basketItem) {
                                if ($basketItem['Item']['UniqueArticleNumber'] === $originalSourceItem['customProductHash']) {
                                    $basketItem['Item']['UnitPriceGross'] += $item['UnitPriceGross'];
                                    break;
                                }
                            }
                            unset($basketItem);

                            // do not add this position to the basket
                            $args->setReturn(null);
                            return;
                        case BasketManagerInterface::MODE_PRODUCT:
                            if (isset($originalSourceItem['customProductHash']) && !empty($originalSourceItem['customProductHash'])) {
                                $item['UniqueArticleNumber'] = $this->getNewHash($originalSourceItem['customProductHash']);
                            }
                            break;
                    }
                }

            } else if ($originalSourceItem instanceof Detail) {
                // modify a order

                if (!$this->checkAttributeTables()) {
                    return;
                }

                $sourceItemAttribute = $originalSourceItem->getAttribute();
                if ($sourceItemAttribute) {
                    switch ($originalSourceItem->getAttribute()->getSwagCustomProductsMode()) {
                        case BasketManagerInterface::MODE_OPTION:
                        case BasketManagerInterface::MODE_VALUE:
                            // do not add this position to the basket
                            $args->setReturn(null);
                            return;
                        case BasketManagerInterface::MODE_PRODUCT:
                            /** @var Detail $siblingDetail */
                            foreach ($originalSourceItem->getOrder()->getDetails() as $siblingDetail) {
                                $siblingAttribute = $siblingDetail->getAttribute();
                                if ($siblingAttribute &&
                                    in_array($siblingAttribute->getSwagCustomProductsMode(), [BasketManagerInterface::MODE_OPTION, BasketManagerInterface::MODE_VALUE], false) &&
                                    $sourceItemAttribute->getSwagCustomProductsConfigurationHash() === $siblingAttribute->getSwagCustomProductsConfigurationHash()
                                ) {
                                    $item['UnitPriceGross'] += TaxHelper::getItemGrossPrice(
                                            $siblingDetail->getOrder(),
                                            $siblingDetail
                                        ) * $siblingDetail->getQuantity();
                                }
                            }
                            break;
                    }
                }
            }
        }

        $args->setReturn($item);
    }

    public function onCreatePositionEntity(CreatePositionEntity $args)
    {
        if (!$this->checkAttributeTables()) {
            return;
        }

        $position = $args->getReturn();
        if ($position instanceof Product) {
            $attribute = $args->getDetail()->getAttribute();
            if ($attribute) {
                switch ($attribute->getSwagCustomProductsMode()) {
                    case BasketManagerInterface::MODE_OPTION:
                    case BasketManagerInterface::MODE_VALUE:
                        // do not add this position
                        $args->setReturn(null);
                        break;
                    case BasketManagerInterface::MODE_PRODUCT:
                        $position->setUniqueNumber($this->getNewHash(
                            $attribute->getSwagCustomProductsConfigurationHash()
                        ));
                        break;
                }
            }
        }
    }

    public function onAdminPositionsLoaded(AdminPositionManagementBasketLoaded $args)
    {
        if (!$this->checkAttributeTables()) {
            return $args->getReturn();
        }

        $return = $args->getReturn();

        foreach ($return as &$item) {
            if (isset($item['orderDetailId'])) {
                $orderDetail = $this->modelManager->find(Detail::class, $item['orderDetailId']);
                if ($orderDetail &&
                    ($attribute = $orderDetail->getAttribute()) &&
                    $attribute->getSwagCustomProductsMode() === BasketManagerInterface::MODE_PRODUCT
                ) {
                    /** @var \Shopware\Models\Order\Detail $siblingDetail */
                    foreach ($orderDetail->getOrder()->getDetails() as $siblingDetail) {
                        $siblingAttribute = $siblingDetail->getAttribute();
                        if ($siblingAttribute &&
                            in_array($siblingAttribute->getSwagCustomProductsMode(), [BasketManagerInterface::MODE_OPTION, BasketManagerInterface::MODE_VALUE], false) &&
                            $attribute->getSwagCustomProductsConfigurationHash() === $siblingAttribute->getSwagCustomProductsConfigurationHash()
                        ) {
                            $item['price'] += $siblingDetail->getPrice();
                        }
                    }
                }
            }
        }

        return $return;
    }

    private function getNewHash($hash)
    {
        // we need to recalculate the hash, cause the hash will be regenerated during the checkout.

        $template = $this->modelManager->getConnection()->createQueryBuilder()
            ->select(['configuration'])
            ->from(HashManager::CONFIG_HASH_TABLE)
            ->where('hash = :hash')
            ->setParameter('hash', $hash)
            ->execute()->fetch(\PDO::FETCH_COLUMN);

        $template = json_decode($template, true);

        // allwo only configured options and a few other fields to make sure, that no fields will be added by plugin
        // updates
        $template = array_filter($template, static function ($key) {
            return is_numeric($key) || in_array($key, ['number', 'shopId']);
        }, ARRAY_FILTER_USE_KEY);

        return md5(json_encode($template));
    }

    private function checkAttributeTables()
    {
        if ($this->_databaseCheck === null) {
            $columns = ['swagCustomProductsMode', 'swagCustomProductsConfigurationHash'];
            $classMeta = $this->modelManager->getClassMetadata(OrderDetail::class);
            $this->_databaseCheck = true;
            foreach ($columns as $field) {
                $this->_databaseCheck = $this->_databaseCheck && $classMeta->hasField($field);
            }
        }

        return $this->_databaseCheck;
    }
}
