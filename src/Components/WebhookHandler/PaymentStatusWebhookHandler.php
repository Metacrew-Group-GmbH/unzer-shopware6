<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\WebhookHandler;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Exception\InvalidUuidException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Components\Struct\Webhook;
use UnzerPayment6\Components\TransactionStateHandler\TransactionStateHandlerInterface;
use UnzerSDK\Resources\Payment;

/**
 * @property Payment $resource
 */
class PaymentStatusWebhookHandler implements WebhookHandlerInterface
{
    /** @var TransactionStateHandlerInterface */
    private $transactionStateHandler;

    /** @var ClientFactoryInterface */
    private $clientFactory;

    /** @var EntityRepositoryInterface */
    private $orderTransactionRepository;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        TransactionStateHandlerInterface $transactionStateHandler,
        ClientFactoryInterface $clientFactory,
        EntityRepositoryInterface $orderTransactionRepository,
        LoggerInterface $logger
    ) {
        $this->transactionStateHandler    = $transactionStateHandler;
        $this->clientFactory              = $clientFactory;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->logger                     = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Webhook $webhook, SalesChannelContext $context): bool
    {
        return stripos($webhook->getEvent(), 'payment.') !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Webhook $webhook, SalesChannelContext $context): void
    {
        $client  = $this->clientFactory->createClient();
        $payment = $client->getResourceService()->fetchResourceByUrl($webhook->getRetrieveUrl());

        if (!$payment instanceof Payment) {
            $this->logger->error(
                sprintf(
                    'Webhook could not be executed due to missing payment for retrieveUrl: %s',
                    $webhook->getRetrieveUrl()
                )
            );

            return;
        }

        $transaction = $this->getOrderTransaction($payment->getOrderId(), $context->getContext());

        if ($transaction === null) {
            $this->logger->error(
                sprintf(
                    'Webhook could not be executed due to missing transaction for payment: %s',
                    $payment->getOrderId()
                )
            );

            return;
        }

        $this->transactionStateHandler->transformTransactionState(
            $transaction->getId(),
            $payment,
            $context->getContext()
        );
    }

    private function getOrderTransaction(?string $orderId, Context $context): ?OrderTransactionEntity
    {
        if (empty($orderId)) {
            return null;
        }

        $criteria = new Criteria([$orderId]);

        try {
            $orderTransactions = $this->orderTransactionRepository->search($criteria, $context);

            if (empty($orderTransactions)) {
                return null;
            }

            return $orderTransactions->first();
        } catch (InvalidUuidException $exception) {
            $this->logger->error($exception->getMessage(), $exception->getTrace());

            return null;
        }
    }
}
