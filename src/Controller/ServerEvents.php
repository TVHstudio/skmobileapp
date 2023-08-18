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
use Skadate\Mobile\ServerEventsChannel\IChannel;
use SKMOBILEAPP_BOL_Service;

class ServerEvents extends AbstractServerUpdates
{
    /**
     * Stream headers
     *
     * @var array
     */
    protected $streamHeaders = [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache',
        'Connection' => 'keep-alive',
        'X-Accel-Buffering' => 'no',
        'Access-Control-Allow-Origin' => '*'
    ];

    /**
     * Start streaming
     *
     * @param integer $userId
     * @return callback
     */
    public function startStreaming($userId = null) {
        return function() use ($userId) {
            session_write_close();
            $endTime = time() + $this->getMaxExecutionTime();

            while (time() < $endTime) {
                // detect changes in channels
                /** @var IChannel $channel */
                foreach ($this->channels as $channel) {
                    // get the channel's data
                    $data = $channel->getData($userId);

                    if ($data !== null && $channel->detectChanges($data)) {
                        echo sprintf("data: %s\n", json_encode([
                            'channel' => $channel->getName(),
                            'data' => $data
                        ]));

                        echo sprintf("id: %s\n\n", date('c'));
                        ob_flush();
                        flush();
                    }
                }

                sleep(self::DETECT_CHANGES_DELAY_SEC);
            }
        };
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

        // connect to server events (for guests)
        $controllers->get('/', function (SilexApplication $app) {
            return $app->stream($this->startStreaming(), 200, $this->streamHeaders);
        });

        // connect to server events (for logged users)
        $controllers->get('/user/{token}/', function (SilexApplication $app, $token) {
            $user = $app['security.jwt.encoder']->decode($token);

            // internal user authentication
            SKMOBILEAPP_BOL_Service::getInstance()->internalUserAuthenticate($user->id);

            return $app->stream($this->startStreaming($user->id), 200, $this->streamHeaders);
        });

        return $controllers;
    }
}
