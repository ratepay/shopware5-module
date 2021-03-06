<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Subscriber;


use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Attribute\OrderDetail as OrderDetailAttribute;
use Shopware\Models\Order\Detail;

class BogxProductConfiguratorSubscriber implements SubscriberInterface
{
    /**
     * @var ModelManager
     */
    private $modelManager;


    public function __construct(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
    }

    public static function getSubscribedEvents()
    {
        if (self::isBogxPluginInstalled()) {
            return [
                'RatePAY_filter_order_items' => 'onFilterOrderItems'
            ];
        }
        return [];
    }

    public function onFilterOrderItems(Enlight_Event_EventArgs $args)
    {
        $item = $args->getReturn();

        $productNumber = $this->findProductNumber($item);
        $productName = $this->findProductName($item);
        $configuration = $this->findProductConfiguration($item);
        if ($configuration == null || count($configuration) === 0) {
            return;
        }
        list($productNumber, $productName) = $this->addConfigurationToProductInfo([$productNumber, $productName], $configuration);
        $this->findProductNumber($item, $productNumber);
        $this->findProductName($item, $productName);

        $args->setReturn($item);
    }

    /**
     * @param array $data to values: 0 => productNumber, 1 => productName
     * @param array $configuration
     * @return array
     */
    protected function addConfigurationToProductInfo($data, $configuration)
    {
        $data[0] = $data[0] . '-' . md5(json_encode($configuration));
        $additionalName = null;
        if (isset($configuration['attributes']) && is_array($configuration['attributes'])) {
            $additionalNameArray = [];
            foreach ($configuration['attributes'] as $attribute) {
                $additionalNameArray[] = $attribute['groupname'] . ': ' . $attribute['title'];
            }
            $additionalName = implode(', ', $additionalNameArray);
        } else if (isset($configuration['additionaltext'])) {
            $additionalName = str_replace(" \n", ', ', trim($configuration['additionaltext'], "\n"));
        }
        if ($additionalName) {
            $data[1] .= ' (' . $additionalName . ')';
        }

        return $data;
    }

    protected function findProductNumber(&$item, $setValue = null)
    {
        if (is_array($item)) {
            return $this->findValueInArray($item, ['ordernumber'], $setValue);
        } else if ($item instanceof Detail) {
            if ($setValue) {
                $item->setArticleNumber($setValue);
                return $setValue;
            } else {
                return $item->getArticleNumber();
            }
        }
        return null;
    }

    protected function findProductName(&$item, $setValue = null)
    {
        if (is_array($item)) {
            return $this->findValueInArray($item, ['articlename'], $setValue);
        } else if ($item instanceof Detail) {
            if ($setValue) {
                $item->setArticleName($setValue);
                return $setValue;
            } else {
                return $item->getArticleName();
            }
        }
        return null;
    }

    /**
     * @param $item
     * @return array|null
     */
    protected function findProductConfiguration(&$item)
    {
        $attribute = null;
        if (is_array($item)) {
            $configuration = $this->findValueInArray($item, ['ob_bogx_configurator']);
            if ($configuration) {
                return json_decode($configuration, true);
            } else {
                $orderDetailId = $this->findValueInArray($item, ['orderDetailId']);
                /** @var OrderDetailAttribute $orderDetailAttribute */
                $attribute = $orderDetailId ? $this->modelManager->find(OrderDetailAttribute::class, $orderDetailId) : null;
            }
        } else if ($item instanceof Detail) {
            $attribute = $item->getAttribute();
        }
        if ($attribute && method_exists($attribute, 'getBogxProductconfigurator')) {
            $config = $attribute->getBogxProductconfigurator();
            return !empty($config) ? json_decode($config, true) : null;
        }
        return null;
    }

    final protected function findValueInArray(&$data = [], $keys = [], $setValue = null)
    {
        foreach ($keys as $key) {
            if (isset($data[$key])) {
                if ($setValue) {
                    return $data[$key] = $setValue;
                } else {
                    return $data[$key];
                }
            }
        }
        return null;
    }

    protected static function isBogxPluginInstalled()
    {
        return class_exists('BogxProductConfigurator\BogxProductConfigurator');
    }
}
