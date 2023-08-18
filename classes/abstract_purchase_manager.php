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
 * Base class for all purchase managers.
 */
abstract class SKMOBILEAPP_CLASS_AbstractPurchaseManager
{
    /**
     * Consumable purchase type.
     */
    const PURCHASE_TYPE_CONSUMABLE = 'consumable';

    /**
     * Subscription purchase type.
     */
    const PURCHASE_TYPE_SUBSCRIPTION = 'subscription';

    /**
     * @var string Plugin component key for the logger.
     */
    private $componentKey;

    /**
     * @var OW_Log
     */
    private $logger;

    /**
     * @var BOL_BillingService
     */
    protected $billingService;

    /**
     * @var SKMOBILEAPP_BOL_PaymentsService
     */
    protected $paymentsService;

    /**
     * @var string
     */
    protected $bundleId;

    /**
     * @param string $componentKey Component key for the logger. Should equal to the descendant class name formatted
     *                             in snake_case.
     */
    public function __construct($componentKey)
    {
        $this->componentKey = 'in_app_purchase.' . $componentKey;
        $this->logger = SKMOBILEAPP_BOL_Service::getLogger();

        $this->billingService = BOL_BillingService::getInstance();
        $this->paymentsService = SKMOBILEAPP_BOL_PaymentsService::getInstance();

        $this->bundleId = OW::getConfig()->getValue(SKMOBILEAPP_BOL_Service::PLUGIN_KEY, 'inapps_apm_package_name');
    }

    /**
     * Add a log entry related to the given user ID's purchase of the given product. Use this method for all log entries
     * of this type to maintain a common format. Call `writeLog` after adding all entries to flush the buffer.
     *
     * @param int $userId ID of the user making the purchase.
     * @param string $productId ID of the purchased product.
     * @param string $message Log message.
     *
     * @return void
     */
    protected function addPurchaseLogEntry($userId, $productId, $message)
    {
        $this->addLogEntry('User ID ' . $userId . '\'s purchase of "' . $productId . '": ' . $message);
    }

    /**
     * Add an entry to the log buffer. Call `writeLog` after adding all entries to flush the buffer.
     *
     * @param string $message
     *
     * @return void
     */
    protected function addLogEntry($message)
    {
        $this->logger->addEntry($message, $this->componentKey);
    }

    /**
     * Flush the log buffer.
     *
     * @return void
     */
    protected function writeLog()
    {
        $this->logger->writeLog();
    }
}