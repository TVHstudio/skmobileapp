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

use SKMOBILEAPP_BOL_Service;

abstract class Base implements IChannel
{
    /**
     * Previous data hash
     *
     * @var string|null
     */
    protected $prevDataHash = null;

    /**
     * Service
     *
     * @var SKMOBILEAPP_BOL_Service
     */
    protected $service;

    /**
     * Configs constructor.
     */
    public function __construct() {
        $this->service = SKMOBILEAPP_BOL_Service::getInstance();
    }

    /**
     * @param string $hash
     *
     * @return self
     */
    public function setPreviousDataHash($hash) {
        $this->prevDataHash = $hash;

        return $this;
    }

    /**
     * @return string
     */
    public function getPreviousDataHash() {
        return $this->prevDataHash;
    }

    /**
     * Detect changes
     *
     * @param mixed $data
     *
     * @return mixed|null
     */
    public function detectChanges($data) {
        if (is_null($this->prevDataHash)
            || $this->prevDataHash !== $this->generateDataHash($data)) {

            // update the chanel's hash
            if ($data) {
                $this->setPreviousDataHash($this->generateDataHash($data));
            }

            return true;
        }

        return false;
    }

    /**
     * @param array $data
     *
     * @return string
     */
    protected function generateDataHash(array $data) {
        return md5(serialize($data));
    }
}
