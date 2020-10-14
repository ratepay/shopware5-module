<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Commands;


use RatePAY\RequestBuilder;
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
use Symfony\Component\Console\Style\SymfonyStyle;
use function Doctrine\ORM\QueryBuilder;

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
            ->addArgument('order', InputArgument::REQUIRED, 'related order (id, number or transaction-id')
            ->addArgument('orderDetail', InputArgument::OPTIONAL, 'order-detail-id/detail-number to ' . $operation . ' (if not provided, a full-action of the operation will be performed)')
            ->addArgument('qty', InputArgument::OPTIONAL, 'qty to ' . $operation . ' (if not provided, the ordered quantity will be used)');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $orderId = $input->getArgument('order');
        $orderDetailId = $input->getArgument('orderDetail');
        $qty = $input->getArgument('qty') ? (float)$input->getArgument('qty') : null;

        $qb = $this->modelManager->getRepository(Order::class)->createQueryBuilder('e');
        $qb->orWhere(
            $qb->expr()->eq('e.id', ':orderIdentifier'),
            $qb->expr()->eq('e.number', ':orderIdentifier'),
            $qb->expr()->eq('e.transactionId', ':orderIdentifier')
        )->setMaxResults(1)->setParameter('orderIdentifier', $orderId);

        $order = $qb->getQuery()->getOneOrNullResult();
        if ($order === null) {
            $io->error("the order with the id/number '$orderId'  was not found.");
            return 1;
        }

        if (PaymentMethods::exists($order->getPayment()->getName()) === false) {
            $io->error("the order with the id/number '$orderId' is not a ratepay order.");
            return 1;
        }

        $position = null;
        if ($orderDetailId) {
            if ($orderDetailId === BasketPosition::SHIPPING_NUMBER) {
                if ($order->getInvoiceShipping() > 0) {
                    $orderDetailNumber = BasketPosition::SHIPPING_NUMBER;
                    $position = new BasketPosition($orderDetailNumber, $qty);
                } else {
                    $io->note('nothing to do. This order does not have shipping costs');
                    return 0;
                }
            } else {
                $orderDetailRepo = $this->modelManager->getRepository(Detail::class);
                $orderDetail = $orderDetailRepo->findOneBy(['order' => $order->getId(), 'id' => $orderDetailId]);
                $orderDetail = $orderDetail ?: $orderDetailRepo->findOneBy(['order' => $order->getId(), 'articleNumber' => $orderDetailId]);

                if ($orderDetail === null) {
                    $io->error("the order detail with the id/number '$orderDetailId' was not found.'");
                    return 1;
                }

                $qty = $qty ?: $orderDetail->getQuantity();
                if ($qty <= 0) {
                    $io->error('the given quantity must be larger then zero');
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
            $io->error('Full action is not possible for this operation');
            return 0;
        }

        if ($response === null) {
            $io->note("Nothing to perform.");
            return 0;
        }

        if ($response === true) {
            $io->success("Operation successfully. (Info: Request has been skipped.");
            return 0;
        }

        if ($response instanceof RequestBuilder) {
            if ($response->getResponse()->isSuccessful()) {
                $io->success($response->getResponse()->getResultMessage());
                return 0;
            }

            $io->error($response->getResponse()->getReasonMessage());
            return 1;
        }

        $io->error("Unknown error");
        return 1;
    }

    /**
     * @return AbstractModifyRequest
     */
    abstract protected function getRequestService();
}
