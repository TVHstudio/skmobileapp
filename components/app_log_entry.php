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

/**
 * Custom view for app-related logs.
 */
class SKMOBILEAPP_CMP_AppLogEntry extends OW_Component
{
    /**
     * @var BOL_Log
     */
    protected $entry;

    /**
     * @param BOL_Log $entry
     */
    public function __construct( $entry )
    {
        parent::__construct();

        $this->entry = $entry;
    }

    /**
     * @inheritDoc
     */
    public function onBeforeRender()
    {
        $entryData = json_decode($this->entry->getMessage(), true);

        // Attach user info (if available).
        if ( isset($entryData['userName']) && isset($entryData['userEmail']) )
        {
            $userName = $entryData['userName'];
            $userEmail = $entryData['userEmail'];

            if ( is_string($userName) )
            {
                $userInfo = array(
                    'name' => htmlspecialchars($userName)
                );

                if ( is_string($userEmail) )
                {
                    $userInfo['name'] .= ' (' . htmlspecialchars($userEmail) . ')';
                }

                $userInfo['profileLink'] = OW::getRouter()->urlForRoute(
                    'base_user_profile',
                    array('username' => $userName)
                );

                $this->assign('userInfo', $userInfo);
            }
        }

        // Attach server response info if the log entry represents a ServerException and it is available.
        if ( isset($entryData['isServerException']) && $entryData['isServerException'] )
        {
            $this->assign('isServerException', true);

            if ( isset($entryData['isResponseInfoAvailable']) && $entryData['isResponseInfoAvailable'] )
            {
                $responseInfo = array(
                    'statusCode' => $entryData['statusCode'],
                    'requestMethod' => $entryData['requestMethod'],
                    'requestUri' => $entryData['requestUri']
                );

                $this->assign('responseInfo', $responseInfo);
            }
        }

        // Attach platform info (if available).
        if ( isset($entryData['platformInfo']) && is_array($entryData['platformInfo']) )
        {
            $platformInfo = $entryData['platformInfo'];
            $platform = $platformInfo['platform'];

            $platformInfoProcessed = array(
                array(
                    'label' => OW::getLanguage()->text('skmobileapp', 'admin_platform_label'),
                    'value' => $platform
                )
            );

            foreach ( $entryData['platformInfo'] as $key => $value )
            {
                if ( $key === 'platform' )
                {
                    continue;
                }

                $keyNormalized = UTIL_String::capsToDelimiter($key);
                $langKey = 'admin_platform_info_' . $platform . '_' . $keyNormalized;

                $platformInfoProcessed[] = array(
                    'label' => OW::getLanguage()->text('skmobileapp', $langKey),
                    'value' => $value
                );
            }

            $this->assign('platformInfo', $platformInfoProcessed);
        }

        // Make stack trace safe to display.
        if ( isset($entryData['stackTrace']) )
        {
            $entryData['stackTrace'] = htmlspecialchars($entryData['stackTrace']);
        }

        $this->assign('entryData', $entryData);
    }
}
