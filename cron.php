<?php

/**
 * Copyright (c) 2017, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.com/
 * and is licensed under Oxwall Store Commercial License.
 *
 * Full text of this license can be found at http://developers.oxwall.com/store/oscl
 */

class SKMOBILEAPP_Cron extends OW_Cron
{
    /**
     * Import location user limit
     */
    const IMPORT_LOCATION_USER_LIMIT = 100;

    /**
     * Expired subscriptions
     */
    const EXPIRED_SUBSCRIPTIONS_LIMIT = 20;

    /**
     * @var OW_Log
     */
    protected $logger;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->logger = SKMOBILEAPP_BOL_Service::getLogger();

        $this->addJob('cleanDeviceTokens', 24 * 60);
    }

    /**
     * Run
     */
    public function run()
    {
        // import locations
        if ( OW::getPluginManager()->isPluginActive('googlelocation') )
        {
            $service = SKMOBILEAPP_BOL_Service::getInstance();
            $lastUserId = (int) OW::getConfig()->getValue('skmobileapp', 'import_location_last_user_id');

            // find users
            $example = new OW_Example();
            $example->andFieldGreaterThan('id', $lastUserId);
            $example->setLimitClause(0, self::IMPORT_LOCATION_USER_LIMIT);
            $example->setOrder('id');

            $users = BOL_UserDao::getInstance()->findListByExample($example);

            // process users
            if ( $users )
            {
                $userIds = [];

                // get user ids
                $lastUserId = 0;
                foreach ( $users as $user )
                {
                    $userIds[] = $user->id;
                    $lastUserId = $user->id;
                }

                // get user locations
                $locations = GOOGLELOCATION_BOL_LocationService::getInstance()->findByUserIdList($userIds);

                // process locations
                foreach ( $locations as $location )
                {
                    if ( !$service->findUserLocation($location->entityId) )
                    {
                        $service->updateUserLocation($location->entityId, $location->lat, $location->lng);
                    }
                }

                OW::getConfig()->saveConfig('skmobileapp', 'import_location_last_user_id', $lastUserId);
            }
        }
    }

    /**
     * Clean expired devices
     */
    public function cleanDeviceTokens()
    {
        SKMOBILEAPP_BOL_DeviceService::getInstance()->cleanExpiredDevices();
    }
}
