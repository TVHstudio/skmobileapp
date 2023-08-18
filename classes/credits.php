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
class SKMOBILEAPP_CLASS_Credits
{
    /**
     * Actions
     *
     * @var array
     */
    private $actions;

    /**
     * Auth actions
     *
     * @var array
     */
    private $authActions = array();

    /**
     * Class constructor
     */
    public function __construct()
    {
        // register credits actions
        $this->actions[] = array('pluginKey' => 'skmobileapp', 'action' => 'tinder_filters', 'amount' => 0);

        $this->authActions['tinder_filters'] = 'tinder_filters';
    }

    /**
     * Bind credit action collect
     *
     * @param BASE_CLASS_EventCollector $e
     * @return void
     */
    public function bindCreditActionsCollect( BASE_CLASS_EventCollector $e )
    {
        foreach ( $this->actions as $action )
        {
            $e->add($action);
        }
    }

    /**
     * Trigger credit actions
     *
     * @return void
     */
    public function triggerCreditActionsAdd()
    {
        $e = new BASE_CLASS_EventCollector('usercredits.action_add');

        foreach ( $this->actions as $action )
        {
            $e->add($action);
        }

        OW::getEventManager()->trigger($e);
    }
}