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
 * @package fgift.controllers
 */
class FGIFT_CTRL_MyGifts extends VIRTUALGIFTS_CTRL_MyGifts
{
    private $toolbarItems = array();

    public function index() 
    {
        parent::index();
        
        $staticUrl = OW::getPluginManager()->getPlugin('fgift')->getStaticUrl();
        OW::getDocument()->addStyleSheet($staticUrl . 'fgift.css');
        
        $tpl = OW::getPluginManager()->getPlugin("virtualgifts")->getCtrlViewDir() . "my_gifts_index.html";
        $this->setTemplate($tpl);
        
        foreach ( $this->assignedVars["gifts"] as $gift )
        {
            /*@var $giftDto VIRTUALGIFTS_BOL_UserGift */
            $giftDto = $gift['dto'];
            $userId = $giftDto->recipientId;
            
            if ( $userId != OW::getUser()->getId() && !OW::getUser()->isAuthorized("fgift") )
            {
                return;
            }

            $uniqId = "fgifts-set-gift-" . $giftDto->id;

            $text = OW::getLanguage()->text("fgift", "set_gift_label");
            $action = "setGift";

            $fGift = FGIFT_BOL_Service::getInstance()->findGift($userId);

            if ( $fGift !== null && $fGift->giftId == $giftDto->id )
            {
                $text = OW::getLanguage()->text("fgift", "unset_gift_label");
                $action = "unsetGift";
            }
            else if ( !FGIFT_CLASS_CreditsBridge::getInstance ()->credits->isAvaliable("add_gift") )
            {
                continue;
            }

            $rsp = OW::getRouter()->urlFor("FGIFT_CTRL_Main", "ajax");

            $js = UTIL_JsGenerator::composeJsString('(function() { 
                $("#' . $uniqId . '").attr("data-action", {$action});
                $("#' . $uniqId . '").attr("data-text", {$text});
                window.FGIFT_CC = window.FGIFT_CC || {$available};
                $("#' . $uniqId . '").click(function() {
                    var self = $(this);

                    if ( self.attr("data-action") == "setGift" && window.FGIFT_CC && window.FGIFT_CC !== true ) {
                        OW.warning(window.FGIFT_CC);
                        
                        return false;
                    }
                    
                    

                    $.getJSON({$rsp}, {giftId: {$giftId}, userId: {$userId}, action: self.attr("data-action")}, function(r) { 
                        OW[r.type](r.msg);
                        window.FGIFT_CC = r.avaliable;
                    });

                    self.attr("data-text", self.attr("data-text") == {$textSet} ? {$textUnset} : {$textSet});
                    self.parent().removeClass("fgift-" + self.attr("data-action"));
                    self.attr("data-action", self.attr("data-action") == "setGift" ? "unsetGift" : "setGift");

                    $(".fgift-unsetGift").each(function() {
                        $("a", this).attr("data-text", {$textSet});
                        $("a", this).attr("data-action", "setGift");
                        
                        $("a", this).text({$textSet});
                        $(this).removeClass("fgift-unsetGift");
                        $(this).addClass("fgift-setGift");
                    });

                    self.parent().addClass("fgift-" + self.attr("data-action"));
                    self.text(self.attr("data-text"));
            }); })();', array(
                "rsp" => $rsp,
                "giftId" => $giftDto->id,
                "userId" => $userId,
                "action" => $action,
                "text" => $text,
                "textSet" => OW::getLanguage()->text("fgift", "set_gift_label"),
                "textUnset" => OW::getLanguage()->text("fgift", "unset_gift_label"),
                "available" => FGIFT_CLASS_CreditsBridge::getInstance()->getAvailable()
            ));
        
            OW::getDocument()->addOnloadScript($js);
            
            array_push($this->toolbarItems, array(
                "class" => "fgift-" . $action,
                "label" => $text,
                "href" => "javascript://",
                "id" => $uniqId
            ));
        }
        
    }
    
    public function processDecorator( $params )
    {
        if ( $params["name"] != "ipc" ) return;
        
        if ( !isset($params["toolbar"]) )
        {
            $params["toolbar"] = array();
        }
        
        $params["toolbar"][] = array_shift($this->toolbarItems);
        $params["addClass"] = empty($params["addClass"]) ? "fgifts-ipc" : $params["addClass"] . " fgifts-ipc";
        
        return OW::getThemeManager()->processDecorator($params["name"], $params);
    }
    
    public function render() {
        
        OW_ViewRenderer::getInstance()->unregisterFunction("decorator");
        OW_ViewRenderer::getInstance()->registerFunction("decorator", array($this, "processDecorator"));
        
        $out = parent::render();
        
        OW_ViewRenderer::getInstance()->unregisterFunction("decorator");
        
        return $out;
    }
}