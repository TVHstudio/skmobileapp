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
 * Creates, updates and persists Google Play purchase token data entity objects.
 */
class SKMOBILEAPP_BOL_GooglePlayPurchaseTokenDataDao extends OW_BaseDao
{
    use OW_Singleton;

    protected function __construct()
    {
        parent::__construct();
    }

    /**
     * Create new Google Play purchase token data for the billing plugin purchase identified by the given billing
     * purchase ID. The returned entity object should be persisted manually by calling the `save` method of this DAO.
     *
     * @param int $billingSaleId ID of the billing plugin sale this token data is related to.
     * @param string $purchaseToken Google Play purchase token value. Purchase tokens represent the entitlement of a
     *                              user to some in-app product and are used for purchase verification
     * @param string $linkedPurchaseToken Linked purchase token value. Linked purchase token is equal to the previous
     *                                    subscription purchase token and is used to identify resubscription. Can be
     *                                    `null`.
     *
     * @return SKMOBILEAPP_BOL_GooglePlayPurchaseTokenData Created Google Play purchase token entity. Should be
     *                                                     persisted manually by calling the `save` method of this DAO.
     */
    public function create($billingSaleId, $purchaseToken, $linkedPurchaseToken)
    {
        $tokenData = new SKMOBILEAPP_BOL_GooglePlayPurchaseTokenData();

        $tokenData->billingSaleId = $billingSaleId;
        $tokenData->purchaseToken = $purchaseToken;
        $tokenData->linkedPurchaseToken = $linkedPurchaseToken;

        return $tokenData;
    }

    /**
     * Find Google Play purchase token data by the related billing plugin sale ID.
     *
     * @param int $billingSaleId
     *
     * @return SKMOBILEAPP_BOL_GooglePlayPurchaseTokenData|null
     */
    public function findByBillingSaleId($billingSaleId)
    {
        $example = new OW_Example();
        $example->andFieldEqual('billingSaleId', $billingSaleId);

        return $this->findObjectByExample($example);
    }

    /**
     * Find Google Play purchase token data using the provided purchase token value.
     *
     * @param string $purchaseToken
     *
     * @return SKMOBILEAPP_BOL_GooglePlayPurchaseTokenData|null
     */
    public function findByPurchaseToken($purchaseToken)
    {
        $example = new OW_Example();
        $example->andFieldEqual('purchaseToken', $purchaseToken);

        return $this->findObjectByExample($example);
    }

    /**
     * @inheritDoc
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . SKMOBILEAPP_BOL_Service::PLUGIN_KEY . '_google_play_purchase_token_data';
    }

    /**
     * @inheritDoc
     */
    public function getDtoClassName()
    {
        return 'SKMOBILEAPP_BOL_GooglePlayPurchaseTokenData';
    }
}
