<?php


namespace RpayRatePay\Services\Request;


use Enlight_Components_Db_Adapter_Pdo_Mysql;
use RpayRatePay\Component\Mapper\BasketArrayBuilder;
use RpayRatePay\Helper\PositionHelper;
use RpayRatePay\Models\ProfileConfig;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Config\ProfileConfigService;
use RpayRatePay\Services\Factory\ExternalArrayFactory;
use RpayRatePay\Services\Factory\InvoiceArrayFactory;
use RpayRatePay\Services\Logger\HistoryLogger;
use RpayRatePay\Services\Logger\RequestLogger;
use Shopware\Components\Model\ModelManager;

class PaymentDeliverService extends AbstractModifyRequest
{

    /**
     * @var InvoiceArrayFactory
     */
    protected $invoiceArrayFactory;
    /**
     * @var ExternalArrayFactory
     */
    private $externalArrayFactory;

    public function __construct(
        Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        ConfigService $configService,
        RequestLogger $requestLogger,
        ProfileConfigService $profileConfigService,
        HistoryLogger $historyLogger,
        ModelManager $modelManager,
        PositionHelper $positionHelper,
        InvoiceArrayFactory $invoiceArrayFactory,
        ExternalArrayFactory $externalArrayFactory
    )
    {
        parent::__construct($db, $configService, $requestLogger, $profileConfigService, $historyLogger, $modelManager, $positionHelper);
        $this->invoiceArrayFactory = $invoiceArrayFactory;
        $this->externalArrayFactory = $externalArrayFactory;
    }

    protected function getCallName()
    {
        return self::CALL_DELIVER;
    }

    protected function isSkipRequest()
    {
        /** @deprecated v6.2 */
        if ($this->_order->getAttribute()->getRatepayDirectDelivery() != true) {
            if ($this->positionHelper->doesOrderHasOpenPositions($this->_order, $this->items)) {
                return true;
            } else {
                //deliver ALL positions!
                $basketArrayBuilder = new BasketArrayBuilder($this->_order, $this->_order->getDetails());
                $basketArrayBuilder->addShippingItem();
                $this->setItems($basketArrayBuilder);
                return false;
            }
        }
        return false;
    }

    protected function getRequestHead(ProfileConfig $profileConfig)
    {
        $head = parent::getRequestHead($profileConfig);
        $externalData = $this->externalArrayFactory->getData($this->_order);
        if ($externalData) {
            $head[ExternalArrayFactory::ARRAY_KEY] = $externalData;
        }
        return $head;
    }

    protected function getRequestContent()
    {
        $requestContent = parent::getRequestContent();

        $invoiceData = $this->invoiceArrayFactory->getData($this->_order);
        if ($invoiceData) {
            $requestContent[InvoiceArrayFactory::ARRAY_KEY] = $invoiceData;
        }

        return $requestContent;
    }

    protected function processSuccess()
    {
        foreach ($this->items as $basketPosition) {
            $position = $this->getOrderPosition($basketPosition);

            if ($this->_order->getAttribute()->getRatepayDirectDelivery() == false && $this->isRequestSkipped == false) {
                /** @deprecated v6.2 */
                // this is a little bit tricky:
                // if a rate payment has been processed and all items has been delivered,
                // we must set the delivered value to the final value manually
                // do not decrease with the returned items, cause the returned items should be always zero
                $position->setDelivered($position->getOrderedQuantity() - $position->getCancelled());
            } else {
                $position->setDelivered($position->getDelivered() + $basketPosition->getQuantity());
            }

            $this->modelManager->flush($position);

            if ($this->isRequestSkipped === false) {
                $this->historyLogger->logHistory($position, $basketPosition->getQuantity(), 'Artikel wurde versand.');
            } else {
                $this->historyLogger->logHistory($position, $basketPosition->getQuantity(), 'Artikel wurde für den Versand vorbereitet.');
            }
        }
        parent::processSuccess();
    }

}
