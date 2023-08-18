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

$sql = [
    "ALTER TABLE `" . OW_DB_PREFIX . "base_billing_sale` CHANGE COLUMN `hash` `hash` VARCHAR(64) NULL DEFAULT NULL;",
    "ALTER TABLE `" . OW_DB_PREFIX . "base_log` CHANGE COLUMN `message` `message` LONGTEXT NOT NULL;"
];

foreach ( $sql as $query )
{
    try
    {
        Updater::getDbo()->query($query);
    }
    catch ( Exception $e )
    {
        Updater::getLogger()->addEntry(json_encode($e));
    }
}