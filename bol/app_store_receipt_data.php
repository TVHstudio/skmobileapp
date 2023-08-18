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
 * Represents App Store receipt data for a particular App Store purchase stored for validation.
 */
class SKMOBILEAPP_BOL_AppStoreReceiptData extends OW_Entity
{
    /**
     * ID of the billing plugin sale this receipt is related to.
     */
    public $billingSaleId;

    /**
     * @var string Base64-encoded receipt data.
     */
    public $encodedReceipt;
}
