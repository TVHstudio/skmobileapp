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

$pluginKey = 'skmobileapp';
$config = Updater::getConfigService();

if ( $config->configExists($pluginKey, 'inapps_apm_key') )
{
    $config->deleteConfig($pluginKey, 'inapps_apm_key');
}

$langService = Updater::getLanguageService();

$langService->deleteLangKey($pluginKey, 'inapps_apm_key_label');
$langService->deleteLangKey($pluginKey, 'inapps_apm_key_desc');