<?php


namespace RpayRatePay\Commands;


use RpayRatePay\DTO\BasketPosition;
use RpayRatePay\Enum\PaymentMethods;
use RpayRatePay\Services\Request\AbstractModifyRequest;
use Shopware\Commands\ShopwareCommand;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\Order;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends ShopwareCommand
{

    /**
     * @var ModelManager
     */
    private $modelManager;

    public function __construct(ModelManager $modelManager, $name = null)
    {
        parent::__construct($name);
        $this->modelManager = $modelManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $operation = explode(':', $this->getName())[1];
        $this
            ->setDescription($operation . ' an item of a ratepay order or ' . $operation . ' a complete ratepay order.')
            ->addArgument('order', InputArgument::REQUIRED, 'related order')
            ->addArgument('orderDetail', InputArgument::OPTIONAL, 'order-detail-id/detail-number to ' . $operation)
            ->addArgument('qty', InputArgument::OPTIONAL, 'qty to ' . $operation);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $orderId = $input->getArgument('order');
        $orderDetailId = $input->getArgument('orderDetail');
        $qty = floatval($input->getArgument('qty'));

        $order = $this->modelManager->find(Order::class, $orderId);
        $order = $order ?: $this->modelManager->getRepository(Order::class)->findOneBy(['number' => $orderId]);
        if ($order == null) {
            $output->writeln('the order with the id/number \'' . $orderId . '\' was not found.');
            return 1;
        }

        if (PaymentMethods::exists($order->getPayment()->getName()) === false) {
            $output->writeln('the order with the id/number \'' . $orderId . '\' is not a ratepay order.');
            return 1;
        }

        $position = null;
        if ($orderDetailId) {
            if ($orderDetailId === BasketPosition::SHIPPING_NUMBER) {
                if ($order->getInvoiceShipping() > 0) {
                    $orderDetailNumber = BasketPosition::SHIPPING_NUMBER;
                    $position = new BasketPosition($orderDetailNumber, $qty);
                } else {
                    $output->writeln('nothing to do. This order does not have shipping costs');
                    return 0;
                }
            } else {
                $orderDetailRepo = $this->modelManager->getRepository(Detail::class);
                $orderDetail = $orderDetailRepo->findOneBy(['order' => $order->getId(), 'id' => $orderDetailId]);
                $orderDetail = $orderDetail ?: $orderDetailRepo->findOneBy(['order' => $order->getId(), 'articleNumber' => $orderDetailId]);

                if ($orderDetail == null) {
                    $output->writeln('the order detail with the id/number \'' . $orderDetailId . '\' was not found.');
                    return 1;
                }

                if ($qty <= 0) {
                    $output->writeln('the given quantity must be larger then zero');
                    return 1;
                }

                $position = new BasketPosition($orderDetail->getNumber(), $qty);
                if ($orderDetail) {
                    $position->setOrderDetail($orderDetail);
                }
            }
        }

        $requestService = $this->getRequestService();
        $requestService->setOrder($order);

        if ($position) {
            $requestService->setItems([$position]);
            $requestService->setOrder($order);
            $response = $requestService->doRequest();
        } else if (method_exists($requestService, 'doFullAction')) {
            $response = $requestService->doFullAction($order);
        } else {
            $output->writeln('nothing to do. full action is not possible for this operation');
            return 0;
        }

        if ($response === true || $response->getResponse()) {
            $output->writeln("Operation successfully");
            return 0;
        } else {
            $output->writeln("Operation failed. (Message: " . $response->getReasonMessage() . ")");
            return 1;
        }
    }

    /**
     * @return AbstractModifyRequest
     */
    protected abstract function getRequestService();
}
