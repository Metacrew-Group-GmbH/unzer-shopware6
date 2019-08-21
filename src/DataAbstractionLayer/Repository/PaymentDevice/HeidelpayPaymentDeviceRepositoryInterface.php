<?php

declare(strict_types=1);

namespace HeidelPayment\DataAbstractionLayer\Repository\PaymentDevice;

use HeidelPayment\DataAbstractionLayer\Entity\PaymentDevice\HeidelpayPaymentDeviceEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;

interface HeidelpayPaymentDeviceRepositoryInterface
{
    public function getCollectionByCustomer(CustomerEntity $customer, Context $context): EntitySearchResult;

    public function create(CustomerEntity $customer, string $deviceType, string $typeId, array $data, Context $context): EntityWrittenContainerEvent;

    public function remove(string $id, Context $context): EntityWrittenContainerEvent;

    public function exists(string $typeId, Context $context): bool;

    public function get(string $id, Context $context): ?HeidelpayPaymentDeviceEntity;
}
