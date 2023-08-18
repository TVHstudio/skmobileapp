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
 * Represents Google Play purchase verification result.
 */
class SKMOBILEAPP_BOL_GooglePlayPurchaseVerificationResult extends SKMOBILEAPP_BOL_AbstractNativePurchaseVerificationResult
{
    /**
     * @var string Token representing the entitlement of a user to some in-app product, used for purchase verification.
     *             These tokens are globally unique and can be used as identifiers.
     */
    public $purchaseToken;

    /**
     * @var string Previous subscription purchase token used to identify resubscription.
     */
    public $linkedPurchaseToken;
}
