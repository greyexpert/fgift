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
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package fgift.bol
 */
class FGIFT_BOL_GiftDao extends OW_BaseDao
{
    /**
     * Singleton instance.
     *
     * @var FGIFT_BOL_GiftDao
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return FGIFT_BOL_GiftDao
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     * @see OW_BaseDao::getDtoClassName()
     *
     */
    public function getDtoClassName()
    {
        return 'FGIFT_BOL_Gift';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'fgift_gift';
    }
    
    /**
     * 
     * @param int $userId
     * @param int $giftId
     * @return FGIFT_BOL_Gift
     */
    public function setGift( $userId, $giftId )
    {
        $gift = $this->findGift($userId);
        
        if ( $gift === null )
        {
            $gift = new FGIFT_BOL_Gift();
        }
        
        $gift->userId = $userId;
        $gift->giftId = $giftId;
        $gift->timeStamp = time();
        
        $this->save($gift);
        
        return $gift;
    }
    
    /**
     * 
     * @param int $userId
     * @return FGIFT_BOL_Gift
     */
    public function findGift( $userId )
    {
        $example = new OW_Example();
        $example->andFieldEqual("userId", $userId);
        
        return $this->findObjectByExample($example);
    }
    
    public function deleteGift( $userId )
    {
        $example = new OW_Example();
        $example->andFieldEqual("userId", $userId);
        
        $this->deleteByExample($example);
    }
}