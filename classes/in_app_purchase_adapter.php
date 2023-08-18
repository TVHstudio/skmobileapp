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

class SKMOBILEAPP_CLASS_InAppPurchaseAdapter implements OW_BillingAdapter
{
    const GATEWAY_KEY = 'skmobileapp';

    /**
     * @var BOL_BillingService
     */
    private $billingService;

    /**
     * @var SKMOBILEAPP_BOL_GooglePlayPurchaseTokenDataDao
     */
    private $purchaseTokenDataDao;

    /**
     * @var SKMOBILEAPP_BOL_AppStoreReceiptDataDao
     */
    private $appStoreReceiptDataDao;

    /**
     * @var OW_Log
     */
    private $logger;

    public function __construct()
    {
        $this->billingService = BOL_BillingService::getInstance();
        $this->purchaseTokenDataDao = SKMOBILEAPP_BOL_GooglePlayPurchaseTokenDataDao::getInstance();
        $this->appStoreReceiptDataDao = SKMOBILEAPP_BOL_AppStoreReceiptDataDao::getInstance();
        $this->logger = SKMOBILEAPP_BOL_Service::getLogger();
    }

    /**
     * @inheritDoc
     */
    public function prepareSale(BOL_BillingSale $sale)
    {
        $extraData = json_decode($sale->extraData, true);

        if (!isset($extraData['platform'])) {
            throw new RuntimeException("Provided sale's `extraData` field does not contain platform information");
        }

        $platform = $extraData['platform'];

        if ($platform === SKMOBILEAPP_BOL_Service::PLATFORM_ANDROID) {
            if (!isset($extraData['purchaseToken'])) {
                throw new InvalidArgumentException("Provided sale's `extraData` field does not contain the purchase token");
            }
        } elseif ($platform === SKMOBILEAPP_BOL_Service::PLATFORM_IOS) {
            if (!isset($extraData['orderId'])) {
                throw new InvalidArgumentException("Provided sale's `extraData` field does not contain the order ID");
            }

            if (!isset($extraData['receiptData'])) {
                throw new InvalidArgumentException("Provided sale's `extraData` field does not contain the App Store receipt data");
            }
        } else {
            throw new RuntimeException("Unknown platform: " . $platform);
        }

        return $this->billingService->saveSale($sale);
    }

    /**
     * @inheritDoc
     */
    public function verifySale(BOL_BillingSale $sale)
    {
        $extraData = json_decode($sale->extraData, true);

        if (!isset($extraData['platform'])) {
            return false;
        }

        $platform = $extraData['platform'];

        if ($platform === SKMOBILEAPP_BOL_Service::PLATFORM_IOS) {
            $verificationResult = $this->verifyAppStoreSale($sale, $extraData);
        } elseif ($platform === SKMOBILEAPP_BOL_Service::PLATFORM_ANDROID) {
            $verificationResult = $this->verifyGooglePlaySale($sale, $extraData);
        } else {
            $verificationResult = false;
        }

        $this->billingService->saveSale($sale);

        return $verificationResult;
    }

    /**
     * @inheritDoc
     */
    public function getFields($params = null)
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getOrderFormUrl()
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getLogoUrl()
    {
        return '';
    }

    /**
     * Verify App Store sale.
     *
     * @param BOL_BillingSale $sale Sale entity to verify. All verification data should be contained in the `extraData`
     *                              field.
     * @param array $extraData Decoded `extraData` field.
     *
     * @return bool True if the sale can be delivered, false otherwise.
     */
    protected function verifyAppStoreSale(BOL_BillingSale $sale, $extraData)
    {
        $manager = new SKMOBILEAPP_CLASS_AppStorePurchaseManager();

        // Determine the purchase type based on whether the sale represents a credit pack or a membership plan.
        // On App Store only credit packs are consumable, membership plans (even non-recurring) are subscriptions and
        // should be handled as such.
        $purchaseType = $sale->entityKey === SKMOBILEAPP_BOL_PaymentsService::PRODUCT_TYPE_CREDIT_PACK
            ? SKMOBILEAPP_CLASS_AbstractPurchaseManager::PURCHASE_TYPE_CONSUMABLE
            : SKMOBILEAPP_CLASS_AbstractPurchaseManager::PURCHASE_TYPE_SUBSCRIPTION;

        $receiptData = $extraData['receiptData'];
        $orderId = $extraData['orderId'];

        // Join entity key and entity ID back to full product ID. The product ID is required for purchase verification.
        $productId = $sale->entityKey . '_' . $sale->entityId;

        $result = $manager->verifyPurchase($purchaseType, $productId, $orderId, $receiptData);

        if (!$result) {
            // Mark the sale as errored if the receipt is invalid.
            $sale->status = BOL_BillingSaleDao::STATUS_ERROR;

            return false;
        }

        // Attempt to retrieve related App Store receipt data from the database.
        $savedReceiptData = $this->appStoreReceiptDataDao->findByBillingSaleId($sale->getId());

        if (!$savedReceiptData) {
            // If there is no saved receipt data, instantiate receipt data entity, fill it with values from the
            // verification result and persist it to the database.
            $savedReceiptData = new SKMOBILEAPP_BOL_AppStoreReceiptData();

            $savedReceiptData->billingSaleId = $sale->getId();
            $savedReceiptData->encodedReceipt = $result->encodedReceipt;

            try {
                $this->appStoreReceiptDataDao->save($savedReceiptData);
            } catch (Throwable $e) {
                $this->logger->addEntry(
                    'Unable to save App Store purchase data for `' . $productId . '`, user ID ' . OW::getUser()->getId() .
                    ', sale ID ' . $sale->getId() ?? '`NULL`' . "; stack trace:\n\n" . $e->getTraceAsString(),
                    'in_app_purchases.in_app_purchase_adapter.verify_app_store_sale'
                );

                $this->logger->writeLog();

                // If the App Store receipt data cannot be saved, the sale should not be considered valid or delivered
                // to the user because in this case there is no way to retrieve the App Store receipt for this sale.
                // Mark the sale as errored.
                $sale->status = BOL_BillingSaleDao::STATUS_ERROR;

                return false;
            }
        }

        // Fill in the missing sale fields using the verification result.
        $sale->transactionUid = $result->orderId;
        $sale->timeStamp = $result->purchaseTimestamp;

        if ($result->isRenewal) {
            $sale->extraData = json_encode([
                'platform' => SKMOBILEAPP_BOL_Service::PLATFORM_IOS,
                'original_transaction_id' => $result->originalOrderId,
                'app_store_renewal' => true
            ]);

            $entityDescription = OW::getLanguage()->text('skmobileapp', 'payment_in_app_renewal');

            $sale->entityDescription = $sale->entityDescription . ', ' . $entityDescription;
        } else {
            $sale->extraData = json_encode([ 'platform' => SKMOBILEAPP_BOL_Service::PLATFORM_IOS ]);
        }

        return true;
    }

    /**
     * Verify Google Play sale.
     *
     * @param BOL_BillingSale $sale Sale entity to verify. All verification data should be contained in the `extraData`
     *                              field.
     * @param array $extraData Decoded `extraData` field.
     *
     * @return bool True if the sale can be delivered, false otherwise.
     */
    protected function verifyGooglePlaySale(BOL_BillingSale $sale, $extraData)
    {
        $manager = new SKMOBILEAPP_CLASS_GooglePlayPurchaseManager();

        // Determine the purchase type based on whether the sale is recurring.
        // On Google Play only recurring subscriptions are considered non-consumable.
        $purchaseType = $sale->recurring
            ? SKMOBILEAPP_CLASS_AbstractPurchaseManager::PURCHASE_TYPE_SUBSCRIPTION
            : SKMOBILEAPP_CLASS_AbstractPurchaseManager::PURCHASE_TYPE_CONSUMABLE;

        $purchaseToken = $extraData['purchaseToken'];

        // Join entity key and entity ID back to full product ID. The product ID is required for purchase verification.
        $productId = $sale->entityKey . '_' . $sale->entityId;

        $result = $manager->verifyPurchase($purchaseType, $productId, $purchaseToken);

        if (
            !$result ||
            $result->purchaseState === SKMOBILEAPP_BOL_AbstractNativePurchaseVerificationResult::PURCHASE_STATE_CANCELED
        ) {
            $this->logger->addEntry(
                'Purchase of `' . $productId . '` by user ID ' . $sale->userId . ' has been CANCELED',
                'in_app_purchases.in_app_purchase_adapter.verify_google_play_sale'
            );

            $this->logger->writeLog();

            // Mark sale as errored if the purchase token is invalid or the payment has been canceled.
            $sale->status = BOL_BillingSaleDao::STATUS_ERROR;

            return false;
        }

        // Attempt to retrieve related purchase token data from the database.
        $purchaseTokenData = $this->purchaseTokenDataDao->findByBillingSaleId($sale->getId());

        if (!$purchaseTokenData) {
            // If there is no saved purchase token data for this sale, instantiate purchase token data entity, fill
            // it with values from the verification result and persist it to the database.
            $purchaseTokenData = new SKMOBILEAPP_BOL_GooglePlayPurchaseTokenData();

            $purchaseTokenData->billingSaleId = $sale->getId();
            $purchaseTokenData->purchaseToken = $purchaseToken;
            $purchaseTokenData->linkedPurchaseToken = $result->linkedPurchaseToken;

            try {
                $this->purchaseTokenDataDao->save($purchaseTokenData);
            } catch (Throwable $e) {
                $this->logger->addEntry(
                    'Unable to save Google Play purchase token data for `' . $productId . '`, user ID ' .
                    OW::getUser()->getId() .  ', sale ID ' . $sale->getId() ?? '`NULL`' . "; stack trace:\n\n" .
                    $e->getTraceAsString(),
                    'in_app_purchases.in_app_purchase_adapter.verify_google_play_sale'
                );

                $this->logger->writeLog();

                // If the Google Play purchase token data cannot be saved, the sale should not be considered valid or
                // delivered to the user because in this case there is no way to retrieve the purchase tokens for this
                // sale.
                // Mark the sale as errored.
                $sale->status = BOL_BillingSaleDao::STATUS_ERROR;

                return false;
            }
        }

        // Handle pending sale.
        if (
            $result->purchaseState
                !== SKMOBILEAPP_BOL_AbstractNativePurchaseVerificationResult::PURCHASE_STATE_COMPLETED
        ) {
            // Mark sale as processing.
            $sale->status = BOL_BillingSaleDao::STATUS_PROCESSING;

            return false;
        }

        // Fill in the missing sale fields using the verification result.
        $sale->transactionUid = $result->orderId;
        $sale->timeStamp = $result->purchaseTimestamp;

        // Remove unneeded extra data.
        $sale->extraData = json_encode([ 'platform' => SKMOBILEAPP_BOL_Service::PLATFORM_ANDROID ]);

        return true;
    }
}
