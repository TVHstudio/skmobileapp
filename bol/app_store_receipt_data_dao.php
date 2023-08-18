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
 * Creates, updates and persists App Store receipt data entity objects.
 */
class SKMOBILEAPP_BOL_AppStoreReceiptDataDao extends OW_BaseDao
{
    use OW_Singleton;

    protected function __construct()
    {
        parent::__construct();
    }

    /**
     * Create new App Store receipt data for the billing plugin purchase identified by the provided billing purchase ID.
     *
     * @param int $billingSaleId ID of the billing plugin sale this receipt is related to.
     * @param string $rawReceiptData Base64-encoded receipt data.
     *
     * @return SKMOBILEAPP_BOL_AppStoreReceiptData Created App Store receipt data instance, should be persisted by the
     *                                             caller by calling the `save` method of this DAO.
     */
    public function create($billingSaleId, $rawReceiptData)
    {
        $receiptData = new SKMOBILEAPP_BOL_AppStoreReceiptData();

        $receiptData->billingSaleId = $billingSaleId;
        $receiptData->encodedReceipt = $rawReceiptData;

        return $receiptData;
    }

    /**
     * Find App Store receipt data using the provided billing sale ID list.
     *
     * @param int[] $billingSaleIdList
     *
     * @return SKMOBILEAPP_BOL_AppStoreReceiptData[]
     */
    public function findByBillingSaleIdList($billingSaleIdList)
    {
        $example = new OW_Example();
        $example->andFieldInArray('billingSaleId', $billingSaleIdList);

        return $this->findListByExample($example);
    }

    /**
     * Find App Store receipt data for the billing plugin sale identified by the given billing sale ID.
     *
     * @param int $billingSaleId
     *
     * @return SKMOBILEAPP_BOL_AppStoreReceiptData|null App Store receipt data entity instance if the receipt data was
     *                                                  found, null otherwise.
     */
    public function findByBillingSaleId($billingSaleId)
    {
        $example = new OW_Example();
        $example->andFieldEqual('billingSaleId', $billingSaleId);

        return $this->findObjectByExample($example);
    }

    /**
     * @inheritDoc
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . SKMOBILEAPP_BOL_Service::PLUGIN_KEY . '_app_store_receipt_data';
    }

    /**
     * @inheritDoc
     */
    public function getDtoClassName()
    {
        return 'SKMOBILEAPP_BOL_AppStoreReceiptData';
    }
}
