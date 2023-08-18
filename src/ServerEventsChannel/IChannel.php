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
namespace Skadate\Mobile\ServerEventsChannel;

interface IChannel
{
    /**
     * @param string $hash
     *
     * @return self
     */
    public function setPreviousDataHash($hash);

    /**
     * @return string
     */
    public function getPreviousDataHash();

    /**
     * Detect changes
     *
     * @param mixed $data
     *
     * @return boolean
     */
    public function detectChanges($data);

    /**
     * Detect changes
     *
     * @param integer $userId
     *
     * @return mixed|null
     */
    public function getData($userId = null);

    /**
     * Get name
     *
     * @return string
     */
    public function getName();
}

