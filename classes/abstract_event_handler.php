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
abstract class SKMOBILEAPP_CLASS_AbstractEventHandler
{
    /**
     * @var OW_Log
     */
    protected $logger;

    /**
     * Generic init
     */
    public function genericInit()
    {
        $this->logger = SKMOBILEAPP_BOL_Service::getLogger();

        $eventManager = OW::getEventManager();

        $eventManager->bind('notifications.collect_actions', array($this, 'onNotifyActions'));
        $eventManager->bind('mailbox.send_message', array($this, 'afterMailboxMessageSent'));
        $eventManager->bind(OW_EventManager::ON_PROCESS_LOG_ENTRY_MESSAGE, array($this, 'onProcessLogEntryMessage'));
        $eventManager->bind(OW_EventManager::ON_LOG_ENTRY_CUSTOM_VIEW, array($this, 'onLogEntryCustomView'));
        $eventManager->bind(OW_EventManager::ON_APPLICATION_INIT, array($this, 'checkApiUrl'));
        $eventManager->bind(OW_EventManager::ON_USER_UNREGISTER, array($this, 'onUserUnRegister'));

        // generate transaction finish redirect URLs for PWA billing
        $eventManager->bind(
            'billing.get_pwa_billing_transaction_finish_redirect_url',
            array($this, 'onPwaBillingTransactionFinishRedirectUrl')
        );

        // init auth labels
        $eventManager->bind('admin.add_auth_labels', array($this, 'addAuthLabels'));

        // init credits
        $eventManager->bind(OW_EventManager::ON_APPLICATION_INIT, array($this, 'afterInit'));
        $eventManager->bind('usercredits.on_action_collect', array($this, 'bindCreditActionsCollect'));
    }

    /**
     * Generate transaction finish redirect URLs for PWA billing.
     *
     * @param OW_Event $event
     *
     * @return void
     */
    public function onPwaBillingTransactionFinishRedirectUrl(OW_Event $event)
    {
        $params = $event->getParams();

        if (!isset($params['type']) || !isset($params['status'])) {
            $event->setData(OW_URL_HOME);

            return;
        }

        $query = http_build_query(
            [
                'payment_provider' => $params['type'],
                'transaction_status' => $params['status']
            ],
            null,
            '&',
            PHP_QUERY_RFC3986
        );

        $url = SKMOBILEAPP_BOL_Service::getInstance()->getPwaUrl() . '?' . $query;

        $event->setData($url);
    }

    /**
     * Add auth labels
     *
     * @param BASE_CLASS_EventCollector $event
     * @return void
     */
    public function addAuthLabels(BASE_CLASS_EventCollector $event)
    {
        $event->add(
            array(
                'skmobileapp' => array(
                    'label' => OW::getLanguage()->text('skmobileapp', 'auth_group_label'),
                    'actions' => array(
                        'tinder_filters' => OW::getLanguage()->text('skmobileapp', 'auth_action_label_tinder_filters'),
                    )
                )
            )
        );
    }

    /**
     * Bind credit actions collect
     *
     * @param BASE_CLASS_EventCollector $e
     * @return void
     */
    public function bindCreditActionsCollect( BASE_CLASS_EventCollector $e )
    {
        $credits = new SKMOBILEAPP_CLASS_Credits();
        $credits->bindCreditActionsCollect($e);
    }

    /**
     * After init
     *
     * @return void
     */
    public function afterInit()
    {
        // add user credits actions
        if ( !OW::getConfig()->getValue('skmobileapp', 'is_credits_initialized') )
        {
            if ( OW::getConfig()->configExists('skmobileapp', 'is_credits_initialized') )
            {
                OW::getConfig()->saveConfig('skmobileapp', 'is_credits_initialized', 1);
            }
            else
            {
                OW::getConfig()->addConfig('skmobileapp', 'is_credits_initialized', 1);
            }

            $credits = new SKMOBILEAPP_CLASS_Credits();
            $credits->triggerCreditActionsAdd();
        }
    }
 
    /**
     * On notify actions
     */
    public function onNotifyActions( BASE_CLASS_EventCollector $e )
    {
        $e->add(array(
            'section' => 'skmobileapp',
            'action' => 'skmobileapp-new_match_message',
            'sectionIcon' => 'ow_ic_mail',
            'sectionLabel' => OW::getLanguage()->text('skmobileapp', 'skmobileapp_email_notifications_section_label'),
            'description' => OW::getLanguage()->text('skmobileapp', 'skmobileapp_email_notifications_new_match_message'),
            'selected' => true
        ));
    }

    /**
     * On user un register
     *
     * @param OW_Event $e
     */
    public function onUserUnRegister( OW_Event $e )
    {
        $params = $e->getParams();

        SKMOBILEAPP_BOL_Service::getInstance()->deleteUserData($params['userId']);
    }

    /**
     * After mailbox message sent
     *
     * @param OW_Event $event
     * @return void
     */
    public function afterMailboxMessageSent( OW_Event $event )
    {
        $params = $event->getParams();
        $message = $event->getData();

        if ( !empty($params['isSystem']) )
        {
            return;
        }

        $userId = $params['recipientId'];
        $senderId = $params['senderId'];
        $conversationId = $params['conversationId'];

        $senderName = BOL_UserService::getInstance()->getDisplayName($senderId);
        $text = strip_tags($params['message']);
        $dataText = json_decode($params['message'], true);

        $isPushAllowed = (bool) BOL_PreferenceService::
                getInstance()->getPreferenceValue('skmobileapp_new_messages_push', $userId);

        if ( !is_array($dataText) && $isPushAllowed )
        {
            $processedMessage = SKMOBILEAPP_BOL_MailboxService::
                    getInstance()->getMessageData($userId, $conversationId, $message);

            // recipient cannot read the message
            if (!$processedMessage['isAuthorized']) {
                $text = trim(strip_tags(OW::getLanguage()->text('skmobileapp', 'conversation_new_message')));
            }

            $pushMessage = new SKMOBILEAPP_BOL_PushMessage;
            $pushMessage->setMessageType('message')
                ->setMessageParams([
                    'conversationId' => (int) $conversationId,
                    'senderId' => (int) $senderId
                ]);

            $pushMessage->sendNotification($userId, 'pn_new_message_title', 'pn_new_message', [
                'username' => $senderName,
                'message' => $text
            ]);
        }
    }

    /**
     * Check api url
     *
     * @return void
     */
    public function checkApiUrl()
    {
        try
        {
            $apiRoute = OW::getRouter()->getRoute('skmobileapp.api');

            if ( stristr($_SERVER['REQUEST_URI'], $apiRoute->getRoutePath()) )
            {
                OW::getRouter()->setUri($apiRoute->getRoutePath()); // redirect all actions to the index action
            }
        }
        catch(Exception $e)
        {}
    }

    /**
     * Process log entry.
     *
     * @param OW_Event $event
     *
     * @return void
     */
    public function onProcessLogEntryMessage( OW_Event $event )
    {
        $eventParams = $event->getParams();
        $entry = $eventParams['entry'] ?? null;

        if ( !$entry instanceof BOL_Log )
        {
            return;
        }

        $messageDecoded = json_decode($entry->getMessage(), true);

        if ( $messageDecoded === false || !isset($messageDecoded['message']) )
        {
            return;
        }

        $event->setData($messageDecoded['message']);
    }

    /**
     * Render custom view for an app log message.
     *
     * @param OW_Event $event
     * @return void
     */
    public function onLogEntryCustomView( OW_Event $event )
    {
        $eventParams = $event->getParams();
        $entry = $eventParams['entry'] ?? null;

        if (
            !$entry instanceof BOL_Log ||
            $entry->getType() !== 'skmobileapp' ||
            $entry->getKey() !== 'app'
        ) {
            return;
        }

        $appLogView = new SKMOBILEAPP_CMP_AppLogEntry($entry);
        $event->setData($appLogView->render());
    }
}
