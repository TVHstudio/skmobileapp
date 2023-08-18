<?php

// credits
require_once dirname(__FILE__) . DS .  'classes' . DS . 'credits.php';
$credits = new SKMOBILEAPP_CLASS_Credits();
$credits->triggerCreditActionsAdd();

$pluginKey = 'skmobileapp';

$widgetService = BOL_ComponentAdminService::getInstance();

$widgetService->deleteWidget('SKADATE_CMP_MobileExperience');

$desktopWidget = $widgetService->addWidget('SKMOBILEAPP_CMP_MobileExperience', false);
$mobileWidget = $widgetService->addWidget('SKMOBILEAPP_MCMP_MobileExperience', false);

try {
    $widgetService->addWidgetToPosition(
        $widgetService->addWidgetToPlace($desktopWidget, BOL_ComponentAdminService::PLACE_INDEX),
        BOL_ComponentService::SECTION_RIGHT
    );
}
catch (Exception $e) {
    OW::getLogger('skmobileapp')->addEntry(
        json_encode($e),
        'activate.desktop_widget_mobile_experience_index'
    );
}

try {
    $widgetService->addWidgetToPosition(
        $widgetService->addWidgetToPlace($desktopWidget, BOL_ComponentAdminService::PLACE_DASHBOARD),
        BOL_ComponentService::SECTION_RIGHT
    );
}
catch (Exception $e) {
    OW::getLogger('skmobileapp')->addEntry(
        json_encode($e),
        'activate.desktop_widget_mobile_experience_dashboard'
    );
}

try {
    $widgetService->addWidgetToPosition(
        $widgetService->addWidgetToPlace($mobileWidget, BOL_MobileWidgetService::PLACE_MOBILE_INDEX),
        BOL_MobileWidgetService::SECTION_MOBILE_MAIN
    );
}
catch (Exception $e) {
    OW::getLogger('skmobileapp')->addEntry(
        json_encode($e),
        'activate.mobile_widget_mobile_experience_index'
    );
}

try {
    $widgetService->addWidgetToPosition(
        $widgetService->addWidgetToPlace($mobileWidget, BOL_MobileWidgetService::PLACE_MOBILE_DASHBOARD),
        BOL_MobileWidgetService::SECTION_MOBILE_MAIN
    );
}
catch (Exception $e) {
    OW::getLogger('skmobileapp')->addEntry(
        json_encode($e),
        'activate.mobile_widget_mobile_experience_dashboard'
    );
}

BOL_BillingService::getInstance()->activateGateway($pluginKey);