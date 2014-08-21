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
class FGIFT_CLASS_GiftsBridge
{
    /**
     * Singleton instance.
     *
     * @var FGIFT_CLASS_GiftsBridge
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return FGIFT_CLASS_GiftsBridge
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }
    
    private function __construct() 
    {
    }
    
    public function isActive()
    {
        return OW::getPluginManager()->isPluginActive("virtualgifts");
    }
    
    public function getGiftByUserId( $userId ) 
    {
        $gift = FGIFT_BOL_Service::getInstance()->findGift($userId);
        
        if ( $gift === null )
        {
            return null;
        }
        
        return $this->getGiftById($gift->giftId);
    }
    
    public function getGiftById( $giftId )
    {
        return $this->findGiftById($giftId);
    }
    
    public function findGiftById( $giftId )
    {
        $gift = VIRTUALGIFTS_BOL_VirtualGiftsService::getInstance()->findUserGiftById($giftId);
        if ( $gift !== null )
        {
            $gift["url"] = OW::getRouter()->urlForRoute("virtual_gifts_view_gift", array(
                "giftId" => $giftId
            ));
        }
        
        return $gift;
    }
    
    public function onCollectContent( BASE_CLASS_EventCollector $event )
    {
        $params = $event->getParams();
        if ( $params["placeName"] != BOL_ComponentService::PLACE_PROFILE || empty($params["entityId"]) )
        {
            return;
        }

        $userId = $params["entityId"];
        $staticUrl = OW::getPluginManager()->getPlugin('fgift')->getStaticUrl();
        OW::getDocument()->addStyleSheet($staticUrl . 'fgift.css');
        
        $selector = '#avatar-console, .ow_avatar_console';
        
        $js = UTIL_JsGenerator::newInstance();
        $js->addScript('window.FGIFT_setGift = function (src, url) { 
            if ( $("' . $selector . '").find(".fgift-gift").length ) {
                $("' . $selector . '").find(".fgift-gift").html("<img src=\"" + src + "\" />");
                $("' . $selector . '").find(".fgift-gift").attr("href", url);
            }        
            else
                $("' . $selector . '").prepend("<a href=\"" + url + "\" class=\"fgift-gift\"><img src=\"" + src + "\" /></div>");
        };');
        
        $js->addScript('window.FGIFT_unsetGift = function () { 
            $("' . $selector . '").find(".fgift-gift").empty();
        };');
        
        OW::getDocument()->addOnloadScript($js);
        
        $gift = $this->getGiftByUserId($userId);
        
        if ( empty($gift) )
        {
            return null;
        }
        
        if ( $userId != OW::getUser()->getId() && !OW::getUser()->isAuthorized("fgift") && !OW::getUser()->isAuthorized("fgift", "view_gift") )
        {
            return null;
        }
        
        $src = $gift["imageUrl"];
        $url = $gift["url"];
        
        $js = UTIL_JsGenerator::newInstance()->addScript('window.FGIFT_setGift({$src}, {$url});', array(
            "src" => $src,
            "url" => $url
        ));
        
        OW::getDocument()->addOnloadScript($js);
    }
    
    public function onNotificationRender( OW_Event $event )
    {
        $params = $event->getParams();
        
        if ( $params["entityType"] != "virtualgifts_send_gift" )
        {
            return;
        }
        
        $gift = FGIFT_CLASS_GiftsBridge::getInstance()->findGiftById($params["entityId"]);
        
        if ( $gift === null )
        {
            return;
        }
        
        $giftDto = $gift["dto"];
        $data = $event->getData();
        
        $uniqId = uniqid("fgift-");
        
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
        
        $data["toolbar"] = empty($data["toolbar"]) ? array() : $data["toolbar"];
        $data["toolbar"][] = array(
            "label" => $text,
            "id" => $uniqId
        );
        
        $js = UTIL_JsGenerator::newInstance();
        $js->addScript(
            '$("#' . $uniqId . '").attr("data-action", {$action});
            $("#' . $uniqId . '").attr("data-text", {$text});',
        array(
            "text" => $text,
            "action" => $action
        ));
        
        $js->jQueryEvent("#" . $uniqId, "click", 
        'var self = $(this);
        window.FGIFT_CC = window.FGIFT_CC || e.data.available;
        if ( self.attr("data-action") == "setGift" && window.FGIFT_CC && window.FGIFT_CC !== true ) {
            OW.warning(window.FGIFT_CC);

            return false;
        }

        if ( self.attr("data-action") == "setGift" )
        {
            if (window.FGIFT_setGift) {
                window.FGIFT_setGift(e.data.giftSrc, e.data.giftUrl);
            }
        }
        else
        {
            if (window.FGIFT_unsetGift) {
                window.FGIFT_unsetGift();
            }
        }
        
        $.getJSON(e.data.rsp, {giftId: e.data.giftId, userId: e.data.userId, action: self.attr("data-action")}, function(r) { 
            OW[r.type](r.msg);
            window.FGIFT_CC = r.avaliable;
        });
        
        self.attr("data-action", self.attr("data-action") == "setGift" ? "unsetGift" : "setGift");
        self.attr("data-text", self.attr("data-text") == e.data.textSet ? e.data.textUnset : e.data.textSet);
        
        self.text(self.attr("data-text"));

        $(".fgift-unsetGift").each(function() {
            $("a", this).attr("data-text", e.data.textSet);
            $("a", this).attr("data-action", "setGift");

            $("a", this).text(e.data.textSet);
            $(this).removeClass("fgift-unsetGift");
            $(this).addClass("fgift-setGift");
        });
        
        var giftNode = $("#fgifts-set-gift-" + e.data.giftId);
        if ( self.attr("data-action") == "unsetGift" )
        {
            giftNode.text(e.data.textUnset);
            giftNode.attr("data-text", e.data.textUnset);
            giftNode.attr("data-action", "unsetGift");
            giftNode.parent().removeClass("fgift-setGift");
            giftNode.parent().addClass("fgift-unsetGift");
        }
        else
        {
            giftNode.text(e.data.textSet);
            giftNode.attr("data-text", e.data.textSet);
            giftNode.attr("data-action", "setGift");
            giftNode.parent().addClass("fgift-setGift");
            giftNode.parent().removeClass("fgift-unsetGift");
        }
        '
        ,array("e"), array(
            "userId" => $giftDto->recipientId,
            "giftId" => $giftDto->id,
            "giftSrc" => $gift["imageUrl"],
            "giftUrl" => $gift["url"],
            "rsp" => $rsp,
            "textSet" => OW::getLanguage()->text("fgift", "set_gift_label"),
            "textUnset" => OW::getLanguage()->text("fgift", "unset_gift_label"),
            "available" => FGIFT_CLASS_CreditsBridge::getInstance()->getAvailable()
        ));
        
        OW::getDocument()->addOnloadScript($js);
        
        $event->setData($data);
    }
    
    public function onInit()
    {
        OW::getRouter()->removeRoute("virtual_gifts_private_list");
        OW::getRouter()->addRoute(new OW_Route('virtual_gifts_private_list', 'virtual-gifts/my-gifts', 'FGIFT_CTRL_MyGifts', 'index'));
        
        OW::getRouter()->removeRoute("virtual_gifts_view_gift");
        OW::getRouter()->addRoute(new OW_Route('virtual_gifts_view_gift', 'virtual-gifts/view/:giftId', 'FGIFT_CTRL_Gifts', 'view'));
    }
    
    public function init()
    {
        if ( !$this->isActive() )
        {
            return;
        }
        
        OW::getEventManager()->bind(OW_EventManager::ON_PLUGINS_INIT, array($this, 'onInit'));
        OW::getEventManager()->bind('base.widget_panel.content.top', array($this, "onCollectContent"));
        OW::getEventManager()->bind("notifications.on_item_render", array($this, 'onNotificationRender'));
    }
}