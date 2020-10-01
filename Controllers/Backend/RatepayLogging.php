<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use RpayRatePay\Component\Model\ShopwareCustomerWrapper;
use Shopware\Models\Customer\Customer;

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
 * RpayRatepayLogging
 *
 * @category   RatePAY
 ** @copyright  Copyright (c) 2013 RatePAY GmbH (http://www.ratepay.com)
 */
class Shopware_Controllers_Backend_RatepayLogging extends Shopware_Controllers_Backend_ExtJs
{

    /**
     * index action is called if no other action is triggered
     *
     * @return void
     */
    public function indexAction()
    {
        $this->View()->assign('title', 'Ratepay-Logging');
    }

    /**
     * This Action loads the loggingdata from the datebase into the backendview
     */
    public function loadLogEntriesAction()
    {
        //TODO improve
        $start = intval($this->Request()->getParam('start'));
        $limit = intval($this->Request()->getParam('limit'));
        $orderId = $this->Request()->getParam('orderId');

        if (!is_null($orderId)) {
            $transactionId = Shopware()->Db()->fetchOne('SELECT `transactionId` FROM `s_order` WHERE `id`=?', [$orderId]);
            $sqlTotal = 'SELECT COUNT(*) FROM `rpay_ratepay_logging` WHERE `transactionId`=?';

            $sql = 'SELECT log.*, s_user.id as user_id FROM `rpay_ratepay_logging` AS `log` '
                . 'LEFT JOIN `s_order` ON `log`.`transactionId`=`s_order`.`transactionID` '
                . 'LEFT JOIN s_user ON s_order.userID=s_user.id '
                . 'WHERE log.transactionId=?'
                . 'ORDER BY `id` DESC';

            $data = Shopware()->Db()->fetchAll($sql, [$transactionId]);
            $total = Shopware()->Db()->fetchOne($sqlTotal, [$transactionId]);
        } else {
            $sqlTotal = 'SELECT COUNT(*) FROM `rpay_ratepay_logging`';

            $sql = 'SELECT log.*, s_user.id as user_id FROM `rpay_ratepay_logging` AS `log` '
                . 'LEFT JOIN `s_order` ON `log`.`transactionId`=`s_order`.`transactionID` '
                . 'LEFT JOIN s_user ON s_order.userID=s_user.id '
                . 'ORDER BY `id` DESC';

            $data = Shopware()->Db()->fetchAll($sql);
            $total = Shopware()->Db()->fetchOne($sqlTotal);
        }

        $store = [];
        foreach ($data as $row) {

            if ($row['user_id']) {
                //TODO hier muss eigentlich die billing_address aus der Order gezogen werden.
                $customer = Shopware()->Models()->find(Customer::class, $row['user_id']);
                $row['firstname'] = $customer->getDefaultBillingAddress()->getFirstname();
                $row['lastname'] = $customer->getDefaultBillingAddress()->getLastname();
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
        } catch (Exception $e) {
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
}
