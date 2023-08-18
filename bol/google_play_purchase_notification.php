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
 * Represents a Google Play purchase state change notification.
 */
class SKMOBILEAPP_BOL_GooglePlayPurchaseNotification extends SKMOBILEAPP_BOL_AbstractWebhookNotification
{
    /**
     * @var string Notification version.
     */
    protected $version;

    /**
     * @var int Notification type, one of the `GOOGLE_PLAY_PRODUCT_*` constants in `SKMOBILEAPP_BOL_WebhookService`.
     */
    protected $notificationType;

    /**
     * @var string Purchase token equal to the one that was received when the purchase was made.
     */
    protected $purchaseToken;

    /**
     * @var string Purchased product ID, e.g. `user_credit_pack_1`.
     */
    protected $productId;

    /**
     * @inheritDoc
     */
    static public function fromJson($json)
    {
        $validNotificationTypes = [
            SKMOBILEAPP_BOL_WebhookService::GOOGLE_PLAY_PRODUCT_NOTIFICATION_TYPE_PURCHASED,
            SKMOBILEAPP_BOL_WebhookService::GOOGLE_PLAY_PRODUCT_NOTIFICATION_TYPE_CANCELED,
        ];

        if (
            !isset($json['version']) ||
            (!isset($json['notificationType']) || empty($json['notificationType'])) ||
            (!isset($json['purchaseToken']) || empty($json['purchaseToken'])) ||
            (!isset($json['sku']) || empty($json['sku'])) ||
            !in_array($json['notificationType'], $validNotificationTypes)
        ) {
            throw new InvalidArgumentException('Invalid notification JSON');
        }

        return new self($json['version'], $json['notificationType'], $json['purchaseToken'], $json['sku']);
    }

    /**
     * SKMOBILEAPP_BOL_GooglePlayPurchaseNotification constructor.
     *
     * @param string $version Notification version.
     * @param int $notificationType Notification type, one of the `GOOGLE_PLAY_PRODUCT_*` constants in
     *                              `SKMOBILEAPP_BOL_WebhookService`.
     * @param string $purchaseToken Purchase token equal to the one that was received when the purchase was made.
     * @param string $productId Purchased product ID, e.g. `user_credit_pack_1`.
     */
    public function __construct($version, $notificationType, $purchaseToken, $productId)
    {
        $this->version = $version;
        $this->notificationType = $notificationType;
        $this->purchaseToken = $purchaseToken;
        $this->productId = $productId;
    }

    /**
     * Get notification version.
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Get notification type, one of the `GOOGLE_PLAY_PRODUCT_*` constants in `SKMOBILEAPP_BOL_WebhookService`.
     *
     * @return int
     */
    public function getNotificationType(): int
    {
        return $this->notificationType;
    }

    /**
     * Get purchase token, equal to the one that was received when the purchase was made.
     *
     * @return string
     */
    public function getPurchaseToken(): string
    {
        return $this->purchaseToken;
    }

    /**
     * Get purchased product ID, e.g. `user_credit_pack_1`.
     *
     * @return string
     */
    public function getProductId(): string
    {
        return $this->productId;
    }
}