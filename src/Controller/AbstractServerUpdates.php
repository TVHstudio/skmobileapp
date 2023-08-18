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

use Skadate\Mobile\ServerEventsChannel\Permissions as PermissionsChannel;
use Skadate\Mobile\ServerEventsChannel\Conversations as ConversationsChannel;
use Skadate\Mobile\ServerEventsChannel\MatchedUsers as MatchedUsersChannel;
use Skadate\Mobile\ServerEventsChannel\Messages as MessagesChannel;
use Skadate\Mobile\ServerEventsChannel\Guests as GuestsChannel;
use Skadate\Mobile\ServerEventsChannel\HotList as HotListChannel;
use Skadate\Mobile\ServerEventsChannel\VideoIm as VideoImChannel;
use Skadate\Mobile\ServerEventsChannel\Configs as ConfigChannel;

abstract class AbstractServerUpdates extends Base
{
    /**
     * Max execution time
     */
    const MAX_EXECUTION_TIME = 30;

    /**
     * Detect chnages delay in seconds
     */
    const DETECT_CHANGES_DELAY_SEC = 5;

    /**
     * Channels
     *
     * @var array
     */
    protected $channels = [];

    /**
     * ServerEvents constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->channels = [
            new PermissionsChannel,
            new ConversationsChannel,
            new MatchedUsersChannel,
            new MessagesChannel,
            new GuestsChannel,
            new HotListChannel,
            new VideoImChannel,
            new ConfigChannel,
        ];
    }

    /**
     * Get max execution time
     */
    protected function getMaxExecutionTime() {
        $iniMaxExecutionTime = (int) ini_get('max_execution_time');

        if ($iniMaxExecutionTime && self::MAX_EXECUTION_TIME > $iniMaxExecutionTime) {
            return $iniMaxExecutionTime;
        }

        return self::MAX_EXECUTION_TIME;
    }
}
