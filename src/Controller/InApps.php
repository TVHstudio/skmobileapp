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

use BOL_QuestionService;
use OW;
use OW_Log;
use Silex\Application as SilexApplication;
use SKMOBILEAPP_BOL_PaymentsService;
use SKMOBILEAPP_BOL_Service;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;
use USERCREDITS_BOL_CreditsService;

class InApps extends Base
{
    /**
     * @var bool True if the paid membership plugin is active.
     */
    protected $isMembershipPluginActive = false;

    /**
     * @var bool True if the user credits plugin is active.
     */
    protected $isUserCreditsPluginActive = false;

    /**
     * @var SKMOBILEAPP_BOL_PaymentsService
     */
    protected $paymentsService;

    /**
     * @var OW_Log
     */
    protected $logger;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->isMembershipPluginActive = OW::getPluginManager()->isPluginActive(
            SKMOBILEAPP_BOL_Service::MEMBERSHIP_PLUGIN_KEY
        );

        $this->isUserCreditsPluginActive = OW::getPluginManager()->isPluginActive(
            SKMOBILEAPP_BOL_Service::USER_CREDITS_PLUGIN_KEY
        );

        $this->paymentsService = SKMOBILEAPP_BOL_PaymentsService::getInstance();
        $this->logger = SKMOBILEAPP_BOL_Service::getLogger();
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
         * Validate and optionally deliver Android purchase. If the purchase is valid, it will be delivered to the user
         * account.
         *
         * Request object:
         *
         *     {
         *         "productId":      string,  // Skadate product ID, e.g. `membership_plan_8`, required.
         *         "purchaseToken":  string,  // Purchase token received from the store, required.
         *     }
         *
         * Response object:
         *
         *     {
         *         "isValid":   string,     // True if the purchase is valid, false otherwise.
         *         "isRenewal": boolean,    // Always false.
         *         "ignore":    boolean,    // Always false.
         *     }
         */
        $controllers->post('/validate/android/', function (Request $request) use ($app) {
            $this->ensurePluginsActive();

            $userId = $app['users']->getLoggedUserId();

            $requestData = json_decode($request->getContent(), true);
            $productId = $requestData['productId'] ?? null;
            $purchaseToken = $requestData['purchaseToken'] ?? null;

            $this->logger->addEntry(
                "Validation request data:\n\n" . print_r($requestData, true),
                'in_app_purchases.controller.validate_android'
            );

            $this->logger->writeLog();

            if (!is_string($productId) || empty($productId) || !is_string($purchaseToken) || empty($purchaseToken)) {
                $this->logger->addEntry(
                    'Invalid validation request data. The request data should be a JSON object containing the ' .
                    'productId and purchaseToken fields.',
                    'in_app_purchases.controller.validate_android'
                );

                $this->logger->writeLog();

                return $app->json([
                    'isValid' => false,
                    'isRenewal' => false,
                    'ignore' => false
                ]);
            }

            try {
                $sale = $this->paymentsService->processNewGooglePlayPurchase($productId, $purchaseToken);

                if (!$sale) {
                    return $app->json([
                        'isValid' => false,
                        'isRenewal' => false,
                        'ignore' => false
                    ]);
                }
            } catch (Throwable $e) {
                $this->logger->addEntry(
                    'Exception during the product validation, product ID: `' . $productId . '`, user ID ' .
                    $userId . ': "' . $e->getMessage() . "\"; stack trace:\n\n" . $e->getTraceAsString(),
                    'in_app_purchases.controller.validate_android'
                );

                $this->logger->writeLog();

                return $app->json([
                    'isValid' => false,
                    'isRenewal' => false,
                    'ignore' => false
                ]);
            }

            return $app->json([
                'isValid' => true,
                'isRenewal' => false,
                'ignore' => false
            ]);
        });

        /*
         * Validate and optionally deliver iOS purchase. If the purchase is valid, it will be delivered to the user
         * account.
         *
         * Request object:
         *
         *     {
         *         "productId":      string,  // Skadate product ID, e.g. `membership_plan_8`, required.
         *         "purchaseId":     string,  // Purchase ID received from the store, required.
         *         "purchaseToken":  string,  // Purchase token received from the store, required.
         *     }
         *
         * Response object:
         *
         *     {
         *         "isValid":   string,     // True if the purchase is valid, false otherwise.
         *         "isRenewal": boolean,    // True if the purchase represents a subscription renewal.
         *         "ignore":    boolean,    // True if the result should be ignored by the app.
         *     }
         */
        $controllers->post('/validate/ios/', function (Request $request) use ($app) {
            $this->ensurePluginsActive();

            $userId = $app['users']->getLoggedUserId();

            $requestData = json_decode($request->getContent(), true);
            $productId = $requestData['productId'] ?? null;
            $orderId = $requestData['purchaseId'] ?? null;
            $receiptData = $requestData['purchaseToken'] ?? null;

            $this->logger->addEntry(
                "Validation request data:\n\n" . print_r($requestData, true),
                'in_app_purchases.controller.validate_ios'
            );

            if (
                !is_string($productId) ||
                empty($productId) ||
                !is_string($orderId) ||
                empty($orderId) ||
                !is_string($receiptData) ||
                empty($receiptData)
            ) {
                $this->logger->addEntry(
                    'Invalid validation request data. The request data should be a JSON object containing the ' .
                    'productId, purchaseId and purchaseToken fields. The purchaseToken field should contain the App Store ' .
                    'receipt data.',
                    'in_app_purchases.controller.validate_ios'
                );

                $this->logger->writeLog();

                return $app->json([
                    'isValid' => false,
                    'isRenewal' => false,
                    'ignore' => false
                ]);
            }

            try {
                $sale = $this->paymentsService->processNewAppStorePurchase($productId, $orderId, $receiptData);

                if (is_array($sale) && $sale['sale_exists']) {
                    return $app->json([
                        'isValid' => false,
                        'isRenewal' => false,
                        'ignore' => true
                    ]);
                }

                if (!$sale) {
                    return $app->json([
                        'isValid' => false,
                        'isRenewal' => false,
                        'ignore' => false
                    ]);
                }
            } catch (Throwable $e) {
                $this->logger->addEntry(
                    'Exception during the product validation, product ID: `' . $productId . '`, user ID ' .
                    $userId . ': "' . $e->getMessage() . "\"; stack trace:\n\n" . $e->getTraceAsString(),
                    'in_app_purchases.controller.validate_android'
                );

                $this->logger->writeLog();

                return $app->json([
                    'isValid' => false,
                    'isRenewal' => false,
                    'ignore' => false
                ]);
            }

            $extraData = json_decode($sale->extraData, true);

            return $app->json([
                'isValid' => true,
                'isRenewal' => $extraData['app_store_renewal'] ?? false,
                'ignore' => false
            ]);
        });

        // get all products
        $controllers->get('/products/', function () use ($app) {
            $loggedUserId = $app['users']->getLoggedUserId();

            $products = [
                'membershipPlans' => [],
                'creditPacks' => []
            ];

            if ( $this->isMembershipPluginActive ) {
                $paymentsService = SKMOBILEAPP_BOL_PaymentsService::getInstance();

                // get all memberships
                $memberships = $paymentsService->getMemberships($loggedUserId);

                foreach ($memberships as $membershipData) {
                    $fullMembershipInfo = $paymentsService->getFullMembershipInfo($membershipData['id'], $loggedUserId);

                    if (!empty($fullMembershipInfo['plans'])) {
                        foreach ($fullMembershipInfo['plans'] as $plan) {
                            $products['membershipPlans'][] = $plan;
                        }
                    }
                }
            }

            if ( $this->isUserCreditsPluginActive )
            {
                $creditsService = USERCREDITS_BOL_CreditsService::getInstance();

                // get user account type
                $user = $this->userService->findUserById($loggedUserId);
                $accTypeName = $user->getAccountType();
                $accType = BOL_QuestionService::getInstance()->findAccountTypeByName($accTypeName);

                // get packs
                $packs = $creditsService->getPackList($accType->id);

                foreach ($packs as $pack)
                {
                    $products['creditPacks'][] = $pack;
                }
            }

            return $app->json($products);
        });

        return $controllers;
    }

    /**
     * Checks whether the membership and usercredits plugins are active. Throws BadRequestHttpException if both of them
     * are inactive.
     *
     * @throws BadRequestHttpException
     *
     * @return void
     */
    protected function ensurePluginsActive()
    {
        if (!$this->isMembershipPluginActive && !$this->isUserCreditsPluginActive) {
            throw new BadRequestHttpException('Neither membership nor usercredits plugins are active.');
        }
    }
}
