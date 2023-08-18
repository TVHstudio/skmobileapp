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
namespace Skadate\Mobile\ServerEventsChannel;

use SKMOBILEAPP_BOL_VideoImService;
use OW;

class VideoIm extends Base
{

    /**
     * @param null $userId
     *
     * @return mixed|null
     */
    public function getData($userId = null) {
        if ($userId && OW::getPluginManager()->isPluginActive('videoim')) {
            return SKMOBILEAPP_BOL_VideoImService::getInstance()->getNotifications($userId);
        }

        return null;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName() {
        return 'videoIm';
    }
}
