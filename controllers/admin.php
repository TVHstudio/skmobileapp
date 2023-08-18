<?php

/**
 * Copyright (c) 2016, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for exclusive use with SkaDate Dating Software (http://www.skadate.com)
 * and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */

class SKMOBILEAPP_CTRL_Admin extends ADMIN_CTRL_Abstract
{
    /**
     * @var SKMOBILEAPP_BOL_Service
     */
    protected $service;

    /**
     * Init 
     */
    public function init()
    {
        parent::init();

        $this->service = SKMOBILEAPP_BOL_Service::getInstance();

        $handler = OW::getRequestHandler()->getHandlerAttributes();
        $menus = array();

        $ads = new BASE_MenuItem();
        $ads->setLabel(OW::getLanguage()->text('skmobileapp', 'menu_ads_label'));
        $ads->setUrl(OW::getRouter()->urlForRoute('skmobileapp_admin_ads'));
        $ads->setActive($handler[OW_RequestHandler::ATTRS_KEY_ACTION] === 'ads');
        $ads->setKey('ads');
        $ads->setIconClass('ow_ic_app');
        $ads->setOrder(0);
        $menus[] = $ads;

        $push = new BASE_MenuItem();
        $push->setLabel(OW::getLanguage()->text('skmobileapp', 'menu_push_label'));
        $push->setUrl(OW::getRouter()->urlForRoute('skmobileapp_admin_push'));
        $push->setActive($handler[OW_RequestHandler::ATTRS_KEY_ACTION] === 'push');
        $push->setKey('push');
        $push->setIconClass('ow_ic_chat');
        $push->setOrder(1);
        $menus[] = $push;

        $inapps = new BASE_MenuItem();
        $inapps->setLabel(OW::getLanguage()->text('skmobileapp', 'menu_inapps_label'));
        $inapps->setUrl(OW::getRouter()->urlForRoute('skmobileapp_admin_inapps'));
        $inapps->setActive($handler[OW_RequestHandler::ATTRS_KEY_ACTION] === 'inapps');
        $inapps->setKey('inapps');
        $inapps->setIconClass('ow_ic_cart');
        $inapps->setOrder(2);
        $menus[] = $inapps;

        $settings = new BASE_MenuItem();
        $settings->setLabel(OW::getLanguage()->text('skmobileapp', 'menu_settings_label'));
        $settings->setUrl(OW::getRouter()->urlForRoute('skmobileapp_admin_settings'));
        $settings->setActive($handler[OW_RequestHandler::ATTRS_KEY_ACTION] === 'settings');
        $settings->setKey('settings');
        $settings->setIconClass('ow_ic_script');
        $settings->setOrder(3);
        $menus[] = $settings;

        $settings = new BASE_MenuItem();
        $settings->setLabel(OW::getLanguage()->text('skmobileapp', 'menu_theme_settings_label'));
        $settings->setUrl(OW::getRouter()->urlForRoute('skmobileapp_admin_theme'));
        $settings->setActive($handler[OW_RequestHandler::ATTRS_KEY_ACTION] === 'theme');
        $settings->setKey('theme');
        $settings->setIconClass('ow_ic_picture');
        $settings->setOrder(4);
        $menus[] = $settings;

        $this->addComponent('menu', new BASE_CMP_ContentMenu($menus));

        // add admin.css
        OW::getDocument()->addStyleSheet(OW::getPluginManager()->getPlugin('skmobileapp')->getStaticCssUrl() . 'admin.css');
    }

    /**
     * Theme settings
     */
    public function theme()
    {
        if ( !OW::getRequest()->isAjax() )
        {
            OW::getDocument()->setHeading(OW::getLanguage()->text('skmobileapp', 'admin_theme_settings'));
        }

        $form = new Form('skmobileapp_theme');
        $form->setEnctype(Form::ENCTYPE_MULTYPART_FORMDATA);

        $this->assign(
            'baseThemeFilesUrl',
            SKMOBILEAPP_BOL_Service::getInstance()->getBaseThemeFileUrl()
        );
        $this->assign(
            'logoFileName',
            OW::getConfig()->getValue('skmobileapp', 'theme_logo')
        );

        $logo = new FileField('logo');
        $logo->addValidator(
            new SKMOBILEAPP_CLASS_ImageValidator('logo', '#logo')
        );
        $logo->setLabel(OW::getLanguage()->text('skmobileapp', 'theme_settings_logo_label'));
        $logo->setDescription(OW::getLanguage()->text('skmobileapp', 'theme_settings_logo_desc'));
        $logo->setValue(OW::getConfig()->getValue('skmobileapp', 'theme_settings_logo'));
        $form->addElement($logo);

        $logoWidth = new TextField('logo_width');
        $logoWidth->setLabel(OW::getLanguage()->text('skmobileapp', 'theme_settings_logo_width_label'));
        $logoWidth->setDescription(OW::getLanguage()->text('skmobileapp', 'theme_settings_logo_width_desc'));
        $logoWidth->setValue(OW::getConfig()->getValue('skmobileapp', 'theme_logo_width'));
        $logoWidth->setRequired(true);
        $logoWidth->addValidator(new IntValidator(1));
        $form->addElement($logoWidth);

        $this->assign(
            'backgroundFileName',
            OW::getConfig()->getValue('skmobileapp', 'theme_background')
        );

        $background = new FileField('background');
        $background->addValidator(
            new SKMOBILEAPP_CLASS_ImageValidator('background', '#background')
        );
        $background->setLabel(OW::getLanguage()->text('skmobileapp', 'theme_settings_background_label'));
        $background->setDescription(OW::getLanguage()->text('skmobileapp', 'theme_settings_background_desc'));
        $background->setValue(OW::getConfig()->getValue('skmobileapp', 'theme_settings_background'));
        $form->addElement($background);

        $submit = new Submit('theme_submit');
        $submit->setValue(OW::getLanguage()->text('skmobileapp', 'theme_submit'));
        $form->addElement($submit);

        if ( OW::getRequest()->isPost() && $form->isValid(array_merge($_POST, $_FILES)) )
        {
            $this->_processThemeFile(
                'theme_logo',
                'logo',
                !empty($_POST['delete_logo']),
                $_FILES[$logo->getName()]
            );
            $this->_processThemeFile(
                'theme_background',
                'background',
                !empty($_POST['delete_background']),
                $_FILES[$background->getName()]
            );
            OW::getConfig()->saveConfig('skmobileapp', 'theme_logo_width', $form->getElement('logo_width')->getValue());

            OW::getFeedback()->info(OW::getLanguage()->text('skmobileapp', 'settings_saved'));

            $this->redirect();
        }

        $this->addForm($form);
    }

    /**
     * General settings
     */
    public function settings()
    {
        if ( !OW::getRequest()->isAjax() )
        {
            OW::getDocument()->setHeading(OW::getLanguage()->text('skmobileapp', 'admin_settings'));
        }

        $form = new Form('skmobileapp_settings');

        $iosAppUrl = new TextField('ios_app_url');
        $iosAppUrl->setValue(OW::getConfig()->getValue('skmobileapp', 'ios_app_url'));
        $iosAppUrl->setLabel(OW::getLanguage()->text('skmobileapp', 'ios_app_url_label'));
        $iosAppUrl->setDescription(OW::getLanguage()->text('skmobileapp', 'default_app_url_desc'));
        $form->addElement($iosAppUrl);

        $androidAppUrl = new TextField('android_app_url');
        $androidAppUrl->setValue(OW::getConfig()->getValue('skmobileapp', 'android_app_url'));
        $androidAppUrl->setLabel(OW::getLanguage()->text('skmobileapp', 'android_app_url_label'));
        $androidAppUrl->setDescription(OW::getLanguage()->text('skmobileapp', 'default_app_url_desc'));
        $form->addElement($androidAppUrl);

        $searchMode = new RadioField('search_mode');
        $searchMode->setLabel(OW::getLanguage()->text('skmobileapp', 'search_mode_label'));
        $searchMode->setOptions(array(
            'both' => OW::getLanguage()->text('skmobileapp', 'search_mode_both'),
            'tinder' => OW::getLanguage()->text('skmobileapp', 'search_mode_tinder'),
            'browse' => OW::getLanguage()->text('skmobileapp', 'search_mode_browse')
        ));
        $searchMode->setValue(OW::getConfig()->getValue('skmobileapp', 'search_mode'));
        $form->addElement($searchMode);

        $googleMapApiKey = new TextField('google_map_api_key');
        $googleMapApiKey->setValue(OW::getConfig()->getValue('skmobileapp', 'google_map_api_key'));
        $googleMapApiKey->setLabel(OW::getLanguage()->text('skmobileapp', 'google_map_api_key_label'));
        $googleMapApiKey->setDescription(OW::getLanguage()->text('skmobileapp', 'google_map_api_key_desc'));
        $googleMapApiKey->setRequired(true);
        $form->addElement($googleMapApiKey);

        $submit = new Submit('settings_submit');
        $submit->setValue(OW::getLanguage()->text('skmobileapp', 'settings_submit'));
        $form->addElement($submit);

        $this->addForm($form);

        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            OW::getConfig()->saveConfig('skmobileapp', 'ios_app_url', $form->getElement('ios_app_url')->getValue());
            OW::getConfig()->saveConfig('skmobileapp', 'android_app_url', $form->getElement('android_app_url')->getValue());
            OW::getConfig()->saveConfig('skmobileapp', 'search_mode', $form->getElement('search_mode')->getValue());
            OW::getConfig()->saveConfig('skmobileapp', 'google_map_api_key', $form->getElement('google_map_api_key')->getValue());

            OW::getFeedback()->info(OW::getLanguage()->text('skmobileapp', 'settings_saved'));

            $this->redirect();
        }
    }

    /**
     * Ads settings
     */
    public function ads( array $params )
    {
        if ( !OW::getRequest()->isAjax() )
        {
            OW::getDocument()->setHeading(OW::getLanguage()->text('skmobileapp', 'admin_settings'));
        }

        $form = new Form('skmobileapp_ads');

        $androidAdUnitKey = new TextField('android_ad_unit_id');
        $androidAdUnitKey->setRequired();
        $androidAdUnitKey->setValue(OW::getConfig()->getValue('skmobileapp', 'android_ad_unit_id'));
        $androidAdUnitKey->setLabel(OW::getLanguage()->text('skmobileapp', 'android_ad_unit_id_label'));
        $androidAdUnitKey->setDescription(
            OW::getLanguage()->text('skmobileapp', 'android_ad_unit_id_desc')
        );

        $form->addElement($androidAdUnitKey);

        $iosAdUnitKey = new TextField('ios_ad_unit_id');
        $iosAdUnitKey->setRequired();
        $iosAdUnitKey->setValue(OW::getConfig()->getValue('skmobileapp', 'ios_ad_unit_id'));
        $iosAdUnitKey->setLabel(OW::getLanguage()->text('skmobileapp', 'ios_ad_unit_id_label'));
        $iosAdUnitKey->setDescription(OW::getLanguage()->text('skmobileapp', 'ios_ad_unit_id_desc'));

        $form->addElement($iosAdUnitKey);

        $enabled = new CheckboxField('ads_enabled');
        $enabled->setValue(OW::getConfig()->getValue('skmobileapp', 'ads_enabled'));
        $enabled->setLabel(OW::getLanguage()->text('skmobileapp', 'ads_enabled_label'));

        $form->addElement($enabled);

        $submit = new Submit('ads_submit');
        $submit->setValue(OW::getLanguage()->text('skmobileapp', 'ads_submit'));

        $form->addElement($submit);

        // get label translations
        $enableAllLabel = OW::getLanguage()->text('skmobileapp', 'admob_enable_all_label');
        $disableAllLabel = OW::getLanguage()->text('skmobileapp', 'admob_disable_all_label');

        // retrieve admob pages
        $admobPages = $this->service->collectAdmobPages();

        // count enabled pages to determine the initial "disable all" link state
        $enabledPagesCount = count(array_filter(array_values($admobPages), function ($value) {
            return $value['adsEnabled'] === true;
        }));

        // translate page names
        $translatedPages = array_reduce(array_keys($admobPages), function ($prev, $pageId) use ($admobPages) {
            $pageData = $admobPages[$pageId];

            return array_merge($prev, [
                $pageId => array_merge($pageData, [
                    'translatedPageName' => OW::getLanguage()->text($pageData['pluginKey'], $pageData['langKey'])
                ])
            ]);
        }, []);

        $this->assign('disableAllLinkState', $enabledPagesCount > 0 ? 'false' : 'true');
        $this->assign('disableAllLinkLabel', $enabledPagesCount > 0 ? $disableAllLabel : $enableAllLabel);
        $this->assign('disableAllLabel', $disableAllLabel);
        $this->assign('enableAllLabel', $enableAllLabel);
        $this->assign('admobPages', $translatedPages);

        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            $submittedAdmobPages = $_POST['admobPages'];

            $this->service->updateAdmobPages(array_reduce(array_keys($admobPages), function ($prev, $pageId) use (
                $submittedAdmobPages
            ) {
                return array_merge($prev, [
                    $pageId => [
                        'adsEnabled' => isset($submittedAdmobPages[$pageId])
                    ]
                ]);
            }, []));

            OW::getConfig()->saveConfig('skmobileapp', 'android_ad_unit_id', $form->getElement('android_ad_unit_id')->getValue());
            OW::getConfig()->saveConfig('skmobileapp', 'ios_ad_unit_id', $form->getElement('ios_ad_unit_id')->getValue());
            OW::getConfig()->saveConfig('skmobileapp', 'ads_enabled', $form->getElement('ads_enabled')->getValue());
            OW::getFeedback()->info(OW::getLanguage()->text('skmobileapp', 'settings_saved'));

            $this->redirect();
        }

        $this->addForm($form);
    }

    /**
     * Inaps settings
     */
    public function inapps( array $params )
    {
        if ( !OW::getRequest()->isAjax() )
        {
            OW::getDocument()->setHeading(OW::getLanguage()->text('skmobileapp', 'admin_settings'));
        }

        if (file_exists(SKMOBILEAPP_BOL_Service::ANDROID_PUBLISHER_KEY_PATH)) {
            $privateKeyJson = json_decode(
                file_get_contents(SKMOBILEAPP_BOL_Service::ANDROID_PUBLISHER_KEY_PATH),
                true
            );

            if (isset($privateKeyJson['client_email']) && isset($privateKeyJson['private_key'])) {
                $this->assign('androidPrivateKeySet', true);
                $this->assign('androidPrivateKeyClientEmail', $privateKeyJson['client_email']);
            }
        }

        $form = new Form('skmobileapp_inapps');
        $form->setEnctype(Form::ENCTYPE_MULTYPART_FORMDATA);

        $inappsEnabled = new CheckboxField('inapps_enable');
        $inappsEnabled->setValue(OW::getConfig()->getValue('skmobileapp', 'inapps_enable'));
        $inappsEnabled->setLabel(OW::getLanguage()->text('skmobileapp', 'inapps_enable'));

        $form->addElement($inappsEnabled);

        $showMembershipActions = new Selectbox('inapps_show_membership_actions');
        $showMembershipActions->setRequired();
        $showMembershipActions->setLabel(OW::getLanguage()->text('skmobileapp', 'inapps_show_membership_actions'));
        $showMembershipActions->setDescription(OW::getLanguage()->text('skmobileapp', 'inapps_show_membership_actions_desc'));
        $showMembershipActions->setValue(OW::getConfig()->getValue('skmobileapp', 'inapps_show_membership_actions'));

        $showMembershipActions->setOptions([
            SKMOBILEAPP_BOL_PaymentsService::APP_ONLY_MEMBERSHIP_ACTIONS => OW::getLanguage()->text('skmobileapp', 'inapps_app_only_membership_actions'),
            SKMOBILEAPP_BOL_PaymentsService::ALL_MEMBERSHIP_ACTIONS => OW::getLanguage()->text('skmobileapp', 'inapps_all_membership_actions'),
        ]);

        $form->addElement($showMembershipActions);

        $promoPackageName = new TextField('inapps_apm_package_name');
        $promoPackageName->setLabel(OW::getLanguage()->text('skmobileapp', 'inapps_apm_package_name_label'));
        $promoPackageName->setDescription(OW::getLanguage()->text('skmobileapp', 'inapps_apm_package_name_desc'));
        $promoPackageName->setValue(OW::getConfig()->getValue('skmobileapp', 'inapps_apm_package_name'));
        $form->addElement($promoPackageName);

        $androidAccountKey = new FileField('inapps_apm_android_account_key');
        $androidAccountKey->addValidator(
            new SKMOBILEAPP_CLASS_AndroidAccountKeyValidator('inapps_apm_android_account_key', '#android_private_key')
        );
        $androidAccountKey->setLabel(OW::getLanguage()->text('skmobileapp', 'inapps_apm_android_account_key_label'));
        $androidAccountKey->setDescription(OW::getLanguage()->text('skmobileapp', 'inapps_apm_android_account_key_desc'));
        $androidAccountKey->setValue(OW::getConfig()->getValue('skmobileapp', 'inapps_apm_android_private_key'));
        $form->addElement($androidAccountKey);

        $secret = new TextField('inapps_itunes_shared_secret');
        $secret->setValue(OW::getConfig()->getValue('skmobileapp', 'inapps_itunes_shared_secret'));
        $secret->setLabel(OW::getLanguage()->text('skmobileapp', 'inapps_itunes_shared_secret_label'));
        $secret->setDescription(OW::getLanguage()->text('skmobileapp', 'inapps_itunes_shared_secret_desc'));
        $form->addElement($secret);

        $enabled = new CheckboxField('inapps_ios_test_mode');
        $enabled->setValue(OW::getConfig()->getValue('skmobileapp', 'inapps_ios_test_mode'));
        $enabled->setLabel(OW::getLanguage()->text('skmobileapp', 'inapps_ios_test_mode_label'));

        $form->addElement($enabled);

        $submit = new Submit('inapps_submit');
        $submit->setValue(OW::getLanguage()->text('skmobileapp', 'inapps_submit'));
        $form->addElement($submit);

        $this->assign('googlePlayWebhookUrl', SKMOBILEAPP_BOL_Service::WEBHOOK_URL_GOOGLE_PLAY);
        $this->assign('appStoreWebhookUrl', SKMOBILEAPP_BOL_Service::WEBHOOK_URL_APP_STORE);

        if ( OW::getRequest()->isPost() && $form->isValid(array_merge($_POST, $_FILES)) )
        {
            OW::getConfig()->saveConfig('skmobileapp', 'inapps_show_membership_actions', $form->getElement('inapps_show_membership_actions')->getValue());
            OW::getConfig()->saveConfig('skmobileapp', 'inapps_itunes_shared_secret', $form->getElement('inapps_itunes_shared_secret')->getValue());
            OW::getConfig()->saveConfig('skmobileapp', 'inapps_ios_test_mode', $form->getElement('inapps_ios_test_mode')->getValue());
            OW::getConfig()->saveConfig('skmobileapp', 'inapps_enable', $form->getElement('inapps_enable')->getValue());
            OW::getConfig()->saveConfig('skmobileapp', 'inapps_apm_package_name', $form->getElement('inapps_apm_package_name')->getValue());

            if ( !empty($_FILES['inapps_apm_android_account_key']['tmp_name']) )
            {
                if (!file_exists(SKMOBILEAPP_BOL_Service::ANDROID_PUBLISHER_KEY_DIR)) {
                    mkdir(SKMOBILEAPP_BOL_Service::ANDROID_PUBLISHER_KEY_DIR, 0777, true);
                }

                move_uploaded_file(
                    $_FILES['inapps_apm_android_account_key']['tmp_name'],
                    SKMOBILEAPP_BOL_Service::ANDROID_PUBLISHER_KEY_PATH
                );
            }

            OW::getFeedback()->info(OW::getLanguage()->text('skmobileapp', 'settings_saved'));

            $this->redirect();
        }

        $this->addForm($form);
    }

    /**
     * Push settings
     */
    public function push( array $params )
    {
        $form = new Form('skmobileapp_push');

        $enabled = new CheckboxField('pn_enabled');
        $enabled->setValue(OW::getConfig()->getValue('skmobileapp', 'pn_enabled'));
        $enabled->setLabel(OW::getLanguage()->text('skmobileapp', 'pn_enabled_label'));

        $form->addElement($enabled);

        $submit = new Submit('push');
        $submit->setValue(OW::getLanguage()->text('skmobileapp', 'pn_submit'));
        $form->addElement($submit);

        if ( OW::getRequest()->isPost() && $form->isValid(array_merge($_POST, $_FILES)) )
        {
            OW::getConfig()->saveConfig('skmobileapp', 'pn_enabled', $form->getElement('pn_enabled')->getValue());

            OW::getFeedback()->info(OW::getLanguage()->text('skmobileapp', 'settings_saved'));

            $this->redirect();
        }

        $this->addForm($form);
    }

    private function _processThemeFile($settingName, $prefix, $deleteFile, array $file = [])
    {
        /** @var SKMOBILEAPP_BOL_Service $service */
        $service = SKMOBILEAPP_BOL_Service::getInstance();
        $oldFile = OW::getConfig()->getValue('skmobileapp', $settingName);

        // replace the old theme file
        if ( !empty($file['tmp_name']) )
        {
            // remove the old file
            if ($oldFile)
            {
                unlink($service->getBaseThemeFilePath() . $oldFile);
            }

            $newFileName = $service->getThemeFileName(
                $prefix,
                $file['name']
            );

            move_uploaded_file(
                $file['tmp_name'],
                $service->getBaseThemeFilePath() . $newFileName
            );

            OW::getConfig()->saveConfig('skmobileapp', $settingName, $newFileName);

            return;
        }

        // delete the old file
        if ($deleteFile && $oldFile)
        {
            unlink($service->getBaseThemeFilePath() . $oldFile);
            OW::getConfig()->saveConfig('skmobileapp', $settingName, '');
        }
    }
}
