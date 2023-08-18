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

use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class SKMOBILEAPP_BOL_PushMessage
{
    /**
     * Message params
     * 
     * @var array
     */
    protected $messageParams = [];

    /**
     * Language prefix
     * 
     * @var string
     */
    protected $languagePrefix = 'skmobileapp';

    /**
     * Message type
     * 
     * @var string
     */
    protected $messageType;

    /**
     * Sound name
     * 
     * @var string
     */
    protected $soundName; // e.g match.wav

    /**
     * Life time in hours
     * 
     * @var integer
     */
    protected $lifeTimeInHours = 1;

    /**
     * Set message params
     * 
     * @return SKMOBILEAPP_BOL_PushMessage
     */
    public function setMessageParams($params)
    {
        $this->messageParams = $params;

        return $this;
    }

    /**
     * Set language prefix
     * 
     * @return SKMOBILEAPP_BOL_PushMessage
     */
    public function setLanguagePrefix($languagePrefix)
    {
        $this->languagePrefix = $languagePrefix;

        return $this;
    }

    /**
     * Set message type
     * 
     * @return SKMOBILEAPP_BOL_PushMessage
     */
    public function setMessageType($type)
    {
        $this->messageType = $type;

        return $this;
    }

    /**
     * Set sound name
     * 
     * @return SKMOBILEAPP_BOL_PushMessage
     */
    public function setSoundName($soundName)
    {
        $this->soundName = $soundName;

        return $this;
    }

    /**
     * Set life time in hours
     * 
     * @return SKMOBILEAPP_BOL_PushMessage
     */
    public function setLifeTimeInHours($lifeTime)
    {
        $this->lifeTimeInHours = $lifeTime;

        return $this;
    }

    /**
     * Send notification
     * 
     * @param integer $recipientId
     * @param string $titleLangKey
     * @param string $messageLangKey
     * @param array $langVars
     * @return void
     */
    public function sendNotification($recipientId, $titleLangKey, $messageLangKey, $langVars = []) 
    {
        // push notifications are disabled
        if ( !OW::getConfig()->getValue('skmobileapp', 'pn_enabled') )
        {
            return;
        }

        // get all registered recipient's devices
        $devices = SKMOBILEAPP_BOL_DeviceService::getInstance()->findByUserId($recipientId);

        if ( empty($devices) )
        {
            return;
        }

        $languageService = BOL_LanguageService::getInstance();

        // send notification to all registered devices
        foreach ( $devices as $device ) {
            $messageLanguage = $languageService->findByTag($device->language);

            if (!$messageLanguage) {
                $messageLanguage = $languageService->getCurrent();
            }

            $languageService->setCurrentLanguage($messageLanguage);

            // translate the message
            $title = $languageService->getText(
                $messageLanguage->getId(),
                $this->languagePrefix,
                $titleLangKey,
                $langVars
            );

            $message = $languageService->getText(
                $messageLanguage->getId(),
                $this->languagePrefix,
                $messageLangKey,
                $langVars
            );

            // common payload
            $payload = [
                'uuid' => uniqid(),
                'type' => $this->messageType
            ] + $this->messageParams;

            $this->sendToFirebase($device->token, $title, $message, $payload);
        }
    }

    /**
     * Send push message to Firebase
     * 
     * @param string $token
     * @param string $notificationTitle
     * @param string $notificationMessage 
     * @param array $payload
     *
     * @return void
     */
    protected function sendToFirebase($token, $notificationTitle, $notificationMessage, $payload)
    {
        /** @var Messaging $firebaseMessaging */
        $firebaseMessaging = SKMOBILEAPP_BOL_Service::getInstance()->getFirebaseMessaging();
        $logger = SKMOBILEAPP_BOL_Service::getLogger();

        if (!$firebaseMessaging) {
            $logger->addEntry(
                "Can't get Firebase Cloud Messaging instance. Make sure that the firebase_auth plugin is installed.",
                'firebase_messaging'
            );

            $logger->writeLog();

            return;
        }

        if (!$firebaseMessaging->validateRegistrationTokens($token)) {
            $logger->addEntry(
                "Won't send a message using an invalid device token.",
                'firebase_messaging'
            );

            $logger->writeLog();

            return;
        }

        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(Notification::create($notificationTitle, $notificationMessage))
            ->withData($payload);

        if ($this->soundName) {
            $soundFileName = pathinfo($this->soundName, PATHINFO_FILENAME);

            $message = $message->withAndroidConfig(AndroidConfig::new()->withSound($soundFileName))
                               ->withApnsConfig(ApnsConfig::new()->withSound($soundFileName));
        } else {
            $message = $message->withDefaultSounds();
        }

        try {
            $firebaseMessaging->validate($message);
            $firebaseMessaging->send($message);
        } catch (MessagingException $exception) {
            $logger->addEntry(
                "Can't send push message: \"" . $exception->getMessage() . "\". Details:\n\n" . print_r($exception->errors(), true),
                'firebase_messaging'
            );

            $logger->writeLog();

            return;
        }
    }
}
