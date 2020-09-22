<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Struct\PageExtension\Account;

use Shopware\Core\Framework\Struct\Struct;
use UnzerPayment6\DataAbstractionLayer\Entity\PaymentDevice\UnzerPaymentDeviceEntity;

class PaymentMethodPageExtension extends Struct
{
    /** @var HeidelpayPaymentDeviceEntity[] */
    protected $savedDevices = [];

    /** @var bool */
    protected $deviceRemoved = false;

    public function addPaymentDevices(array $paymentDevices): self
    {
        $this->savedDevices = array_merge($this->savedDevices, $paymentDevices);

        return $this;
    }

    /**
     * @return HeidelpayPaymentDeviceEntity[]
     */
    public function getSavedDevices(): array
    {
        return $this->savedDevices;
    }

    /**
     * @param HeidelpayPaymentDeviceEntity[] $savedDevices
     *
     * @return PaymentMethodPageExtension
     */
    public function setSavedDevices(array $savedDevices): self
    {
        $this->savedDevices = $savedDevices;

        return $this;
    }

    public function isDeviceRemoved(): bool
    {
        return $this->deviceRemoved;
    }

    public function setDeviceRemoved(bool $deviceRemoved): self
    {
        $this->deviceRemoved = $deviceRemoved;

        return $this;
    }
}
