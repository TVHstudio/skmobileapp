<?php

/**
 * Copyright (c) 2021, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.com/
 * and is licensed under Oxwall Store Commercial License.
 *
 * Full text of this license can be found at http://developers.oxwall.com/store/oscl
 */

/**
 * Represents a Google Play subscription status change notification.
 */
class SKMOBILEAPP_BOL_GooglePlaySubscriptionNotification extends SKMOBILEAPP_BOL_AbstractWebhookNotification
{
    /**
     * @var string Notification version.
     */
    protected $version;

    /**
     * @var int Notification type, one of the `GOOGLE_PLAY_SUB_*` constants in `SKMOBILEAPP_BOL_WebhookService`.
     */
    protected $notificationType;

    /**
     * @var string Subscription purchase token, equals to the one that was provided when the subscription was purchased.
     */
    protected $purchaseToken;

    /**
     * @var string Subscription product ID, e.g. `membership_plan_1`.
     */
    protected $subscriptionId;

    /**
     * @inheritDoc
     */
    static public function fromJson($json)
    {
        $validNotificationTypes = [
            SKMOBILEAPP_BOL_WebhookService::GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_RECOVERED,
            SKMOBILEAPP_BOL_WebhookService::GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_RENEWED,
            SKMOBILEAPP_BOL_WebhookService::GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_CANCELED,
            SKMOBILEAPP_BOL_WebhookService::GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_PURCHASED,
            SKMOBILEAPP_BOL_WebhookService::GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_ON_HOLD,
            SKMOBILEAPP_BOL_WebhookService::GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_IN_GRACE_PERIOD,
            SKMOBILEAPP_BOL_WebhookService::GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_RESTARTED,
            SKMOBILEAPP_BOL_WebhookService::GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_PRICE_CHANGE_CONFIRMED,
            SKMOBILEAPP_BOL_WebhookService::GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_DEFERRED,
            SKMOBILEAPP_BOL_WebhookService::GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_PAUSED,
            SKMOBILEAPP_BOL_WebhookService::GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_PAUSE_SCHEDULE_CHANGED,
            SKMOBILEAPP_BOL_WebhookService::GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_REVOKED,
            SKMOBILEAPP_BOL_WebhookService::GOOGLE_PLAY_SUB_NOTIFICATION_TYPE_EXPIRED
        ];

        if (
            !isset($json['version']) ||
            (!isset($json['notificationType']) || empty($json['notificationType'])) ||
            (!isset($json['purchaseToken']) || empty($json['purchaseToken'])) ||
            (!isset($json['subscriptionId']) || empty($json['subscriptionId'])) ||
            !in_array($json['notificationType'], $validNotificationTypes)
        ) {
            throw new InvalidArgumentException('Invalid notification JSON');
        }

        return new self($json['version'], $json['notificationType'], $json['purchaseToken'], $json['subscriptionId']);
    }

    /**
     * Construct a new Google Play subscription notification.
     *
     * @param string $version Notification version.
     * @param int $notificationType Notification type, one of the `GOOGLE_PLAY_SUB_*` constants in
     *                              `SKMOBILEAPP_BOL_WebhookService`.
     * @param string $purchaseToken Subscription purchase token, equals to the one that was provided when the
     *                              subscription was purchased.
     * @param string $subscriptionId Subscription product ID, e.g. `membership_plan_1`.
     */
    public function __construct($version, $notificationType, $purchaseToken, $subscriptionId)
    {
        $this->version = $version;
        $this->notificationType = $notificationType;
        $this->purchaseToken = $purchaseToken;
        $this->subscriptionId = $subscriptionId;
    }

    /**
     * Get notification version.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Get notification type, one of the `GOOGLE_PLAY_SUB_*` constants in `SKMOBILEAPP_BOL_WebhookService`.
     *
     * @return int
     */
    public function getNotificationType()
    {
        return $this->notificationType;
    }

    /**
     * Subscription purchase token, equals to the one that was provided when the subscription was purchased.
     *
     * @return string
     */
    public function getPurchaseToken()
    {
        return $this->purchaseToken;
    }

    /**
     * Subscription product ID, e.g. `membership_plan_1`.
     *
     * @return string
     */
    public function getSubscriptionId()
    {
        return $this->subscriptionId;
    }
}