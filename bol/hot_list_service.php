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

class SKMOBILEAPP_BOL_HotListService extends SKMOBILEAPP_BOL_Service
{
    use OW_Singleton;

    /**
     * Class constructor
     */
    private function __construct()
    {
        $this->userMatchActionDao = SKMOBILEAPP_BOL_UserMatchActionDao::getInstance();
        $this->userLocationDao = SKMOBILEAPP_BOL_UserLocationDao::getInstance();
    }

    /**
     * Find user
     *
     * @param $userId
     *
     * @return mixed
     */
    public function findUser($userId)
    {
        $hotListDao = HOTLIST_BOL_UserDao::getInstance();
        $example = new OW_Example();
        $example->andFieldEqual('userId', $userId);

        return $hotListDao->findObjectByExample($example);
    }

    /**
     * Format hot list data
     *
     * @param int   $loggedUserId
     * @param array $hotListUsers
     *
     * @return array
     */
    public function formatHotListData($loggedUserId, array $hotListUsers)
    {
        $processedUsers = [];
        $ids = [];

        // process users
        foreach ($hotListUsers as $hotList) {
            $userDto = BOL_UserService::getInstance()->findUserById(
                $hotList->userId
            );

            // skip deleted users
            if (empty($userDto)) {
                continue;
            }

            $ids[] = $hotList->userId;

            $processedUsers[$hotList->userId] = [
                'id'     => (int)$hotList->id,
                'user'   => [
                    'id'       => (int)$hotList->userId,
                    'userName' => null,
                    'isOnline' => false,
                    'age'      => 0,
                    'distance' => null,
                    'avatar'   => null,
                ]
            ];
        }

        // load avatars
        $avatarList = BOL_AvatarService::getInstance()->findByUserIdList($ids);

        foreach ($avatarList as $avatar) {
            $processedUsers[$avatar->userId]['user']['avatar'] = $this->getAvatarData(
                $avatar, false
            );
        }

        // load user names
        $userNames = BOL_UserService::getInstance()->getUserNamesForList($ids);

        foreach ($userNames as $userId => $userName) {
            $processedUsers[$userId]['user']['userName'] = $userName;
        }

        // load display names
        $displayNames = BOL_UserService::getInstance()->getDisplayNamesForList(
            $ids
        );

        foreach ($displayNames as $userId => $displayName) {
            if ($displayName) {
                $processedUsers[$userId]['user']['userName'] = $displayName;
            }
        }

        // find online statuses
        $onlineStatuses = BOL_UserService::getInstance(
        )->findOnlineStatusForUserList($ids);

        foreach ($onlineStatuses as $userId => $isOnline) {
            $processedUsers[$userId]['user']['isOnline'] = (bool)$isOnline;
        }

        // find ages
        $questionList = BOL_QuestionService::getInstance()->getQuestionData(
            $ids, ['birthdate']
        );

        foreach ($questionList as $userId => $questions) {
            if (isset($questions['birthdate'])) {
                $date = UTIL_DateTime::parseDate(
                    $questions['birthdate'],
                    UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT
                );
                $processedUsers[$userId]['user']['age'] = UTIL_DateTime::getAge(
                    $date['year'], $date['month'], $date['day']
                );
            }
        }

        // find location
        $loggedUserLocation = $this->findUserLocation($loggedUserId);

        if ($loggedUserLocation) {
            $distanceUnit = $this->getDistanceUnits();
            $usersLocation = $this->findUsersLocation($ids);

            foreach ($usersLocation as $userLocation) {
                if ($userLocation->userId != $loggedUserId) {
                    $distance = $this->distance(
                        $loggedUserLocation->latitude,
                        $loggedUserLocation->longitude, $userLocation->latitude,
                        $userLocation->longitude, $distanceUnit
                    );

                    $processedUsers[$userLocation->userId]['user']['distance'] = [
                        'distance' => (int)$distance < 1 ? 1 : (int)$distance,
                        'unit'     => $distanceUnit
                    ];
                }
            }
        }

        $data = [];
        foreach ($processedUsers as $userData) {
            $data[] = $userData;
        }

        $event = new OW_Event(
            'skmobileapp.formatted_hotlist_users_data', [], $data
        );
        OW_EventManager::getInstance()->trigger($event);

        return $event->getData();
    }
}
