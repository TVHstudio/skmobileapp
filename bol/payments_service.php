<?php

/**
 * Copyright (c) 2016, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.com/
 * and is licensed under Oxwall Store Commercial License.
 *
 * Full text of this license can be found at http://developers.oxwall.com/store/oscl
 */

use Symfony\Component\HttpKernel\Exception\Exception;

class SKMOBILEAPP_BOL_PaymentsService extends SKMOBILEAPP_BOL_Service
{
    use OW_Singleton;

    const APP_ONLY_MEMBERSHIP_ACTIONS = 'app_only';
    const ALL_MEMBERSHIP_ACTIONS = 'all';

    const MOBILE_BILLING_PAYPAL = 'billingpaypal';
    const MOBILE_BILLING_STRIPE = 'billingstripe';

    const PRODUCT_TYPE_CREDIT_PACK = 'user_credits_pack';
    const PRODUCT_TYPE_MEMBERSHIP_PLAN = 'membership_plan';

    static $allowedMobileBillingGateways = [
        self::MOBILE_BILLING_PAYPAL,
        self::MOBILE_BILLING_STRIPE
    ];

    static $redirectableMobileBillingGateways = [
        self::MOBILE_BILLING_PAYPAL,
        self::MOBILE_BILLING_STRIPE
    ];

    /**
     * @var SKMOBILEAPP_BOL_AppStoreReceiptDataDao
     */
    protected $appStoreReceiptDataDao;

    /**
     * @var SKMOBILEAPP_BOL_GooglePlayPurchaseTokenDataDao
     */
    protected $googlePlayPurchaseTokenDataDao;

    /**
     * @var BOL_BillingService
     */
    protected $billingService;

    /**
     * @var OW_Log
     */
    protected $logger;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->appStoreReceiptDataDao = SKMOBILEAPP_BOL_AppStoreReceiptDataDao::getInstance();
        $this->googlePlayPurchaseTokenDataDao = SKMOBILEAPP_BOL_GooglePlayPurchaseTokenDataDao::getInstance();
        $this->billingService = BOL_BillingService::getInstance();
        $this->logger = SKMOBILEAPP_BOL_Service::getLogger();
    }

    /**
     * Set sale status to `error` by its hash. The sale should belong to the user identified by the provided user ID.
     *
     * @param int $userId
     * @param string $saleHash
     *
     * @return bool True on success, false on failure.
     */
    public function markSaleAsErrorByHash($userId, $saleHash)
    {
        $sale = $this->billingService->getSaleByHash($saleHash);

        if (!$sale || ((int) $sale->userId) !== $userId) {
            return false;
        }

        // Don't change status on sales that are already marked as error.
        if ($sale->status !== BOL_BillingSaleDao::STATUS_ERROR) {
            $sale->status = BOL_BillingSaleDao::STATUS_ERROR;
            $result = $this->billingService->saveSale($sale);

            return $result !== null;
        }

        return true;
    }

    /**
     * Init mobile purchase session
     *
     * @param array $billingSessionData
     * @param integer $userId
     * @throws Exception
     * @return integer
     */
    public function initMobilePurchaseSession($billingSessionData, $userId) 
    {
        $pluginKey = isset($billingSessionData['pluginKey']) 
            ? $billingSessionData['pluginKey'] 
            : '';

        $gatewayKey = isset($billingSessionData['gatewayKey']) 
            ? $billingSessionData['gatewayKey'] 
            : '';

        $product = isset($billingSessionData['product']) 
            ? $billingSessionData['product'] 
            : [];
 
        $productId = isset($product['id']) 
            ? floatval($product['id']) 
            : 0;

        $productPrice = isset($product['price']) 
            ? floatval($product['price']) 
            : 0;

        $productPeriod = isset($product['period']) 
            ? $product['period'] 
            : 30;
 
        $isProductRecurring = isset($product['isRecurring']) 
            ? $product['isRecurring'] 
            : false;

        $periodUnits = isset($product['periodUnits']) 
            ? $product['periodUnits'] 
            : null;

        $billingService = BOL_BillingService::getInstance();
        $productDescription = '';

        switch ($pluginKey)
        {
            case SKMOBILEAPP_BOL_Service::MEMBERSHIP_PLUGIN_KEY:
                $productAdapter = new MEMBERSHIP_CLASS_MembershipPlanProductAdapter();
                $productDescription = MEMBERSHIP_BOL_MembershipService::getInstance()->
                        getFormattedPlan($productPrice, $productPeriod, $isProductRecurring, null, $periodUnits);
                break;

            case SKMOBILEAPP_BOL_Service::USER_CREDITS_PLUGIN_KEY:
                $productAdapter = new USERCREDITS_CLASS_UserCreditsPackProductAdapter();
                $productDescription = USERCREDITS_BOL_CreditsService::
                        getInstance()->getPackTitle($productPrice, (isset($product['credits']) ? $product['credits'] : 0));
                break;

            default:
                throw new Exception('Plugin is not supported');
        }

        // sale object
        $sale = new BOL_BillingSale();
        $sale->pluginKey = $pluginKey;
        $sale->entityDescription = strip_tags($productDescription);
        $sale->entityKey = $productAdapter->getProductKey();
        $sale->entityId = $productId;
        $sale->price = $productPrice;
        $sale->period = $productPeriod;
        $sale->userId = $userId;
        $sale->recurring = $isProductRecurring;
        $sale->periodUnits = $periodUnits;
        $sale->hash = $this->getSaleHash($userId);

        $billingService->initSale($sale, $gatewayKey);

        return $sale->hash;
    }

    /**
     * Add trial membership
     * 
     * @param integer $userId
     * @param MEMBERSHIP_BOL_MembershipPlan $plan
     * @return void
     */
    public function addTrialMembership($userId, MEMBERSHIP_BOL_MembershipPlan $plan) 
    {
        $membershipService = MEMBERSHIP_BOL_MembershipService::getInstance();
        $userMembership = new MEMBERSHIP_BOL_MembershipUser();

        $userMembership->userId = $userId;
        $userMembership->typeId = $plan->typeId;
        $userMembership->expirationStamp = time() + $plan->period * 
                MEMBERSHIP_BOL_MembershipService::getInstance()->getPeriodUnitFactor($plan->periodUnits);

        $userMembership->recurring = 0;
        $userMembership->trial = 1;

        $membershipService->setUserMembership($userMembership);
        $membershipService->addTrialPlanUsage($userId, $plan->id, $plan->period, $plan->periodUnits);
    }

    /**
     * Get full membership info
     * 
     * @param integer $id
     * @param integer $userId
     * @return array
     */
    public function getFullMembershipInfo($id, $userId)
    {
        $membershipId = (int) $id;
        $authService = BOL_AuthorizationService::getInstance();
        $membershipService = MEMBERSHIP_BOL_MembershipService::getInstance();
        $defaultRole = $authService->getDefaultRole();
        $groupActionList = $membershipService->getSubscribePageGroupActionList();
        $userMembership = $membershipService->getUserMembership($userId);
        $userRoleIds = array();
        $currentMembershipTitle = '';

        // get default membership level 
        if ( !$membershipId ) 
        {
            /* @var $default MEMBERSHIP_BOL_MembershipType */
            $default = new MEMBERSHIP_BOL_MembershipType();
            $default->roleId = $defaultRole->id;
            /* @var $defaultRole BOL_AuthorizationRole */
            $userRoleIds = array($defaultRole->id);
            $mTypes = array($default);

        }
        else  
        {
            $mTypes = array($membershipService->findTypeById($membershipId) );
        }

        // find user's roles ids
        if ( $userMembership ) 
        {
            $type = $membershipService->findTypeById($userMembership->typeId);

            if ( $type ) 
            {
                $userRoleIds[] = $type->roleId;
                $currentMembershipTitle = $membershipService->getMembershipTitle($type->roleId);
            }
        }

        $permissions = $authService->getPermissionList();
        $perms = array();

        // get list of allowed permissions for user's roles
        foreach ( $permissions as $permission ) 
        {
            /* @var $permission BOL_AuthorizationPermission */
            $perms[$permission->roleId][$permission->actionId] = true;
        }

        $exclude = $membershipService->getUserTrialPlansUsage($userId);
        $mPlans = $membershipService->getTypePlanList( $exclude );
        $mTypesPermissions = array();

        foreach ( $mTypes as $membership )
        {
            $mId = $membership->id;
            $plans = isset($mPlans[$mId]) ? $mPlans[$mId] : null;

            $data = array(
                'id' => $mId,
                'title' => $membershipService->getMembershipTitle($membership->roleId),
                'roleId' => $membership->roleId,
                'permissions' => isset($perms[$membership->roleId]) ? $perms[$membership->roleId] : null,
                'current' => isset($userRoleIds) ? in_array($membership->roleId, $userRoleIds) : null,
                'plans' =>  $plans
            );

            $mTypesPermissions[$membershipId] = $data;
        }

        // get permissions labeles
        $event = new BASE_CLASS_EventCollector('admin.add_auth_labels');
        OW::getEventManager()->trigger($event);
        $data = $event->getData();
        $dataLabels = empty($data) ? array() : call_user_func_array('array_merge', $data);

        $allowedPermissions = array();
        $showMembershipActions = OW::getConfig()->getValue('skmobileapp', 'inapps_show_membership_actions');
        $appMembershipActions = $showMembershipActions != self::ALL_MEMBERSHIP_ACTIONS
            ? SKMOBILEAPP_BOL_Service::getInstance()->getAppPermissionList()
            : array();


        // filter permissions actions related  to admin settings 
        foreach( $groupActionList as $groupAction ) 
        {
            foreach( $groupAction['actions'] as $action ) 
            {
                foreach( $mTypesPermissions as $mTypesPermission ) 
                {
                    if ( isset($mTypesPermission['permissions'][$action->id]) ) 
                    {
                        $allowToAdd  = $showMembershipActions == self::ALL_MEMBERSHIP_ACTIONS ? true : false;

                        if ( !$allowToAdd ) 
                        {
                            foreach ( $appMembershipActions as $appMembershipData ) 
                            {
                                if ($appMembershipData['group'] == $groupAction['name'] && in_array($action->name, $appMembershipData['actions'])) {
                                    $allowToAdd = true;

                                    break;
                                }
                            }
                        }

                        if (!$allowToAdd) {
                            continue;
                        }

                        $permissionLabel = !empty($dataLabels[$groupAction['name']]['actions'][$action->name])
                            ? $dataLabels[$groupAction['name']]['actions'][$action->name]
                            : $action->name;

                        if ( !isset($allowedPermissions[$groupAction['name']]) ) 
                        {
                            $allowedPermissions[$groupAction['name']] = array(
                                'label' => !empty($dataLabels[$groupAction['name']]) ? $dataLabels[$groupAction['name']]['label'] : $groupAction['name'],
                                'permissions' => [
                                    $permissionLabel
                                ]
                            );
                        } 
                        else 
                        {
                            $allowedPermissions[$groupAction['name']]['permissions'][] = $permissionLabel;
                        }
                    }
                }
            }
        }

        // process allowed permissions
        if ( $allowedPermissions ) 
        {
            $processedPermissions = array();
            foreach( $allowedPermissions as $allowedPermission ) 
            {
                $processedPermissions[] = $allowedPermission;
            }

            $allowedPermissions = $processedPermissions;
        }

        // process plans
        $processedPlans = [];

        if (isset($mPlans[$membershipId])) {
            foreach( $mPlans[$membershipId] as $plan )
            {
                $processedPlans[] = [
                    'id' => (int) $plan['dto']->id,
                    'price' => floatval($plan['dto']->price),
                    'period' => (int) $plan['dto']->period,
                    'periodUnits' => $plan['dto']->periodUnits,
                    'productId' => $plan['productId'],
                    'isRecurring' => $plan['dto']->recurring == 1
                ];
            }
        }

        $isActive = $userMembership && $userMembership->typeId == $membershipId || !$userMembership && !$membershipId;

        return array(
            'id' => (int) $membershipId,
            'title' => $mTypesPermissions[$membershipId]['title'],
            'isActive' => $isActive,
            'isActiveAndTrial' => $isActive && $userMembership && $userMembership->trial == 1,
            'isPlansAvailable' => count($processedPlans) > 1,
            'expire' =>  $isActive && $userMembership
                ? UTIL_DateTime::formatDate($userMembership->expirationStamp) 
                : null,
            'isRecurring' => $isActive && $userMembership && $userMembership->recurring == 1
                ? true
                : false,
            'actions' => $allowedPermissions,
            'plans' => $processedPlans
        );
    }

    /**
     * Get all memberships
     * 
     * @param integer $userId
     * @return array
     */
    public function getMemberships($userId)
    {
        $membershipService = MEMBERSHIP_BOL_MembershipService::getInstance();
        $authService = BOL_AuthorizationService::getInstance();

        $accTypeName = BOL_UserService::getInstance()->findUserById($userId)->getAccountType();
        $accType = BOL_QuestionService::getInstance()->findAccountTypeByName($accTypeName);

        $mTypes = $membershipService->getTypeList($accType->id);

        /* @var $defaultRole BOL_AuthorizationRole */
        $defaultRole = $authService->getDefaultRole();

        /* @var $default MEMBERSHIP_BOL_MembershipType */
        $default = new MEMBERSHIP_BOL_MembershipType();
        $default->roleId = $defaultRole->id;

        $mTypes = array_merge(array($default), $mTypes);

        $userMembership = $membershipService->getUserMembership($userId);
        $exclude = $membershipService->getUserTrialPlansUsage($userId);
        $mPlans = $membershipService->getTypePlanList($exclude);
        $memberships = [];

        foreach ( $mTypes as $membership )
        {
            $isActive = $userMembership && $userMembership->typeId == $membership->id || !$userMembership && !$membership->id;

            $data = array(
                'id' => (int) $membership->id,
                'title' => $membershipService->getMembershipTitle($membership->roleId),
                'isActive' => $isActive,
                'isActiveAndTrial' => $isActive && $userMembership && $userMembership->trial == 1,
                'isPlansAvailable' => isset($mPlans[$membership->id]),
                'expire' =>  $isActive && $userMembership
                    ? UTIL_DateTime::formatDate($userMembership->expirationStamp) 
                    : null,
                'isRecurring' => $isActive && $userMembership && $userMembership->recurring == 1
                    ? true
                    : false
            );

            $memberships[] = $data;
        }

        return $memberships;
    }

    /**
     * Process Google Play sale of a product identified by the provided product ID represented by the given purchase
     * token.
     *
     * @param string $productId
     * @param string $purchaseToken
     *
     * @return BOL_BillingSale|null Billing sale entity instance on success, `null` on failure.
     */
    public function processNewGooglePlayPurchase($productId, $purchaseToken)
    {
        $sale = $this->createSaleFromProductId($productId, SKMOBILEAPP_BOL_Service::PLATFORM_ANDROID, [
            'platform' => SKMOBILEAPP_BOL_Service::PLATFORM_ANDROID,
            'purchaseToken' => $purchaseToken
        ]);

        return $this->tryDeliverNativeSale($sale);
    }

    /**
     * Process existing Google Play sale.
     *
     * @param BOL_BillingSale $sale
     */
    public function processExistingGooglePlaySale($sale)
    {
        if (!$this->isSaleFinished($sale)) {
            return $this->tryDeliverNativeSale($sale);
        }

        return null;
    }

    /**
     * Process App Store sale of a product and verify it using the provided receipt data.
     *
     * @param string $productId
     * @param string $orderId Order ID received from the App Store to identify this sale among others.
     * @param string $receiptData Base64-encoded receipt data.
     *
     * @return BOL_BillingSale|array|null
     */
    public function processNewAppStorePurchase($productId, $orderId, $receiptData)
    {
        // Attempt to find an existing sale with the given order ID.
        $existingSale
            = $this->billingService->getSaleByGatewayTransactionId(SKMOBILEAPP_CLASS_InAppPurchaseAdapter::GATEWAY_KEY, $orderId);

        // If a sale already exists, return an array signifying that.
        if ($existingSale) {
            return [
                'sale_exists' => true
            ];
        }

        $sale = $this->createSaleFromProductId($productId, SKMOBILEAPP_BOL_Service::PLATFORM_IOS, [
            'platform' => SKMOBILEAPP_BOL_Service::PLATFORM_IOS,
            'orderId' => $orderId,
            'receiptData' => $receiptData
        ]);

        return $this->tryDeliverNativeSale($sale);
    }

    /**
     * Attempt to deliver the provided native sale. Returns the updated sale instance if there was no error (even though
     * the product itself might not be delivered) or `null` if there was some error.
     *
     * @param BOL_BillingSale $sale
     *
     * @return BOL_BillingSale|null
     */
    public function tryDeliverNativeSale($sale)
    {
        $productId = $this->getSaleProductId($sale);
        $adapter = new SKMOBILEAPP_CLASS_InAppPurchaseAdapter();

        if (!$sale->getId()) {
            if (!$this->billingService->initSale($sale, SKMOBILEAPP_CLASS_InAppPurchaseAdapter::GATEWAY_KEY)) {
                $this->logger->addEntry(
                    'Sale of `' . $productId . '` COULD NOT BE INITIALIZED for the user ID ' . OW::getUser()->getId(),
                    'in_app_purchases.payments_service.try_deliver_native_sale'
                );

                $this->logger->writeLog();
            }
        }

        $this->logger->addEntry(
            'Attempting to deliver `' . $productId . '` to the user ID ' . OW::getUser()->getId(),
            'in_app_purchases.payments_service.try_deliver_native_sale'
        );

        $this->logger->writeLog();

        try {
            // Prepare sale to verify that all the necessary verification parameters are passed in the `extraData` field
            // of the sale.
            // SKMOBILEAPP_CLASS_InAppPurchaseAdapter's prepareSale method throws an exception if the parameters are
            // invalid.
            if ($this->billingService->prepareSale($adapter, $sale)) {
                // Verify the sale. The `verifySale` method of `SKMOBILEAPP_CLASS_InAppPurchaseAdapter` connects to the
                // store API and verifies the purchase token/receipt data depending on the platform. This method returns
                // `true` only when the sale should be delivered immediately (i.e. the payment has been successfully
                // processed).
                if ($this->billingService->verifySale($adapter, $sale)) {
                    $productAdapter = $this->getProductAdapter($productId);

                    if (!$productAdapter) {
                        return null;
                    }

                    $this->billingService->deliverSale($productAdapter, $sale);

                    $this->logger->addEntry(
                        'Sale of `' . $productId . '` was DELIVERED to the user ID ' . OW::getUser()->getId(),
                        'in_app_purchases.payments_service.try_deliver_native_sale'
                    );
                } else {
                    $this->logger->addEntry(
                        'Sale of `' . $productId . '` was NOT DELIVERED to the user ID ' . OW::getUser()->getId() .
                        ', new sale status is ' . mb_strtoupper($sale->status),
                        'in_app_purchases.payments_service.try_deliver_native_sale'
                    );
                }

                $this->logger->writeLog();
            } else {
                $this->logger->addEntry(
                    'Sale of `' . $productId . '` COULD NOT BE PREPARED for the user ID ' . OW::getUser()->getId() .
                    ', new sale status is ' . mb_strtoupper($sale->status),
                    'in_app_purchases.payments_service.try_deliver_native_sale'
                );

                $this->logger->writeLog();
            }
        } catch (Throwable $e) {
            $this->logger->addEntry(
                'Exception while attempting to deliver the sale of `' . $productId .
                '` to the user ID ' . OW::getUser()->getId() .  ': "' . $e->getMessage() . "\"; stack trace:\n\n" .
                $e->getTraceAsString(),
                'in_app_purchases.payments_service.try_deliver_native_sale'
            );

            $this->logger->writeLog();

            return null;
        }

        return $sale;
    }

    /**
     * Create a native sale using the provided product ID. Sales created using this method should be delivered using the
     * standard sale delivery flow (init -> prepare -> verify -> delivery).
     *
     * @param string $productId
     * @param string $platform
     * @param array $extraData
     *
     * @throws InvalidArgumentException Thrown if the product ID is invalid.
     *
     * @return BOL_BillingSale
     */
    public function createSaleFromProductId($productId, $platform, $extraData = [])
    {
        $product = $this->findProductByProductId($productId);

        if (!$product) {
            throw new InvalidArgumentException('Invalid product ID: `' . $productId . '`');
        }

        $platformLabel = OW::getLanguage()->text(
            SKMOBILEAPP_BOL_Service::PLUGIN_KEY,
            'inapps_' . $platform . '_platform_label'
        );

        $entityDescription = $product['entityDescription'] . ' ' . $platformLabel;

        $sale = new BOL_BillingSale();

        $sale->pluginKey = $product['pluginKey'];
        $sale->entityDescription = $entityDescription;
        $sale->entityKey = $product['entityKey'];
        $sale->entityId = $product['entityId'];
        $sale->price = $product['price'];
        $sale->period = $product['period'];
        $sale->periodUnits = $product['periodUnits'];
        $sale->recurring = $product['recurring'];
        $sale->userId = OW::getUser()->getId();
        $sale->hash = $this->getSaleHash(OW::getUser()->getId());

        if (!empty($extraData)) {
            $sale->extraData = json_encode($extraData);
        }

        return $sale;
    }

    /**
     * Create a subscription renewal sale. Subscription renewals are fake sales used to add renewal entry into the
     * transaction log. These sales should not be delivered or somehow handled using the billing service. Instead, they
     * should be saved using the `BOL_BillingSaleDao` class after the subscription has been renewed.
     *
     * @param string $productId
     * @param string $platform
     *
     * @throws InvalidArgumentException Thrown if the product ID is invalid.
     *
     * @return BOL_BillingSale
     */
    public function createSubscriptionRenewalSale($productId, $platform)
    {
        $parts = $this->parseProductId($productId);
        $entityKey = $parts['entityType'] ?? null;

        if ($entityKey !== self::PRODUCT_TYPE_MEMBERSHIP_PLAN) {
            throw new InvalidArgumentException("Only membership plans can be renewed");
        }

        $product = $this->findProductByProductId($productId);

        if (!$product) {
            throw new InvalidArgumentException('Invalid product ID: "' . $productId . '"');
        }

        $platformLabel = OW::getLanguage()->text(
            SKMOBILEAPP_BOL_Service::PLUGIN_KEY,
            'inapps_' . $platform . '_renewal_platform_label'
        );

        $sale = new BOL_BillingSale();

        $sale->pluginKey = $product['pluginKey'];
        $sale->entityDescription = $product['entityDescription'] . ' ' . $platformLabel;
        $sale->entityKey = $product['entityKey'];
        $sale->entityId = $product['entityId'];
        $sale->price = $product['price'];
        $sale->period = $product['period'];
        $sale->periodUnits = $product['periodUnits'];
        $sale->recurring = false;
        $sale->status = BOL_BillingSaleDao::STATUS_DELIVERED;
        $sale->userId = OW::getUser()->getId();
        $sale->hash = $this->getSaleHash(OW::getUser()->getId());

        return $sale;
    }

    /**
     * Find billing product using the provided product ID. Returns an array containing the product data if the product
     * was found, false otherwise. Array reference:
     *
     * ```
     * $product = [
     *     'entityKey'          =>  (string) Product entity key, e.g. `membership_plan` or `user_credits_pack`,
     *     'entityId'           =>  (int)    Product entity ID,
     *     'pluginKey'          =>  (string) Key of the plugin managing the product,
     *     'entityDescription'  =>  (string) Translated human-readable entity description, can be shown to the user,
     *     'membershipTitle'    =>  (string) Memberships only, translated membership title,
     *     'price'              =>  (double) Product price,
     *     'period'             =>  (int)    Membership activity period in periodUnits, always equals to 30 for the
     *                                       credit packs,
     *     'periodUnits'        =>  (string) Period units,
     *     'recurring'          =>  (bool)   True if the membership is recurring, false if it is one-time, always false
     *                                       for credit packs.
     * ];
     * ```
     *
     * @param string $productId Billing product ID, e.g. `user_credits_pack_5`
     *
     * @return array|null
     */
    public function findProductByProductId($productId)
    {
        $parts = $this->parseProductId($productId);

        $entityType = $parts['entityType'] ?? null;
        $entityId = $parts['entityId'] ?? null;
        $result = [];

        if ($entityType === null || $entityId === null) {
            return null;
        }

        if ($entityType === self::PRODUCT_TYPE_MEMBERSHIP_PLAN) {
            if (!OW::getPluginManager()->isPluginActive(SKMOBILEAPP_BOL_Service::MEMBERSHIP_PLUGIN_KEY)) {
                return null;
            }

            $membershipService = MEMBERSHIP_BOL_MembershipService::getInstance();
            $membershipPlan = $membershipService->findPlanById($entityId);

            if (!$membershipPlan) {
                return null;
            }

            $membershipType = $membershipService->findTypeById($membershipPlan->typeId);

            if (!$membershipType) {
                return null;
            }

            $entityDescription = $membershipService->getFormattedPlan(
                $membershipPlan->price,
                $membershipPlan->period,
                $membershipPlan->recurring,
                null,
                $membershipPlan->periodUnits
            );

            $result = [
                'pluginKey' => SKMOBILEAPP_BOL_Service::MEMBERSHIP_PLUGIN_KEY,
                'entityDescription' => $entityDescription,
                'membershipTitle' => $membershipService->getMembershipTitle($membershipType->roleId),
                'price' => (double) $membershipPlan->price,
                'period' => (int) $membershipPlan->period,
                'periodUnits' => $membershipPlan->periodUnits,
                'recurring' => $membershipPlan->recurring,
            ];
        } elseif ($entityType === self::PRODUCT_TYPE_CREDIT_PACK) {
            if (!OW::getPluginManager()->isPluginActive(SKMOBILEAPP_BOL_Service::USER_CREDITS_PLUGIN_KEY)) {
                return null;
            }

            $creditsService = USERCREDITS_BOL_CreditsService::getInstance();
            $pack = $creditsService->findPackById($entityId);

            if (!$pack) {
                return null;
            }

            $result = [
                'pluginKey' => SKMOBILEAPP_BOL_Service::USER_CREDITS_PLUGIN_KEY,
                'entityDescription' => $creditsService->getPackTitle($pack->price, $pack->credits),
                'price' => (double) $pack->price,
                'period' => 30,
                'periodUnits' => '',
                'recurring' => 0
            ];
        }

        $result['entityKey'] = $entityType;
        $result['entityId'] = $entityId;

        return $result;
    }

    /**
     * Extend user membership expiration time for the given period using the provided billing sale instance. If the new
     * expiration timestamp is `null` the membership will be extended for exactly one period according to the membership
     * plan's period units.
     *
     * @param BOL_BillingSale $sale
     * @param int|null $newExpirationTimestamp
     *
     * @return bool True if the membership was extended successfully, false otherwise.
     */
    public function extendMembershipBySale($sale, $newExpirationTimestamp = null)
    {
        $productId = $sale->entityKey . '_' . $sale->entityId;

        try {
            $user = $this->findMembershipUserBySale($sale);
        } catch (Throwable $e) {
            $this->logger->addEntry(
                'Exception while trying to extend `' . $productId . '` for user ID ' . $sale->userId . ': "' .
                $e->getMessage() . "\"; stack trace:\n\n" . $e->getTraceAsString(),
                'in_app_purchases.payments_service.extend_membership_by_sale'
            );

            $this->logger->writeLog();

            return false;
        }

        if (!$user) {
            $this->logger->addEntry(
                'Can\'t find assigned membership for user ID ' . $sale->userId,
                'in_app_purchases.payments_service.extend_membership_by_sale'
            );

            $this->logger->writeLog();

            return false;
        }

        $membershipService = MEMBERSHIP_BOL_MembershipService::getInstance();

        if (!$newExpirationTimestamp) {
            $plan = $membershipService->findPlanById((int) $sale->entityId);
            $newExpirationTimestamp = time() + ((int) $plan->period) * $membershipService->getPeriodUnitFactor($plan->periodUnits);
        }

        $user->expirationStamp = $newExpirationTimestamp;
        $membershipService->updateMembershipUser($user);

        return true;
    }

    /**
     * Find membership plan assignment using the provided billing sale entity.
     *
     * @param BOL_BillingSale $sale
     *
     * @throws InvalidArgumentException Thrown if the sale does not represent a membership plan.
     *
     * @return MEMBERSHIP_BOL_MembershipUser|null Membership user entity instance if the user has membership level
     *                                            assigned and the sale represents that membership level, `null`
     *                                            otherwise.
     */
    public function findMembershipUserBySale($sale)
    {
        // Sale instance must represent a membership plan.
        if ($sale->entityKey !== self::PRODUCT_TYPE_MEMBERSHIP_PLAN) {
            throw new InvalidArgumentException(
                'Invalid sale entity: `' . $sale->entityKey . '`, `' . self::PRODUCT_TYPE_MEMBERSHIP_PLAN . '` expected'
            );
        }

        // Find membership of the user that made the purchase.
        $membershipUserDao = MEMBERSHIP_BOL_MembershipUserDao::getInstance();
        $membershipUser = $membershipUserDao->findByUserId((int) $sale->userId);

        // Return null if the user has no assigned membership.
        if (!$membershipUser) {
            return null;
        }

        // Find membership type for the purchased membership plan ID.
        $membershipService = MEMBERSHIP_BOL_MembershipService::getInstance();
        $type = $membershipService->findTypeByPlanId((int) $sale->entityId);

        // Return null if the type was not found.
        if (!$type) {
            return null;
        }

        // Return null if the user's membership type is different from what is in the sale.
        if (((int) $membershipUser->typeId) !== $type->getId()) {
            return null;
        }

        return $membershipUser;
    }

    /**
     * Reset membership user
     */
    public function resetMembershipUser( MEMBERSHIP_BOL_MembershipUser $membershipUser )
    {
        $membershipService = MEMBERSHIP_BOL_MembershipService::getInstance();

        $membershipUser->expirationStamp = time();
        $membershipService->updateMembershipUser($membershipUser);
    }

    /**
     * Get product ID for the given billing sale.
     *
     * @param BOL_BillingSale $sale
     *
     * @return string
     */
    public function getSaleProductId($sale)
    {
        return $this->getProductId($sale->entityKey, (int) $sale->entityId);
    }

    /**
     * Get product ID for the entity identified by the provided entity key and entity ID. Only membership and user
     * credit pack entities are supported.
     *
     * @param string $entityKey
     * @param string $entityId
     *
     * @throws InvalidArgumentException Thrown if the entity key does not represent membership plan or a user credit
     *                                  pack.
     * @throws RuntimeException Thrown if the get product ID event returned null.
     *
     * @return string
     */
    public function getProductId($entityKey, $entityId)
    {
        switch ($entityKey) {
            case self::PRODUCT_TYPE_MEMBERSHIP_PLAN:
                $eventName = 'membership.get_product_id';
                break;

            case self::PRODUCT_TYPE_CREDIT_PACK:
                $eventName = 'usercredits.get_product_id';
                break;

            default:
                throw new InvalidArgumentException('Unknown entity key: "' . $entityKey . '"');
        }

        $event = new OW_Event($eventName, [ 'id' => $entityId ]);
        OW::getEventManager()->trigger($event);

        $productId = $event->getData();

        if (!$productId) {
            throw new RuntimeException(
                'Could not get product ID for entity key "' . $entityKey . '" and entity ID ' . $entityId
            );
        }

        return $event->getData();
    }

    /**
     * Find credit pack by its ID.
     *
     * @param int $creditPackId
     *
     * @return USERCREDITS_BOL_Pack
     */
    public function findCreditPackById($creditPackId)
    {
        return USERCREDITS_BOL_CreditsService::getInstance()->findPackById($creditPackId);
    }

    /**
     * Find membership plan by its ID and check whether it is available for the given user ID. Return the plan if it is
     * available, return null otherwise.
     *
     * @param int $userId
     * @param int $planId
     *
     * @return MEMBERSHIP_BOL_MembershipPlan|null
     */
    public function findAvailableMembershipPlanByUserIdAndPlanId($userId, $planId)
    {
        // Get user account type using the provided user ID.
        $accountType = BOL_UserService::getInstance()->findUserById($userId)->accountType;
        $accountTypeId = BOL_QuestionService::getInstance()->findAccountTypeByName($accountType)->getId();

        // Retrieve all roles and plans available for the account ID in prepared form (there is no method to retrieve
        // raw plan objects).
        $formattedPlans = MEMBERSHIP_BOL_MembershipService::getInstance()->getTypeListWithPlans($accountTypeId);

        /** @var MEMBERSHIP_BOL_MembershipPlan[] $planDtosById */
        $planDtosById = [];

        // Form a `planId => planDto` array.
        foreach ($formattedPlans as $formattedPlan) {
            foreach ($formattedPlan['plans'] as $plan) {
                /** @var MEMBERSHIP_BOL_MembershipPlan $planDto */
                $planDto = $plan['dto'];

                $planDtosById[ $planDto->getId() ] = $planDto;
            }
        }

        // Return the plan DTO with the given `$planId` if it is present among the retrieved plans.
        return isset($planDtosById[$planId]) ? $planDtosById[$planId] : null;
    }

    /**
     * Formats `USERCREDITS_BOL_Pack` object into a JSON-serializable array ready for consumption by the Skadate mobile
     * application. Returns empty array if `$creditPack` is `null`. Resulting array structure:
     *
     * ```
     * $formattedCreditPack = [
     *     'id'         => (int)    Credit pack ID,
     *     'credits'    => (int)    Number of credits in the credit pack,
     *     'price'      => (int)    Price of the credit pack in the site billing currency,
     *     'productId'  => (string) In-app product id,
     * ];
     * ```
     *
     * @param USERCREDITS_BOL_Pack $creditPack
     */
    public function formatCreditPack($creditPack)
    {
        if (!$creditPack) {
            return [];
        }

        $getProductIdEvent = new OW_Event('usercredits.get_product_id', [
            'id' => $creditPack->getId()
        ]);

        OW::getEventManager()->trigger($getProductIdEvent);

        $productId = $getProductIdEvent->getData();

        return [
            'id' => $creditPack->getId(),
            'credits' => (int) $creditPack->credits,
            'price' => (double) $creditPack->price,
            'productId' => $productId
        ];
    }

    /**
     * Formats `MEMBERSHIP_BOL_MembershipPlan` object into a JSON-serializable array ready for consumption by the
     * Skadate mobile application. Returns empty array if `$plan` is `null`. Resulting array structure:
     *
     * ```
     * $formattedPlan = [
     *     'id'             => (int)    Membership plan ID,
     *     'price'          => (int)    Membership plan price for the given period in the site billing currency,
     *     'period'         => (int)    Membership plan activity period,
     *     'periodUnits'    => (string) Activity period units (days, months, etc.),
     *     'isRecurring'    => (bool)   Is this plan recurring,
     *     'productId'      => (string) Inn-app product id,
     * ];
     * ```
     *
     * @param MEMBERSHIP_BOL_MembershipPlan $plan
     *
     * @return array
     */
    public function formatMembershipPlan($plan)
    {
        if (!$plan) {
            return [];
        }

        $getProductIdEvent = new OW_Event('membership.get_product_id', [
            'id' => $plan->getId()
        ]);

        OW::getEventManager()->trigger($getProductIdEvent);

        $productId = $getProductIdEvent->getData();

        return [
            'id' => $plan->getId(),
            'price' => (double) $plan->price,
            'period' => (int) $plan->period,
            'periodUnits' => $plan->periodUnits,
            'isRecurring' => (bool) $plan->recurring,
            'productId' => $productId
        ];
    }

    /**
     * Parses in-app product ID and returns an array containing the product entity type and ID. Returns empty array if
     * the given product ID is incorrect. Array structure:
     *
     * ```
     * $productInfo = [
     *     'entityType' => (string) Product entity type,
     *     'entityId'   => (string) Product entity ID,
     * ]
     * ```
     *
     * @param string $productId
     *
     * @return array
     */
    public function parseProductId($productId)
    {
        $entityType = strtolower(substr($productId, 0, strrpos($productId, '_')));
        $entityId = (int) substr($productId, strrpos($productId, '_') + 1);

        return [
            'entityType' => $entityType,
            'entityId' => (int) $entityId
        ];
    }

    /**
     * Get PWA billing gateways. Returns an array of billing gateways supported by the Skadate PWA that can be sent to
     * the application as JSON. Array structure:
     *
     * ```
     * $gateways = [
     *     [
     *         'name'           => (string)  Gateway key,
     *         'isRedirectable' => (boolean) Is this gateway redirectable,
     *     ]
     * ]
     * ```
     *
     * @return array[]
     */
    public function getMobileBillingGateways()
    {
        $allowedGateways = array_filter(BOL_BillingService::getInstance()->getActiveGatewaysList(true), function ($gateway) {
            return in_array($gateway->gatewayKey, self::$allowedMobileBillingGateways);
        });

        return array_map(function ($gateway) {
            return [
                'name' => $gateway->gatewayKey,
                'isRedirectable' => in_array($gateway->gatewayKey, self::$redirectableMobileBillingGateways)
            ];
        }, $allowedGateways);
    }

    /**
     * Get existing billing sale instance by the purchase token related to it.
     *
     * @param string $purchaseToken
     *
     * @return BOL_BillingSale|null Billing sale entity object if the sale was found, `null` otherwise.
     */
    public function findExistingSaleByPurchaseToken($purchaseToken)
    {
        $purchaseTokenData = $this->googlePlayPurchaseTokenDataDao->findByPurchaseToken($purchaseToken);

        if ($purchaseTokenData !== null) {
            $billingSaleId = (int) $purchaseTokenData->billingSaleId;
            $sale = $this->billingService->getSaleById($billingSaleId);

            if ($sale) {
                $sale->extraData = json_encode([
                    'platform' => self::PLATFORM_ANDROID,
                    'purchaseToken' => $purchaseTokenData->purchaseToken
                ]);

                return $sale;
            }
        }

        return null;
    }

    /**
     * Return whether the given sale is finished. The sale is considered finished when its state is `delivered` or
     * `error`.
     *
     * @param BOL_BillingSale $sale
     *
     * @return bool
     */
    public function isSaleFinished($sale)
    {
        return $sale->status === BOL_BillingSaleDao::STATUS_DELIVERED
            || $sale->status === BOL_BillingSaleDao::STATUS_ERROR;
    }

    /**
     * Get product adapter for the given product ID.
     *
     * @param string $productId
     *
     * @return OW_BillingProductAdapter|null Product adapter instance if one was found, `null` otherwise.
     */
    protected function getProductAdapter($productId)
    {
        $parts = $this->parseProductId($productId);
        $entityType = $parts['entityType'] ?? null;

        if (!$entityType) {
            $this->logger->addEntry(
                'Invalid product ID: "' . $productId . '"',
                'in_app_purchases.payments_service.get_product_adapter'
            );

            $this->logger->writeLog();

            return null;
        }

        switch ($entityType) {
            case self::PRODUCT_TYPE_MEMBERSHIP_PLAN:
                return new MEMBERSHIP_CLASS_MembershipPlanProductAdapter();

            case self::PRODUCT_TYPE_CREDIT_PACK:
                return new USERCREDITS_CLASS_UserCreditsPackProductAdapter();
        }

        $this->logger->addEntry(
            'Unknown entity type: "' . $entityType . '"',
            'in_app_purchases.payments_service.get_product_adapter'
        );

        $this->logger->writeLog();

        return null;
    }

    /**
     * Get unique sale hash based on the buyer ID and current microtime.
     *
     * @param int $buyerId
     *
     * @return string 40 symbols long sale hash string
     */
    protected function getSaleHash($buyerId)
    {
        return sha1($buyerId . microtime(true));
    }
}
