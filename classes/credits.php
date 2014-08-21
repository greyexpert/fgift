<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2012, Sergey Kambalin
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package fgift.classes
 */
class FGIFT_CLASS_Credits
{
    const ACTION_ADD_GIFT = 'add_gift';

    public $allActions = array();

    private $actions;

    public function __construct()
    {
        $this->actions[] = array('pluginKey' => 'fgift', 'action' => "add_fgift", 'amount' => 0);

        $this->allActions = array(
            self::ACTION_ADD_GIFT
        );
        
        $this->authActions[self::ACTION_ADD_GIFT] = 'add_fgift';
    }

    public function bindCreditActionsCollect( BASE_CLASS_EventCollector $e )
    {
        foreach ( $this->actions as $action )
        {
            $e->add($action);
        }
    }

    public function triggerCreditActionsAdd()
    {
        $e = new BASE_CLASS_EventCollector('usercredits.action_add');

        foreach ( $this->actions as $action )
        {
            $e->add($action);
        }

        OW::getEventManager()->trigger($e);
    }

    public function isAvaliable( $action )
    {
        if ( OW::getUser()->isAuthorized('fgift', $action) )
        {
            return true;
        }
        
        return $this->isPromoted($action);
    }
    
    public function isPromoted( $action )
    {
        $status = BOL_AuthorizationService::getInstance()->getActionStatus('fgift', $action);
        
        return $status['status'] == BOL_AuthorizationService::STATUS_PROMOTED;
    }

    public function getErrorMessage( $action )
    {
        $status = BOL_AuthorizationService::getInstance()->getActionStatus('fgift', $action);
        
        if ( $status['status'] == BOL_AuthorizationService::STATUS_PROMOTED )
        {
            return $status['msg'];
        }
        
        return null;
    }

    public function trackUse( $action )
    {
        BOL_AuthorizationService::getInstance()->trackAction('fgift', $action);
    }
    
    public function getActionKey( OW_Event $e )
    {
        $params = $e->getParams();
        $authAction = $params['actionName'];

        if ( $params['groupName'] != 'fgift' )
        {
            return;
        }

        if ( !empty($this->authActions[$authAction]) )
        {
            $e->setData($this->authActions[$authAction]);
        }
    }
}