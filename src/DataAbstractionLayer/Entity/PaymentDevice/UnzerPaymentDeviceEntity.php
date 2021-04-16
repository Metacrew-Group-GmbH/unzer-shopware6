<?php

declare(strict_types=1);

namespace UnzerPayment6\DataAbstractionLayer\Entity\PaymentDevice;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class UnzerPaymentDeviceEntity extends Entity
{
    use EntityIdTrait;

    public const DEVICE_TYPE_CREDIT_CARD          = 'credit_card';
    public const DEVICE_TYPE_PAYPAL               = 'paypal_account';
    public const DEVICE_TYPE_DIRECT_DEBIT         = 'direct_debit';
    public const DEVICE_TYPE_DIRECT_DEBIT_SECURED = 'direct_debit_secured';

    /** @var string */
    protected $customerId;

    /** @var string */
    protected $deviceType;

    /** @var string */
    protected $typeId;

    /** @var array */
    protected $data;

    /** @var string */
    protected $addressHash;

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function setCustomerId(string $customerId): self
    {
        $this->customerId = $customerId;

        return $this;
    }

    public function getDeviceType(): string
    {
        return $this->deviceType;
    }

    public function setDeviceType(string $deviceType): self
    {
        $this->deviceType = $deviceType;

        return $this;
    }

    public function getTypeId(): string
    {
        return $this->typeId;
    }

    public function setTypeId(string $typeId): self
    {
        $this->typeId = $typeId;

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getAddressHash(): string
    {
        return $this->addressHash;
    }

    public function setAddressHash(string $addressHash): self
    {
        $this->addressHash = $addressHash;

        return $this;
    }
}
