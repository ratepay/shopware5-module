<?php

    require_once 'PrivateKey.php';

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
    abstract class Pi_Util_Encryption_EncryptionAbstract
    {

        /**
         * Service responsible for private key handling.
         *
         * @var Pi_Util_Encryption_PrivateKey
         */
        private $_privateKeyService;

        /**
         * Contains the privateKey
         *
         * @var string
         */
        private $_privateKey;

        /**
         * Databasename for the bankdatatable
         *
         * @var string
         */
        protected $_tableName = 'rpay_ratepay_user_bankdata';

        /**
         * Creates an instance of this class
         *
         * @param Pi_Util_Encryption_PrivateKey $privateKeyService
         */
        public function __construct(Pi_Util_Encryption_PrivateKey $privateKeyService = null)
        {
            $this->_privateKeyService = isset($privateKeyService) ? $privateKeyService : new Pi_Util_Encryption_PrivateKey();
            $this->_privateKey = $this->_privateKeyService->getPrivateKey();
        }

        /**
         * loads the Bankdata for the given User
         *
         * @param string $userId
         *
         * @return array
         */
        public function loadBankdata($userId)
        {
            $selectSql = $this->_createBankdataSelectSql($userId);
            $bankdata = $this->_selectBankdataFromDatabase($selectSql);

            return $bankdata;
        }

        /**
         * Saves the Bankdata for the given User
         *
         * @param string $userId
         * @param array  $bankdata
         */
        public function saveBankdata($userId, array $bankdata)
        {
            if ($this->isBankdataSetForUser($userId)) {
                $saveSql = $this->_createBankdataUpdateSql($userId, $bankdata);
            }
            else {
                $saveSql = $this->_createBankdataInsertSql($userId, $bankdata);
            }
            $this->_insertBankdataToDatabase($saveSql);
        }

        /**
         * Creates an SQL for DB-Insert
         *
         * @param string $userId
         * @param array  $bankdata
         *
         * @return string
         */
        private function _createBankdataInsertSql($userId, array $bankdata)
        {
            $insertSql = 'INSERT INTO ' . $this->_tableName . ' (userID, ';
            $key = $this->_privateKey;
            $arr = array_keys($bankdata);
            $lastArrayKey = array_pop($arr);

            foreach ($bankdata as $columnName => $columnValue) {
                $insertSql .= $columnName;
                $insertSql .= $lastArrayKey != $columnName ? ', ' : ')';
            }

            $insertSql .= ' Values (' . "'" . $userId . "', ";

            foreach ($bankdata as $columnName => $columnValue) {
                $insertSql .= "AES_ENCRYPT('" . $this->_convertBinaryToHex($columnValue) . "', '" . $key . "')";
                $insertSql .= $lastArrayKey != $columnName ? ', ' : ')';
            }

            return $insertSql;
        }

        /**
         * Creates an SQL for DB-Update
         *
         * @param string $userId
         * @param array  $bankdata
         *
         * @return string
         */
        private function _createBankdataUpdateSql($userId, array $bankdata)
        {
            $updateSql = 'UPDATE ' . $this->_tableName . ' SET ';
            $key = $this->_privateKey;
            $arr = array_keys($bankdata);
            $lastArrayKey = array_pop($arr);

            foreach ($bankdata as $columnName => $columnValue) {
                $updateSql .= $columnName . " = AES_ENCRYPT('" . $this->_convertBinaryToHex($columnValue) . "', '" . $key . "')";
                $updateSql .= $lastArrayKey != $columnName ? ', ' : ' ';
            }

            $updateSql .= ' where userID = ' . "'" . $userId . "'";

            return $updateSql;
        }

        /**
         * Checks if Bankdata are stored in the Database for the given User
         *
         * @param string $userId
         *
         * @return boolean
         */
        public function isBankdataSetForUser($userId)
        {
            $sanitizedString = $userId;
            $userSql = "Select userID from " . $this->_tableName . " where userID = '$sanitizedString'";
            $userIdStoredInDb = $this->_selectUserIdFromDatabase($userSql);

            return $userId === $userIdStoredInDb;
        }

        /**
         * Creates an SQL for DB-Select
         *
         * @param string $userId
         *
         * @return string
         */
        private function _createBankdataSelectSql($userId)
        {
            $key = $this->_privateKey;
            $selectSql = "SELECT userID, AES_DECRYPT(bankholder, '$key') as decrypt_bankholder, AES_DECRYPT(account, '$key') as decrypt_account, AES_DECRYPT(bankcode, '$key') as decrypt_bankcode, AES_DECRYPT(bankname, '$key') as decrypt_bankname from " . $this->_tableName . " where userID = '$userId'";

            return $selectSql;
        }

        /**
         * converts the given Value from binary to hex
         *
         * @param string $value
         *
         * @return string
         */
        protected function _convertBinaryToHex($value)
        {
            $toHex = bin2hex($value);

            return $toHex;
        }

        /**
         * converts the given Value from hex to binary
         *
         * @param string $value
         *
         * @return string
         */
        protected function _convertHexToBinary($value)
        {
            $toBinary = pack("H*", $value);

            return $toBinary;
        }

        /**
         * Must be overwritten
         */
        abstract protected function _insertBankdataToDatabase($insertSql);

        /**
         * Must be overwritten
         */
        abstract protected function _selectBankdataFromDatabase($selectSql);

        /**
         * Must be overwritten
         */
        abstract protected function _selectUserIdFromDatabase($userSql);
    }
