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

use ReceiptValidator\iTunes\PurchaseItem;
use ReceiptValidator\iTunes\ResponseInterface;
use ReceiptValidator\iTunes\Validator as ITunesValidator;

/**
 * Used to validate and manage iTunes purchases (in-app products and subscriptions on iOS).
 */
class SKMOBILEAPP_CLASS_AppStorePurchaseManager extends SKMOBILEAPP_CLASS_AbstractPurchaseManager
{
    use OW_Singleton;

    /**
     * @var string
     */
    protected $itunesSharedSecret;

    /**
     * @var string
     */
    protected $itunesEndpoint;

    /**
     * @var SKMOBILEAPP_BOL_PaymentsService
     */
    protected $paymentsService;

    /**
     * SKMOBILEAPP_CLASS_ItunesPurchaseValidator constructor.
     */
    public function __construct()
    {
        parent::__construct('itunes_purchase_manager');

        $this->paymentsService = SKMOBILEAPP_BOL_PaymentsService::getInstance();

        $itunesSharedSecret = OW::getConfig()->getValue(
            SKMOBILEAPP_BOL_Service::PLUGIN_KEY,
            'inapps_itunes_shared_secret'
        );

        $isSandboxModeEnabled = (bool) OW::getConfig()->getValue(
            SKMOBILEAPP_BOL_Service::PLUGIN_KEY,
            'inapps_ios_test_mode'
        );

        if (!$itunesSharedSecret) {
            $message = 'iTunes shared secret is not set, check the plugin settings';

            $this->addLogEntry($message);
            $this->writeLog();

            throw new RuntimeException($message);
        }

        $this->itunesSharedSecret = $itunesSharedSecret;
        $this->itunesEndpoint = $isSandboxModeEnabled
            ? ITunesValidator::ENDPOINT_SANDBOX
            : ITunesValidator::ENDPOINT_PRODUCTION;
    }

    /**
     * Verify App Store purchase.
     *
     * @param string $purchaseType Purchase type, see the `PURCHASE_TYPE_*` constants in this class for possible values.
     * @param string $productId Billing plugin product ID.
     * @param string $orderId App Store order ID to uniquely identify this purchase among others.
     * @param string $receiptData Base64-encoded App Store receipt data for verification.
     *
     * @return SKMOBILEAPP_BOL_AppStorePurchaseVerificationResult|null
     */
    public function verifyPurchase($purchaseType, $productId, $orderId, $receiptData)
    {
        $verificationResponse = $this->verifyReceiptData($purchaseType, $receiptData);

        if (!$verificationResponse) {
            return null;
        }

        $purchases = $this->sortPurchaseItemList($verificationResponse->getLatestReceiptInfo());
        $purchase = $this->findPurchasedProductByTransactionId($purchases, $orderId);

        if (!$purchase) {
            $this->addPurchaseLogEntry(
                OW::getUser()->getId(),
                $productId,
                'order ID was not found among the returned transactions, the purchase is INVALID'
            );

            $this->writeLog();

            return null;
        }

        return $this->createVerificationResult(
            $purchaseType,
            SKMOBILEAPP_BOL_AbstractNativePurchaseVerificationResult::PURCHASE_STATE_COMPLETED,
            $receiptData,
            $purchase
        );
    }

    /**
     * Find product in purchase list using the given transaction ID. Returns `null` if no product with such transaction
     * ID was found.
     *
     * @param PurchaseItem[] $purchaseList Purchase list to search the product in.
     * @param string $transactionId Transaction ID.
     *
     * @return PurchaseItem|null
     */
    public function findPurchasedProductByTransactionId($purchaseList, $transactionId)
    {
        // Use `array_values` to reset array indices.
        $result = array_values(
            array_filter($purchaseList, function ($purchase) use ($transactionId) {
                return $purchase->getTransactionId() === $transactionId;
            })
        );

        if (count($result) > 0) {
            return $result[0];
        }

        return null;
    }

    /**
     * Find the latest purchase in purchase list using the given product ID. Returns `null` no product with such ID was
     * found.
     *
     * @param PurchaseItem[] $purchaseList
     * @param string $productId
     *
     * @return PurchaseItem|null
     */
    public function findLatestPurchaseByProductId($purchaseList, $productId)
    {
        $sortedList = $this->sortPurchaseItemList($purchaseList);
        $products = $this->findPurchasedProductsByProductId($sortedList, $productId);

        if (count($products) > 0) {
            return $products[0];
        }

        return null;
    }

    /**
     * Find purchased products in purchase list using the given product ID. Returns empty array if no products with such
     * product ID were found.
     *
     * @param PurchaseItem[] $purchaseList
     * @param string $productId
     *
     * @return PurchaseItem[]
     */
    public function findPurchasedProductsByProductId($purchaseList, $productId)
    {
        // Use `array_values` to reset array indices.
        return array_values(array_filter($purchaseList, function ($purchase) use ($productId) {
            return $purchase->getProductId() === $productId;
        }));
    }

    /**
     * Sort purchase item list by purchase timestamp in the descending order.
     *
     * @param PurchaseItem[] $purchaseItemList
     *
     * @return PurchaseItem[]
     */
    public function sortPurchaseItemList($purchaseItemList)
    {
        // Clone purchase item list to prevent modification of the argument value.
        $purchaseListCloned = array_slice($purchaseItemList, 0);

        // Sort cloned purchase list by the purchase timestamp in descending order.
        usort($purchaseListCloned, function ($p1, $p2) {
            /** @var PurchaseItem $p1 */
            $timestamp1 = $p1->getPurchaseDate()->timestamp;

            /** @var PurchaseItem $p2 */
            $timestamp2 = $p2->getPurchaseDate()->timestamp;

            return $timestamp2 <=> $timestamp1;
        });

        return $purchaseListCloned;
    }

    /**
     * Verify App Store receipt.
     *
     * @param string $purchaseType Purchase type, see the `PURCHASE_TYPE_*` constants in this class for possible values.
     * @param string $receiptData Encoded receipt data.
     *
     * @return ResponseInterface|null Validated receipt data if the receipt is valid, `null` otherwise.
     */
    public function verifyReceiptData($purchaseType, $receiptData)
    {
        $validator = new ITunesValidator($this->itunesEndpoint);

        try {
            $validator->setReceiptData($receiptData);
            $validator->setSharedSecret($this->itunesSharedSecret);

            $result = $validator->validate();

            $this->addLogEntry("Raw app store receipt data:\n\n" . print_r($result, true));
            $this->writeLog();
        } catch (Throwable $e) {
            $this->addLogEntry(
                'App Store receipt validation error: "' . $e->getMessage() . "\"; stack trace:\n\n" .
                $e->getTraceAsString()
            );

            $this->writeLog();

            return null;
        }

        if (!$result->isValid()) {
            $this->addLogEntry('App store receipt is INVALID');
            $this->writeLog();

            return null;
        }

        return $result;
    }

    /**
     * Create App Store purchase verification result using the provided data.
     *
     * @param string $purchaseType Purchase type, see `PURCHASE_TYPE_*` constants in the
     *                             `SKMOBILEAPP_CLASS_AbstractPurchaseManager` class for supported values.
     * @param string $purchaseState Purchase state, see `PURCHASE_STATE_*` constants in the
     *                              `SKMOBILEAPP_BOL_AbstractNativePurchaseVerificationResult` class for supported
     *                              values.
     * @param string $receiptData Base64-encoded receipt data.
     * @param PurchaseItem $purchase Purchase item received in the App Store verification response.
     *
     * @see SKMOBILEAPP_CLASS_AbstractPurchaseManager
     * @see SKMOBILEAPP_BOL_AbstractNativePurchaseVerificationResult
     *
     * @return SKMOBILEAPP_BOL_AppStorePurchaseVerificationResult
     */
    protected function createVerificationResult($purchaseType, $purchaseState, $receiptData, $purchase)
    {
        $verificationResult = new SKMOBILEAPP_BOL_AppStorePurchaseVerificationResult();

        $verificationResult->orderId = $purchase->getTransactionId();
        $verificationResult->originalOrderId = $purchase->getOriginalTransactionId();
        $verificationResult->purchaseState = $purchaseState;
        $verificationResult->purchaseSource = SKMOBILEAPP_BOL_Service::NATIVE_PURCHASE_SOURCE_APP_STORE;
        $verificationResult->encodedReceipt = $receiptData;
        $verificationResult->isRenewal = $this->isRenewalPurchase($purchase);
        $verificationResult->purchaseItem = $purchase;
        $verificationResult->purchaseTimestamp = $purchase->getPurchaseDate()->timestamp;

        return $verificationResult;
    }

    /**
     * Check if the given purchase represents a subscription renewal.
     *
     * @param PurchaseItem $purchase
     *
     * @return bool
     */
    protected function isRenewalPurchase($purchase)
    {
        return $purchase->getTransactionId() !== $purchase->getOriginalTransactionId();
    }

    /**
     * Create a verification result with CANCELED purchase state and all other fields set to `null`.
     *
     * @return SKMOBILEAPP_BOL_AppStorePurchaseVerificationResult
     */
    protected function createEmptyCancelledVerificationResult()
    {
        $verificationResult = new SKMOBILEAPP_BOL_AppStorePurchaseVerificationResult();
        $verificationResult->purchaseState
            = SKMOBILEAPP_BOL_AbstractNativePurchaseVerificationResult::PURCHASE_STATE_CANCELED;

        return $verificationResult;
    }
}
