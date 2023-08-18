<?php

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;

/**
 * Copyright (c) 2016, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.com/
 * and is licensed under Oxwall Store Commercial License.
 *
 * Full text of this license can be found at http://developers.oxwall.com/store/oscl
 */

 class SKMOBILEAPP_BOL_Service
{
    use OW_Singleton;

    /**
     * Plugin key
     */
    const PLUGIN_KEY = 'skmobileapp';

    /**
     * Path to the Android publisher key directory.
     */
    const ANDROID_PUBLISHER_KEY_DIR = OW_DIR_PLUGINFILES . self::PLUGIN_KEY . DS . 'android_publisher';

    /**
     * Fully qualified path to the Android publisher key file.
     */
    const ANDROID_PUBLISHER_KEY_PATH = self::ANDROID_PUBLISHER_KEY_DIR . DS . 'key.json';

    /**
     * Api version
     */
    const API_VERSION = '3.0.0';

    /**
     * Default user location distance
     */
    const DEFAULT_USER_LOCATION_DISTANCE = 1;

    /**
     * Distance units miles
     */
    const DISTANCE_UNITS_MILES = 'miles';

    /**
     * Distance units km
     */
    const DISTANCE_UNITS_KM = 'km';

    /**
     * Default avatar size
     */
    const DEFAULT_AVATAR_SIZE = 2;

    /**
     * Big avatar size
     */
    const BIG_AVATAR_SIZE = 3;

    /**
     * Google map location question
     */
    const QUESTION_PRESENTATION_GOOGLEMAP_LOCATION = 'googlemap_location';

    /**
     * Date range question
     */
    const QUESTION_PRESENTATION_DATE_RANGE = 'date_range';

    /**
     * Extended google map location question
     */
    const QUESTION_PRESENTATION_EXTENDED_GOOGLEMAP_LOCATION = 'extended_googlemap_location';

    /**
     * Location api url
     */
    const LOCATION_API_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

     /**
      * A Place Details request url
      */
    const PLACE_DETAILS_API_URL = 'https://maps.googleapis.com/maps/api/place/details/json';

    /**
     * Base plugin cache key
     */
    const BASE_PLUGIN_CACHE_KEY = 'skmobileapp.';

    /**
     * Location autocomplete cache type
     */
    const LOCATION_AUTOCOMPLETE_CACHE_TYPE = 'location.autocomplete';

    /**
     * Location details cache type
     */
    const LOCATION_DETAILS_CACHE_TYPE = 'location.details';

    /**
     * Location cache life time in seconds
     */
    const LOCATION_CACHE_LIFE_TIME = 2678400; // one month

    /**
     * Membership plugin key
     */
    const MEMBERSHIP_PLUGIN_KEY = 'membership';

    /**
     * App Store native purchase source ID.
     */
    const NATIVE_PURCHASE_SOURCE_APP_STORE = 'app_store';

    /**
     * Google Play native purchase source ID.
     */
    const NATIVE_PURCHASE_SOURCE_GOOGLE_PLAY = 'google_play';

    /**
     * Native purchase canceled state ID.
     */
    const NATIVE_PURCHASE_STATE_CANCELED = 'canceled';

    /**
     * Native purchase completed state ID.
     */
    const NATIVE_PURCHASE_STATE_COMPLETED = 'completed';

    /**
     * Native purchase expired state ID.
     */
    const NATIVE_PURCHASE_STATE_EXPIRED = 'expired';

    /**
     * Native purchase pending state ID.
     */
    const NATIVE_PURCHASE_STATE_PENDING = 'pending';

    /**
     * Android platform identifier.
     */
    const PLATFORM_ANDROID = 'android';

    /**
     * iOS platform identifier.
     */
    const PLATFORM_IOS = 'ios';

    /**
     * User credits plugin key
     */
    const USER_CREDITS_PLUGIN_KEY = 'usercredits';

    /**
     * Valid image mime types
     */
    const VALID_IMAGE_MIME_TYPES = [
        'image/jpg',
        'image/jpeg',
        'image/png'
    ];

    /**
     * List of controllers and actions on which the user should NOT be redirected to the PWA.
     *
     * Format:
     *
     * ```
     * [
     *     controllerName: string => '*'|string[]    // Controller class name as key and either '*' or an array of
     *                                               // action names as value. If the value is '*' as value, ALL actions
     *                                               // of the given controller will be handled in the mobile context
     *                                               // instead of redirecting to PWA.
     * ]
     * ```
     */
    const NON_REDIRECTABLE_CONTROLLERS = [
        'SKMOBILEAPP_MCTRL_Api' => '*',
        'BILLINGSTRIPE_MCTRL_Action' => ['checkoutSuccess', 'checkoutCancel'],
        'BILLINGPAYPAL_MCTRL_Order' => ['appOrderCompleted', 'appOrderCancelled']
    ];

    /**
     * Redirect to firebird
     */
    const REDIRECT_TO_FIREBIRD = true;

    /**
     * Redirect links to desktop
     */
    const REDIRECT_LINKS_TO_DESKTOP = false;

    /**
     * Google Play webhook URL.
     */
    const WEBHOOK_URL_GOOGLE_PLAY = OW_URL_HOME . self::PLUGIN_KEY . '/api/webhooks/google-play/';

     /**
      * App Store webhook URL.
      */
    const WEBHOOK_URL_APP_STORE = OW_URL_HOME . self::PLUGIN_KEY . '/api/webhooks/app-store/';

    /**
     * User match action DAO
     *
     * @var SKMOBILEAPP_BOL_UserMatchActionDao
     */
    protected $userMatchActionDao;

    /**
     * User match action DAO
     *
     * @var SKMOBILEAPP_BOL_UserLocationDao
     */
     protected $userLocationDao;

    /**
     * Language tags for conversion
     */
    private $langTagsConversion = [
        'uk' => 'ua',   // Ukraine
        'ch' => 'cn',   // China
        'zh' => 'cn',   // China
        'nb' => 'no',   // Norway
        'ja' => 'jp',   // Japan
        'vi' => 'vn',   // Viet Nam
        'ko' => 'kr',   // Korea, Republic of
        'el' => 'gr'    // Greece
    ];

    /**
     * Return logger instance with `PLUGIN_KEY` as prefix.
     *
     * @return OW_Log
     */
    static public function getLogger()
    {
        return OW::getLogger(self::PLUGIN_KEY);
    }

    /**
     * Class constructor
     */
    private function __construct()
    {
        $this->userMatchActionDao = SKMOBILEAPP_BOL_UserMatchActionDao::getInstance();
        $this->userLocationDao = SKMOBILEAPP_BOL_UserLocationDao::getInstance();
    }

     /**
      * Get Firebase admin instance.
      *
      * @return Factory|null
      */
    public function getFirebaseAdmin()
    {
        $firebaseAuthPlugin = OW::getPluginManager()->getPlugin('firebaseauth');

        if (!$firebaseAuthPlugin) {
            return null;
        }

        $adminKeyPath = $firebaseAuthPlugin->getPluginFilesDir() . FIREBASEAUTH_BOL_Service::ADMIN_KEY_FILE_NAME;

        return (new Factory())->withServiceAccount($adminKeyPath);
    }

     /**
      * Get Firebase Cloud Messaging admin instance.
      *
      * @return Messaging|null
      */
    public function getFirebaseMessaging()
    {
        $admin = $this->getFirebaseAdmin();

        if (!$admin) {
            return null;
        }

        return $admin->createMessaging();
    }

    /**
     * is demo mode activated
     */
    public function isDemoModeActivated()
    {
        if (stristr(OW_URL_HOME, 'demo.skadate.com') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Get PWA URL.
     *
     * @return string
     */
    public function getPwaUrl()
    {
        return OW_URL_HOME . 'm/';
    }

    /**
     * Get pwa icon
     *
     * @return string
     */
    public function getPwaIcon()
    {
        return OW::getPluginManager()->getPlugin('skmobileapp')->getStaticUrl() . 'src/assets/image/app/pwa_icon.png';
    }

    /**
     * Get base theme file path
     *
     * @return string
     */
    public function getBaseThemeFilePath()
    {
        return OW::getPluginManager()->getPlugin('skmobileapp')->getUserFilesDir();
    }

     /**
      * Get base theme file url
      *
      * @return string
      */
    public function getBaseThemeFileUrl()
    {
        return OW::getPluginManager()->getPlugin('skmobileapp')->getUserFilesUrl();
    }

    public function getThemeFileName($prefix, $fileName) {
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        return $prefix . '_' . time() . '.' . $extension;
    }

    /**
     * Returns cache key
     *
     * @param string $type
     * @param string $value
     * @return string
     */
    private function getCacheKey($type, $value)
    {
        return md5( self::BASE_PLUGIN_CACHE_KEY . $type . '.' . trim( $value ) );
    }

    /**
     * Create user match action
     *
     * @param integer $userId
     * @param integer $recipientId
     * @param string $type
     * @return SKMOBILEAPP_BOL_UserMatchAction
     */
    public function createUserMatchAction($userId, $recipientId, $type)
    {
        return $this->userMatchActionDao->createUserMatchAction($userId, $recipientId, $type);
    }

    /**
     * Find user match actions
     *
     * @param integer $userId
     * @param array $userIdList
     * @return array
     */
    public function findUserMatchActionsByUserIdList($userId, array $userIdList)
    {
        return $this->userMatchActionDao->findUserMatchActionsByUserIdList($userId, $userIdList);
    }

    /**
     * Find matched users
     *
     * @param integer $userId
     * @param integer $limit
     * @return array
     */
    public function findMatchedUsers($userId, $limit = 200)
    {
        $matchedUsers = $this->userMatchActionDao->findMatchedUsers($userId, $limit);
        $processedMatches = [];
        $ids = [];

        // process matches
        foreach( $matchedUsers as $match )
        {
            $ids[] = $match['userId'];

            $processedMatches[$match['userId']] = [
                'id' => (int) $match['id'],
                'isViewed' => (bool) $match['read'],
                'isNew' => (bool) $match['new'],
                'createStamp' => (int) $match['createStamp'],
                'user' => [
                    'id' => (int) $match['userId'],
                    'userName' => null,
                    'avatar' => null,
                ]
            ];
        }

        // load avatars
        $avatarList = BOL_AvatarService::getInstance()->findByUserIdList($ids);

        foreach( $avatarList as $avatar )
        {
            $processedMatches[$avatar->userId]['user']['avatar'] = $this->getAvatarData($avatar, false);
        }

        // load user names
        $userNames = BOL_UserService::getInstance()->getUserNamesForList($ids);

        foreach( $userNames as $userId => $userName )
        {
            $processedMatches[$userId]['user']['userName'] = $userName;
        }

        // load display names
        $displayNames = BOL_UserService::getInstance()->getDisplayNamesForList($ids);

        foreach( $displayNames as $userId => $displayName )
        {
            if ( $displayName )
            {
                $processedMatches[$userId]['user']['userName'] = $displayName;
            }
        }

        $data = [];
        foreach($processedMatches as $userData)
        {
            $data[] = $userData;
        }

        $event = new OW_Event('skmobileapp.formatted_matched_users_data', [], $data);
        OW_EventManager::getInstance()->trigger($event);

        return $event->getData();
    }

    /**
     * Find user match by id
     *
     * @param integer $id
     * @return SKMOBILEAPP_BOL_UserMatchAction
     */
    public function findUserMatchById($id)
    {
        return $this->userMatchActionDao->findById($id);
    }

    /**
     * Save user match
     *
     * @param SKMOBILEAPP_BOL_UserMatchAction $userMatch
     * @return void
     */
    public function saveUserMatch(SKMOBILEAPP_BOL_UserMatchAction $userMatch)
    {
        $this->userMatchActionDao->save($userMatch);
    }

    /**
     * Find user match
     *
     * @param integer $userId
     * @param integer $recipientId
     * @return SKMOBILEAPP_BOL_UserMatchAction
     */
    public function findUserMatch($userId, $recipientId)
    {
        return $this->userMatchActionDao->findUserMatch($userId, $recipientId);
    }

    /**
     * Delete user match
     *
     * @param $id
     * @return void
     */
    public function deleteUserMatch($id)
    {
        $this->userMatchActionDao->deleteUserMatch($id);
    }

    /**
     * GetLocation autocomplete
     *
     * @param string $query
     * @return array
     */
    public function getLocationAutocomplete($query)
    {
        $autocompleate = [];

        $apiKey = OW::getConfig()->getValue('skmobileapp', 'google_map_api_key');

        if ($apiKey) {
            $cacheKey = $this->getCacheKey(self::LOCATION_AUTOCOMPLETE_CACHE_TYPE, $query);
            $cacheService = OW::getCacheService();
            $cachedLocation = $cacheService->get($cacheKey);

            if ( $cachedLocation )
            {
                return json_decode( $cachedLocation, true );
            }

            $clientParams = new UTIL_HttpClientParams();
            $clientParams->addParams([
                'key' => $apiKey,
                'language' => substr(BOL_LanguageService::getInstance()->getCurrent()->getTag(), 0, 2),
                'address' => $query
            ]);
            $result = UTIL_HttpClient::get(self::LOCATION_API_URL, $clientParams);
            if ($result && $result->getBody()) {
                $response = json_decode($result->getBody(), true);

                if ($response['status'] == 'OK') {
                    // process response
                    $autoComplete = [];
                    foreach ($response['results'] as $prediction) {
                        $autoComplete[] = $prediction['formatted_address'];
                    }

                    $cacheService->set( $cacheKey, json_encode( $autoComplete ), self::LOCATION_CACHE_LIFE_TIME );

                    return $autoComplete;
                }
                else if ($response['status'] == 'REQUEST_DENIED'){
                    throw new Exception('Googlelocation plugin not configured');
                }
            }
        }

        return [];
    }

    /**
     * Get location details
     *
     * @param string $location
     * @return array
     */
    public function getLocationDetails($location)
    {
        try
        {
            $apiKey = OW::getConfig()->getValue('skmobileapp', 'google_map_api_key');
            if ( $apiKey )
            {
                $cacheKey = $this->getCacheKey(self::LOCATION_DETAILS_CACHE_TYPE, $location);
                $cacheService = OW::getCacheService();
                $cachedLocationDetails = $cacheService->get($cacheKey);

                if ( $cachedLocationDetails )
                {
                    return json_decode( $cachedLocationDetails, true );
                }

                // get short location details
                $clientParams = new UTIL_HttpClientParams();
                $clientParams->addParams([
                    'key' => $apiKey,
                    'language' => substr(BOL_LanguageService::getInstance()->getCurrent()->getTag(), 0, 2),
                    'address' => $location
                ]);

                $result = UTIL_HttpClient::get(self::LOCATION_API_URL, $clientParams);
                if ( $result && $result->getBody() )
                {
                    $response = json_decode($result->getBody(), true);
                    if ( $response['status'] == 'OK' )
                    {
                        $placeId = '';

                        // process response
                        if ( !empty($response['results']) )
                        {
                            $responsePrediction = array_shift($response['results']);
                            if ( !empty($responsePrediction['formatted_address']) && !empty($responsePrediction['place_id']) )
                            {
                                $placeId = $responsePrediction['place_id'];
                            }
                        }

                        // get place details
                        if ( $placeId )
                        {
                            $clientParams = new UTIL_HttpClientParams();
                            $clientParams->addParams([
                                'key' => $apiKey,
                                'placeid' => $placeId
                            ]);

                            $result = UTIL_HttpClient::get(self::PLACE_DETAILS_API_URL, $clientParams);
                            if ( $result && $result->getBody() )
                            {
                                $response = json_decode($result->getBody(), true);
                                if ( $response['status'] == 'OK' )
                                {
                                    $json = $response['result'];
                                    $locationDetails = [
                                        'address' => $json['formatted_address'],
                                        'latitude' => $json['geometry']['location']['lat'],
                                        'longitude' => $json['geometry']['location']['lng'],
                                        'northEastLat' => $json['geometry']['viewport']['northeast']['lat'],
                                        'northEastLng' => $json['geometry']['viewport']['northeast']['lng'],
                                        'southWestLat' => $json['geometry']['viewport']['southwest']['lat'],
                                        'southWestLng' => $json['geometry']['viewport']['southwest']['lng'],
                                        'json' => [
                                            'formatted_address' => $json['formatted_address'],
                                            'address_components' => $json['address_components'],
                                            'geometry' => $json['geometry']
                                        ]
                                    ];

                                    $cacheService->set( $cacheKey, json_encode( $locationDetails ), self::LOCATION_CACHE_LIFE_TIME );

                                    return $locationDetails;
                                }
                            }
                        }
                    }
                }
            }
        }
        catch(Exception $e) {}

        return [];
    }

    /**
     * Save edited questions in user's preference
     *
     * @param integer $userId
     * @param array $questions
     * @return void
     */
    public function saveEditedQuestionsInPreference($userId, array $questions)
    {
        $prevChangedValues = array();
        $prefValue = BOL_PreferenceService::getInstance()->
        getPreferenceValue(BASE_CTRL_Edit::PREFERENCE_LIST_OF_CHANGES, $userId);

        if ( !empty($prefValue) )
        {
            $prevChangedValues = json_decode($prefValue, true);
        }

        $changesList = BOL_QuestionService::getInstance()->getChangedQuestionList($questions, $userId);
        $allChangesList = array_merge($prevChangedValues, $changesList);

        OW::getEventManager()->trigger(new OW_Event(OW_EventManager::ON_USER_EDIT, array(
            'userId' => $userId,
            'method' => 'native',
            'moderate' => BOL_QuestionService::getInstance()->isNeedToModerate($allChangesList)
        )));

        BOL_PreferenceService::getInstance()->
        savePreferenceValue(BASE_CTRL_Edit::PREFERENCE_LIST_OF_CHANGES, json_encode($allChangesList), $userId);
    }

    /**
     * Get all user question data
     *
     * @param integer $userId
     * @param array $includeOnlyQuestions
     * @return array
     */
    public function getAllUserQuestionData($userId, $includeOnlyQuestions = [])
    {
        $userDto = BOL_UserService::getInstance()->findUserById($userId);
        $result  = [];

        if ( $userDto )
        {
            $questionData = BOL_QuestionDataDao::getInstance();
            $question = BOL_QuestionDao::getInstance();

            $baseQuestions = [
                'username',
                'email'
            ];

            $questions = OW::getDbo()->queryForList("
            SELECT
                a.`id`,
                a.`questionName`,
                b.`presentation`
            FROM
                `{$questionData->getTableName()}` as a
            INNER JOIN
                `{$question->getTableName()}` as b
            ON
                a.`questionName` = b.`name`
            WHERE a.`userId` = ?", [$userId]);

            $questionNames = [];
            foreach ( $questions as $question )
            {
                $questionNames[] = $question['questionName'];
            }

            BOL_QuestionService::getInstance()->clearCachedQuestionData($userId);
            $questionData = BOL_QuestionService::getInstance()->getQuestionData([$userId], $questionNames);
            $questionsValues = array_shift($questionData);
            $result = [];

            // process questions
            foreach ( $questions as $question )
            {
                if ( $includeOnlyQuestions
                    && !in_array($question['questionName'], $includeOnlyQuestions )
                ) {

                    continue;
                }

                // skip base questions
                if ( in_array($question['questionName'], $baseQuestions ) )
                {
                    continue;
                }

                switch( $question['questionName'] )
                {
                    case 'googlemap_location' :
                        $presentationQuestion = self::QUESTION_PRESENTATION_GOOGLEMAP_LOCATION;
                        break;

                    default :
                        $presentationQuestion = $question['presentation'];
                }

                $value = $this->convertQuestionValueToApplicationFormat(
                    $presentationQuestion,
                    $questionsValues[$question['questionName']],
                    $question['questionName'],
                    $userId);

                $result[] = [
                    'id' => $question['id'],
                    'name' => $question['questionName'],
                    'value' => $value,
                    'type' => $presentationQuestion
                ];
            }

            // add base fields
            if ( in_array('username', $includeOnlyQuestions) || !$includeOnlyQuestions)
            {
                $result[] = [
                    'id' => -1,
                    'name' => 'username',
                    'value' => $userDto->getUsername(),
                    'type' => ''
                ];
            }

            if ( in_array('email', $includeOnlyQuestions) || !$includeOnlyQuestions) {
                $result[] = [
                    'id' => -2,
                    'name' => 'email',
                    'value' => $userDto->getEmail(),
                    'type' => ''
                ];
            }
        }

        return $result;
    }

    /**
     * Convert value to application format
     *
     * @param string $type
     * @param mixed $value
     * @param string $questionName
     * @param integer $userId
     * @return mixed
     */
    public function convertQuestionValueToApplicationFormat($type, $value, $questionName, $userId)
    {
        $event = new OW_Event('skmobileapp.question_value_for_app', [
            'questionType' => $type,
            'questionValue' => $value,
            'questionName' => $questionName,
            'userId' => $userId,
        ]);

        $data = OW::getEventManager()->trigger($event);

        if ( $data->getData() || !is_null($data->getData()) )
        {
            return $data->getData();
        }

        switch ($type)
        {
            case self::QUESTION_PRESENTATION_GOOGLEMAP_LOCATION:
                if ($value) {
                    return !empty($value['address']) ? $value['address'] : '';
                }

                return $value;

            case BOL_QuestionService::QUESTION_PRESENTATION_CHECKBOX:
                if ($value) {
                    return boolval($value);
                }

                return false;

            case BOL_QuestionService::QUESTION_PRESENTATION_AGE:
            case BOL_QuestionService::QUESTION_PRESENTATION_BIRTHDATE:
            case BOL_QuestionService::QUESTION_PRESENTATION_DATE:
                if ($value) {
                    list($date,) = explode(' ', $value);

                    return $date;
                }

                return $value;

            case BOL_QuestionService::QUESTION_PRESENTATION_RANGE:
                if ($value) {
                    list($lower, $upper) = explode('-', $value);

                    return [
                        'lower' => $lower,
                        'upper' => $upper
                    ];
                }

                return $value;

            case BOL_QuestionService::QUESTION_PRESENTATION_MULTICHECKBOX:
                if ($value) {
                    $values = $this->getMultipleValues($value);

                    return $values;
                }

                return [];

            default :
                return $value;
        }
    }

    /**
     * Get multiple values
     *
     * @param integer $value
     * @return array
     */
    public function getMultipleValues($value) {
        $values = [];

        for ( $bit = 0; $bit < 32; $bit++ )
        {
            $binPower = 1 << $bit;

            if ($binPower & $value)
            {
                $values[] = $binPower;
            }
        }

        return $values;
    }

    /**
     * Convert value to skadate format
     *
     * @param string $type
     * @param mixed $value
     * @param string $name
     * @param integer $userId
     * @return mixed
     */
    public function convertQuestionValueToSkadateFormat($type, $value, $name, $userId)
    {
        $event = new OW_Event('skmobileapp.convert_question_value_to_skadate_format', [
            'questionType' => $type,
            'questionValue' => $value,
            'questionName' => $name,
            'userId' => $userId
        ]);

        $data = OW::getEventManager()->trigger($event);

        if ( $data->getData() || !is_null($data->getData()) )
        {
            return $data->getData();
        }

        switch ($type) {
            case BOL_QuestionService::QUESTION_PRESENTATION_MULTICHECKBOX:
                return $value
                    ? array_sum($value)
                    : [];

            case BOL_QuestionService::QUESTION_PRESENTATION_AGE:
            case BOL_QuestionService::QUESTION_PRESENTATION_BIRTHDATE:
            case BOL_QuestionService::QUESTION_PRESENTATION_DATE:
                return $value
                    ? $value . ' 00:00:00'
                    : '';

            case BOL_QuestionService::QUESTION_PRESENTATION_RANGE:
                if ($value) {
                    return $value['lower'] . '-' . $value['upper'];
                }

                return $value;

            case self::QUESTION_PRESENTATION_GOOGLEMAP_LOCATION:
                if (!$value) {
                    return [
                        'remove' => true
                    ];
                }

                $locationDetails = $this->getLocationDetails($value);
                $locationDetails['json'] = json_encode( $locationDetails['json'] );

                return $locationDetails;

            case BOL_QuestionService::QUESTION_PRESENTATION_RADIO:
            case BOL_QuestionService::QUESTION_PRESENTATION_SELECT:
            case BOL_QuestionService::QUESTION_PRESENTATION_FSELECT:
                return isset($value) ? $value[0] : 0;

            default :
                return $value;
        }
    }

    /**
     * Convert value to skadate search format
     *
     * @param string $type
     * @param mixed $value
     * @return mixed
     */
    public function convertQuestionValueToSkadateSearchFormat($type, $value)
    {
        switch ($type) {
            case BOL_QuestionService::QUESTION_PRESENTATION_CHECKBOX:
                if ($value) {
                    return 'on';
                }

                return $value;

            case BOL_QuestionService::QUESTION_PRESENTATION_RANGE:
                if ($value) {
                    return [
                        'from' => $value['lower'],
                        'to' => $value['upper']
                    ];
                }

                return $value;

            case self::QUESTION_PRESENTATION_DATE_RANGE:
                if ($value && $value['start'] && $value['end']) {
                    return [
                        'from' => date('Y/n/j', strtotime($value['start'])),
                        'to' => date('Y/n/j', strtotime($value['end']))
                    ];
                }

                return $value;

            case self::QUESTION_PRESENTATION_EXTENDED_GOOGLEMAP_LOCATION:
                if ($value && $value['location']) {
                    $locationDetails = $this->getLocationDetails($value['location']);
                    $locationDetails['json'] = json_encode( $locationDetails['json'] );

                    return $locationDetails + [
                        'distance' => $value['distance']
                    ];
                }

                return '';

            default :
                return $value;
        }
    }

    /**
     * Is permission allowed
     *
     * @param integer $userId
     * @param string $group
     * @param string $action
     * @return boolean
     */
    public function isPermissionAllowed($userId, $group, $action)
    {
        // check if action allowed by a role
        $isActionAllowed = OW::getAuthorization()->isUserAuthorized($userId, $group, $action);

        // check if action allowed by credits
        if ( !$isActionAllowed && OW::getPluginManager()->isPluginActive(self::USER_CREDITS_PLUGIN_KEY) )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus($group, $action, [
                'userId' => $userId
            ]);

            switch($status['status'])
            {
                case BOL_AuthorizationService::STATUS_AVAILABLE :
                    return true;

                default :
            }
        }

        return $isActionAllowed;
    }

    /**
     * Get app permission list
     *
     * @return array
     */
    public function getAppPermissionList() {
        $permissions = [
            [
                'group' => 'base',
                'plugin' => '',
                'actions' => [
                    'search_users',
                    'view_profile'
                ],
                'tracking_actions' => [
                ]
            ],
            [
                'group' => 'skmobileapp',
                'plugin' => 'skmobileapp',
                'actions' => [
                    'tinder_filters',
                ],
                'tracking_actions' => [
                ]
            ],
            [
                'group' => 'ads',
                'plugin' => '',
                'actions' => [
                    'hide_ads',
                ],
                'tracking_actions' => [
                ]
            ],
            [
                'group' => 'photo',
                'plugin' => 'photo',
                'actions' => [
                    'upload',
                    'view'
                ],
                'tracking_actions' => [
                ]
            ],
            [
                'group' => 'mailbox',
                'plugin' => 'mailbox',
                'actions' => [
                    'reply_to_chat_message',
                    'send_chat_message',
                    'read_chat_message'
                ],
                'tracking_actions' => [
                    'read_chat_message' => function ($userId, &$isAllowed) {
                        list($isActionAllowedAfterTracking, $isAllowed) = SKMOBILEAPP_BOL_MailboxService::getInstance()->
                                isReadChatMessageAllowedAfterTracking($userId, $isAllowed);

                        return $isActionAllowedAfterTracking;
                    }
                ]
            ],
            [
                'group' => 'hotlist',
                'plugin' => 'hotlist',
                'actions' => [
                    'add_to_list'
                ],
                'tracking_actions' => [
                ]
            ]
        ];

        $event = new OW_Event('skmobileapp.get_application_permissions', [], $permissions);
        $permissionData = OW::getEventManager()->trigger($event);

        return $permissionData->getData();
    }

    /**
     * Get permissions
     *
     * @param array $userIdList
     * @param boolean $refreshCaches
     * @return array
     */
    public function getPermissions(array $userIdList, $refreshCaches = false)
    {
        $authService = BOL_AuthorizationService::getInstance();

        // clear caches
        if ( $refreshCaches )
        {
            $authService->generateCaches();
            BOL_PluginService::getInstance()->clearPluginListCache();
        }

        $isCreditsActive = OW::getPluginManager()->isPluginActive(self::USER_CREDITS_PLUGIN_KEY) ;
        $isMembershipActive = OW::getPluginManager()->isPluginActive(self::MEMBERSHIP_PLUGIN_KEY) ;

        $permissionList = $this->getAppPermissionList();
        $permissions = [];

        // process user list
        foreach( $userIdList as $userId )
        {
            $index = 1;

            // process permissions
            foreach ( $permissionList as $permission )
            {
                // skip not active plugins
                if ($permission['plugin'] && !OW::getPluginManager()->isPluginActive($permission['plugin'])) {
                    continue;
                }

                // process actions
                foreach ( $permission['actions'] as $action )
                {
                    // check if is an action allowed by a role or credits
                    $isAllowed = $this->isPermissionAllowed($userId, $permission['group'], $action);
                    $creditsCost = 0;

                    // check the promotion status
                    $promotedStatus = BOL_AuthorizationService::getInstance()->getActionStatus($permission['group'], $action, [
                        'userId' => $userId
                    ]);

                    // get action cost
                    if ( isset($promotedStatus['authorizedBy']) && $promotedStatus['authorizedBy'] == self::USER_CREDITS_PLUGIN_KEY )
                    {
                        if ( $isCreditsActive  )
                        {
                            $creditsAction = USERCREDITS_BOL_CreditsService::getInstance()->findAction($permission['group'], $action);

                            if ($creditsAction) {
                                $actionPrice = USERCREDITS_BOL_CreditsService::getInstance()->findActionPriceForUser($creditsAction->id, $userId);

                                if (!$actionPrice->disabled) {
                                    $creditsCost = $actionPrice->amount;
                                }
                            }
                        }
                        else
                        {
                            $promotedStatus = [];
                        }
                    }

                    $permissions[$userId][] = [
                        'id' => $userId . '_' . $index,
                        'permission' => $permission['group'] . '_' . $action,
                        'isAllowedAfterTracking' => isset($permission['tracking_actions'][$action])
                            ? $permission['tracking_actions'][$action]($userId, $isAllowed)
                            : false,
                        'isAllowed' => $isAllowed,
                        'isPromoted' => !empty($promotedStatus['status']) &&
                                $promotedStatus['status'] == BOL_AuthorizationService::STATUS_PROMOTED && ($isMembershipActive || $isCreditsActive),
                        'authorizedByCredits' => isset($promotedStatus['authorizedBy'])
                                && $promotedStatus['authorizedBy'] == self::USER_CREDITS_PLUGIN_KEY && $isCreditsActive,
                        'creditsCost' => (int) $creditsCost,
                        'user' => [
                            'id' => $userId
                        ]
                    ];

                    $index++;
                }
            }
        }

        return $permissions;
    }

    /**
     * Is avatar valid
     *
     * @return boolean
     */
    public function isAvatarValid($fileType, $fileSize)
    {
        if (!in_array($fileType, self::VALID_IMAGE_MIME_TYPES, true)) {
            return false;
        }

        if ($fileSize > $this->getAvatarMaxUploadSize()) {
            return false;
        }

        return true;
    }

    /**
     * Get avatar max upload size
     *
     * @return float
     */
    public function getAvatarMaxUploadSize()
    {
        return floatVal(OW::getConfig()->getValue('base', 'avatar_max_upload_size')) * 1024 * 1024;
    }

    /**
     * Get photo max upload size
     *
     * @return float
     */
    public function getPhotoMaxUploadSize()
    {
        return floatVal(OW::getConfig()->getValue('photo', 'accepted_filesize')) * 1024 * 1024;
    }

    /**
     * Get attachment max upload size
     *
     * @return float
     */
    public function getAttachmentMaxUploadSize()
    {
        return floatVal(OW::getConfig()->getValue('base', 'attch_file_max_size_mb')) * 1024 * 1024;
    }

    /**
     * Update user avatar
     *
     * @param integer $userId
     * @param string $avatarPath
     * @return array
     */
    public function updateUserAvatar($userId, $avatarPath)
    {
        $avatarService = BOL_AvatarService::getInstance();
        $oldAvatar = $avatarService->findByUserId($userId);

        // apply new avatar
        OW::getEventManager()->trigger(new OW_Event('base.before_avatar_change', [
            'userId' => $userId,
            'avatarId' => $oldAvatar ? $oldAvatar->id : null,
            'upload' => false,
            'crop' => true
        ]));

        $avatarService->deleteUserAvatar($userId);
        $avatarService->clearCahche($userId);
        $avatarService->setUserAvatar($userId, $avatarPath);

        // get new avatar dto
        $newAvatar = $avatarService->findByUserId($userId, false);

        OW::getEventManager()->trigger(new OW_Event('base.after_avatar_change', array(
            'userId' => $userId,
            'avatarId' => $newAvatar ? $newAvatar->id : null,
            'upload' => false,
            'crop' => true
        )));

        return $this->getAvatarData($newAvatar);
    }

    /**
     * Get avatar data
     *
     * @param BOL_Avatar $avatar
     * @param boolean $addTimestamp
     * @return array
     */
    public function getAvatarData(BOL_Avatar $avatar, $addTimestamp = false)
    {
        $avatarService = BOL_AvatarService::getInstance();

        return [
            'id' => (int) $avatar->id,
            'userId' => (int) $avatar->userId,
            'url' => $avatar->status == 'active'
                ? $avatarService->getAvatarUrlByAvatarDto($avatar, self::DEFAULT_AVATAR_SIZE) . ($addTimestamp ? '?t=' . time() : '')
                : $this->getDefaultAvatar(),
            'pendingUrl' => $avatarService->getAvatarUrlByAvatarDto($avatar, self::DEFAULT_AVATAR_SIZE, null, false) .  ($addTimestamp ? '?t=' . time() : ''),
            'bigUrl' => $avatar->status == 'active'
                ? $avatarService->getAvatarUrlByAvatarDto($avatar, self::BIG_AVATAR_SIZE) . ($addTimestamp ? '?t=' . time() : '')
                : $this->getDefaultAvatar(true),
            'pendingBigUrl' => $avatarService->getAvatarUrlByAvatarDto($avatar, self::BIG_AVATAR_SIZE, null, false) .  ($addTimestamp ? '?t=' . time() : ''),
            'active' => $avatar->status == 'active'
        ];
    }

    /**
     * Get default avatar
     *
     * @param boolean $bigAvatar
     * @return string
     */
    public function getDefaultAvatar($bigAvatar = false)
    {
        return !$bigAvatar
            ? OW::getPluginManager()->getPlugin('skmobileapp')->getStaticUrl() . 'images/no_avatar_sm.png'
            : OW::getPluginManager()->getPlugin('skmobileapp')->getStaticUrl() . 'images/no_avatar.png';
    }

    /**
     * Search users
     *
     * @param integer $userId
     * @param array $filter
     * @return integer
     * @throws Exception
     */
    public function searchUsers($userId, array $filter = [])
    {
        $userId = (int) $userId;
        $processedFilter = [];

        // convert questions values
        foreach ($filter as $data) {
            if ( $data['name'] == 'username' ) {
                $displayNameQuestion = OW::getConfig()->getValue('base', 'display_name_question');
                $processedFilter[$displayNameQuestion] = $this->convertQuestionValueToSkadateSearchFormat($data['type'], $data['value']);
            } else {
                $processedFilter[$data['name']] = $this->convertQuestionValueToSkadateSearchFormat($data['type'], $data['value']);
            }
        }

        // get user's questions
        $questionsData = BOL_QuestionService::getInstance()->getQuestionData([$userId], ['match_sex', 'sex']);

        // check the match sex filter param
        if (empty($processedFilter['match_sex'])) {
            if (!empty($questionsData[$userId]['match_sex'])) {
                $processedFilter['match_sex'] = $this->getMultipleValues($questionsData[$userId]['match_sex'])[0];
            }
        }

        // we still cannot define user's match sex
        if (empty($processedFilter['match_sex'])) {
            throw new Exception('Cannot define math sex');
        }

        $processedFilter['sex'] = !empty($questionsData[$userId]['sex']) ? $questionsData[$userId]['sex'] : -1;
        $processedFilter = USEARCH_BOL_Service::getInstance()->updateSearchData($processedFilter);

        // add some extra conditions
        OW::getEventManager()->bind('base.query.user_filter', function(BASE_CLASS_QueryBuilderEvent $event) use ($processedFilter, $userId) {
            // get only online users
            if (!empty($processedFilter['online'])) {
                $event->addJoin(" INNER JOIN `" . BOL_UserOnlineDao::getInstance()->getTableName()."` `online` ON (`online`.`userId` = `base_user_table_alias`.`id`) ");
            }

            // get users only with avatars
            if (!empty($processedFilter['with_photo'])) {
                $event->addJoin(" INNER JOIN `" . OW_DB_PREFIX . "base_avatar` avatar ON (`avatar`.`userId` = `base_user_table_alias`.`id` AND `avatar`.`status` = 'active') ");
            }

            // exclude disliked users
            $event->addJoin(" LEFT JOIN `" . SKMOBILEAPP_BOL_UserMatchActionDao::getInstance()->getTableName()
                . "` `userMatchAction` ON (`userMatchAction`.`userId` = {$userId}"
                . " AND `userMatchAction`.`recipientId` = `base_user_table_alias`.`id` "
                . " AND `userMatchAction`.`type` = '" . SKMOBILEAPP_BOL_UserMatchActionDao::ACTION_DISLIKE . "' "
                . " AND `userMatchAction`.`expirationStamp` > " . time() . ") ");

            $event->addWhere("`userMatchAction`.`id` IS NULL");
        });

        $userIdList = USEARCH_BOL_Service::getInstance()->findUserIdListByQuestionValues($processedFilter, 0, BOL_SearchService::USER_LIST_SIZE);
        $listId = 0;

        // remove current user from list
        if ($userIdList) {
            foreach ($userIdList as $key => $id) {
                if ($userId == $id ) {
                    unset($userIdList[$key]);
                }
            }
        }

        if (count($userIdList) > 0) {
            $listId = BOL_SearchService::getInstance()->saveSearchResult($userIdList);
        }

        return $listId;
    }

    /**
     * Tinder search users
     *
     * @param integer $userId
     * @param integer $limit
     * @param integer $distance
     * @param array $excludeIds
     * @return integer
     * @throws Exception
     */
    public function tinderSearchUsers(
        $userId,
        $limit,
        $defaultDistance = 100,
        array $excludeIds = [],
        array $filter = []
    ) {
        $userId = (int) $userId;
        $processedFilter = [];

        $userLatitude = null;
        $userLongitude = null;

        $distance = !empty($filter['distance'])
            ? abs((int) $filter['distance'])
            : $defaultDistance;

        // use a logged in user's location
        if (empty($filter['location'])) {
            $userLocation = $this->findUserLocation($userId);
            if (!$userLocation) {
                return;
            }

            $userLatitude = $userLocation->latitude;
            $userLongitude = $userLocation->longitude;
        }
        else {
            // get a user's defined location
            $userLocation = $this->getLocationDetails($filter['location']);
            if (!$userLocation) {
                return;
            }

            $userLatitude = $userLocation['latitude'];
            $userLongitude = $userLocation['longitude'];
        }

        // get user's questions
        $questionsData = BOL_QuestionService::getInstance()->getQuestionData([$userId], [
            'match_sex',
            'sex',
            'match_age'
        ]);

        // get a user defined match sex
        if (!empty($filter['matchSex'])) {
            $processedFilter['match_sex'] =  array_map('intval', explode(',', $filter['matchSex']));
        }
        // get the match sex from questions
        else if (!empty($questionsData[$userId]['match_sex'])) {
            $processedFilter['match_sex'] = $this->getMultipleValues($questionsData[$userId]['match_sex']);
        }

        if (!empty($questionsData[$userId]['sex'])) {
            $processedFilter['sex'] = $questionsData[$userId]['sex'];
        }

        // get a user defined age range
        if (!empty($filter['lowerAge']) && !empty($filter['upperAge'])) {
            $processedFilter['birthdate'] = [
                'from' => (int) $filter['lowerAge'],
                'to' => (int) $filter['upperAge']
            ];
        }
        // get the age from questions
        else if (!empty($questionsData[$userId]['match_age'])) {
            list($fromAge, $toAge) = explode('-', $questionsData[$userId]['match_age']);

            $processedFilter['birthdate'] = [
                'from' => $fromAge,
                'to' => $toAge
            ];
        }

        // we still cannot define user's match sex
        if (empty($processedFilter['match_sex'])) {
            throw new Exception('Cannot define math sex');
        }

        $processedFilter = USEARCH_BOL_Service::getInstance()->updateSearchData($processedFilter);

        // add some extra conditions
        OW::getEventManager()->bind('base.query.user_filter', function(BASE_CLASS_QueryBuilderEvent $event)
                use ($processedFilter, $userId, $userLatitude, $userLongitude, $distance, $excludeIds) {

            // filter by location
            $southWest = $this->getNewCoordinates($userLatitude, $userLongitude, 'sw', $distance);
            $northEast = $this->getNewCoordinates($userLatitude, $userLongitude, 'ne', $distance);

            if ( !$this->isDemoModeActivated() )
            {
                $locationSql = "
                    INNER JOIN
                        " . SKMOBILEAPP_BOL_UserLocationDao::getInstance()->getTableName() ." `userLocation`
                    ON
                        `base_user_table_alias`.`id` = `userLocation`.`userId`
                    AND
                        (
                            `userLocation`.`southWestLatitude` >= " . (float) $southWest['latitude'] . "
                                AND
                            `userLocation`.`southWestLatitude` <= " . (float) $northEast['latitude'] . "
                                AND
                            `userLocation`.`northEastLatitude` >= " . (float)$southWest['latitude']  . "
                                AND
                            `userLocation`.`northEastLatitude` <= " . (float) $northEast['latitude'] . "
                        )
                    AND
                        (
                            `userLocation`.`southWestLongitude` >= " . (float) $southWest['longitude'] . "
                                AND
                            `userLocation`.`southWestLongitude` <= " . (float) $northEast['longitude'] . "
                                AND
                            `userLocation`.`northEastLongitude` >= " . (float) $southWest['longitude'] . "
                                AND
                            `userLocation`.`northEastLongitude` <= " . (float) $northEast['longitude'] . "
                        )
                ";

                $event->addJoin($locationSql);
            }

            // exclude liked|disliked users
            $event->addJoin(" LEFT JOIN `" . SKMOBILEAPP_BOL_UserMatchActionDao::getInstance()->getTableName()
                . "` `userMatchAction` ON `userMatchAction`.`userId` = {$userId}"
                . " AND `userMatchAction`.`recipientId` = `base_user_table_alias`.`id` ");

            $event->addWhere("`userMatchAction`.`id` IS NULL");

            // exclude users
            if ( count($excludeIds) )
            {
                $event->addWhere('`base_user_table_alias`.`id` NOT IN(' . implode(', ', array_map('intval', $excludeIds)) . ')');
            }
        });

        $userIdList = USEARCH_BOL_Service::getInstance()->findUserIdListByQuestionValues($processedFilter, 0, $limit);
        $listId = 0;

        // remove current user from list
        if ($userIdList) {
            foreach ($userIdList as $key => $id) {
                if ($userId == $id ) {
                    unset($userIdList[$key]);
                }
            }
        }

        if (count($userIdList) > 0) {
            $listId = BOL_SearchService::getInstance()->saveSearchResult($userIdList);
        }

        return $listId;
    }

    /**
     * Find user location
     *
     * @param integer $userId
     * @return SKMOBILEAPP_BOL_UserLocation
     */
    public function findUserLocation($userId)
    {
        return $this->userLocationDao->findUserLocation($userId);
    }

    /**
     * Find users location
     *
     * @param $ids
     * @return array
     */
    public function findUsersLocation($ids)
    {
        return $this->userLocationDao->findUsersLocation($ids);
    }

    /**
     * Get new coordinates
     *
     * @param float $latitude
     * @param float $longitude
     * @param string $heading
     * @param integer $distance
     * @param string $distanceUnit
     * @return array
     */
    public function getNewCoordinates( $latitude, $longitude, $heading, $distance, $distanceUnit = null )
    {
        $heading = $heading == 'sw' ? 225 : 45;

        if ( !$distanceUnit )
        {
            $distanceUnit = $this->getDistanceUnits();
        }

        if ( $distanceUnit == self::DISTANCE_UNITS_KM )
        {
            $distance /= 1.609344;
        }

        $distance = $distance * 1000;
        $distance = sqrt((pow($distance, 2)) * 2);
        $distance = $distance / 6378137;
        $heading = $this->toRad($heading);
        $latitude = $this->toRad($latitude);
        $longitude = $this->toRad($longitude);

        $d = cos($distance);
        $f = sin($latitude);

        $latitude = cos($latitude);
        $g = $d * $f + $distance * $latitude * cos($heading);

        $newCoordinates = [];
        $newCoordinates['latitude'] = $this->toDeg(asin($g));
        $newCoordinates['longitude'] = $this->toDeg($longitude + atan2($distance * $latitude * sin($heading), $d - $f * $g));

        return $newCoordinates;
    }

    /**
     * Get distance
     *
     * @param float $lat
     * @param float $lon
     * @param float $lat1
     * @param float $lon1
     * @param string $unit
     * @return float
     */
    function distance($lat, $lon, $lat1, $lon1, $unit = null)
    {
        $start = array($lat, $lon);
        $finish = array($lat1, $lon1);

        $theta = $start[1] - $finish[1];
        $distance = (sin(deg2rad($start[0])) * sin(deg2rad($finish[0]))) + (cos(deg2rad($start[0])) * cos(deg2rad($finish[0])) * cos(deg2rad($theta)));
        $distance = acos($distance);
        $distance = rad2deg($distance);
        $distance = $distance * 60 * 1.1515;

        if ( empty($unit) )
        {
            $unit = $this->getDistanceUnits();
        }

        if ( $unit == self::DISTANCE_UNITS_KM )
        {
            $distance *= 1.609344;
        }

        return round($distance, 2);

    }

    /**
     * To rad
     *
     * @param integer|float $i
     * @return float
     */
    protected function toRad($i)
    {
        return $i * pi() / 180;
    }

    /**
     * To deg
     *
     * @param integer|float $i
     * @return float
     */
    protected function toDeg($i)
    {
        return $i * 180 / pi();
    }

    /**
     * Get distance units
     */
    public function getDistanceUnits()
    {
        if ( OW::getPluginManager()->isPluginActive('googlelocation') )
        {
            return GOOGLELOCATION_BOL_LocationService::getInstance()->getDistanseUnits();
        }

        return self::DISTANCE_UNITS_MILES;
    }

    /**
     * Get view questions
     *
     * @param array $userIds
     * @param array $exclude
     * @return array
     */
    public function getViewQuestions(array $userIds, array $exclude = [])
    {
        $questionService = BOL_QuestionService::getInstance();
        $userService = BOL_UserService::getInstance();

        $sortedSections = $questionService->findSortedSectionList();
        $viewSections  = [];

        // process user list
        foreach( $userIds as $userId )
        {
            $userDto = $userService->findUserById($userId);

            if ( $userDto )
            {
                // get all view questions
                $viewQuestionList = $userService->getUserViewQuestions($userId, false);

                // process questions
                foreach ( $viewQuestionList['questions'] as $sectionName => $section )
                {
                    $order = 0;

                    foreach ( $sortedSections as $sorted )
                    {
                        if ( $sorted->name == $sectionName )
                        {
                            $order = $sorted->sortOrder;
                        }
                    }

                    $viewSections[$userDto->getId()][$sectionName] = [
                        'order' => (int) $order,
                        'section' => $questionService->getSectionLang($sectionName),
                        'items' => []
                    ];
                }

                // fill sections with questions
                $data = $viewQuestionList['data'][$userId];
                foreach ( $viewQuestionList['questions'] as $sectName => $section )
                {
                    foreach ( $section as $question )
                    {
                        $name = $question['name'];

                        if (in_array($name, $exclude)) {
                            continue;
                        }

                        $value = is_array($data[$name]) ? implode(', ', $data[$name]) :  $data[$name];

                        // get new label
                        $event = new OW_Event('base.questions_field_get_label', [
                            'presentation' => $question['presentation'],
                            'fieldName' => $question['name'],
                            'configs' => $question['custom'],
                            'type' => 'view'
                        ]);

                        OW::getEventManager()->trigger($event);
                        $newLabel = $event->getData();

                        // get new value
                        $event = new OW_Event('base.questions_field_get_value', [
                            'presentation' => $question['presentation'],
                            'fieldName' => $question['name'],
                            'value' => $data[$name],
                            'questionInfo' => $question,
                            'userId' => $userId
                        ]);

                        OW::getEventManager()->trigger($event);
                        $newValue = $event->getData();

                        $viewSections[$userId][$sectName]['items'][] = [
                            'name' => $name,
                            'label' => !empty($newLabel) ? $newLabel : $questionService->getQuestionLang($name),
                            'value' => !empty($newValue) ? trim(strip_tags($newValue)) : trim(strip_tags($value))
                        ];
                    }
                }

                // sort sections
                usort($viewSections[$userDto->getId()], function( $el1, $el2 )
                {
                    if ( $el1['order'] === $el2['order'] )
                    {
                        return 0;
                    }

                    return $el1['order'] > $el2['order'] ? 1 : -1;
                });

            }
        }

        return $viewSections;
    }

    /**
     * Block user
     *
     * @param integer $userId
     * @param integer $recipientId
     * @return BOL_UserBlock
     */
    function blockUser($userId, $recipientId)
    {
        $dto = new BOL_UserBlock();
        $dto->setUserId($userId);
        $dto->setBlockedUserId($recipientId);

        BOL_UserBlockDao::getInstance()->save($dto);

        $event = new OW_Event(OW_EventManager::ON_USER_BLOCK, ['userId' => $userId, 'blockedUserId' => $recipientId]);
        OW::getEventManager()->trigger($event);

        return $dto;
    }

    /**
     * Unblock user
     *
     * @param integer $userId
     * @param integer $recipientId
     * @return void
     */
    public function unblockUser($userId, $recipientId)
    {
        $dto = BOL_UserBlockDao::getInstance()->findBlockedUser($userId, $recipientId);

        if ( $dto )
        {
            BOL_UserBlockDao::getInstance()->delete($dto);

            $event = new OW_Event(OW_EventManager::ON_USER_UNBLOCK, array('userId' => $userId, 'blockedUserId' => $recipientId));
            OW::getEventManager()->trigger($event);
        }
    }

    /**
     * Is user blocked
     *
     * @param integer $userId
     * @param integer $recipientId
     * @return bool
     */
    public function isUserBlocked($userId, $recipientId)
    {
        $dto = BOL_UserBlockDao::getInstance()->findBlockedUser($userId, $recipientId);

        return !empty($dto);
    }

    /**
     * @param integer $userId
     */
    public function loginEvents($userId)
    {
        $event = new OW_Event(OW_EventManager::ON_BEFORE_USER_LOGIN, array('userId' => $userId));
        OW::getEventManager()->trigger($event);

        $event = new OW_Event(OW_EventManager::ON_USER_LOGIN, array('userId' => $userId));
        OW::getEventManager()->trigger($event);
    }

    /**
     * Find active plugins
     *
     * @return array
     */
    protected function findActivePlugins()
    {
        $isInappsEnabled = boolval(OW::getConfig()->getValue('skmobileapp', 'inapps_enable'));
        $plugins = [];

        BOL_PluginService::getInstance()->clearPluginListCache();
        foreach ( BOL_PluginService::getInstance()->findActivePlugins() as $plugin )
        {
            // exclude membership and credits plugins if inapps are disabled
            if (!$isInappsEnabled && in_array($plugin->key, [self::MEMBERSHIP_PLUGIN_KEY, self::USER_CREDITS_PLUGIN_KEY])) {
                continue;
            }

            $plugins[] = $plugin->key;
        }

        return $plugins;
    }

    /**
     * Is application ready for usage
     *
     * @return array
     *      boolean is ready
     *      string  error message
     */
    public function isApplicationReadyForUsage()
    {
        // check the required plugins
        $requiredPlugins = [
            'mailbox',
            'photo',
            'usearch',
            'firebaseauth'
        ];

        $missingPlugin = [];

        foreach ( $requiredPlugins as $requiredPlugin )
        {
            if ( !OW::getPluginManager()->isPluginActive($requiredPlugin)  )
            {
                $missingPlugin[] = $requiredPlugin;
            }
        }

        if ( $missingPlugin )
        {
            return array(
                false,
                OW::getLanguage()->text('skmobileapp', 'missing_plugins_error', [
                    'plugins' => implode(', ', $missingPlugin)
                ])
            );
        }

        // check the google api key
        if ( !OW::getConfig()->getValue('skmobileapp', 'google_map_api_key') )
        {
            return array(
                false,
                OW::getLanguage()->text('skmobileapp', 'missing_google_api_setting_error', [
                    'url' => OW::getRouter()->urlForRoute('skmobileapp_admin_settings')
                ])
            );
        }

        // check monetizations settings
        if ( (bool) OW::getConfig()->getValue('skmobileapp', 'inapps_enable') )
        {
            $settings = [
                'inapps_apm_package_name',
                'inapps_itunes_shared_secret'
            ];

            foreach( $settings as $settingName )
            {
                if ( !OW::getConfig()->getValue('skmobileapp', $settingName) )
                {
                    return array(
                        false,
                        OW::getLanguage()->text('skmobileapp', 'missing_google_monetizations_settings_error', [
                            'url' => OW::getRouter()->urlForRoute('skmobileapp_admin_inapps')
                        ])
                    );
                }
            }
        }

        return array(
            true,
            null
        );
    }

    /**
     * Get application config
     *
     * @param boolean $refreshCache
     * @return array
     */
    public function getApplicationConfig($refreshCache = false)
    {
        // clean cache
        if ($refreshCache) {
            OW::getConfig()->generateCache();
        }

        $themeLogo = OW::getConfig()->getValue('skmobileapp', 'theme_logo');
        $themeBackground = OW::getConfig()->getValue('skmobileapp', 'theme_background');

        $configs = [
            'isDebugMode' => OW_DEBUG_MODE !== false,
            'isDemoModeActivated' => $this->isDemoModeActivated(),
            'searchMode' => OW::getConfig()->getValue('skmobileapp', 'search_mode'), // both, tinder, browse,
            'activePlugins' => $this->findActivePlugins(),
            'isSearchByUserNameActive' => (int) OW::getConfig()->getValue('usearch', 'enable_username_search') == 1,
            'isTosActive' => (int) OW::getConfig()->getValue('base', 'join_display_terms_of_use') == 1,
            'isAvatarRequired' => OW::getConfig()->getValue('base', 'join_display_photo_upload') == 'display_and_required',
            'isAvatarHidden' => OW::getConfig()->getValue('base', 'join_display_photo_upload') == 'not_display',
            'defaultAvatar' => $this->getDefaultAvatar(),
            'bigDefaultAvatar' => $this->getDefaultAvatar(true),
            'minPasswordLength' => UTIL_Validator::PASSWORD_MIN_LENGTH,
            'maxPasswordLength' => UTIL_Validator::PASSWORD_MAX_LENGTH,
            'maintenanceMode' => (bool) OW::getConfig()->getValue('base', 'maintenance'),
            'validationDelay' => 1000,
            'emailRegexp' => substr(UTIL_Validator::EMAIL_PATTERN, 1, strlen(UTIL_Validator::EMAIL_PATTERN) - 2),
            'urlRegexp' => substr(UTIL_Validator::URL_PATTERN, 1, strlen(UTIL_Validator::URL_PATTERN) - 2),
            'validImageMimeTypes' => self::VALID_IMAGE_MIME_TYPES,
            'androidAdUnitId' => OW::getConfig()->getValue('skmobileapp', 'android_ad_unit_id'),
            'iosAdUnitId' => OW::getConfig()->getValue('skmobileapp', 'ios_ad_unit_id'),
            'isAdmobEnabled' => (bool) OW::getConfig()->getValue('skmobileapp', 'ads_enabled'),
            'admobPages' => $this->getAdmobEnabledPagesData(),
            'vapidKey' => OW::getConfig()->getValue('skmobileapp', 'pn_vapid_key'),
            'toastDuration' => 3000,
            'showOnlineOnlyInSearch' => true,
            'showWithPhotoOnlyInSearch' => true,
            'profilePhotosLimit' => 4,
            'messagesLimit' => 16,
            'installPwaBannerShortPeriod' => 300, // 5 minutes
            'installPwaBannerLongPeriod' => 3600 * 24 * 9999,
            'tinderSearchTimeout' => 30000,
            'billingCurrency' => OW::getConfig()->getValue('base', 'billing_currency'),
            'avatarMaxUploadSize' => $this->getAvatarMaxUploadSize(),
            'attachMaxUploadSize' => $this->getAttachmentMaxUploadSize(),
            'photoMaxUploadSize' => $this->getPhotoMaxUploadSize(),
            'defaultTinderFilterLocationMin' => 5,
            'defaultTinderFilterLocationMax' => 100,
            'defaultTinderFilterLocationStep' => 10,
            'defaultTinderFilterDistanceUnit' => $this->getDistanceUnits(),
            'defaultTinderFilterDefaultMinAge' => 18,
            'defaultTinderFilterDefaultMaxAge' => 100,
            'themeLogo' => $themeLogo ? $this->getBaseThemeFileUrl() . $themeLogo : null,
            'themeLogoWidth' => (int) OW::getConfig()->getValue('skmobileapp', 'theme_logo_width'),
            'themeBackground' => $themeBackground ? $this->getBaseThemeFileUrl() . $themeBackground : null,
        ];

        $event = new OW_Event('skmobileapp.get_application_config', [], $configs);
        $applicationConfig = OW::getEventManager()->trigger($event);

        return $applicationConfig->getData();
    }

    /**
     * Get user id, username and email for token
     *
     * @param integer $userId
     * @throws Exception
     * @return array
     */
    public function getUserDataForToken( $userId )
    {
        $userDto = BOL_UserService::getInstance()->findUserById($userId);

        $isAdmin = BOL_AuthorizationService::getInstance()->isActionAuthorized(
            BOL_AuthorizationService::ADMIN_GROUP_NAME,
            null,
            [
                'userId' => $userDto->id
            ]
        );

        if ($userDto)
        {
            return [
                'id' => $userDto->id,
                'name' => $userDto->username,
                'email' => $userDto->email,
                'isAdmin' => $isAdmin
            ];
        }

        throw new Exception('User not found');
    }

    /**
     * Update user location
     *
     * @param integer $userId
     * @param float $latitude
     * @param float $longitude
     * @param integer $distance (in miles)
     * @return array
     */
    public function updateUserLocation( $userId, $latitude, $longitude, $distance = self::DEFAULT_USER_LOCATION_DISTANCE )
    {
        // we always store coordinates in DB in miles
        $southWest = $this->getNewCoordinates($latitude, $longitude, 'sw', $distance, self::DISTANCE_UNITS_MILES);
        $northEast = $this->getNewCoordinates($latitude, $longitude, 'ne', $distance, self::DISTANCE_UNITS_MILES);

        return $this->userLocationDao->updateUserLocation($userId, $latitude, $longitude, $southWest, $northEast);
    }

    /**
     * Delete user data
     *
     * @param integer $userId
     * @return void
     */
    public function deleteUserData( $userId )
    {
        // clear devices
        SKMOBILEAPP_BOL_DeviceDao::getInstance()->removeUserDevices( $userId );

        // clear locations
        $this->userLocationDao->deleteUserLocation( $userId );

        // clear matches
        $this->userMatchActionDao->deleteAllMatchesByUserId( $userId );
    }

    /**
     * Conversion a language tag
     *
     * @param string $id
     * @return string
     */
    public function conversionLang( $id )
    {
        if ( empty($id) )
        {
            return $id;
        }

        $event = new OW_Event('skmobileapp.lang_tags_conversion', [], $this->langTagsConversion);
        $eventManager = OW::getEventManager()->trigger($event);
        $this->langTagsConversion = $eventManager->getData();

        if ( isset($this->langTagsConversion[$id]) )
        {
            return $this->langTagsConversion[$id];
        }

        return $id;
    }

    /**
     * Internal user authenticate
     *
     * @param string $userId
     * @return void
     */
    public function internalUserAuthenticate($userId)
    {
        $onUserLoginCallback = function (OW_Event $e) {
            $e->stopPropagation();
        };

        OW::getEventManager()->bind(OW_EventManager::ON_USER_LOGIN, $onUserLoginCallback, 1);

        OW::getUser()->authenticate(new SKMOBILEAPP_CLASS_AuthAdapter($userId));

        OW::getEventManager()->unbind(OW_EventManager::ON_USER_LOGIN, $onUserLoginCallback);

        // set time zone
        $timeZone = BOL_PreferenceService::getInstance()->getPreferenceValue('timeZoneSelect', $userId);

        if ( !empty($timeZone ))
        {
            date_default_timezone_set($timeZone);
            OW::getDbo()->setTimezone();
        }
    }

     /**
      * Get skmobileapp admob pages.
      *
      * @return array[]
      */
    public function getPluginAdmobPages()
    {
        return [
            'upgrades' => [
                'adsEnabled' => true,
                'pluginKey' => 'skmobileapp',
                'langKey' => 'admob_page_upgrades',
                'regex' => [
                    '^\/upgrades'
                ]
            ],
            'guests' => [
                'adsEnabled' => true,
                'pluginKey' => 'skmobileapp',
                'langKey' => 'admob_page_guests',
                'regex' => [
                    '^\/guests$'
                ]
            ],
            'bookmarks' => [
                'adsEnabled' => true,
                'pluginKey' => 'skmobileapp',
                'langKey' => 'admob_page_bookmarks',
                'regex' => [
                    '^\/bookmarks$'
                ]
            ],
            'compatible_users' => [
                'adsEnabled' => true,
                'pluginKey' => 'skmobileapp',
                'langKey' => 'admob_page_compatible_users',
                'regex' => [
                    '^\/compatible-users$'
                ]
            ],
            'edit' => [
                'adsEnabled' => true,
                'pluginKey' => 'skmobileapp',
                'langKey' => 'admob_page_edit',
                'regex' => [
                    '^\/edit$'
                ]
            ],
            'edit_photos' => [
                'adsEnabled' => true,
                'pluginKey' => 'skmobileapp',
                'langKey' => 'admob_page_edit_photos',
                'regex' => [
                    '^\/edit\/photos$'
                ]
            ],
            'forgot_password' => [
                'adsEnabled' => true,
                'pluginKey' => 'skmobileapp',
                'langKey' => 'admob_page_forgot_password',
                'regex' => [
                    '^\/forgot-password$'
                ]
            ],
            'forgot_password_verify_code' => [
                'adsEnabled' => true,
                'pluginKey' => 'skmobileapp',
                'langKey' => 'admob_page_forgot_password_verify_code',
                'regex' => [
                    '^\/forgot-password\/verify-code(\/.*)?$',
                ]
            ],
            'forgot_password_verify_code_new_password' => [
                'adsEnabled' => true,
                'pluginKey' => 'skmobileapp',
                'langKey' => 'admob_page_forgot_password_verify_code_new_password',
                'regex' => [
                    '^\/forgot-password\/new-password$'
                ]
            ],
            'join' => [
                'adsEnabled' => true,
                'pluginKey' => 'skmobileapp',
                'langKey' => 'admob_page_join',
                'regex' => [
                    '^\/join$'
                ]
            ],
            'join_finalize' => [
                'adsEnabled' => true,
                'pluginKey' => 'skmobileapp',
                'langKey' => 'admob_page_join_finalize',
                'regex' => [
                    '^\/join\/finalize$',
                ]
            ],
            'login' => [
                'adsEnabled' => true,
                'pluginKey' => 'skmobileapp',
                'langKey' => 'admob_page_login',
                'regex' => null
            ],
            'messages' => [
                'adsEnabled' => true,
                'pluginKey' => 'skmobileapp',
                'langKey' => 'admob_page_messages',
                'regex' => [
                    '^\/messages/\d+$'
                ]
            ],
            'privacy_policy' => [
                'adsEnabled' => true,
                'pluginKey' => 'skmobileapp',
                'langKey' => 'admob_page_privacy_policy',
                'regex' => [
                    '^\/privacy-policy$'
                ]
            ],
            'profiles' => [
                'adsEnabled' => true,
                'pluginKey' => 'skmobileapp',
                'langKey' => 'admob_page_profiles',
                'regex' => [
                    '^\/profiles\/\d+$'
                ]
            ],
            'settings' => [
                'adsEnabled' => true,
                'pluginKey' => 'skmobileapp',
                'langKey' => 'admob_page_settings',
                'regex' => [
                    '^\/settings$'
                ]
            ],
            'settings_contact_us' => [
                'adsEnabled' => true,
                'pluginKey' => 'skmobileapp',
                'langKey' => 'admob_page_settings_contact_us',
                'regex' => [
                    '^\/settings\/contact-us$'
                ]
            ],
            'settings_change_password' => [
                'adsEnabled' => true,
                'pluginKey' => 'skmobileapp',
                'langKey' => 'admob_page_settings_change_password',
                'regex' => [
                    '^\/settings\/change-password$'
                ]
            ],
            'settings_email_notifications' => [
                'adsEnabled' => true,
                'pluginKey' => 'skmobileapp',
                'langKey' => 'admob_page_settings_email_notifications',
                'regex' => [
                    '^\/settings\/email-notifications$'
                ]
            ],
            'settings_push_notifications' => [
                'adsEnabled' => true,
                'pluginKey' => 'skmobileapp',
                'langKey' => 'admob_page_settings_push_notifications',
                'regex' => [
                    '^\/settings\/push-notifications$'
                ]
            ],
            'settings_third_party' => [
                'adsEnabled' => true,
                'pluginKey' => 'skmobileapp',
                'langKey' => 'admob_page_settings_third_party',
                'regex' => [
                    '^\/settings\/third-party$'
                ]
            ],
            'settings_user_data' => [
                'adsEnabled' => true,
                'pluginKey' => 'skmobileapp',
                'langKey' => 'admob_page_settings_user_data',
                'regex' => [
                    '^\/settings\/user-data$'
                ]
            ],
            'terms_of_use' => [
                'adsEnabled' => true,
                'pluginKey' => 'skmobileapp',
                'langKey' => 'admob_page_terms_of_use',
                'regex' => [
                    '^\/terms-of-use$'
                ]
            ],
            'dashboard' => [
                'adsEnabled' => true,
                'pluginKey' => 'skmobileapp',
                'langKey' => 'admob_page_dashboard',
                'regex' => null
            ]
        ];
    }

    /**
     * Collect Admob-supporting pages data.
     *
     * @return array
     */
    public function collectAdmobPages()
    {
        // Retrieve saved page data from the database.
        $config = OW::getConfig();
        $savedPages = json_decode($config->getValue('skmobileapp', 'admob_pages'), true);

        // Collect page data from plugins.
        $admobPagesCollectorEvent = new SKMOBILEAPP_CLASS_AdmobPagesEventCollector(
            SKMOBILEAPP_CLASS_AdmobPagesEventCollector::EVENT_NAME
        );

        OW::getEventManager()->trigger($admobPagesCollectorEvent);

        $collectedPages = $admobPagesCollectorEvent->getData();

        // If there are no saved page data, save the collected data and return it.
        if (empty($savedPages)) {
            $config->saveConfig('skmobileapp', 'admob_pages', json_encode($collectedPages));
            return $collectedPages;
        }

        // Otherwise, get deleted pages by computing the difference in keys between the saved pages and the collected
        // pages array. If a key is present in the saved pages but is absent in the collected pages, it is considered
        // to be deleted.
        $deletedPages = array_diff_key($savedPages, $collectedPages);

        // Form the final pages array.
        $newPages = array_reduce(array_keys($collectedPages), function ($prev, $pageId) use (
            $savedPages,
            $collectedPages,
            $deletedPages
        ) {
            if (!isset($deletedPages[$pageId])) {
                $oldPatterns = isset($savedPages[$pageId]) ? ($savedPages[$pageId]['regex'] ?? []) : [];
                $newPatterns = $collectedPages[$pageId]['regex'] ?? [];

                // Determine whether two arrays contain same patterns.
                $patternsAreSame =
                    count($oldPatterns) == count($newPatterns) &&
                    array_reduce($oldPatterns, function ($prev, $regex) use ($newPatterns) {
                        return !$prev ? $prev : in_array($regex, $newPatterns);
                    }, true);

                return array_merge($prev, [
                    $pageId => $patternsAreSame ? $savedPages[$pageId] : $collectedPages[$pageId]
                ]);
            }

            return $prev;
        }, []);

        $config->saveConfig('skmobileapp', 'admob_pages', json_encode($newPages));

        return $newPages;
    }

     /**
      * Update saved admob pages data.
      *
      * The $pages parameter should contain a [ pageId => updatePageData ] mapping. `updatePageData` structure may
      * contain any `pageData` key and the updated value. See `SKMOBILEAPP_CLASS_AdmobPagesEventCollector::add()` method
      * docs for the `pageData` structure reference.
      *
      * @param array $pages [ pageId => updatedPageData ] mapping.
      * @see SKMOBILEAPP_CLASS_AdmobPagesEventCollector::add()
      */
    public function updateAdmobPages($pages)
    {
        $oldPages = $this->collectAdmobPages();

        $newPages = array_reduce(array_keys($oldPages), function ($prev, $pageId) use ($oldPages, $pages) {
            return array_merge($prev, [
                $pageId => array_merge($oldPages[$pageId], $pages[$pageId])
            ]);
        }, []);

        OW::getConfig()->saveConfig('skmobileapp', 'admob_pages', json_encode($newPages));
    }

    /**
     * Get page data for pages that have ads enabled.
     *
     * @return array
     */
    public function getAdmobEnabledPagesData()
    {
        $config = OW::getConfig();
        $pages = json_decode($config->getValue('skmobileapp', 'admob_pages'), true);

        return array_reduce(array_keys($pages), function ($prev, $pageId) use ($pages) {
            $patterns = $pages[$pageId]['regex'] ?? [];

            if ($pages[$pageId]['adsEnabled']) {
                $mergedData = empty($patterns)
                    ? [$pageId => null]
                    : [
                        $pageId => implode('|', array_map(function ($regex) {
                            return '(' . $regex . ')';
                        }, $patterns))
                    ];

                return array_merge($prev, $mergedData);
            }

            return $prev;
        }, []);
    }
}
