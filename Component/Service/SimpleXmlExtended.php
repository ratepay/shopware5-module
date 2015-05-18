<?php

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
     * {@inheritdoc}
     *
     * Extends SimpleXMLElement with a method to easyily add CData Child to XML.
     *
     * @package PayIntelligent_RatePAY
     * @extends SimpleXMLElement
     */
    class Shopware_Plugins_Frontend_RpayRatePay_Component_Service_SimpleXmlExtended extends SimpleXMLElement
    {

        /**
         * create CData child
         *
         * @param string $sName
         * @param string $sValue
         * @param bool   $utfMode
         *
         * @return SimpleXMLElement
         */
        public function addCDataChild($sName, $sValue)
        {
            $oNodeOld = dom_import_simplexml($this);
            $oNodeNew = new DOMNode();
            $oDom = new DOMDocument();
            $oDataNode = $oDom->appendChild($oDom->createElement($sName));
            $oDataNode->appendChild($oDom->createCDATASection($this->removeSpecialChars($sValue)));
            $oNodeTarget = $oNodeOld->ownerDocument->importNode($oDataNode, true);
            $oNodeOld->appendChild($oNodeTarget);

            return simplexml_import_dom($oNodeTarget);
        }

        /**
         * This method replaced all zoot signs
         *
         * @param string $str
         *
         * @return string
         */
        private function removeSpecialChars($str)
        {
            $search = array("–", "´", "‹", "›", "‘", "’", "‚", "“", "”", "„", "‟", "•", "‒", "―", "—", "™", "¼", "½", "¾");
            $replace = array("-", "'", "<", ">", "'", "'", ",", '"', '"', '"', '"', "-", "-", "-", "-", "TM", "1/4", "1/2", "3/4");

            return str_replace($search, $replace, $str);
        }

    }