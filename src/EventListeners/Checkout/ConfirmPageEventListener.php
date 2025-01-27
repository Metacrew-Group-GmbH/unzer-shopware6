<?php

declare(strict_types=1);

namespace UnzerPayment6\EventListeners\Checkout;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;
use UnzerPayment6\Components\ClientFactory\ClientFactoryInterface;
use UnzerPayment6\Components\ConfigReader\ConfigReader;
use UnzerPayment6\Components\ConfigReader\ConfigReaderInterface;
use UnzerPayment6\Components\PaymentFrame\PaymentFrameFactoryInterface;
use UnzerPayment6\Components\Struct\Configuration;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\ApplePayPageExtension;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\CreditCardPageExtension;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\DirectDebitPageExtension;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\DirectDebitSecuredPageExtension;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\FraudPreventionPageExtension;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\InstallmentSecuredPageExtension;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\PaymentFramePageExtension;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\PayPalPageExtension;
use UnzerPayment6\Components\Struct\PageExtension\Checkout\Confirm\UnzerDataPageExtension;
use UnzerPayment6\DataAbstractionLayer\Entity\PaymentDevice\UnzerPaymentDeviceEntity;
use UnzerPayment6\DataAbstractionLayer\Repository\PaymentDevice\UnzerPaymentDeviceRepositoryInterface;
use UnzerPayment6\Installer\PaymentInstaller;
use UnzerSDK\Resources\Customer;

class ConfirmPageEventListener implements EventSubscriberInterface
{
    private const INSTALLMENT_SECURED_EFFECTIVE_INTEREST_DEFAULT = 4.5;

    /** @var Configuration */
    protected $configData;

    /** @var UnzerPaymentDeviceRepositoryInterface */
    private $deviceRepository;

    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var PaymentFrameFactoryInterface */
    private $paymentFrameFactory;

    /** @var SystemConfigService */
    private $systemConfigReader;

    /** @var EntityRepository */
    private $languageRepository;

    /** @var ClientFactoryInterface */
    private $clientFactory;

    public function __construct(
        UnzerPaymentDeviceRepositoryInterface $deviceRepository,
        ConfigReaderInterface $configReader,
        PaymentFrameFactoryInterface $paymentFrameFactory,
        SystemConfigService $systemConfigReader,
        EntityRepository $languageRepository,
        ClientFactoryInterface $clientFactory
    ) {
        $this->deviceRepository    = $deviceRepository;
        $this->configReader        = $configReader;
        $this->paymentFrameFactory = $paymentFrameFactory;
        $this->systemConfigReader  = $systemConfigReader;
        $this->languageRepository  = $languageRepository;
        $this->clientFactory       = $clientFactory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class  => 'onCheckoutConfirm',
            AccountEditOrderPageLoadedEvent::class => 'onCheckoutConfirm',
        ];
    }

    public function onCheckoutConfirm(PageLoadedEvent $event): void
    {
        if (!($event instanceof CheckoutConfirmPageLoadedEvent) && !($event instanceof AccountEditOrderPageLoadedEvent)) {
            return;
        }

        $salesChannelContext = $event->getSalesChannelContext();
        $this->configData    = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());

        if ($salesChannelContext->getPaymentMethod()->getId() === PaymentInstaller::PAYMENT_ID_CREDIT_CARD
        ) {
            $this->addCreditCardExtension($event);
        }

        if ($salesChannelContext->getPaymentMethod()->getId() === PaymentInstaller::PAYMENT_ID_PAYPAL
        ) {
            $this->addPayPalExtension($event);
        }

        if ($salesChannelContext->getPaymentMethod()->getId() === PaymentInstaller::PAYMENT_ID_DIRECT_DEBIT
        ) {
            $this->addDirectDebitExtension($event);
        }

        if ($salesChannelContext->getPaymentMethod()->getId() === PaymentInstaller::PAYMENT_ID_DIRECT_DEBIT_SECURED
        ) {
            $this->addDirectDebitSecuredExtension($event);
        }

        if ($salesChannelContext->getPaymentMethod()->getId() === PaymentInstaller::PAYMENT_ID_PAYLATER_INVOICE) {
            $this->addFraudPreventionExtension($event);
        }

        if ($salesChannelContext->getPaymentMethod()->getId() === PaymentInstaller::PAYMENT_ID_INSTALLMENT_SECURED) {
            $this->addInstallmentSecuredExtension($event);
        }

        if ($salesChannelContext->getPaymentMethod()->getId() === PaymentInstaller::PAYMENT_ID_APPLE_PAY) {
            $this->addApplePayExtension($event);
        }

        if (in_array($salesChannelContext->getPaymentMethod()->getId(), PaymentInstaller::PAYMENT_METHOD_IDS)) {
            $this->addPaymentFrameExtension($event);
            $this->addUnzerDataExtension($event);
        }
    }

    private function addFraudPreventionExtension(PageLoadedEvent $event): void
    {
        $extension = new FraudPreventionPageExtension();
        $extension->setFraudPreventionSessionId(Uuid::randomHex());

        $event->getPage()->addExtension(FraudPreventionPageExtension::EXTENSION_NAME, $extension);
    }

    private function addUnzerDataExtension(PageLoadedEvent $event): void
    {
        $context = $event->getSalesChannelContext()->getContext();

        $extension = new UnzerDataPageExtension();
        $extension->setPublicKey($this->configData->get(ConfigReader::CONFIG_KEY_PUBLIC_KEY));
        $extension->setLocale($this->getLocaleByLanguageId($context->getLanguageId(), $context));
        $extension->setShowTestData((bool) $this->configData->get(ConfigReader::CONFIG_KEY_TEST_DATA));
        $extension->setUnzerCustomer($this->getUnzerCustomer($event));

        $event->getPage()->addExtension(UnzerDataPageExtension::EXTENSION_NAME, $extension);
    }

    private function getUnzerCustomer(PageLoadedEvent $event): ?Customer
    {
        $customer = $event->getSalesChannelContext()->getCustomer();

        if ($customer === null) {
            return null;
        }

        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();

        $client         = $this->clientFactory->createClient($salesChannelId);
        $customerNumber = $customer->getCustomerNumber();
        $billingAddress = $customer->getActiveBillingAddress();

        if ($billingAddress !== null && !empty($billingAddress->getCompany())) {
            $customerNumber .= '_b';
        }

        try {
            return $client->fetchCustomerByExtCustomerId($customerNumber);
        } catch (Throwable $t) {
            return null;
        }
    }

    private function addPaymentFrameExtension(PageLoadedEvent $event): void
    {
        $paymentId           = $event->getSalesChannelContext()->getPaymentMethod()->getId();
        $mappedFrameTemplate = $this->paymentFrameFactory->getPaymentFrame($paymentId);

        if (!$mappedFrameTemplate) {
            return;
        }

        $shopName = $this->systemConfigReader->get(
            'core.basicInformation.shopName',
            $event->getSalesChannelContext()->getSalesChannel()->getId()
        );

        $event->getPage()->addExtension(
            PaymentFramePageExtension::EXTENSION_NAME,
            (new PaymentFramePageExtension())
                ->setPaymentFrame($mappedFrameTemplate)
                ->setShopName(is_string($shopName) ? $shopName : '')
        );
    }

    private function addCreditCardExtension(PageLoadedEvent $event): void
    {
        $customer = $event->getSalesChannelContext()->getCustomer();

        if (!$customer) {
            return;
        }

        $creditCards = $this->deviceRepository->getCollectionByCustomer($customer, $event->getContext(), UnzerPaymentDeviceEntity::DEVICE_TYPE_CREDIT_CARD);
        $extension   = new CreditCardPageExtension();

        /** @var UnzerPaymentDeviceEntity $creditCard */
        foreach ($creditCards as $creditCard) {
            $extension->addCreditCard($creditCard);
        }

        $event->getPage()->addExtension(CreditCardPageExtension::EXTENSION_NAME, $extension);
    }

    private function addPayPalExtension(PageLoadedEvent $event): void
    {
        $customer = $event->getSalesChannelContext()->getCustomer();

        if (!$customer) {
            return;
        }

        $payPalAccounts = $this->deviceRepository->getCollectionByCustomer($customer, $event->getContext(), UnzerPaymentDeviceEntity::DEVICE_TYPE_PAYPAL);
        $extension      = new PayPalPageExtension();

        /** @var UnzerPaymentDeviceEntity $payPalAccount */
        foreach ($payPalAccounts as $payPalAccount) {
            $extension->addPayPalAccount($payPalAccount);
        }

        $event->getPage()->addExtension(PayPalPageExtension::EXTENSION_NAME, $extension);
    }

    private function addDirectDebitExtension(PageLoadedEvent $event): void
    {
        $customer = $event->getSalesChannelContext()->getCustomer();

        if (!$customer) {
            return;
        }

        $directDebitDevices = $this->deviceRepository->getCollectionByCustomer($customer, $event->getContext(), UnzerPaymentDeviceEntity::DEVICE_TYPE_DIRECT_DEBIT);
        $extension          = new DirectDebitPageExtension();

        /** @var UnzerPaymentDeviceEntity $directDebitDevice */
        foreach ($directDebitDevices as $directDebitDevice) {
            $extension->addDirectDebitDevice($directDebitDevice);
        }

        $event->getPage()->addExtension(DirectDebitPageExtension::EXTENSION_NAME, $extension);
    }

    private function addDirectDebitSecuredExtension(PageLoadedEvent $event): void
    {
        $customer = $event->getSalesChannelContext()->getCustomer();

        if (!$customer) {
            return;
        }

        $directDebitDevices = $this->deviceRepository->getCollectionByCustomer($customer, $event->getContext(), UnzerPaymentDeviceEntity::DEVICE_TYPE_DIRECT_DEBIT_SECURED);
        $extension          = (new DirectDebitSecuredPageExtension())->setDisplayDirectDebitDeviceSelection(true);

        /** @var UnzerPaymentDeviceEntity $directDebitDevice */
        foreach ($directDebitDevices as $directDebitDevice) {
            $extension->addDirectDebitDevice($directDebitDevice);
        }

        $event->getPage()->addExtension(DirectDebitSecuredPageExtension::EXTENSION_NAME, $extension);
    }

    private function addInstallmentSecuredExtension(PageLoadedEvent $event): void
    {
        $extension = new InstallmentSecuredPageExtension();
        $extension->setCurrency($event->getSalesChannelContext()->getCurrency()->getIsoCode());
        $extension->setEffectiveInterest((float) $this->configData->get(ConfigReader::CONFIG_KEY_INSTALLMENT_SECURED_INTEREST, self::INSTALLMENT_SECURED_EFFECTIVE_INTEREST_DEFAULT));

        if ($event instanceof CheckoutConfirmPageLoadedEvent) {
            $extension->setAmount($event->getPage()->getCart()->getPrice()->getTotalPrice());
        } elseif ($event instanceof AccountEditOrderPageLoadedEvent) {
            $extension->setAmount($event->getPage()->getOrder()->getPrice()->getTotalPrice());
        }
        $extension->setOrderDate(date('Y-m-d'));

        $event->getPage()->addExtension(InstallmentSecuredPageExtension::EXTENSION_NAME, $extension);
    }

    private function addApplePayExtension(PageLoadedEvent $event): void
    {
        $event->getPage()->addExtension(ApplePayPageExtension::EXTENSION_NAME, new ApplePayPageExtension());
    }

    private function getLocaleByLanguageId(string $languageId, Context $context): string
    {
        $critera = new Criteria([$languageId]);
        $critera->addAssociation('locale');

        /** @var null|\Shopware\Core\System\Language\LanguageEntity $searchResult */
        $searchResult = $this->languageRepository->search($critera, $context)->first();

        if ($searchResult === null || $searchResult->getLocale() === null) {
            return ClientFactoryInterface::DEFAULT_LOCALE;
        }

        return $searchResult->getLocale()->getCode();
    }
}
