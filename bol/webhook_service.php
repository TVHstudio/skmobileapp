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

use ReceiptValidator\iTunes\ResponseInterface as AppStoreResponseInterface;

/**
 * Handles webhook notifications.
 */
class SKMOBILEAPP_BOL_WebhookService
{
    use OW_Singleton;

    /**
     * A product was successfully purchased by a user.
     */
    const GOOGLE_PLAY_PRODUCT_NOTIFICATION_TYPE_PURCHASED = 1;

    /**
     * A pending one-time product purchase has been canceled by the user.
     */
    const GOOGLE_PLAY_PRODUCT_NOTIFICATION_TYPE_CANCELED = 2;

    /**
     * A subscription was recovered from account hold.
     */
    const GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_RECOVERED = 1;

    /**
     * An active subscription was renewed.
     */
    const GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_RENEWED = 2;

    /**
     * A subscription was canceled, either voluntarily or involuntarily. For voluntary cancellation a notification
     * of this type is sent when the user cancels the subscription.
     */
    const GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_CANCELED = 3;

    /**
     * A new subscription was purchased.
     */
    const GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_PURCHASED = 4;

    /**
     * A subscription has entered account hold (if enabled).
     */
    const GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_ON_HOLD = 5;

    /**
     * A subscription has entered grace period (if enabled).
     */
    const GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_IN_GRACE_PERIOD = 6;

    /**
     * User has reactivated their subscription from Play > Account > Subscriptions (requires opt-in).
     */
    const GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_RESTARTED = 7;

    /**
     * A subscription price change has been confirmed by the user.
     */
    const GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_PRICE_CHANGE_CONFIRMED = 8;

    /**
     * A subscription's recurrence time has been extended.
     */
    const GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_DEFERRED = 9;

    /**
     * A subscription has been paused.
     */
    const GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_PAUSED = 10;

    /**
     * A subscription pause schedule has been changed.
     */
    const GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_PAUSE_SCHEDULE_CHANGED = 11;

    /**
     * A subscription has been revoked from the user before the expiration time.
     */
    const GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_REVOKED = 12;

    /**
     * A subscription has expired.
     */
    const GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_EXPIRED = 13;

    /**
     * Customer has renewed their subscription through either the app interface or the App Store
     * "Manage subscriptions" interface.
     */
    const APP_STORE_SUB_NOTIFICATION_TYPE_INTERACTIVE_RENEWAL = 'INTERACTIVE_RENEWAL';

    /**
     * A subscription has been successfully auto-renewed.
     */
    const APP_STORE_SUB_NOTIFICATION_TYPE_DID_RENEW = 'DID_RENEW';

    /**
     * A subscription has been successfully auto-renewed during the billing retry period.
     */
    const APP_STORE_SUB_NOTIFICATION_TYPE_DID_RECOVER = 'DID_RECOVER';

    /**
     * Google Play one-time purchase handler type.
     */
    const HANDLER_TYPE_GOOGLE_PLAY_PURCHASE = 'google_play_purchase';

    /**
     * Google Play subscription handler type.
     */
    const HANDLER_TYPE_GOOGLE_PLAY_SUB = 'google_play_subscription';

    /**
     * Generic App Store notification handler.
     */
    const HANDLER_TYPE_APP_STORE_GENERIC = 'app_store_generic';

    /**
     * `INTERACTIVE_RENEWAL` notification App Store handler.
     */
    const HANDLER_TYPE_APP_STORE_RENEWAL = 'app_store_renewal';

    /**
     * @var SKMOBILEAPP_BOL_PaymentsService
     */
    protected $paymentsService;

    /**
     * @var BOL_BillingService
     */
    protected $billingService;

    /**
     * @var OW_Log
     */
    protected $logger;

    /**
     * SKMOBILEAPP_BOL_WebhookService constructor.
     */
    public function __construct()
    {
        $this->paymentsService = SKMOBILEAPP_BOL_PaymentsService::getInstance();
        $this->billingService = BOL_BillingService::getInstance();
        $this->logger = SKMOBILEAPP_BOL_Service::getLogger();
    }

    /**
     * Handle one-time purchase state change.
     *
     * @param SKMOBILEAPP_BOL_GooglePlayPurchaseNotification $notification Notification object instance.
     *
     * @return void
     */
    public function handleGooglePlayPurchaseNotification($notification)
    {
        $purchaseToken = $notification->getPurchaseToken();
        $sale = $this->paymentsService->findExistingSaleByPurchaseToken($purchaseToken);

        if (!$sale) {
            $this->addHandlerLogEntry(
                'Could not find sale for the received purchase token: "' . $purchaseToken . '"',
                self::HANDLER_TYPE_GOOGLE_PLAY_PURCHASE
            );

            $this->writeLog();

            return;
        }

        $result = $this->paymentsService->processExistingGooglePlaySale($sale);

        if ($result) {
            $manager = new SKMOBILEAPP_CLASS_GooglePlayPurchaseManager();

            $manager->acknowledgePurchase(
                SKMOBILEAPP_CLASS_AbstractPurchaseManager::PURCHASE_TYPE_CONSUMABLE,
                $notification->getProductId(),
                $purchaseToken
            );
        }
    }

    /**
     * Handle subscription state change.
     *
     * @param SKMOBILEAPP_BOL_GooglePlaySubscriptionNotification $notification Notification object instance.
     *
     * @return void
     */
    public function handleGooglePlaySubscriptionNotification($notification)
    {
        switch ($notification->getNotificationType())
        {
            case self::GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_PURCHASED:
                $this->handleGooglePlaySubscriptionPurchase($notification);
                return;

            case self::GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_RENEWED:
                $this->handleGooglePlaySubscriptionRenewal($notification);
                return;
        }

        $this->addHandlerLogEntry(
            'Cannot handle subscription status "' . $this->getGooglePlaySubscriptionNotificationStatusValueName($notification->getNotificationType()) .
            '" for purchase `' . $notification->getSubscriptionId() . '`, purchase token: "' . $notification->getPurchaseToken() . '"',
            self::HANDLER_TYPE_GOOGLE_PLAY_SUB
        );

        $this->writeLog();
    }

    /**
     * Handle App Store notification.
     *
     * @param array $notification Decoded notification data
     *
     * @return void
     */
    public function handleAppStoreNotification($notification)
    {
        $notificationType = $notification['notification_type'] ?? null;

        if (!$notificationType) {
            throw new RuntimeException('Invalid notification JSON, the `notification_type` field is not present.');
        }

        // Make notificationType uppercase just in case Apple sends something inconsistent.
        $notificationTypeUppercase = mb_strtoupper($notificationType);

        // Check if the notification type is supported.
        if (
            !in_array($notificationTypeUppercase, [
                self::APP_STORE_SUB_NOTIFICATION_TYPE_INTERACTIVE_RENEWAL,
                self::APP_STORE_SUB_NOTIFICATION_TYPE_DID_RENEW,
                self::APP_STORE_SUB_NOTIFICATION_TYPE_DID_RECOVER
            ])
        ) {
            // Uncomment when debugging.
//            $this->addHandlerLogEntry(
//                'Unsupported notification type: `' . $notificationTypeUppercase . '`',
//                self::HANDLER_TYPE_APP_STORE_GENERIC
//            );
//
//            $this->writeLog();

            return;
        }

        // Retrieve `unified_receipt` object.

        $unifiedReceipt = $notification['unified_receipt'] ?? null;

        if (!is_array($unifiedReceipt)) {
            throw new RuntimeException('Invalid notification JSON, `unified_receipt` field is not present.');
        }

        // Retrieve the latest receipt information from the unified receipt.

        $latestReceipt = $unifiedReceipt['latest_receipt'] ?? null;

        if (!is_string($latestReceipt)) {
            throw new RuntimeException('Invalid notification JSON, `unified_receipt.latest_receipt` field is not present.');
        }

        // Verify receipt data.

        $manager = new SKMOBILEAPP_CLASS_AppStorePurchaseManager();

        $receiptData = $manager->verifyReceiptData(
            SKMOBILEAPP_CLASS_AbstractPurchaseManager::PURCHASE_TYPE_SUBSCRIPTION,
            $latestReceipt
        );

        if (!$receiptData) {
            return;
        }

        switch ($notificationTypeUppercase) {
            case self::APP_STORE_SUB_NOTIFICATION_TYPE_INTERACTIVE_RENEWAL:
            case self::APP_STORE_SUB_NOTIFICATION_TYPE_DID_RENEW:
            case self::APP_STORE_SUB_NOTIFICATION_TYPE_DID_RECOVER:
                $this->handleAppStoreRenewal($notification, $receiptData);
                return;

            // Add more cases here.
        }
    }

    /**
     * Handle `INTERACTIVE_RENEWAL` notification received from App Store.
     *
     * @param array $notification Decoded notification data.
     * @param AppStoreResponseInterface $receiptData Parsed App Store receipt validation response.
     *
     * @return void
     */
    protected function handleAppStoreRenewal($notification, $receiptData)
    {
        $productId = $notification['auto_renew_product_id'] ?? null;

        if (!is_string($productId)) {
            throw new RuntimeException(
                'Invalid notification JSON, `auto_renew_product_id` field is not present or contains invalid value.'
            );
        }

        $manager = new SKMOBILEAPP_CLASS_AppStorePurchaseManager();
        $latestPurchase = $manager->findLatestPurchaseByProductId($receiptData->getLatestReceiptInfo(), $productId);

        if (!$latestPurchase) {
            $this->addHandlerLogEntry(
                'No purchase with product ID `' . $productId . '` was found in the receipt, considering renewal invalid',
                self::HANDLER_TYPE_APP_STORE_RENEWAL
            );

            $this->writeLog();

            return;
        }

        $renewalTransactionId = $latestPurchase->getTransactionId();
        $originalTransactionId = $latestPurchase->getOriginalTransactionId();

        // Try finding original subscription sale.

        $originalSale = $this->billingService->getSaleByGatewayTransactionId(
            SKMOBILEAPP_CLASS_InAppPurchaseAdapter::GATEWAY_KEY,
            $originalTransactionId
        );

        if (!$originalSale) {
            $this->addHandlerLogEntry(
                'Original sale (orig. txn ID: ' . $originalTransactionId . ') for renewal txn ID ' . $renewalTransactionId . ' was not found, ' .
                'considering the renewal invalid',
                self::HANDLER_TYPE_APP_STORE_RENEWAL
            );

            $this->writeLog();

            return;
        }

        // Check the original sale product ID.

        $originalSaleProductId = $originalSale->entityKey . '_' . $originalSale->entityId;

        if ($originalSaleProductId !== $productId) {
            $this->addHandlerLogEntry(
                'Original sale product ID `' . $originalSaleProductId . '` does not equal to the renewal product ID `' . $productId . '`, ' .
                'considering the renewal invalid',
                self::HANDLER_TYPE_APP_STORE_RENEWAL
            );

            $this->writeLog();

            return;
        }

        // If the original sale is found, try to extend membership for the user.

        if (!$this->paymentsService->extendMembershipBySale($originalSale)) {
            $this->addHandlerLogEntry(
                'Cannot extend `' . $productId . '` for user ID ' . $originalSale->userId . ', check previous log messages',
                self::HANDLER_TYPE_APP_STORE_RENEWAL
            );

            $this->writeLog();

            return;
        }

        // If the membership was extended successfully, create and register a renewal sale.

        $renewalSale = $this->paymentsService->createSubscriptionRenewalSale(
            $productId,
            SKMOBILEAPP_BOL_Service::PLATFORM_IOS
        );

        $renewalSaleId
            = $this->billingService->initSale($renewalSale, SKMOBILEAPP_CLASS_InAppPurchaseAdapter::GATEWAY_KEY);

        $renewalSaleEntity = $this->billingService->getSaleById($renewalSaleId);

        $renewalSaleEntity->userId = $originalSale->userId;
        $renewalSaleEntity->status = BOL_BillingSaleDao::STATUS_DELIVERED;
        $renewalSaleEntity->transactionUid = $renewalTransactionId;
        $renewalSaleEntity->timeStamp = $latestPurchase->getPurchaseDate()->timestamp;
        $renewalSaleEntity->extraData = json_encode([
            'platform' => SKMOBILEAPP_BOL_Service::PLATFORM_IOS,
            'original_transaction_id' => $originalTransactionId
        ]);

        $this->billingService->saveSale($renewalSaleEntity);

        $this->addHandlerLogEntry(
            'Subscription to `' . $productId . '` for user ID ' . $originalSale->userId . ' was EXTENDED successfully',
            self::HANDLER_TYPE_GOOGLE_PLAY_SUB
        );

        $this->writeLog();
    }

    /**
     * Handle Google Play subscription purchase. Should be called when deferred payment for a subscription has been
     * completed.
     *
     * @param SKMOBILEAPP_BOL_GooglePlaySubscriptionNotification $notification Notification object instance.
     *
     * @return void
     */
    protected function handleGooglePlaySubscriptionPurchase($notification)
    {
        $purchaseToken = $notification->getPurchaseToken();
        $sale = $this->paymentsService->findExistingSaleByPurchaseToken($purchaseToken);

        if (!$sale) {
            $this->addHandlerLogEntry(
                'Could not find membership plan sale for the received purchase token: "' . $purchaseToken . '"',
                self::HANDLER_TYPE_GOOGLE_PLAY_PURCHASE
            );

            $this->writeLog();

            return;
        }

        $result = $this->paymentsService->processExistingGooglePlaySale($sale);

        if ($result) {
            $manager = new SKMOBILEAPP_CLASS_GooglePlayPurchaseManager();

            $manager->acknowledgePurchase(
                SKMOBILEAPP_CLASS_AbstractPurchaseManager::PURCHASE_TYPE_SUBSCRIPTION,
                $notification->getSubscriptionId(),
                $purchaseToken
            );
        }
    }

    /**
     * Handle Google Play subscription renewal.
     *
     * @param SKMOBILEAPP_BOL_GooglePlaySubscriptionNotification $notification Notification object instance.
     *
     * @return void
     */
    protected function handleGooglePlaySubscriptionRenewal($notification)
    {
        $purchaseToken = $notification->getPurchaseToken();
        $sale = $this->paymentsService->findExistingSaleByPurchaseToken($notification->getPurchaseToken());

        if (!$sale) {
            $this->addHandlerLogEntry(
                'Could not find membership plan sale for the received purchase token: "' . $purchaseToken . '"',
                self::HANDLER_TYPE_GOOGLE_PLAY_SUB
            );

            $this->writeLog();

            return;
        }

        $productId = $notification->getSubscriptionId();
        $productIdParts = $this->paymentsService->parseProductId($productId);
        $entityType = $productIdParts['entityType'] ?? null;

        if ($entityType !== $sale->entityKey) {
            $this->addHandlerLogEntry(
                'Entity keys don\'t match: "' . $entityType . '" and "' . $sale->entityKey . '"',
                self::HANDLER_TYPE_GOOGLE_PLAY_SUB
            );

            $this->writeLog();

            return;
        }

        $manager = new SKMOBILEAPP_CLASS_GooglePlayPurchaseManager();

        $verificationResult = $manager->verifyPurchaseToken(
            SKMOBILEAPP_CLASS_AbstractPurchaseManager::PURCHASE_TYPE_SUBSCRIPTION,
            $productId,
            $purchaseToken
        );

        if (!$verificationResult) {
            $this->addHandlerLogEntry(
                'Cannot verify `' . $productId . '` renewal for user ID ' . $sale->userId . ', check previous log messages',
                self::HANDLER_TYPE_GOOGLE_PLAY_SUB
            );

            $this->writeLog();

            return;
        }

        if (!$this->paymentsService->extendMembershipBySale($sale)) {
            $this->addHandlerLogEntry(
                'Cannot extend `' . $productId . '` for user ID ' . $sale->userId . ', check previous log messages',
                self::HANDLER_TYPE_GOOGLE_PLAY_SUB
            );

            $this->writeLog();

            return;
        }

        $renewalSale
            = $this->paymentsService->createSubscriptionRenewalSale($productId, SKMOBILEAPP_BOL_Service::PLATFORM_ANDROID);

        $renewalSaleId
            = $this->billingService->initSale($renewalSale, SKMOBILEAPP_CLASS_InAppPurchaseAdapter::GATEWAY_KEY);

        $renewalSaleEntity = $this->billingService->getSaleById($renewalSaleId);

        $renewalSaleEntity->userId = $sale->userId;
        $renewalSaleEntity->status = BOL_BillingSaleDao::STATUS_DELIVERED;
        $renewalSaleEntity->transactionUid = $verificationResult->orderId;
        $renewalSaleEntity->timeStamp = time();
        $renewalSaleEntity->extraData = json_encode([
            'platform' => SKMOBILEAPP_BOL_Service::PLATFORM_ANDROID
        ]);

        $this->billingService->saveSale($renewalSaleEntity);

        $this->addHandlerLogEntry(
            'Subscription to `' . $productId . '` for user ID ' . $sale->userId . ' was EXTENDED successfully',
            self::HANDLER_TYPE_GOOGLE_PLAY_SUB
        );

        $this->writeLog();
    }

    /**
     * Return name of the given notification status code.
     *
     * @param int $statusCode
     *
     * @return string
     */
    protected function getGooglePlaySubscriptionNotificationStatusValueName($statusCode)
    {
        $unknown = 'GOOGLE_PLAY_SUB_NOTIFICATION_STATUS_UNKNOWN';

        if ($statusCode > 13) {
            return $unknown;
        }

        return [
            $unknown,
            'GOOGLE_PLAY_SUB_NOTIFICATION_STATUS_RECOVERED',
            'GOOGLE_PLAY_SUB_NOTIFICATION_STATUS_RENEWED',
            'GOOGLE_PLAY_SUB_NOTIFICATION_STATUS_CANCELED',
            'GOOGLE_PLAY_SUB_NOTIFICATION_STATUS_PURCHASED',
            'GOOGLE_PLAY_SUB_NOTIFICATION_STATUS_ON_HOLD',
            'GOOGLE_PLAY_SUB_NOTIFICATION_STATUS_IN_GRACE_PERIOD',
            'GOOGLE_PLAY_SUB_NOTIFICATION_STATUS_RESTARTED',
            'GOOGLE_PLAY_SUB_NOTIFICATION_STATUS_PRICE_CHANGE_CONFIRMED',
            'GOOGLE_PLAY_SUB_NOTIFICATION_STATUS_DEFERRED',
            'GOOGLE_PLAY_SUB_NOTIFICATION_STATUS_PAUSED',
            'GOOGLE_PLAY_SUB_NOTIFICATION_STATUS_PAUSE_SCHEDULE_CHANGED',
            'GOOGLE_PLAY_SUB_NOTIFICATION_STATUS_REVOKED',
            'GOOGLE_PLAY_SUB_NOTIFICATION_STATUS_EXPIRED',
        ][$statusCode];
    }

    /**
     * Add notification handler log entry. Call `writeLog` to write added entries to the database.
     *
     * @param string $message Log message.
     * @param string $handlerType Notification handler type, one of the `HANDLER_TYPE_*` constants.
     *
     * @return void
     */
    protected function addHandlerLogEntry($message, $handlerType)
    {
        $this->logger->addEntry($message, 'in_app_purchase.webhook_service.' . $handlerType . '_handler');
    }

    /**
     * Flush the logger buffer.
     *
     * @return void
     */
    protected function writeLog()
    {
        $this->logger->writeLog();
    }
}
