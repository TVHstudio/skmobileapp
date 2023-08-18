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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use SKMOBILEAPP_BOL_CompatibleUsersService;
use OW;

class CompatibleUsers extends Base
{
    const MAX_COMPATIBLE_USERS_LIMIT = 100;

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

        $this->isPluginActive = OW::getPluginManager()->isPluginActive('matchmaking');
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

        $controllers->get('/', function () use ($app) {
            if ($this->isPluginActive) {
                $loggedUserId = $app['users']->getLoggedUserId();

                $users = SKMOBILEAPP_BOL_CompatibleUsersService::getInstance()
                    ->findUsers($loggedUserId, self::MAX_COMPATIBLE_USERS_LIMIT);

                return $app->json($users);
            }

            throw new BadRequestHttpException('Matchmaking plugin is not activated');
        });

        return $controllers;
    }
}
