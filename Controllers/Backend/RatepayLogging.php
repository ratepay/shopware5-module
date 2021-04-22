<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use RpayRatePay\Helper\XmlHelper;
use RpayRatePay\Models\Log;

class Shopware_Controllers_Backend_RatepayLogging extends Shopware_Controllers_Backend_Application
{

    protected $model = Log::class;
    protected $alias = 'log';

    protected function getListQuery()
    {
        $builder = parent::getListQuery();
        $builder->addSelect('e_order')
            ->addSelect('billing_address')
            ->leftJoin('log.order', 'e_order')
            ->leftJoin('e_order.billing', 'billing_address')
            ->orderBy('log.date', 'DESC');
        return $builder;
    }

    protected function getList($offset, $limit, $sort = [], $filter = [], array $wholeParams = [])
    {
        $results = parent::getList($offset, $limit, $sort, $filter, $wholeParams);

        foreach ($results['data'] as &$result) {
            $matchesRequest = [];
            preg_match("/(.*)(<\?.*)/s", $result['request'], $matchesRequest);
            $result['request'] = $matchesRequest[1] . "\n" . $this->formatXml(trim($matchesRequest[2]));

            $matchesResponse = [];
            preg_match('/(.*)(<response xml.*)/s', $result['response'], $matchesResponse);
            $result['response'] = $matchesResponse[1] . "\n" . $this->formatXml(trim($matchesResponse[2]));

            $result['status_code'] = XmlHelper::findValue($result['response'], 'status');

            if (isset($result['order']['billing'])) {
                $result['firstname'] = $result['order']['billing']['firstName'];
                $result['lastname'] = $result['order']['billing']['lastName'];
            }

            unset($result['order']);
        }

        return $results;
    }

    /**
     * Formats Xml into a better humanreadable form
     *
     * @return string
     */
    private function formatXml($xmlString)
    {
        $str = str_replace("\n", '', $xmlString);
        if ($this->validate($str)) {
            $xml = new DOMDocument('1.0');
            $xml->preserveWhiteSpace = false;
            $xml->formatOutput = true;
            $xml->loadXML($str);

            return $xml->saveXML();
        }

        return $xmlString;
    }

    private function parseXml($xmlString)
    {
        $str = str_replace("\n", '', $xmlString);
        if ($this->validate($str)) {
            $xml = new DOMDocument('1.0');
            $xml->preserveWhiteSpace = false;
            $xml->formatOutput = true;
            $xml->loadXML($str);

            return $xml;
        }
        return null;
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
