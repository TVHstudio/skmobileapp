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

$pluginKey = 'skmobileapp';

// add permissions
$authorization = OW::getAuthorization();
$authorization->addGroup($pluginKey);
$authorization->addAction($pluginKey, 'tinder_filters');

$config = OW::getConfig();

OW::getPluginManager()->addPluginSettingsRouteName($pluginKey, $pluginKey . '_admin_ads');

$defaultConfigs = array(
    'import_location_last_user_id' => 0,
    'android_ad_unit_id' => '',
    'ios_ad_unit_id' => '',
    'ads_enabled' => false,
    'pn_enabled' => true,
    'inapps_enable' => true,
    'inapps_itunes_shared_secret' => '',
    'inapps_ios_test_mode' => false,
    'inapps_show_membership_actions' => 'app_only', // app_only | all
    'ios_app_url' => 'https://itunes.apple.com/in/app/date-finder-app/id1263891062?mt=8',
    'android_app_url' => 'https://play.google.com/store/apps/details?id=com.skmobile&hl=en',
    'search_mode' => 'both',
    'inapps_apm_package_name' => '',
    'service_account_auth_expiration_time' => '',
    'service_account_auth_token' => '',
    'google_map_api_key' => '',
    'admob_pages' => '[]',
    'theme_logo' => '',
    'theme_logo_width' => 102,
    'theme_background' => '',
);

foreach ($defaultConfigs as $key => $value)
{
    if ( !$config->configExists($pluginKey, $key) )
    {
        $config->addConfig($pluginKey, $key, $value);
    }
}

$sql = "CREATE TABLE IF NOT EXISTS `" . OW_DB_PREFIX . $pluginKey . "_device` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `userId` int(11) UNSIGNED NOT NULL,
    `deviceUuid` varchar(255) NOT NULL,
    `token` varchar(255) NOT NULL,
    `platform` varchar(10) NOT NULL,
    `activityTime` int(11) UNSIGNED DEFAULT NULL,
    `language` varchar(10) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `userId` (`userId`),
    KEY `activityTime` (`activityTime`),
    UNIQUE KEY `token` (`token`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

OW::getDbo()->query($sql);

// create db tables
$sql = "CREATE TABLE IF NOT EXISTS `" . OW_DB_PREFIX . $pluginKey . "_user_match_action` (
    `id` int(11) NOT NULL auto_increment,
    `userId` int(11) NOT NULL,
    `recipientId` int(11) NOT NULL,
    `type` varchar(20) NOT NULL,
    `createStamp` int(11) NOT NULL,
    `expirationStamp` int(11) NOT NULL,
    `mutual` tinyint(1) NOT NULL DEFAULT 0,
    `read` tinyint(1) NOT NULL DEFAULT 0,
    `new` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY  (`id`),
    UNIQUE KEY `userMatch` (`userId`, `recipientId`),
    KEY `expiration` (`userId`, `recipientId`, `type`, `expirationStamp`),
    KEY `mutual` (`userId`, `type`, `mutual`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

OW::getDbo()->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS `".OW_DB_PREFIX . $pluginKey ."_user_location` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `userId` int(11) UNSIGNED NOT NULL,
    `latitude` DECIMAL( 15, 4 ) NOT NULL,
    `longitude` DECIMAL( 15, 4 ) NOT NULL,
    `northEastLatitude` DECIMAL( 15, 4 ) NOT NULL,
    `northEastLongitude` DECIMAL( 15, 4 ) NOT NULL,
    `southWestLatitude` DECIMAL( 15, 4 ) NOT NULL,
    `southWestLongitude` DECIMAL( 15, 4 ) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `userId` (`userId`),
    KEY `userLocation` (`userId`, `southWestLatitude`, `northEastLatitude`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";

OW::getDbo()->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS `" . OW_DB_PREFIX . $pluginKey . "_recurring_membership_plan`(
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `userId` INT(11) UNSIGNED NOT NULL COMMENT 'ID of the user this membership plan was purchased by.',
    `membershipUserId` INT(11) UNSIGNED NOT NULL COMMENT 'Membership-to-user relation ID.',
    `billingSaleId` INT(11) UNSIGNED NOT NULL COMMENT 'ID of the sale this membership was purchased with.',
    `platform` ENUM('android', 'ios') NOT NULL COMMENT 'Platform this membership plan was purchased on.',
    PRIMARY KEY(`id`),
    INDEX `idx_membership_user_id`(`membershipUserId`),
    INDEX `idx_user_id`(`userId`)
) ENGINE = MyISAM DEFAULT CHARSET = utf8;";

OW::getDbo()->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS `" . OW_DB_PREFIX . $pluginKey . "_expiring_membership_plan`(
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `userId` INT(11) UNSIGNED NOT NULL COMMENT 'ID of the user this membership plan was purchased by.',
    `membershipUserId` INT(11) UNSIGNED NOT NULL COMMENT 'Membership-to-user relation ID.',
    `expirationTimestamp` BIGINT NOT NULL COMMENT 'Membership expiration timestamp, seconds.',
    `retriesCount` INT(11) UNSIGNED NOT NULL COMMENT 'Number of renewal retries attempted.',
    PRIMARY KEY(`id`),
    INDEX `idx_membership_user_id`(`membershipUserId`),
    INDEX `idx_user_id`(`userId`)
) ENGINE = MyISAM DEFAULT CHARSET = utf8;";

OW::getDbo()->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS `" . OW_DB_PREFIX . $pluginKey . "_app_store_receipt_data`(
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `billingSaleId` INT(11) UNSIGNED NOT NULL COMMENT 'ID of the billing plugin purchase this receipt is related to.',
    `encodedReceipt` TEXT NOT NULL COMMENT 'Base64-encoded receipt content.',
    PRIMARY KEY(`id`),
    INDEX `idx_billing_sale_id`(`billingSaleId`)
) ENGINE = MyISAM DEFAULT CHARSET = utf8;";

OW::getDbo()->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS `" . OW_DB_PREFIX . $pluginKey . "_google_play_purchase_token_data`(
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `billingSaleId` INT(11) UNSIGNED NOT NULL COMMENT 'ID of the billing plugin purchase these tokens are related to.',
    `purchaseToken` VARCHAR(240) NOT NULL COMMENT 'Token representing the entitlement of a user to some in-app product, used for purchase verification. These tokens are globally unique and can be used as identifiers',
    `linkedPurchaseToken` VARCHAR(240) COMMENT 'Previous subscription purchase token used to identify resubscription.',
    PRIMARY KEY(`id`),
    INDEX `idx_billing_sale_id`(`billingSaleId`),
    INDEX `idx_purchase_token`(`purchaseToken`)
) ENGINE = MyISAM DEFAULT CHARSET = utf8;";

OW::getDbo()->query($sql);

$billingService = BOL_BillingService::getInstance();

$gateway = new BOL_BillingGateway();
$gateway->gatewayKey = $pluginKey;
$gateway->adapterClassName = 'SKMOBILEAPP_CLASS_InAppPurchaseAdapter';
$gateway->active = 0;
$gateway->mobile = 1;
$gateway->recurring = 1;
$gateway->dynamic = 0;
$gateway->hidden = 1;
$gateway->currencies = 'AUD,CAD,EUR,GBP,JPY,USD';

$billingService->addGateway($gateway);

// user preferences
try {
    $sectionName = $pluginKey . '_pushes';
    $preferenceSection = new BOL_PreferenceSection();
    $preferenceSection->name = $sectionName;
    $preferenceSection->sortOrder = -1;
    BOL_PreferenceService::getInstance()->savePreferenceSection($preferenceSection);

    $preference = new BOL_Preference();
    $preference->key = $pluginKey . '_new_matches_push';
    $preference->sectionName = $sectionName;
    $preference->defaultValue = 'true';
    $preference->sortOrder = 1;
    BOL_PreferenceService::getInstance()->savePreference($preference);

    $preference = new BOL_Preference();
    $preference->key = $pluginKey . '_new_messages_push';
    $preference->sectionName = $sectionName;
    $preference->defaultValue = 'true';
    $preference->sortOrder = 2;
    BOL_PreferenceService::getInstance()->savePreference($preference);
}
catch (Exception $e)
{
    OW::getLogger('skmobileapp')->addEntry($e->getMessage(), 'install');
}

// import languages
$plugin = OW::getPluginManager()->getPlugin($pluginKey);
OW::getLanguage()->importLangsFromDir($plugin->getRootDir() . 'langs');
