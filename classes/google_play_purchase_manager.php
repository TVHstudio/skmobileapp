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

use Google_Service_AndroidPublisher_ProductPurchase as ProductPurchase;
use Google_Service_AndroidPublisher_SubscriptionPurchase as SubscriptionPurchase;
use Google_Service_AndroidPublisher_ProductPurchasesAcknowledgeRequest as GooglePlayPurchaseAckRequest;
use Google_Service_AndroidPublisher_SubscriptionPurchasesAcknowledgeRequest as GooglePlaySubscriptionAckRequest;

/**
 * Used to validate and manage Android purchases through Android Publisher API.
 */
class SKMOBILEAPP_CLASS_GooglePlayPurchaseManager extends SKMOBILEAPP_CLASS_AbstractPurchaseManager
{
    use OW_Singleton;

    /**
     * Pattern of the subscription order ID with the actual order ID put into a capture group.
     */
    const SUBSCRIPTION_ORDER_ID_PATTERN = '/^(.+)?[.]{2}\d+$/';

    /**
     * @var Google_Client
     */
    protected $googleClient;

    /**
     * @var Google_Service_AndroidPublisher
     */
    protected $androidPublisherClient;

    /**
     * @var SKMOBILEAPP_BOL_GooglePlayPurchaseTokenDataDao
     */
    protected $purchaseTokenDataDao;

    /**
     * SKMOBILEAPP_CLASS_GooglePlayPurchaseValidator constructor.
     */
    public function __construct()
    {
        parent::__construct('google_play_purchase_manager');

        $this->purchaseTokenDataDao = SKMOBILEAPP_BOL_GooglePlayPurchaseTokenDataDao::getInstance();
        $this->billingService = BOL_BillingService::getInstance();
        $this->paymentsService = SKMOBILEAPP_BOL_PaymentsService::getInstance();

        if (!file_exists(SKMOBILEAPP_BOL_Service::ANDROID_PUBLISHER_KEY_PATH)) {
            $message =
                'Android publisher key file does not exist: ' . SKMOBILEAPP_BOL_Service::ANDROID_PUBLISHER_KEY_PATH;

            $this->addLogEntry($message);
            $this->writeLog();

            throw new RuntimeException($message);
        }

        // Set Android publisher key path environment variable.
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . SKMOBILEAPP_BOL_Service::ANDROID_PUBLISHER_KEY_PATH);

        $this->googleClient = new Google_Client();
        $this->googleClient->useApplicationDefaultCredentials();
        $this->googleClient->addScope(Google_Service_AndroidPublisher::ANDROIDPUBLISHER);

        $this->androidPublisherClient = new Google_Service_AndroidPublisher($this->googleClient);
    }

    /**
     * Verify Google Play purchase. Checks whether the purchase with this purchase token has already been delivered and
     * verifies the purchase token if not.
     *
     * @param string $purchaseType Purchase type, see the `PURCHASE_TYPE_*` constants in this class for possible values.
     * @param string $productId Billing plugin product ID.
     * @param string $purchaseToken Purchase token.
     *
     * @return SKMOBILEAPP_BOL_GooglePlayPurchaseVerificationResult|null Purchase verification result with purchase data
     *                                                                   if the purchase is valid, `null` otherwise.
     */
    public function verifyPurchase($purchaseType, $productId, $purchaseToken)
    {
        $sale = $this->paymentsService->findExistingSaleByPurchaseToken($purchaseToken);

        if ($sale !== null && $this->paymentsService->isSaleFinished($sale)) {
            $this->addPurchaseLogEntry(
                OW::getUser()->getId(),
                $productId,
                'sale has already been processed, its status is ' . mb_strtoupper($sale->status)
            );

            $this->writeLog();

            return null;
        }

        return $this->verifyPurchaseToken($purchaseType, $productId, $purchaseToken, $sale);
    }

    /**
     * Verify purchase token.
     *
     * @param string $purchaseType Purchase type, see the `PURCHASE_TYPE_*` constants in this class for possible values.
     * @param string $productId Billing plugin product ID.
     * @param string $purchaseToken Purchase token.
     * @param BOL_BillingSale|null $sale Billing sale entity to include in the verification result.
     *
     * @return SKMOBILEAPP_BOL_GooglePlayPurchaseVerificationResult|null Purchase verification result with purchase data
     *                                                                   if the purchase is valid, `null` otherwise.
     */
    public function verifyPurchaseToken($purchaseType, $productId, $purchaseToken, $sale = null)
    {
        $response = $this->doVerifyPurchaseToken($purchaseType, $productId, $purchaseToken);

        $this->addPurchaseLogEntry(
            OW::getUser()->getId(),
            $productId,
            "purchase token verification result:\n\n" . var_export($response, true)
        );

        $this->writeLog();

        if (!$response) {
            $this->addPurchaseLogEntry(
                OW::getUser()->getId(),
                $productId,
                'purchase token VERIFICATION ERROR'
            );

            $this->writeLog();

            return null;
        }

        if (!$response->getOrderId()) {
            $this->addPurchaseLogEntry(OW::getUser()->getId(), $productId, 'order state is INVALID');
            $this->writeLog();

            return null;
        }

        return $this->createVerificationResult($purchaseToken, $response, $sale);
    }

    /**
     * Acknowledge a purchase. All purchases that were not acknowledged in the app during the standard payment flow
     * (e.g. pending purchases that are handled while the app is closed) should be acknowledged immediately after
     * delivery to prevent automatic refund).
     *
     * @param string $purchaseType
     * @param string $productId
     * @param string $purchaseToken
     *
     * @return bool True if the purchase was acknowledged successfully, false otherwise.
     */
    public function acknowledgePurchase($purchaseType, $productId, $purchaseToken)
    {
        try {
            if ($purchaseType === SKMOBILEAPP_CLASS_AbstractPurchaseManager::PURCHASE_TYPE_SUBSCRIPTION) {
                $ackRequest = new GooglePlaySubscriptionAckRequest();

                $this->androidPublisherClient->purchases_subscriptions->acknowledge(
                    $this->bundleId,
                    $productId,
                    $purchaseToken,
                    $ackRequest
                );
            } else {
                $ackRequest = new GooglePlayPurchaseAckRequest();

                $this->androidPublisherClient->purchases_products->acknowledge(
                    $this->bundleId,
                    $productId,
                    $purchaseToken,
                    $ackRequest
                );
            }
        } catch (Exception $e) {
            $this->addLogEntry(
                'Cannot acknowledge `' . $productId . '` for user ID ' . OW::getUser()->getId() . ': "' .
                $e->getMessage() . "\"; stack trace:\n\n" . $e->getTraceAsString()
            );

            $this->writeLog();

            return false;
        }

        return true;
    }

    /**
     * Convert Google Play server token verification response to verification result.
     *
     * @param string $purchaseToken
     * @param ProductPurchase|SubscriptionPurchase $response
     * @param BOL_BillingSale|null $sale
     *
     * @return SKMOBILEAPP_BOL_GooglePlayPurchaseVerificationResult
     */
    protected function createVerificationResult($purchaseToken, $response, $sale)
    {
        $verificationResult = new SKMOBILEAPP_BOL_GooglePlayPurchaseVerificationResult();

        if ($response instanceof SubscriptionPurchase) {
            $verificationResult->orderId = $this->getActualSubscriptionOrderId($response->getOrderId());

            // Subscription purchase timestamp is its start time in seconds.
            $verificationResult->purchaseTimestamp = floor($response->getStartTimeMillis() / 1000);

            $expirationTimestamp = floor($response->getExpiryTimeMillis() / 1000);

            $verificationResult->purchaseState = $this->getSubscriptionPurchaseState(
                $response->getPaymentState(),
                $expirationTimestamp
            );

            $verificationResult->linkedPurchaseToken = $response->getLinkedPurchaseToken();
        } else {
            $verificationResult->orderId = $response->getOrderId();
            $verificationResult->purchaseTimestamp = floor($response->getPurchaseTimeMillis() / 1000);
            $verificationResult->purchaseState = $this->getConsumablePurchaseState($response->purchaseState);
            $verificationResult->linkedPurchaseToken = null;
        }

        $verificationResult->billingSale = $sale;
        $verificationResult->purchaseSource = SKMOBILEAPP_BOL_Service::NATIVE_PURCHASE_SOURCE_GOOGLE_PLAY;
        $verificationResult->purchaseToken = $purchaseToken;

        return $verificationResult;
    }

    /**
     * Attempt to verify the given purchase token. Possible return values:
     *
     * * `ProductPurchase` instance, contains data related to a consumable product. Returned if the token is valid and
     * the purchase type is consumable.
     * * `SubscriptionPurchase` instance, contains data related to a subscription. Returned if the token is valid and
     * the purchase type is subscription.
     * * `null` is returned when there is an error during the token validation process. The error details are logged
     * to the log table.
     *
     * Use the `instanceof` operator to determine the exact type of the returned data in the caller code.
     *
     * @param string $purchaseType Purchase type, see the `PURCHASE_TYPE_*` constants in this class for possible values.
     * @param string $productId Billing plugin product ID.
     * @param string $purchaseToken Purchase token.
     *
     * @see SKMOBILEAPP_CLASS_GooglePlayPurchaseManager::PURCHASE_TYPE_CONSUMABLE
     * @see SKMOBILEAPP_CLASS_GooglePlayPurchaseManager::PURCHASE_TYPE_SUBSCRIPTION
     *
     * @return ProductPurchase|SubscriptionPurchase|null
     */
    protected function doVerifyPurchaseToken($purchaseType, $productId, $purchaseToken)
    {
        try {
            if ($purchaseType === self::PURCHASE_TYPE_CONSUMABLE) {
                return $this->androidPublisherClient->purchases_products->get(
                    $this->bundleId,
                    $productId,
                    $purchaseToken
                );
            } elseif ($purchaseType === self::PURCHASE_TYPE_SUBSCRIPTION) {
                return $this->androidPublisherClient->purchases_subscriptions->get(
                    $this->bundleId,
                    $productId,
                    $purchaseToken
                );
            } else {
                throw new InvalidArgumentException('Unknown purchase type: ' . $purchaseType);
            }
        } catch (Throwable $e) {
            $this->addLogEntry(
                'Can\'t validate purchase `' . $productId . '` for user ID ' . OW::getUser()->getId() .
                ': "' . $e->getMessage() . "\"; stack trace:\n\n" . $e->getTraceAsString()
            );

            $this->writeLog();
        }

        return null;
    }

    /**
     * IMPORTANT: should be used only to convert consumable product purchase state! Should NOT be used with
     * subscriptions. To convert the state of a subscription, use the `getSubscriptionPurchaseStatus` method.
     *
     * Convert raw purchase status to the status string.
     *
     * @param int $rawState Raw purchase status.
     *
     * @throws InvalidArgumentException
     *
     * @see SKMOBILEAPP_CLASS_GooglePlayPurchaseManager::getSubscriptionPurchaseState()
     *
     * @return string
     *
     */
    protected function getConsumablePurchaseState($rawState)
    {
        $states = [
            SKMOBILEAPP_BOL_Service::NATIVE_PURCHASE_STATE_COMPLETED,
            SKMOBILEAPP_BOL_Service::NATIVE_PURCHASE_STATE_CANCELED,
            SKMOBILEAPP_BOL_Service::NATIVE_PURCHASE_STATE_PENDING
        ];

        if ($rawState >= count($states)) {
            throw new InvalidArgumentException(
                "Invalid purchase state, should be within range 0-2 but the value is " . $rawState
            );
        }

        return $states[$rawState];
    }

    /**
     * IMPORTANT: should be used only to convert the subscription state! Should NOT be used for consumable products.
     * To convert the state of a consumable product, use the `getConsumablePurchaseState` method.
     *
     * Convert raw subscription state to the status string.
     *
     * @param int $rawState Raw subscription state.
     * @param int $expirationTimestamp Subscription expiration timestamp in seconds.
     *
     * @return string
     * @throws InvalidArgumentException
     *
     *
     * @see SKMOBILEAPP_CLASS_GooglePlayPurchaseManager::getConsumablePurchaseState()
     *
     */
    protected function getSubscriptionPurchaseState($rawState, $expirationTimestamp)
    {
        if ($rawState === null) {
            return $expirationTimestamp <= time()
                ? SKMOBILEAPP_BOL_Service::NATIVE_PURCHASE_STATE_EXPIRED
                : SKMOBILEAPP_BOL_Service::NATIVE_PURCHASE_STATE_CANCELED;
        }

        $states = [
            SKMOBILEAPP_BOL_Service::NATIVE_PURCHASE_STATE_PENDING,
            SKMOBILEAPP_BOL_Service::NATIVE_PURCHASE_STATE_COMPLETED,
            SKMOBILEAPP_BOL_Service::NATIVE_PURCHASE_STATE_COMPLETED,
            SKMOBILEAPP_BOL_Service::NATIVE_PURCHASE_STATE_PENDING
        ];

        if ($rawState >= count($states)) {
            throw new InvalidArgumentException(
                'Invalid subscription status, should be within range 0-3, but the value is ' . $rawState
            );
        }

        return $states[$rawState];
    }

    /**
     * Discard the subscription suffix from the given raw order ID and return the actual ID.
     *
     * @param string $rawOrderId
     *
     * @return string
     */
    protected function getActualSubscriptionOrderId($rawOrderId)
    {
        $matches = [];
        preg_match(self::SUBSCRIPTION_ORDER_ID_PATTERN, $rawOrderId, $matches);

        if (count($matches) < 2) {
            // Return original order ID.
            return $rawOrderId;
        }

        // Discard the subscription suffix and return the actual ID.
        return $matches[1];
    }
}
