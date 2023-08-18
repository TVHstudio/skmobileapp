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

use ReceiptValidator\iTunes\PurchaseItem as AppStorePurchaseItem;

/**
 * Represents App Store purchase verification result.
 */
class SKMOBILEAPP_BOL_AppStorePurchaseVerificationResult extends SKMOBILEAPP_BOL_AbstractNativePurchaseVerificationResult
{
    /**
     * @var string Original order ID for subscription renewals. In case of a non-renewable subscription, an in-app
     * purchase or an initial purchase of a subscription equals to `$orderId`.
     *
     * @see $orderId
     */
    public $originalOrderId;

    /**
     * @var AppStorePurchaseItem Decoded App Store receipt data for this purchase.
     */
    public $purchaseItem;

    /**
     * @var string Base64-encoded receipt content.
     */
    public $encodedReceipt;

    /**
     * @var bool Is this a renewal purchase.
     */
    public $isRenewal;
}
