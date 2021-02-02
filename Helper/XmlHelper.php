<?php


namespace RpayRatePay\Helper;


class XmlHelper
{

    public static function findValue($xml, $tagKey, $default = null)
    {
        preg_match("/<" . $tagKey . "[^>]*>(.*)<\/" . $tagKey . ">/", $xml, $matches);
        return isset($matches[1]) ? $matches[1] : $default;
    }

    public static function findAttributeValue($xml, $tagKey, $attributeName, $default = null)
    {
        preg_match("/<" . $tagKey . "[^>]* ".$attributeName."=\"(.*)\"[^>]*>.*<\/" . $tagKey . ">/", $xml, $matches);
        return isset($matches[1]) ? $matches[1] : $default;
    }



}