<?php

    require_once 'EncryptionAbstract.php';

    /**
     * This program is free software; you can redistribute it and/or modify it under the terms of
     * the GNU General Public License as published by the Free Software Foundation; either
     * version 3 of the License, or (at your option) any later version.
     *
     * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
     * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
     * See the GNU General Public License for more details.
     *
     * You should have received a copy of the GNU General Public License along with this program;
     * if not, see <http://www.gnu.org/licenses/>.
     *
     * @category   RatePAY
     ** @copyright  Copyright (c) 2013 RatePAY GmbH (http://www.ratepay.com)
     */
    class Shopware_Plugins_Frontend_RpayRatePay_Component_Encryption_ShopwareEncryption extends Pi_Util_Encryption_EncryptionAbstract
    {

        /**
         * Executes the given SQL
         *
         * @param string $insertSql
         */
        protected function _insertBankdataToDatabase($insertSql)
        {
            Shopware()->Db()->query($insertSql);
        }

        /**
         * Executes the given SQL and returns the result
         *
         * @param string $selectSql
         */
        protected function _selectBankdataFromDatabase($selectSql)
        {
            $result = Shopware()->Db()->fetchRow($selectSql);

            return array(
                'bankname'   => $this->_convertHexToBinary($result['decrypt_bankname']),
                'bankcode'   => $this->_convertHexToBinary($result['decrypt_bankcode']),
                'bankholder' => $this->_convertHexToBinary($result['decrypt_bankholder']),
                'account'    => $this->_convertHexToBinary($result['decrypt_account']),
            );
        }

        /**
         * Executes the given SQL and returns the UserID
         *
         * @param string $userSql
         */
        protected function _selectUserIdFromDatabase($userSql)
        {
            $userID = Shopware()->Db()->fetchOne($userSql);

            return (string)$userID;
        }

    }
