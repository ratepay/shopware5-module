<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use RpayRatePay\Component\Model\ShopwareCustomerWrapper;

    class Shopware_Controllers_Backend_RpayRatepayLogging extends Shopware_Controllers_Backend_ExtJs
    {
        /**
         * index action is called if no other action is triggered
         *
         * @return void
         */
        public function indexAction()
        {
            $this->View()->loadTemplate('backend/rpay_ratepay_logging/app.js');
            $this->View()->assign('title', 'Ratepay-Logging');
        }

        /**
         * This Action loads the loggingdata from the datebase into the backendview
         */
        public function loadStoreAction()
        {
            $offset = intval($this->Request()->getParam('start') ? : 0);
            $limit = intval($this->Request()->getParam('limit') ? : 10);
            $orderId = $this->Request()->getParam('orderId');

            if (!is_null($orderId)) {
                $transactionId = Shopware()->Db()->fetchOne('SELECT `transactionId` FROM `s_order` WHERE `id`=?', [$orderId]);
                $sqlTotal = 'SELECT COUNT(*) FROM `rpay_ratepay_logging` WHERE `transactionId`=?';

                $sql = 'SELECT log.*, s_user.id as user_id FROM `rpay_ratepay_logging` AS `log` '
                    . 'LEFT JOIN `s_order` ON `log`.`transactionId`=`s_order`.`transactionID` '
                    . 'LEFT JOIN s_user ON s_order.userID=s_user.id '
                    . 'WHERE log.transactionId=?'
                    . 'ORDER BY `id` DESC'
                    //. 'LIMIT '.$offset.','.$limit //TODO add pagination to ExtJs
                ;

                $data = Shopware()->Db()->fetchAll($sql, [$transactionId]);
                $total = Shopware()->Db()->fetchOne($sqlTotal, [$transactionId]);
            } else {
                $sqlTotal = 'SELECT COUNT(*) FROM `rpay_ratepay_logging`';

                $sql = 'SELECT log.*, s_user.id as user_id FROM `rpay_ratepay_logging` AS `log` '
                    . 'LEFT JOIN `s_order` ON `log`.`transactionId`=`s_order`.`transactionID` '
                    . 'LEFT JOIN s_user ON s_order.userID=s_user.id '
                    . 'ORDER BY `id` DESC '
                    . 'LIMIT '.$offset.','.$limit
                ;

                $data = Shopware()->Db()->fetchAll($sql);
                $total = Shopware()->Db()->fetchOne($sqlTotal);
            }

            $store = [];
            foreach ($data as $row) {

                if ($row['user_id']) {
                    $customer = Shopware()->Models()->find('Shopware\Models\Customer\Customer', $row['user_id']);
                    $customerWrapped = new ShopwareCustomerWrapper($customer, Shopware()->Models());
                    $row['firstname'] = $customerWrapped->getBillingFirstName();
                    $row['lastname'] = $customerWrapped->getBillingLastName();
                }

                $matchesRequest = [];
                preg_match("/(.*)(<\?.*)/s", $row['request'], $matchesRequest);
                $row['request'] = $matchesRequest[1] . "\n" . $this->formatXml(trim($matchesRequest[2]));

                $matchesResponse = [];
                preg_match('/(.*)(<response xml.*)/s', $row['response'], $matchesResponse);
                $row['response'] = $matchesResponse[1] . "\n" . $this->formatXml(trim($matchesResponse[2]));

                $store[] = $row;
            }

            $this->View()->assign(
                [
                    'data' => $store,
                    'total' => $total,
                    'success' => true
                ]
            );
        }

        /**
         * Formats Xml into a better humanreadable form
         *
         * @return string
         */
        private function formatXml($xmlString)
        {
            $str = str_replace("\n", '', $xmlString);
            $xml = new DOMDocument('1.0');
            $xml->preserveWhiteSpace = false;
            $xml->formatOutput = true;
            if ($this->validate($str)) {
                $xml->loadXML($str);

                return $xml->saveXML();
            }

            return $xmlString;
        }

        /**
         * Validate if the given xml string is valid
         *
         * @param string $xml
         *
         * @return boolean
         */
        private function validate($xml)
        {
            libxml_use_internal_errors(true);

            $doc = new DOMDocument('1.0', 'utf-8');

            try {
                $doc->loadXML($xml);
            } catch (\Exception $e) {
                return false;
            }

            $errors = libxml_get_errors();
            if (empty($errors)) {
                return true;
            }

            $error = $errors[0];
            if ($error->level < 3) {
                return true;
            }

            return false;
        }

        /**
         * Return all present xml validation errors
         *
         * @return string
         */
        public static function getXmlValidationError()
        {
            $message = '';
            foreach (libxml_get_errors() as $error) {
                $message .= str_replace("\n", '', $error->message) . ' at line ' . $error->line . ' on column ' . $error->column . "\n";
            }

            return $message;
        }
    }
