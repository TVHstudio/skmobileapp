<?php

/**
 * Copyright (c) 2016, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.com/
 * and is licensed under Oxwall Store Commercial License.
 *
 * Full text of this license can be found at http://developers.oxwall.com/store/oscl
 */
namespace Skadate\Mobile\Controller;

use OW;
use OW_Event;
use Silex\Application as SilexApplication;
use SKMOBILEAPP_BOL_PaymentsService;
use Symfony\Component\HttpFoundation\Request;

class MobileBilling extends Base
{
    /**
     * @var SKMOBILEAPP_BOL_PaymentsService
     */
    protected $paymentsService;

    public function __construct()
    {
        parent::__construct();

        $this->paymentsService = SKMOBILEAPP_BOL_PaymentsService::getInstance();
    }

    /**
     * Connect methods
     *
     * @param SilexApplication $app
     * @return mixed
     */
    public function connect(SilexApplication $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];

        /*
         * Initialize mobile sale.
         *
         * Request data:
         *
         *     {
         *         product: ProductModel,    // Product to deliver to the user after the sale is completed.
         *         gatewayKey: string,       // Payment gateway key.
         *         pluginKey: string,        // Key of the plugin that delivers the product.
         *     }
         *
         * ProductModel:
         *
         *     {
         *         id: int,                  // ID of the product in the database.
         *         price: double,            // Product price.
         *         period: ?int,             // Number of `periodUnits` this product is available, only for subscriptions.
         *         isRecurring: ?bool,       // True if this subscription is required, false otherwise, only for subscriptions.
         *         periodUnits: ?string,     // Period units (days, months, etc.), only for subscriptions.
         *     }
         *
         * Response data:
         *
         *     {
         *         saleId: string,           // Unique ID of the initialized sale.
         *     }
         */
        $controllers->post('/inits/', function (Request $request, SilexApplication $app) {
            $billingSessionData = json_decode($request->getContent(), true);
            $loggedUserId = $app['users']->getLoggedUserId();

            $saleId = SKMOBILEAPP_BOL_PaymentsService::getInstance()->initMobilePurchaseSession(
                $billingSessionData,
                $loggedUserId
            );

            return $app->json([ 'saleId' => $saleId ]);
        });

        /*
         * Prepare PayPal sale identified by the given `saleId` and return the related form fields.
         */
        $controllers->post('/prepare/paypal/{saleId}/', function (Request $request, SilexApplication $app, $saleId) {
            $event = new OW_Event('skmobileapp.prepare_paypal_sale', [
                'saleId' => $saleId
            ]);

            OW::getEventManager()->trigger($event);

            return $app->json($event->getData());
        });

        /*
         * Prepare Stripe sale identified by the given `saleId` and return Stripe checkout redirect URL.
         *
         * Response data:
         *
         *     {
         *         redirectUrl: string, // Stripe checkout redirect URL
         *     }
         */
        $controllers->post('/prepare/stripe/{saleId}/', function (Request $request, SilexApplication $app, $saleId) {
            $event = new OW_Event('skmobileapp.prepare_stripe_sale', [
                'saleId' => $saleId
            ]);

            OW::getEventManager()->trigger($event);

            $data = $event->getData();

            if (!is_array($data) || !isset($data['redirectUrl'])) {
                $data = [
                    'redirectUrl' => null
                ];
            }

            return $app->json($data);
        });

        /*
         * Set sale status to `error` by its hash.
         */
        $controllers->post('/mark-as-error/{saleHash}/', function (Request $request, SilexApplication $app, $saleHash) {
            $userId = $app['users']->getLoggedUserId();

            return $app->json([
                'success' => $this->paymentsService->markSaleAsErrorByHash($userId, $saleHash)
            ]);
        });

        return $controllers;
    }
}
