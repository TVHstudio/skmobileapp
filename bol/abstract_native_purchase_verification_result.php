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

/**
 * Contains fields common for all types of verification results. All custom verification results should extend this
 * class.
 */
abstract class SKMOBILEAPP_BOL_AbstractNativePurchaseVerificationResult
{
    /**
     * Purchase state representing canceled purchase.
     */
    const PURCHASE_STATE_CANCELED = 'canceled';

    /**
     * Purchase state representing completed purchase.
     */
    const PURCHASE_STATE_COMPLETED = 'completed';

    /**
     * Purchase state representing pending purchase.
     */
    const PURCHASE_STATE_PENDING = 'pending';

    /**
     * App Store purchase source.
     */
    const PURCHASE_SOURCE_APP_STORE = 'app_store';

    /**
     * Google Play purchase source.
     */
    const PURCHASE_SOURCE_GOOGLE_PLAY = 'google_play';

    /**
     * Store-dependent order ID.
     */
    public $orderId;

    /**
     * @var string Current purchase state. Possible values are `canceled`, `completed` and `pending`. Use the constants
     *             in this class instead of literals.
     *
     * @see SKMOBILEAPP_BOL_AbstractNativePurchaseVerificationResult::PURCHASE_STATE_CANCELED
     * @see SKMOBILEAPP_BOL_AbstractNativePurchaseVerificationResult::PURCHASE_STATE_COMPLETED
     * @see SKMOBILEAPP_BOL_AbstractNativePurchaseVerificationResult::PURCHASE_STATE_PENDING
     */
    public $purchaseState;

    /**
     * @var string Store this purchase is coming from. Possible values are `app_store` and `google_play`. Use the
     *             constants in this class instead of literals.
     *
     * @see SKMOBILEAPP_BOL_AbstractNativePurchaseVerificationResult::PURCHASE_SOURCE_APP_STORE
     * @see SKMOBILEAPP_BOL_AbstractNativePurchaseVerificationResult::PURCHASE_SOURCE_GOOGLE_PLAY
     */
    public $purchaseSource;

    /**
     * @var BOL_BillingSale|null Related billing sale object. Can be `null` in case there is no related sale and a new
     *                           one should be created.
     */
    public $billingSale;

    /**
     * Timestamp indicating when this purchase was made.
     */
    public $purchaseTimestamp;
}
