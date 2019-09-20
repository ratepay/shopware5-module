<?php


namespace RpayRatePay\Services\Request;


use Doctrine\ORM\Query\Expr\Join;
use Enlight_Components_Db_Adapter_Pdo_Mysql;
use RpayRatePay\Component\Mapper\BasketArrayBuilder;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Helper\PositionHelper;
use RpayRatePay\Models\Position\AbstractPosition;
use RpayRatePay\Models\Position\Product as ProductPosition;
use RpayRatePay\Services\Config\ConfigService;
use RpayRatePay\Services\Config\ProfileConfigService;
use RpayRatePay\Services\Factory\BasketArrayFactory;
use RpayRatePay\Services\Factory\InvoiceArrayFactory;
use RpayRatePay\Services\Logger\HistoryLogger;
use RpayRatePay\Services\Logger\RequestLogger;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\Order;

class PaymentDeliverService extends AbstractModifyRequest
{

    /**
     * @var InvoiceArrayFactory
     */
    protected $invoiceArrayFactory;

    public function __construct(
        Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        ConfigService $configService,
        ProfileConfigService $profileConfigService,
        RequestLogger $requestLogger,
        HistoryLogger $historyLogger,
        ModelManager $modelManager,
        PositionHelper $positionHelper,
        InvoiceArrayFactory $invoiceArrayFactory
    )
    {
        parent::__construct($db, $configService, $profileConfigService, $requestLogger, $historyLogger, $modelManager, $positionHelper);
        $this->invoiceArrayFactory = $invoiceArrayFactory;
    }

    protected function getCallName()
    {
        return self::CALL_DELIVER;
    }

    protected function isSkipRequest() {
        if (PaymentMethods::isInstallment($this->_order->getPayment())) {
            foreach ($this->items as $productNumber => $quantity) {
                $position = $this->getOrderPosition($productNumber);
                if (($position->getOpenQuantity() - $quantity) !== 0) {
                    //if the order is NOT complete
                    return true;
                }
            }
        }
        return false;
    }

    protected function getRequestContent()
    {
        $requestContent = parent::getRequestContent();

        $invoiceData = $this->invoiceArrayFactory->getData($this->_order);
        if($invoiceData) {
            $requestContent[InvoiceArrayFactory::ARRAY_KEY] = $invoiceData;
        }
        return $requestContent;
    }

    protected function processSuccess()
    {
        foreach ($this->items as $productNumber => $quantity) {
            $position = $this->getOrderPosition($productNumber);
            $position->setDelivered($position->getDelivered() + $quantity);
            $this->modelManager->flush($position);

            if ($this->isRequestSkipped === false) {
                $this->historyLogger->logHistory($position, $quantity, 'Artikel wurde versand.');
            } else {
                $this->historyLogger->logHistory($position, $quantity, 'Artikel wurde für den Versand vorbereitet.');
            }
        }
    }

}
