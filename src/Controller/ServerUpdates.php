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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class ServerUpdates extends AbstractServerUpdates
{
    public function checkUpdates($userId = null, array $hashes = []) {
        session_write_close();
        $endTime = time() + $this->getMaxExecutionTime();
        $isReturned = false;
        $response = [
            'data' => [],
            'data_hashes' => []
        ];

        while (time() < $endTime && !connection_aborted()) {
            // detect changes in the channels
            /** @var IChannel $channel */
            foreach ($this->channels as $channel) {
                // fill the previous data hash to prevent getting old data
                if (!empty($hashes[$channel->getName()])) {
                    $channel->setPreviousDataHash($hashes[$channel->getName()]);
                }

                // get the channel's data
                $data = $channel->getData($userId);

                // cleanup the previously loaded data
                if (!$data && !empty($hashes[$channel->getName()])) {
                    $response['data'][$channel->getName()] = null;
                    $response['data_hashes'][$channel->getName()] = null;
                    $isReturned = true;
                }

                if ($data) {
                    $isDataChanged = $channel->detectChanges($data);

                    if ($isDataChanged) {
                        $response['data'][$channel->getName()] = $data;
                        $response['data_hashes'][$channel->getName()] = $channel->getPreviousDataHash();

                        $isReturned = true;
                    }
                }
            }

            if ($isReturned) {
                return $response;
            }

            // sleep and then waiting for the changes
            sleep(self::DETECT_CHANGES_DELAY_SEC);
        }
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

        $controllers->post('/', function(Request $request) use ($app) {
            $vars = json_decode($request->getContent(), true);
            $userId = null;
            $hashes = !empty($vars['data_hashes']) && is_array($vars['data_hashes'])
                ? $vars['data_hashes']
                : [];

            // internal user authentication
            if (!empty($vars['token'])) {
                try {
                    $user = $app['security.jwt.encoder']->decode($vars['token']);
                }
                catch(\Exception $e) {
                    throw new UnauthorizedHttpException('jwt realm="access to api"');
                }

                // internal user authentication
                SKMOBILEAPP_BOL_Service::getInstance()->internalUserAuthenticate($user->id);
                $userId = $user->id;
            }

            return $app->json($this->checkUpdates($userId, $hashes));
        });

        return $controllers;
    }
}
