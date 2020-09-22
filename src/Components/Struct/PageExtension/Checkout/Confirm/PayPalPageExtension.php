<?php

declare(strict_types=1);

namespace UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm;

use Shopware\Core\Framework\Struct\Struct;
use UnzerPayment6\DataAbstractionLayer\Entity\PaymentDevice\HeidelpayPaymentDeviceEntity;

class PayPalPageExtension extends Struct
{
    /** @var HeidelpayPaymentDeviceEntity[] */
    protected $payPalAccounts = [];

    /** @var bool */
    protected $displayPayPalAccountselection;

    public function addPayPalAccount(HeidelpayPaymentDeviceEntity $paypalAccount): self
    {
        $this->payPalAccounts[] = $paypalAccount;

        return $this;
    }

    /**
     * @return HeidelpayPaymentDeviceEntity[]
     */
    public function getPayPalAccounts(): array
    {
        return $this->payPalAccounts;
    }

    /**
     * @param HeidelpayPaymentDeviceEntity[] $payPalAccounts
     *
     * @return PayPalPageExtension
     */
    public function setPayPalAccounts(array $payPalAccounts): self
    {
        $this->payPalAccounts = $payPalAccounts;

        return $this;
    }

    public function getDisplaypayPalAccountselection(): bool
    {
        return $this->displayPayPalAccountselection;
    }

    public function setDisplaypayPalAccountselection(bool $displayPayPalAccountselection): self
    {
        $this->displayPayPalAccountselection = $displayPayPalAccountselection;

        return $this;
    }
}
