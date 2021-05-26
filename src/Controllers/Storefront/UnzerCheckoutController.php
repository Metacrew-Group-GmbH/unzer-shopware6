<?php

declare(strict_types=1);

namespace UnzerPayment6\Controllers\Storefront;

use ArgumentCountError;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\CheckoutController;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use UnzerPayment6\Components\PaymentHandler\Exception\UnzerPaymentProcessException;

/**
 * @RouteScope(scopes={"storefront"})
 */
class UnzerCheckoutController extends StorefrontController
{
    /** @var CheckoutController */
    protected $innerService;

    /** @var LoggerInterface */
    protected $logger;

    /** @var CheckoutFinishPageLoader */
    private $finishPageLoader;

    public function __construct(
        CheckoutController $innerService,
        LoggerInterface $logger,
        CheckoutFinishPageLoader $finishPageLoader
    ) {
        $this->innerService     = $innerService;
        $this->logger           = $logger;
        $this->finishPageLoader = $finishPageLoader;
    }

    public function cartPage(Request $request, SalesChannelContext $context): Response
    {
        return $this->innerService->cartPage($request, $context);
    }

    public function confirmPage(Request $request, SalesChannelContext $context): Response
    {
        return $this->innerService->confirmPage($request, $context);
    }

    public function info(Request $request, SalesChannelContext $context): Response
    {
        return $this->innerService->info($request, $context);
    }

    public function offcanvas(Request $request, SalesChannelContext $context): Response
    {
        return $this->innerService->offcanvas($request, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function order(RequestDataBag $data, SalesChannelContext $context, Request $request): Response
    {
        try {
            return $this->innerService->order($data, $context, $request);
        } catch (UnzerPaymentProcessException $apiException) {
            return $this->forwardToRoute(
                'frontend.checkout.finish.page',
                [
                    'orderId'                      => $apiException->getOrderId(),
                    'changedPayment'               => false,
                    'paymentFailed'                => true,
                    'unzerPaymentExceptionMessage' => $apiException->getClientMessage(),
                ]
            );
        }
    }

    public function finishPage(Request $request, SalesChannelContext $context, ?RequestDataBag $dataBag = null): Response
    {
        if (!$context->getCustomer()) {
            return $this->redirectToRoute('frontend.checkout.register.page');
        }

        $unzerPaymentExceptionMessage = $request->get('unzerPaymentExceptionMessage', '');

        if ($request->get('paymentFailed', false) === true && !empty($unzerPaymentExceptionMessage)) {
            $page = $this->finishPageLoader->load($request, $context);

            $this->addFlash(
                'danger',
                sprintf(
                    '%s %s',
                    $unzerPaymentExceptionMessage,
                    $this->trans(
                        'UnzerPayment.finishPaymentFailed',
                        [
                            '%editOrderUrl%' => $this->generateUrl(
                                'frontend.account.edit-order.page',
                                ['orderId' => $request->get('orderId')]
                            ),
                        ]
                    )
                )
            );

            return $this->renderStorefront(
                '@Storefront/storefront/page/checkout/finish/index.html.twig',
                ['page' => $page]
            );
        }

        try {
            /** @phpstan-ignore-next-line */
            return $this->innerService->finishPage($request, $context, $dataBag);
        } catch (ArgumentCountError $exception) {
            // signature changed with v6.4, this is the old version without dataBag Parameter
            /** @phpstan-ignore-next-line */
            return $this->innerService->finishPage($request, $context);
        }
    }
}
