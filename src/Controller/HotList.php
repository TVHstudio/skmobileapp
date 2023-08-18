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
use SKMOBILEAPP_BOL_HotListService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use OW;
use HOTLIST_BOL_Service;

class HotList extends Users
{
    /**
     * Is plugin active
     *
     * @var bool
     */
    protected $isPluginActive = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->isPluginActive = OW::getPluginManager()->isPluginActive('hotlist');
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

        // remove from hot list
        $controllers->delete('/me/', function (SilexApplication $app) {
            if ($this->isPluginActive) {
                $loggedUserId = $app['users']->getLoggedUserId();
                $hotListService = HOTLIST_BOL_Service::getInstance();

                // is user in hot list
                $userId = $hotListService->findUserById($loggedUserId);

                if ($userId) {
                    $hotListService->deleteUser($loggedUserId);

                    $hotListUsers = $hotListService->getHotList();

                    if ($hotListUsers) {
                        $hotListUsers = SKMOBILEAPP_BOL_HotListService::getInstance()
                            ->formatHotListData($loggedUserId, $hotListUsers);
                    }
    
                    return $app->json($hotListUsers);
                }

                throw new BadRequestHttpException('User cannot be deleted');
            }

            throw new BadRequestHttpException('Hot list plugin is not activated');
        });

        // join in hot list
        $controllers->post('/me/', function (SilexApplication $app) {
            if ($this->isPluginActive) {
                $loggedUserId = $app['users']->getLoggedUserId();

                // check permissions
                if (!$this->service->isPermissionAllowed($loggedUserId, 'hotlist', 'add_to_list')) {
                    throw new AccessDeniedHttpException;
                }

                $hotListService = HOTLIST_BOL_Service::getInstance();
                $hotListService->addUser($loggedUserId);
                $this->authService->trackActionForUser($loggedUserId, 'hotlist', 'add_to_list');

                $hotListUsers = $hotListService->getHotList();

                if ($hotListUsers) {
                    $hotListUsers = SKMOBILEAPP_BOL_HotListService::getInstance()
                        ->formatHotListData($loggedUserId, $hotListUsers);
                }

                return $app->json($hotListUsers);
            }

            throw new BadRequestHttpException('Hot list plugin is not activated');
        });

        return $controllers;
    }
}
