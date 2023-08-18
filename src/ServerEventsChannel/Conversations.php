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

use SKMOBILEAPP_BOL_MailboxService;
use OW;

class Conversations extends Base
{
    /**
     * Conversations limit
     */
    const CONVERSATIONS_LIMIT = 100;

    /**
     * @param null $userId
     *
     * @return mixed|null
     */
    public function getData($userId = null) {
        if ($userId && OW::getPluginManager()->isPluginActive('mailbox')) {
            $conversations = SKMOBILEAPP_BOL_MailboxService::getInstance()
                ->getConversations($userId, self::CONVERSATIONS_LIMIT);

            return $conversations;
        }

        return null;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName() {
        return 'conversations';
    }
}
