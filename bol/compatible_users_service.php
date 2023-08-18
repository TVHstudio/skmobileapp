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

class SKMOBILEAPP_BOL_CompatibleUsersService extends SKMOBILEAPP_BOL_Service
{
    use OW_Singleton;

    /**
     * Find users
     *
     * @param integer $userId
     * @param integer $limit
     * @return array
     */
    public function findUsers($userId, $limit)
    {
        $users = MATCHMAKING_BOL_Service::getInstance()->findMatchList($userId, 0, $limit, 'compatible');

        foreach ($users as $index => $user)
        {
            $users[$index]['compatibility'] = (int) MATCHMAKING_BOL_Service::getInstance()->getCompatibilityByValue($user['compatibility']);
        }

        $processedUsers = $users
            ? $this->formatUserData($userId, $users)
            : [];

        return $processedUsers;
    }

    /**
     * Format user data
     *
     * @param integer $userId
     * @param array $users
     * @return array
     */
    public function formatUserData($userId, array $users)
    {
        $processedUsers = [];
        $ids = [];

        // process users
        foreach( $users as $user ) 
        {
            $ids[] = $user['id'];
            $processedUsers[$user['id']] = [
                'id' => (int) $user['id'],
                'user' => [
                    'id' => (int) $user['id'],
                    'userName' => null,
                    'compatibility' => $user['compatibility'],
                    'avatar' => null,
                    'matchAction' => null,
                ]
            ];
        }

        // load matches
        $mathList = SKMOBILEAPP_BOL_UserMatchActionDao::getInstance()->findUserMatchActionsByUserIdList($userId, $ids);

        foreach( $mathList as $matchAction ) 
        {
            $processedUsers[$matchAction->recipientId]['user']['matchAction'] = [
                'id' => (int) $matchAction->id,
                'type' => $matchAction->type,
                'userId' => (int) $matchAction->recipientId,
                'isMutual' => boolval($matchAction->mutual),
                'createStamp' => (int) $matchAction->createStamp,
                'isRead' => boolval($matchAction->read),
                'isNew' => boolval($matchAction->new)
            ];
        }

        // load avatars
        $avatarList = BOL_AvatarService::getInstance()->findByUserIdList($ids);

        foreach( $avatarList as $avatar ) 
        {
            $processedUsers[$avatar->userId]['user']['avatar'] = $this->getAvatarData($avatar, false);
        }

        // load user names
        $userNames = BOL_UserService::getInstance()->getUserNamesForList($ids);

        foreach( $userNames as $userId => $userName ) 
        {
            $processedUsers[$userId]['user']['userName'] = $userName;
        }

        // load display names
        $displayNames = BOL_UserService::getInstance()->getDisplayNamesForList($ids);

        foreach( $displayNames as $userId => $displayName ) 
        {
            if ( $displayName ) 
            {
                $processedUsers[$userId]['user']['userName'] = $displayName;
            }
        }

        $data = [];
        foreach( $processedUsers as $userData ) 
        {
            $data[] = $userData;
        }

        $event = new OW_Event('skmobileapp.formatted_compatible_users_data', [], $data);
        OW_EventManager::getInstance()->trigger($event);

        return $event->getData();
    }
}