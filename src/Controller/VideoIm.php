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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use LogicException;
use OW_Event;
use OW;

use VIDEOIM_BOL_Notification;
use VIDEOIM_BOL_NotificationDao;
use VIDEOIM_BOL_VideoImService;
use VIDEOIM_CLASS_NotificationHandler;
use BOL_UserService;
use BOL_AuthorizationService;
use SKMOBILEAPP_BOL_PushService;

class VideoIm extends Base
{
    /**
     * Is plugin active
     *
     * @var bool
     */
    protected $isPluginActive = false;

    /**
     * VideoIm constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->isPluginActive = OW::getPluginManager()->isPluginActive('videoim');
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

        // sends notification
        $controllers->post('/notifications/', function (Request $request) use ($app) {
            $data = json_decode($request->getContent(), true);

            if ($this->isPluginActive) {
                try {
                    $interlocutorId = !empty($data['interlocutorId']) ? (int) $data['interlocutorId'] : -1;
                    $sessionId = !empty($data['sessionId']) ? (string) $data['sessionId'] : 'nosession';
                    $notification = !empty($data['notification']) ? $data['notification'] : null;

                    $result = VIDEOIM_CLASS_NotificationHandler::getInstance()
                        ->sendNotification($app['users']->getLoggedUserId(), $interlocutorId, $sessionId, $notification);

                    return $app->json($result);
                }
                catch (LogicException $e) {
                    return $app->json([
                        'success' => false,
                        'message' => $e->getMessage()
                    ]);
                }
            }

            throw new BadRequestHttpException('VideoIm plugin not activated');
        });

        // marks notification as accepted
        $controllers->put('/notifications/me/', function (Request $request) use ($app) {
            $data = json_decode($request->getContent(), true);

            if ($this->isPluginActive) {
                $userId = !empty($data['userId']) ? (int) $data['userId'] : -1;
                $sessionId = !empty($data['sessionId']) ? (string) $data['sessionId'] : 'nosession';

                VIDEOIM_BOL_VideoImService::getInstance()->markAcceptedNotifications($userId, $app['users']->getLoggedUserId(), $sessionId);

                return $app->json(); // ok
            }

            throw new BadRequestHttpException('VideoIM plugin is not activated');
        });

        // Marks the notifications as accepted. JSON structure:
        //
        // {
        //   "sessionId": string,     // Video IM session ID
        //   "notificationIds": int[] // Array of notification IDs to mark as received
        // }
        //
        // Returns status code 204 No Content on success, 400 Bad Request on error.
        $controllers->put('/notifications/received/', function (Request $request) use ($app) {
            if ($this->isPluginActive) {
                $data = json_decode($request->getContent(), true);

                $sessionId = isset($data['sessionId']) ? $data['sessionId'] : null;

                $notificationIds = isset($data['notificationIds']) && is_array($data['notificationIds'])
                                 ? $data['notificationIds']
                                 : null;

                if (!$sessionId || !$notificationIds || empty($notificationIds)) {
                    return $app->json([], 204);
                }

                VIDEOIM_BOL_VideoImService::getInstance()->markAcceptedNotificationsByIds(
                    $app['users']->getLoggedUserId(),
                    $sessionId,
                    $notificationIds
                );

                return $app->json([], 204);
            }

            throw new BadRequestHttpException('VideoIM plugin is not activated');
        });

        return $controllers;
    }
}
