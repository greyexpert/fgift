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
class FGIFT_CLASS_NewsfeedBridge
{
    /**
     * Singleton instance.
     *
     * @var FGIFT_CLASS_NewsfeedBridge
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return FGIFT_CLASS_NewsfeedBridge
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }
    
    public function isActive()
    {
        return OW::getPluginManager()->isPluginActive("newsfeed");
    }
    
    public function onItemRender( OW_Event $event )
    {
        $params = $event->getParams();
        $data = $event->getData();
        
        if ( $params["action"]["entityType"] != "user_gift" )
        {
            return;
        }
        
        $giftId = $params["action"]["entityId"];
        $data["contextMenu"] = empty($data["contextMenu"]) ? array() : $data["contextMenu"];
        $realGift = FGIFT_CLASS_GiftsBridge::getInstance()->findGiftById($giftId);
        if ( $realGift === null )
        {
            return;
        }
        
        /*@var $giftDto VIRTUALGIFTS_BOL_UserGift */
        $giftDto = $realGift["dto"];
        
        if ( $giftDto->recipientId != OW::getUser()->getId() && !OW::getUser()->isAuthorized("fgift") )
        {
            return;
        }
        
        $uniqId = "fgifts-set-gift-" . $giftDto->id;
        $rsp = OW::getRouter()->urlFor("FGIFT_CTRL_Main", "ajax");
        $text = OW::getLanguage()->text("fgift", "set_gift_label");
        $action = "setGift";

        $fGift = FGIFT_BOL_Service::getInstance()->findGift($giftDto->recipientId);

        if ( $fGift !== null && $fGift->giftId == $giftDto->id )
        {
            $text = OW::getLanguage()->text("fgift", "unset_gift_label");
            $action = "unsetGift";
        } 
        else if ( !FGIFT_CLASS_CreditsBridge::getInstance ()->credits->isAvaliable("add_gift") )
        {
            return;
        }

        $data["contextMenu"][] = array(
            'label' => $text,
            'attributes' => array(
                'id' => $uniqId,
                "data-action" => $action,
                "data-text" => $text
            )
        );
        
        $event->setData($data);
        
        $js = UTIL_JsGenerator::composeJsString('(function() { 
            $("#' . $uniqId . '").click(function() {
                var self = $(this);
                window.FGIFT_CC = window.FGIFT_CC || {$available};
                if ( self.attr("data-action") == "setGift" && window.FGIFT_CC && window.FGIFT_CC !== true ) {
                    OW.warning(window.FGIFT_CC);

                    return false;
                }
                
                if ( self.attr("data-action") == "setGift" )
                {
                    if (window.FGIFT_setGift) {
                        window.FGIFT_setGift({$giftSrc}, {$giftUrl});
                    }
                }
                else
                {
                    if (window.FGIFT_unsetGift) {
                        window.FGIFT_unsetGift();
                    }
                }

                $.getJSON({$rsp}, {giftId: {$giftId}, userId: {$userId}, action: self.attr("data-action")}, function(r) { 
                    OW[r.type](r.msg);
                    window.FGIFT_CC = r.avaliable;
                });

                self.attr("data-text", self.attr("data-text") == {$textSet} ? {$textUnset} : {$textSet});
                self.attr("data-action", self.attr("data-action") == "setGift" ? "unsetGift" : "setGift");
                self.text(self.attr("data-text"));
        }); })();', array(
            "rsp" => $rsp,
            "giftId" => $giftDto->id,
            "userId" => $giftDto->recipientId,
            "action" => $action,
            "text" => $text,
            "textSet" => OW::getLanguage()->text("fgift", "set_gift_label"),
            "textUnset" => OW::getLanguage()->text("fgift", "unset_gift_label"),
            "giftSrc" => $realGift["imageUrl"],
            "giftUrl" => $realGift["url"],
            "available" => FGIFT_CLASS_CreditsBridge::getInstance()->getAvailable()
        ));

        OW::getDocument()->addOnloadScript($js);
    }
    
    public function init()
    {
        if ( !$this->isActive() )
        {
            return;
        }
        
        OW::getEventManager()->bind("feed.on_item_render", array($this, 'onItemRender'));
    }
}