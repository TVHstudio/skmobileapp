<?php

/**
 * Copyright (c) 2021, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.com/
 * and is licensed under Oxwall Store Commercial License.
 *
 * Full text of this license can be found at http://developers.oxwall.com/store/oscl
 */

namespace Skadate\Mobile\Controller;

use Exception;
use OW;
use OW_Log;
use Silex\Application as SilexApplication;
use Silex\ControllerCollection;
use SKMOBILEAPP_BOL_GooglePlayPurchaseNotification;
use SKMOBILEAPP_BOL_GooglePlaySubscriptionNotification;
use SKMOBILEAPP_BOL_Service;
use SKMOBILEAPP_BOL_WebhookService;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

/**
 * Handles various external notifications.
 *
 * @package Skadate\Mobile\Controller
 */
class Webhooks extends Base
{
    const WEBHOOK_SOURCE_GOOGLE_PLAY = 'google_play';
    const WEBHOOK_SOURCE_APP_STORE = 'app_store';

    /**
     * @var OW_Log
     */
    protected $logger;

    /**
     * @var string
     */
    protected $bundleId;

    /**
     * @var SKMOBILEAPP_BOL_WebhookService
     */
    protected $webhookService;

    public function __construct()
    {
        parent::__construct();

        $this->logger = OW::getLogger(SKMOBILEAPP_BOL_Service::PLUGIN_KEY);

        $this->bundleId = OW::getConfig()->getValue(
            SKMOBILEAPP_BOL_Service::PLUGIN_KEY,
            'inapps_apm_package_name'
        );

        $this->webhookService = SKMOBILEAPP_BOL_WebhookService::getInstance();
    }

    public function connect(SilexApplication $app)
    {
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        /*
         * Handle Google Play notifications.
         */
        $controllers->post('/google-play/', function (Request $request) use ($app) {
            $data = json_decode($request->getContent(), true);
            $messageRaw = $data['message']['data'] ?? null;

            if (!$data || !$messageRaw) {
                $this->addLogEntry('Invalid webhook data: invalid JSON', self::WEBHOOK_SOURCE_GOOGLE_PLAY);
                $this->writeLog();

                return $app->json([], 204);
            }

            $notificationRaw = base64_decode($messageRaw, true);
            $notification = json_decode($notificationRaw, true);
            $bundleId = $notification['packageName'] ?? null;

            if (!$notification || !$bundleId) {
                $this->addLogEntry('Invalid webhook data: invalid JSON', self::WEBHOOK_SOURCE_GOOGLE_PLAY);
                $this->writeLog();

                return $app->json([], 204);
            }

            $this->addLogEntry(
                "Raw notification data:\n\n" . print_r($notification, true),
                self::WEBHOOK_SOURCE_GOOGLE_PLAY
            );

            $this->writeLog();

            if ($bundleId !== $this->bundleId) {
                $this->addLogEntry(
                    'Received notification for bundle ID `' . $bundleId . '` while the webhook is set ' .
                    'to handle notifications for bundle ID `' . $this->bundleId . '`, ignoring',
                    self::WEBHOOK_SOURCE_GOOGLE_PLAY
                );

                $this->writeLog();

                return $app->json([], 204);
            }

            // Handle one-time product notification.
            if (
                isset($notification['oneTimeProductNotification']) &&
                !empty($notification['oneTimeProductNotification'])
            ) {
                try {
                    $purchase = SKMOBILEAPP_BOL_GooglePlayPurchaseNotification::fromJson(
                        $notification['oneTimeProductNotification']
                    );
                } catch (Exception $e) {
                    $this->addLogEntry(
                        'Exception during the product notification parsing: "' . $e->getMessage() . '"' .
                        "; stack trace:\n\n" . $e->getTraceAsString(),
                        self::WEBHOOK_SOURCE_GOOGLE_PLAY
                    );

                    $this->writeLog();

                    return $app->json([], 204);
                }

                $this->addLogEntry(
                    'Product notification received for product `' . $purchase->getProductId() . '`, ' .
                    'purchase token: "' . $purchase->getPurchaseToken() . '"',
                    self::WEBHOOK_SOURCE_GOOGLE_PLAY
                );

                $this->webhookService->handleGooglePlayPurchaseNotification($purchase);
            }

            // Handle subscription notification.
            if (isset($notification['subscriptionNotification']) && !empty($notification['subscriptionNotification'])) {
                try {
                    $sub = SKMOBILEAPP_BOL_GooglePlaySubscriptionNotification::fromJson(
                        $notification['subscriptionNotification']
                    );
                } catch (Exception $e) {
                    $this->addLogEntry(
                        'Exception during the subscription notification parsing: "' . $e->getMessage() . '"' .
                        "; stack trace:\n\n" . $e->getTraceAsString(),
                        self::WEBHOOK_SOURCE_GOOGLE_PLAY
                    );

                    $this->writeLog();

                    return $app->json([], 204);
                }

                $this->addLogEntry(
                    'Subscription notification received for product `' . $sub->getSubscriptionId() . '`' .
                    ', purchase token: "' . $sub->getPurchaseToken() . '"',
                    self::WEBHOOK_SOURCE_GOOGLE_PLAY
                );

                $this->writeLog();

                $this->webhookService->handleGooglePlaySubscriptionNotification($sub);
            }

            // Handle test notification.
            if (isset($notification['testNotification']) && !empty($notification['testNotification'])) {
                $this->addLogEntry('Test notification received', self::WEBHOOK_SOURCE_GOOGLE_PLAY);
                $this->writeLog();
            }

            return $app->json([], 204);
        });

        /*
         * Handle App Store notifications.
         */
        $controllers->post('/app-store/', function (Request $request) use ($app) {
            $notificationRaw = $request->getContent();
            $notification = json_decode($notificationRaw, true);

            // Uncomment when debugging.
//            $this->addLogEntry(
//                "App Store notification data:\n\n" . json_encode($notification, JSON_PRETTY_PRINT),
//                self::WEBHOOK_SOURCE_APP_STORE
//            );
//
//            $this->writeLog();

            // Validate bundle ID.
            $bundleId = $notification['bid'] ?? null;

            if ($bundleId === null) {
                $this->addLogEntry(
                    'Invalid webhook data: required field `bid` is not present.',
                    self::WEBHOOK_SOURCE_APP_STORE
                );

                $this->writeLog();

                return $app->json([], 204);
            }

            if ($bundleId !== $this->bundleId) {
                $this->addLogEntry(
                    'Invalid webhook data: receiving notifications only for bundle ID `' . $this->bundleId . '` but ' .
                    'this notification is for `' . $bundleId . '`',
                    self::WEBHOOK_SOURCE_APP_STORE
                );

                $this->writeLog();

                return $app->json([], 204);
            }

            try {
                $this->webhookService->handleAppStoreNotification($notification);
            } catch (Throwable $e) {
                $this->addLogEntry(
                    'Exception during App Store notification handling: "' . $e->getMessage() . "\"; stack trace:\n\n" .
                    $e->getTraceAsString(),
                    self::WEBHOOK_SOURCE_APP_STORE
                );

                $this->writeLog();
            }

            return $app->json([], 204);
        });

        return $controllers;
    }

    /**
     * Add an entry to the log buffer. Call `writeLog` to write the added entries to the database.
     *
     * @param string $message Log message.
     * @param string $webhookSource Webhook source identifier. Possible values: `app_store`, `google_play`.
     *
     * @return void
     */
    protected function addLogEntry($message, $webhookSource)
    {
        $this->logger->addEntry($message, 'in_app_purchase.' . $webhookSource . '_webhook');
    }

    /**
     * Flush the log buffer.
     *
     * @return void
     */
    protected function writeLog()
    {
        $this->logger->writeLog();
    }
}