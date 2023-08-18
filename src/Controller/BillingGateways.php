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
namespace Skadate\Mobile\Controller;

use Silex\Application as SilexApplication;
use SKMOBILEAPP_BOL_PaymentsService;
use Symfony\Component\HttpFoundation\Request;

class BillingGateways extends Base
{
    /**
     * @var SKMOBILEAPP_BOL_PaymentsService
     */
    protected $paymentsService;

    public function __construct()
    {
        parent::__construct();

        $this->paymentsService = SKMOBILEAPP_BOL_PaymentsService::getInstance();
    }

    /**
     * Connect methods
     *
     * @param SilexApplication $app
     * @return mixed
     */
    public function connect(SilexApplication $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];

        // get all active gateways supported mobile platform
        $controllers->get('/', function (SilexApplication $app) {
            return $app->json($this->paymentsService->getMobileBillingGateways());
        });

        $controllers->get('/with-product/', function (Request $request, SilexApplication $app) {
            $userId = $app['users']->getLoggedUserId();
            $gateways = $this->paymentsService->getMobileBillingGateways();
            $productId = $request->query->get('id');

            $data = [
                'product' => [],
                'billingGateways' => $gateways,
            ];

            if (!$productId) {
                return $app->json($data);
            }

            $productInfo = $this->paymentsService->parseProductId($productId);

            if (!isset($productInfo['entityType']) || !isset($productInfo['entityId']) || !$productInfo['entityId']) {
                return $app->json($data);
            }

            if ($productInfo['entityType'] === SKMOBILEAPP_BOL_PaymentsService::PRODUCT_TYPE_MEMBERSHIP_PLAN) {
                $plan = $this->paymentsService->formatMembershipPlan(
                    $this->paymentsService->findAvailableMembershipPlanByUserIdAndPlanId($userId, $productInfo['entityId'])
                );

                if (empty($plan)) {
                    return $app->json($data);
                }

                $data['product'] = $plan;
            } elseif ($productInfo['entityType'] === SKMOBILEAPP_BOL_PaymentsService::PRODUCT_TYPE_CREDIT_PACK) {
                $creditPack = $this->paymentsService->formatCreditPack(
                    $this->paymentsService->findCreditPackById($productInfo['entityId'])
                );

                if (empty($creditPack)) {
                    return $app->json($data);
                }

                $data['product'] = $creditPack;
            }

            return $app->json($data);
        });

        return $controllers;
    }
}
