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
 * Represents a notification received in a webhook handler.
 */
abstract class SKMOBILEAPP_BOL_AbstractWebhookNotification
{
    /**
     * Construct a notification entity instance from JSON.
     *
     * @param array $json JSON data.
     *
     * @throws InvalidArgumentException Thrown when the JSON data is invalid.
     *
     * @return SKMOBILEAPP_BOL_AbstractWebhookNotification
     */
    static abstract function fromJson($json);
}
