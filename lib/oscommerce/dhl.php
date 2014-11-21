<?php

/*
  Copyright (c) 2003-2006 Joshua Dechant, osCommerce, Hans Anderson, Erich Spencer, Fritz Clapp, Greg Carleu, Wi-Gear Inc.

  Released under the GNU General Public License
 */

define('MODULE_SHIPPING_AIRBORNE_TEXT_TITLE', 'DHL/Airborne');
define('MODULE_SHIPPING_AIRBORNE_TEXT_QUOTE_TITLE', 'DHL Express');
define('MODULE_SHIPPING_AIRBORNE_TEXT_DESCRIPTION', 'DHL/Airborne Shipping Rates<br><em>(Using the DHL/Airborne ShipIT XML 2.1 API)</em><p>');

define('MODULE_SHIPPING_AIRBORNE_ICON', 'shipping_dhlairborne.gif');

define('MODULE_SHIPPING_AIRBORNE_TEXT_GROUND', 'Ground');
define('MODULE_SHIPPING_AIRBORNE_TEXT_SECOND_DAY', 'Second Day Service');
define('MODULE_SHIPPING_AIRBORNE_TEXT_NEXT_AFTERNOON', 'Next Afternoon');
define('MODULE_SHIPPING_AIRBORNE_TEXT_EXPRESS', 'Express');
define('MODULE_SHIPPING_AIRBORNE_TEXT_EXPRESS_1030', 'Express 10:30 AM');
define('MODULE_SHIPPING_AIRBORNE_TEXT_EXPRESS_SAT', 'Express Saturday');
define('MODULE_SHIPPING_AIRBORNE_TEXT_INTERNATIONAL_EXPRESS', 'International Express');
define('MODULE_SHIPPING_AIRBORNE_TEXT_CONTENTS_DESCRIPTION', 'Shipment Contents');
define('MODULE_SHIPPING_AIRBORNE_DEBUG_METHOD', 'Print to screen');

define('MODULE_SHIPPING_AIRBORNE_TEXT_ERROR', 'An error occured with the DHL/Airborne shipping calculations.<br>If you prefer to use DHL/Airborne as your shipping method, please contact the store owner.');

class dhl {

    var $code, $title, $quote_title, $description, $icon, $enabled, $debug, $types, $allowed_methods;
    // DHL/Airborne Vars
    var $service;
    var $container;
    var $shipping_day;
    var $weight;
    var $dimensions;
    var $length;
    var $width;
    var $height;
    var $destination_street_address;
    var $destination_city;
    var $destination_state;
    var $destination_postal;
    var $destination_country;
    var $additionalProtection;
    var $live_url = 'https://xmlpi-ea.dhl.com/XMLShippingServlet';
    var $test_url = 'https://xmlpitest-ea.dhl.com/XMLShippingServlet';

    function dhl() {
        global $order;

        $this->code = 'dhl';
        $this->title = MODULE_SHIPPING_AIRBORNE_TEXT_TITLE;
        $this->quote_title = MODULE_SHIPPING_AIRBORNE_TEXT_QUOTE_TITLE;
        $this->description = MODULE_SHIPPING_AIRBORNE_TEXT_DESCRIPTION;
        $this->sort_order = MODULE_SHIPPING_AIRBORNE_SORT_ORDER;
        $this->icon = DIR_WS_ICONS . MODULE_SHIPPING_AIRBORNE_ICON;
        $this->tax_class = MODULE_SHIPPING_AIRBORNE_TAX_CLASS;
        $this->debug = ((MODULE_SHIPPING_AIRBORNE_DEBUG == 'True') ? true : false);
        $this->enabled = ((MODULE_SHIPPING_AIRBORNE_STATUS == 'True') ? true : false);

        $this->types = array('G' => MODULE_SHIPPING_AIRBORNE_TEXT_GROUND,
            'S' => MODULE_SHIPPING_AIRBORNE_TEXT_SECOND_DAY,
            'N' => MODULE_SHIPPING_AIRBORNE_TEXT_NEXT_AFTERNOON,
            'E' => MODULE_SHIPPING_AIRBORNE_TEXT_EXPRESS,
            'E 10:30AM' => MODULE_SHIPPING_AIRBORNE_TEXT_EXPRESS_1030,
            'E SAT' => MODULE_SHIPPING_AIRBORNE_TEXT_EXPRESS_SAT,
            'IE' => MODULE_SHIPPING_AIRBORNE_TEXT_INTERNATIONAL_EXPRESS);
    }

// class methods
    function quote() {
        global $order, $shipping_weight, $shipping_num_boxes, $method;

        define('MODULE_SHIPPING_AIRBORNE_SYSTEMID', $method['apiuser']);
        define('MODULE_SHIPPING_AIRBORNE_PASS', $method['apipw']);
        define('MODULE_SHIPPING_AIRBORNE_ACCT_NBR', $method['account']);
        define('MODULE_SHIPPING_AIRBORNE_SHIP_KEY', $method['apik']);
        define('MODULE_SHIPPING_AIRBORNE_SHIP_KEY_INTL', $method['apikintl']);
        define('MODULE_SHIPPING_AIRBORNE_SERVER', ($method["test_mode"] == "1" ? 'test' : 'production'));

        $this->_setMethods($method['types']);

        $this->_setDestination($order->delivery['street_address'], $order->delivery['city'], $order->delivery['state'], $order->delivery['postcode'], $order->delivery['country']['iso_code_2']);
        $this->_setContainer(MODULE_SHIPPING_AIRBORNE_PACKAGE);
        $this->_setWeight($shipping_weight);
        $this->_setShippingDay(MODULE_SHIPPING_AIRBORNE_DAYS_TO_SHIP, MODULE_SHIPPING_AIRBORNE_SHIPMENT_DAY);
        //if (MODULE_SHIPPING_AIRBORNE_DIMENSIONAL_WEIGHT == 'true') $this->_setDimensions(MODULE_SHIPPING_AIRBORNE_DIMENSIONAL_EXCLUSIVE);
        //if (MODULE_SHIPPING_AIRBORNE_ADDITIONAL_PROTECTION == 'true') $this->_setAdditionalProtection(MODULE_SHIPPING_AIRBORNE_ADDITIONAL_PROTECTION_VALUE);

        $dhlAirborneQuotes = $this->_getQuote();

        if (is_array($dhlAirborneQuotes)) {
            //if (MODULE_SHIPPING_AIRBORNE_SHIP_WEIGHT=='true') {
            // Wi-Gear Changed in v2.2 - Made shipping weight to title optional
            //$module = $this->quote_title . ' (' . $shipping_num_boxes . ' x ' . $this->weight . 'lbs)';
            //} else {
            $module = $this->quote_title;
            //}
            if (isset($dhlAirborneQuotes['error'])) {
                $this->quotes = array('module' => $module,
                    'error' => $dhlAirborneQuotes['error']);
            } else {
                $this->quotes = array('id' => $this->code,
                    'module' => $module);

                $methods = array();
                foreach ($dhlAirborneQuotes as $dhlAirborneQuote) {
                    list($type, $cost) = each($dhlAirborneQuote);
                    if ($cost != '0.00') {
                        $methods[] = array('id' => $type,
                            'title' => ((isset($this->types[$type])) ? $this->types[$type] : $type) . $dhlAirborneQuote['description'],
                            'cost' => ($cost * $shipping_num_boxes) + MODULE_SHIPPING_AIRBORNE_HANDLING);
                    }
                }

                $this->quotes['methods'] = $methods;

                //if ($this->tax_class > 0) {
                //  $this->quotes['tax'] = tep_get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
                //}
            }
        } else {
            $this->quotes = array('module' => $this->quote_title,
                'error' => MODULE_SHIPPING_AIRBORNE_TEXT_ERROR);
        }

        //if (tep_not_null($this->icon)) $this->quotes['icon'] = tep_image($this->icon, $this->quote_title);

        return $this->quotes;
    }

    function check() {
        if (!isset($this->_check)) {
            $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_SHIPPING_AIRBORNE_STATUS'");
            $this->_check = tep_db_num_rows($check_query);
        }
        return $this->_check;
    }

    function keys() {
        //
        // Add in International Shipping Key
        //
        return array('MODULE_SHIPPING_AIRBORNE_STATUS', 'MODULE_SHIPPING_AIRBORNE_SYSTEMID', 'MODULE_SHIPPING_AIRBORNE_PASS', 'MODULE_SHIPPING_AIRBORNE_SHIP_KEY', 'MODULE_SHIPPING_AIRBORNE_SHIP_KEY_INTL', 'MODULE_SHIPPING_AIRBORNE_ACCT_NBR', 'MODULE_SHIPPING_AIRBORNE_SERVER', 'MODULE_SHIPPING_AIRBORNE_TYPES', 'MODULE_SHIPPING_AIRBORNE_DUTIABLE', 'MODULE_SHIPPING_AIRBORNE_DUTY_PAYMENT_TYPE', 'MODULE_SHIPPING_AIRBORNE_CONTENTS_DESCRIPTION', 'MODULE_SHIPPING_AIRBORNE_EST_DELIVERY', 'MODULE_SHIPPING_AIRBORNE_SHIP_WEIGHT', 'MODULE_SHIPPING_AIRBORNE_PACKAGE', 'MODULE_SHIPPING_AIRBORNE_SHIPMENT_DAY_TYPE', 'MODULE_SHIPPING_AIRBORNE_DAYS_TO_SHIP', 'MODULE_SHIPPING_AIRBORNE_SHIPMENT_DAY', 'MODULE_SHIPPING_AIRBORNE_OVERRIDE_EXP_SAT', 'MODULE_SHIPPING_AIRBORNE_DIMENSIONAL_WEIGHT', 'MODULE_SHIPPING_AIRBORNE_DIMENSIONAL_TABLE', 'MODULE_SHIPPING_AIRBORNE_DIMENSIONAL_EXCLUSIVE', 'MODULE_SHIPPING_AIRBORNE_ADDITIONAL_PROTECTION', 'MODULE_SHIPPING_AIRBORNE_ADDITIONAL_PROTECTION_TYPE', 'MODULE_SHIPPING_AIRBORNE_ADDITIONAL_PROTECTION_VALUE', 'MODULE_SHIPPING_AIRBORNE_HANDLING', 'MODULE_SHIPPING_AIRBORNE_TAX_CLASS', 'MODULE_SHIPPING_AIRBORNE_ZONE', 'MODULE_SHIPPING_AIRBORNE_SORT_ORDER', 'MODULE_SHIPPING_AIRBORNE_DEBUG', 'MODULE_SHIPPING_AIRBORNE_DEBUG_METHOD', 'MODULE_SHIPPING_AIRBORNE_DEBUG_DIRECTORY');
    }

    function _setService($service) {
        $this->service = $service;
    }

    function _setMethods($methods) {
        $this->allowed_methods = explode(",", $methods);
    }

    function _setContainer($container) {
        $this->container = $container;
    }

    function _setShippingDay($days_to_ship, $day) {
        if (MODULE_SHIPPING_AIRBORNE_SHIPMENT_DAY_TYPE == 'Ship in x number of days') {
            $this->shipping_day = ((_makedate3254($days_to_ship, 'day', 'dddd') == 'Saturday') ? $days_to_ship + 2 : ((_makedate3254($days_to_ship, 'day', 'dddd') == 'Sunday') ? $days_to_ship + 1 : $days_to_ship));
        } elseif (MODULE_SHIPPING_AIRBORNE_SHIPMENT_DAY_TYPE == 'Ship on certain day') {
            $i = 1;
            while (_makedate3254($i, 'day', 'dddd') != $day) {
                $i++;
            }

            $this->shipping_day = $i;
        }
    }

    function _setWeight($shipping_weight) {
        $shipping_weight = ($shipping_weight < .5 ? .5 : $shipping_weight);
        // Wi-Gear Changed in v2.2 - round up weight
        $shipping_pounds = ceil($shipping_weight);
        $this->weight = $shipping_pounds;
    }

    function _setDimensions($exclusive) {
        $dimensions = split("[:xX,]", MODULE_SHIPPING_AIRBORNE_DIMENSIONAL_TABLE);
        $size = sizeof($dimensions);
        for ($i = 0, $n = $size; $i < $n; $i+=4) {
            if ($exclusive == 'true') {
                if (($_SESSION['cart']->count_contents()) == $dimensions[$i]) {
                    $this->dimensions = true;
                    // Wi-Gear Changed in v2.2 - round up dimensions
                    $this->length = ceil($dimensions[$i + 1]);
                    $this->width = ceil($dimensions[$i + 2]);
                    $this->height = ceil($dimensions[$i + 3]);
                }
            } else {
                if (($_SESSION['cart']->count_contents()) >= $dimensions[$i]) {
                    $this->dimensions = true;
                    // Wi-Gear Changed in v2.2 - round up dimensions
                    $this->length = ceil($dimensions[$i + 1]);
                    $this->width = ceil($dimensions[$i + 2]);
                    $this->height = ceil($dimensions[$i + 3]);
                }
            }
        }
    }

    function _setDestination($street_address, $city, $state, $postal, $country) {
        global $order;

        $postal = str_replace(' ', '', $postal);

        $this->destination_street_address = $street_address;
        $this->destination_city = $city;
        $this->destination_state = $state;
        $this->destination_postal = $postal;
        $this->destination_country = $country;
    }

    function _setAdditionalProtection($additional_value) {
        global $order;

        $additional_protection = $order->info['total'];
        if (substr_count($additional_value, '%') > 0) {
            $additional_protection += ((($additional_protection * 10) / 10) * ((str_replace('%', '', $additional_value)) / 100));
        } else {
            $additional_protection += $additional_value;
        }

        $this->additionalProtection = round($additional_protection, 0);
    }

    function _getQuote() {
        global $order;


        // if it is an international order get an international quote
        if ($order->delivery['country']['iso_code_2'] != 'US') {
            $rates = $this->_getInternationalQuote();
            return ((sizeof($rates) > 0) ? $rates : false);
        }

        // start the XML request
        $request = $this->populateXmlDocument();

        if (isset($this->service)) {
            $this->types = array($this->service => $this->types[$this->service]);
        }

        $allowed_types = array();
        foreach ($this->types as $key => $value) {
            if (!in_array($key, $this->allowed_methods))
                continue;
            // Letter Express not allowed with ground
            if (($key == 'G') && ($this->container == 'L'))
                continue;

            // International Express not allowed with Domestic
            if ($key == 'IE')
                continue;

            // basic shipment information
            $allowed_types[$key] = $value;
        }

        // select proper server
        switch (MODULE_SHIPPING_AIRBORNE_SERVER) {
            case 'production':
                $api = $this->live_url;
                break;
            case 'test':
            default:
                $api = $this->test_url;
                break;
        }

        $request = $this->populateXmlDocument();
        // begin cURL engine & execute the request
        //if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$api");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "$request");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //Added in 1.401 fix
//    curl_setopt($ch, CURLOPT_CAINFO, 'C:/apache/bin/curl-ca-bundle.crt');

        $airborne_response = curl_exec($ch);
        curl_close($ch);
        // } //else {
        // cURL method using exec() // curl -d -k if you have SSL issues
        //exec("/usr/bin/curl -d \"$request\" https://xmlapi.dhl-usa.com/$api", $response);
        //$airborne_response = '';
        //foreach ($response as $key => $value) {
        //$airborne_response .= "$value";
        //}//
        // }

        // Debugging
        if ($this->debug) {
            $this->captureXML($request, $airborne_response);
        }

        $airborne = _parsexml3254($airborne_response);

        if ($airborne['res:ErrorResponse']['->']['Response']['->']['Status'][0]['->']['ActionStatus'][0]['->'] == 'Error') {
            $error_message = 'The following errors have occured:';
            $i = 0;
            // We dont want to return this type of error
          return array('error' => $error_message);
        } elseif ($airborne['res:DCTResponse']['->']['GetQuoteResponse'][0]['->']['Note'][0]['->']['Condition'][0]['->']['ConditionData'][0]['->']) {
            $error_message = 'The following errors have occured:';
            for ($i = 0; $i < 5; $i++) {
                if ($airborne['res:DCTResponse']['->']['GetQuoteResponse'][0]['->']['Note'][0]['->']['Condition'][$i]['->']['ConditionData'][0]['->'])
                    $error_message .= '<br>' . ($i + 1) . '.&nbsp;' . $airborne['res:DCTResponse']['->']['GetQuoteResponse'][0]['->']['Note'][0]['->']['Condition'][$i]['->']['ConditionData'][0]['->'];
                if (!$airborne['res:DCTResponse']['->']['GetQuoteResponse'][0]['->']['Note'][0]['->']['Condition'][$i + 1]['->']['ConditionData'][0]['->'])
                    break;
            }
            return array('error' => $error_message);
        } else {
            $rates = array();
            $i = 0;
            foreach ($allowed_types as $key => $value) {
                // $postage = $airborne['res:DCTResponse']['->']['GetQuoteResponse']['0']['->']['QtdShp']['0']['->']['ShippingCharge']['0']['->'];
                if ($airborne['res:DCTResponse']['->']['GetQuoteResponse']['0']['->']['BkgDetails']['0']['->']['QtdShp'][$i]['->']['GlobalProductCode'][0]['->']) {
                    $service = $key;
                    $postage = $airborne['res:DCTResponse']['->']['GetQuoteResponse']['0']['->']['BkgDetails']['0']['->']['QtdShp'][$i]['->']['ShippingCharge']['0']['->'];
                    $description = (MODULE_SHIPPING_AIRBORNE_EST_DELIVERY == 'true') ? '&nbsp;<span class="smallText"><em>(' . $airborne['res:DCTResponse']['->']['GetQuoteResponse']['0']['->']['BkgDetails']['0']['->']['QtdShp'][$i]['->']['ProductShortName']['0']['->'] . ')</em></span>' : '';
                    $rates[] = array($service => $postage, 'description' => $description);
                }
                $i++;
            }
        }

        return ((sizeof($rates) > 0) ? $rates : false);
    }

    function _getInternationalQuote() {
        global $order;

        // Check that 'IE' is a selected method
        if (!in_array('IE', $this->allowed_methods)) {
            return(array('error' => 'Error - In order to use DHL International Express Shipping the shipping zone must be enabled (which has been completed) and IE must be checked off as an available shipping method (which has not been completed)'));
//  return(false);
        };

        // select proper server
        switch (MODULE_SHIPPING_AIRBORNE_SERVER) {
            case 'production':
                $api = $this->live_url;
                break;
            case 'test':
            default:
                $api = $this->test_url;
                break;
        }

        $request = $this->populateXmlDocument();

        // begin cURL engine & execute the request
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "$api");
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "$request");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $airborne_response = curl_exec($ch);
            curl_close($ch);
        } else {
            // cURL method using exec() // curl -d -k if you have SSL issues
            exec("/usr/bin/curl -d \"$request\" $api", $response);
            $airborne_response = '';
            foreach ($response as $key => $value) {
                $airborne_response .= "$value";
            }
        }

        // Debugging
        if ($this->debug) {
            $this->captureXML($request, $airborne_response);
        }

        $airborne = _parsexml3254($airborne_response);
        // Check for errors
        if ($airborne['res:ErrorResponse']['->']['Response']['->']['Status'][0]['->']['ActionStatus'][0]['->'] == 'Error') {
            $error_message = 'The following errors have occured:';
            $i = 0;
            // We dont want to return this type of error
          return array('error' => $error_message);
        } elseif ($airborne['res:DCTResponse']['->']['GetQuoteResponse'][0]['->']['Note'][0]['->']['Condition'][0]['->']['ConditionData'][0]['->']) {
            $error_message = 'The following errors have occured:';
            for ($i = 0; $i < 5; $i++) {
                if ($airborne['res:DCTResponse']['->']['GetQuoteResponse'][0]['->']['Note'][0]['->']['Condition'][$i]['->']['ConditionData'][0]['->'])
                    $error_message .= '<br>' . ($i + 1) . '.&nbsp;' . $airborne['res:DCTResponse']['->']['GetQuoteResponse'][0]['->']['Note'][0]['->']['Condition'][$i]['->']['ConditionData'][0]['->'];
                if (!$airborne['res:DCTResponse']['->']['GetQuoteResponse'][0]['->']['Note'][0]['->']['Condition'][$i + 1]['->']['ConditionData'][0]['->'])
                    break;
            }
            return array('error' => $error_message);
        } else {
            $rates = array();
            $i = 0;

            $service = 'IE';
            $postage = $airborne['res:DCTResponse']['->']['GetQuoteResponse']['0']['->']['BkgDetails']['0']['->']['QtdShp']['0']['->']['ShippingCharge']['0']['->'];
            if (strcmp(MODULE_SHIPPING_AIRBORNE_EST_DELIVERY, "true") == 0) {
                $description = ' (' . $airborne['res:DCTResponse']['->']['GetQuoteResponse']['0']['->']['BkgDetails']['0']['->']['QtdShp']['0']['->']['ProductShortName']['0']['->'] . ')';
            } else {
                $description = '';
            }
            $rates[] = array('IE' => $postage, 'description' => $description);
        }
        return ((sizeof($rates) > 0) ? $rates : false);
    }

    function captureXML($request, $response) {
        if (MODULE_SHIPPING_AIRBORNE_DEBUG_METHOD == 'Print to screen') {
            echo 'Request:<br /><pre>' . htmlspecialchars($request) . '</pre><br /><br />';
            echo 'Response:<br /><pre>' . htmlspecialchars($response) . '</pre>';
        } else {
            $folder = ((substr(MODULE_SHIPPING_AIRBORNE_DEBUG_DIRECTORY, -1) != '/') ? MODULE_SHIPPING_AIRBORNE_DEBUG_DIRECTORY . '/' : MODULE_SHIPPING_AIRBORNE_DEBUG_DIRECTORY);

            $filename = $folder . 'request.txt';
            if (!$fp = fopen($filename, "w"))
                die("Failed opening file $filename");
            if (!fwrite($fp, $request))
                die("Failed writing to file $filename");
            fclose($fp);

            $filename = $folder . 'response.txt';
            if (!$fp = fopen($filename, "w"))
                die("Failed opening file $filename");
            if (!fwrite($fp, $response))
                die("Failed writing to file $filename");
            fclose($fp);
        }

        return true;
    }

    function zip_to_state($zip) {
// A PHP function to convert a zip code to a state code
// Created 6/16/06
// Copyright:  Verango
// Free for commercial and private usage under  GNU, copyright information must remain intact.

        switch (TRUE) {
            case (($zip >= 600 AND $zip <= 799) || ($zip >= 900 AND $zip <= 999)): // Puerto Rico (00600-00799 and 900--00999 ranges)
                return "PR";
            case ($zip >= 800 AND $zip <= 899): // US Virgin Islands (00800-00899 range)
                return "VI";
            case ($zip >= 1000 AND $zip <= 2799): // Massachusetts (01000-02799 range)
                return "MA";
            case ($zip >= 2800 AND $zip <= 2999): // Rhode Island (02800-02999 range)
                return "RI";
            case ($zip >= 3000 AND $zip <= 3899): // New Hampshire (03000-03899 range)
                return "NH";
            case ($zip >= 3900 AND $zip <= 4999): // Maine (03900-04999 range)
                return "ME";
            case ($zip >= 5000 AND $zip <= 5999): // Vermont (05000-05999 range)
                return "VT";
            case (($zip >= 6000 AND $zip <= 6999) AND $zip != 6390): // Connecticut (06000-06999 range excluding 6390)
                return "CT";
            case ($zip >= 70000 AND $zip <= 8999): // New Jersey (07000-08999 range)
                return "NJ";
            case (($zip >= 10000 AND $zip <= 14999) OR $zip == 6390 OR $zip == 501 OR $zip == 544): // New York (10000-14999 range and 6390, 501, 544)
                return "NY";
            case ($zip >= 15000 AND $zip <= 19699): // Pennsylvania (15000-19699 range)
                return "PA";
            case ($zip >= 19700 AND $zip <= 19999): // Delaware (19700-19999 range)
                return "DE";
            case (($zip >= 20000 AND $zip <= 20099) OR ( $zip >= 20200 AND $zip <= 20599) OR ( $zip >= 56900 AND $zip <= 56999)): // District of Columbia (20000-20099, 20200-20599, and 56900-56999 ranges)
                return "DC";
            case ($zip >= 20600 AND $zip <= 21999): // Maryland (20600-21999 range)
                return "MD";
            case (($zip >= 20100 AND $zip <= 20199) OR ( $zip >= 22000 AND $zip <= 24699)): // Virginia (20100-20199 and 22000-24699 ranges, also some taken from 20000-20099 DC range)
                return "VA";
            case ($zip >= 24700 AND $zip <= 26999): // West Virginia (24700-26999 range)
                return "WV";
            case ($zip >= 27000 AND $zip <= 28999): // North Carolina (27000-28999 range)
                return "NC";
            case ($zip >= 29000 AND $zip <= 29999): // South Carolina (29000-29999 range)
                return "SC";
            case (($zip >= 30000 AND $zip <= 31999) OR ( $zip >= 39800 AND $zip <= 39999)): // Georgia (30000-31999, 39901[Atlanta] range)
                return "GA";
            case ($zip >= 32000 AND $zip <= 34999): // Florida (32000-34999 range)
                return "FL";
            case ($zip >= 35000 AND $zip <= 36999): // Alabama (35000-36999 range)
                return "AL";
            case ($zip >= 37000 AND $zip <= 38599): // Tennessee (37000-38599 range)
                return "TN";
            case ($zip >= 38600 AND $zip <= 39799): // Mississippi (38600-39999 range)
                return "MS";
            case ($zip >= 40000 AND $zip <= 42799): // Kentucky (40000-42799 range)
                return "KY";
            case ($zip >= 43000 AND $zip <= 45999): // Ohio (43000-45999 range)
                return "OH";
            case ($zip >= 46000 AND $zip <= 47999): // Indiana (46000-47999 range)
                return "IN";
            case ($zip >= 48000 AND $zip <= 49999): // Michigan (48000-49999 range)
                return "MI";
            case ($zip >= 50000 AND $zip <= 52999): // Iowa (50000-52999 range)
                return "IA";
            case ($zip >= 53000 AND $zip <= 54999): // Wisconsin (53000-54999 range)
                return "WI";
            case ($zip >= 55000 AND $zip <= 56799): // Minnesota (55000-56799 range)
                return "MN";
            case ($zip >= 57000 AND $zip <= 57999): // South Dakota (57000-57999 range)
                return "SD";
            case ($zip >= 58000 AND $zip <= 58999): // North Dakota (58000-58999 range)
                return "ND";
            case ($zip >= 59000 AND $zip <= 59999): // Montana (59000-59999 range)
                return "MT";
            case ($zip >= 60000 AND $zip <= 62999): // Illinois (60000-62999 range)
                return "IL";
            case ($zip >= 63000 AND $zip <= 65999): // Missouri (63000-65999 range)
                return "MO";
            case ($zip >= 66000 AND $zip <= 67999): // Kansas (66000-67999 range)
                return "KS";
            case ($zip >= 68000 AND $zip <= 69999): // Nebraska (68000-69999 range)
                return "NE";
            case ($zip >= 70000 AND $zip <= 71599): // Louisiana (70000-71599 range)
                return "LA";
            case ($zip >= 71600 AND $zip <= 72999): // Arkansas (71600-72999 range)
                return "AR";
            case ($zip >= 73000 AND $zip <= 74999): // Oklahoma (73000-74999 range)
                return "OK";
            case (($zip >= 75000 AND $zip <= 79999) OR ( $zip >= 88500 AND $zip <= 88599)): // Texas (75000-79999 and 88500-88599 ranges)
                return "TX";
            case ($zip >= 80000 AND $zip <= 81999): // Colorado (80000-81999 range)
                return "CO";
            case ($zip >= 82000 AND $zip <= 83199): // Wyoming (82000-83199 range)
                return "WY";
            case ($zip >= 83200 AND $zip <= 83999): // Idaho (83200-83999 range)
                return "ID";
            case ($zip >= 84000 AND $zip <= 84999): // Utah (84000-84999 range)
                return "UT";
            case ($zip >= 85000 AND $zip <= 86999): // Arizona (85000-86999 range)
                return "AZ";
            case ($zip >= 87000 AND $zip <= 88499): // New Mexico (87000-88499 range)
                return "NM";
            case ($zip >= 88900 AND $zip <= 89999): // Nevada (88900-89999 range)
                return "NV";
            case ($zip >= 90000 AND $zip <= 96199): // California (90000-96199 range)
                return "CA";
            case ($zip >= 96700 AND $zip <= 96899): // Hawaii (96700-96899 range)            
                return "HI";
            case ($zip >= 97000 AND $zip <= 97999): // Oregon (97000-97999 range)
                return "OR";
            case ($zip >= 98000 AND $zip <= 99499): // Washington (98000-99499 range)
                return "WA";
            case ($zip >= 99500 AND $zip <= 99999): // Alaska (99500-99999 range) 
                return "AK";
        }
    }

    function pieceXml() {
        return '<Piece>
               <PieceID>1</PieceID>
               <Height>' . _xmlEnc1234($this->height) . '</Height>
               <Depth>' . _xmlEnc1234($this->length) . '</Depth>
               <Width>' . _xmlEnc1234($this->width) . '</Width>
               <Weight>' . _xmlEnc1234($this->weight) . '</Weight>
            </Piece>';
    }

    function populateXmlDocument() {
        global $order, $shipping_weight, $shipping_num_boxes, $method;

        $returnXml = '<?xml version="1.0" encoding="UTF-8"?>
                        <p:DCTRequest xmlns:p="http://www.dhl.com" xmlns:p1="http://www.dhl.com/datatypes" xmlns:p2="http://www.dhl.com/DCTRequestdatatypes" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.dhl.com DCT-req.xsd ">
                            <GetQuote>
                                <Request>
                                    <ServiceHeader>
                                        <SiteID>' . _xmlEnc1234(MODULE_SHIPPING_AIRBORNE_SYSTEMID) . '</SiteID>
                                        <Password>' . _xmlEnc1234(MODULE_SHIPPING_AIRBORNE_PASS) . '</Password>
                                    </ServiceHeader>
                                </Request>
                                <From>
                                    <CountryCode>' . _xmlEnc1234(SHIPPING_ORIGIN_COUNTRY_CODE) . '</CountryCode>
                                    <Postalcode>' . _xmlEnc1234(SHIPPING_ORIGIN_ZIP) . '</Postalcode>
                                    ' . ((SHIPPING_ORIGIN_CITY) ? '<City>' . _xmlEnc1234(SHIPPING_ORIGIN_CITY) . '</City>' : '') . '
                                </From>
                                <BkgDetails>
                                    <PaymentCountryCode>' . _xmlEnc1234(SHIPPING_ORIGIN_COUNTRY_CODE) . '</PaymentCountryCode>
                                    <Date>' . _makedate3254($this->shipping_day, 'day', 'yyyy-mm-dd') . '</Date>
                                    <ReadyTime>PT9H</ReadyTime>
                                    <DimensionUnit>IN</DimensionUnit>
                                    <WeightUnit>LB</WeightUnit>
                                    <Pieces>
                                    ' . ((isset($this->dimensions)) ? $this->pieceXml() : '<Piece><PieceID>1</PieceID><Weight>' . _xmlEnc1234($this->weight) . '</Weight></Piece>') . '
                                    </Pieces>
                                    '.((MODULE_SHIPPING_AIRBORNE_ACCT_NBR)?'<PaymentAccountNumber>' . MODULE_SHIPPING_AIRBORNE_ACCT_NBR . '</PaymentAccountNumber>':'').'
                                    <IsDutiable>' . (($this->dutiable) ? 'Y' : 'N') . '</IsDutiable>
                                </BkgDetails>
                                <To>
                                    <CountryCode>' . _xmlEnc1234($this->destination_country == "GB" ? "UK" : $this->destination_country) . '</CountryCode>
                                    <Postalcode>' . _xmlEnc1234($this->destination_postal) . '</Postalcode>
                                    ' . (($this->destination_city) ? '<City>' . _xmlEnc1234($this->destination_city) . '</City>' : '') . '
                                </To>
                                ' . (($this->dutiable) ? '<Dutiable>
                                        <DeclaredCurrency>USD</DeclaredCurrency>
                                        <DeclaredValue>' . $order->info['total'] . '</DeclaredValue>
                                    </Dutiable>' : '') . '
                            </GetQuote>
                        </p:DCTRequest>';

        return $returnXml;
    }

    // End of class
}

/*
  Function to parse the returned XML data into an array.
  Borrowed from Hans Anderson's xmlize() function.
  http://www.hansanderson.com/php/xml/
 */

function _parsexml3254($data, $WHITE = 1) {
    $data = trim($data);
    $vals = $index = $array = array();
    $parser = xml_parser_create();
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, $WHITE);
    xml_parse_into_struct($parser, $data, $vals, $index);
    xml_parser_free($parser);

    $i = 0;
    $tagname = $vals[$i]['tag'];
    $array[$tagname]['@'] = (isset($vals[$i]['attributes'])) ? $vals[$i]['attributes'] : array();
    $array[$tagname]["->"] = xml_depth3254($vals, $i);
    return $array;
}

function xml_depth3254($vals, &$i) {
    $children = array();
    if (isset($vals[$i]['value']))
        array_push($children, $vals[$i]['value']);

    while (++$i < count($vals)) {
        switch ($vals[$i]['type']) {
            case 'open':
                $tagname = (isset($vals[$i]['tag'])) ? $vals[$i]['tag'] : '';
                $size = (isset($children[$tagname])) ? sizeof($children[$tagname]) : 0;
                if (isset($vals[$i]['attributes']))
                    $children[$tagname][$size]['@'] = $vals[$i]["attributes"];
                $children[$tagname][$size]['->'] = xml_depth3254($vals, $i);
                break;
            case 'cdata':
                array_push($children, $vals[$i]['value']);
                break;
            case 'complete':
                $tagname = $vals[$i]['tag'];
                $size = (isset($children[$tagname])) ? sizeof($children[$tagname]) : 0;
                $children[$tagname][$size]["->"] = (isset($vals[$i]['value'])) ? $vals[$i]['value'] : '';
                if (isset($vals[$i]['attributes']))
                    $children[$tagname][$size]['@'] = $vals[$i]['attributes'];
                break;
            case 'close':
                return $children;
                break;
        }
    }

    return $children;
}

/*
  Function to generate arbitrary, formatted numeric or string date.
  Copyright (C) 2003  Erich Spencer
 */

function _makedate3254($unit = '', $time = '', $mask = '') {
    $validunit = '/^[-+]?\b[0-9]+\b$/';
    $validtime = '/^\b(day|week|month|year)\b$/i';
    $validmask = '/^(short|long|([dmy[:space:][:punct:]]+))$/i';

    if (!preg_match($validunit, $unit))
        $unit = -1;
    if (!preg_match($validtime, $time))
        $time = 'day';
    if (!preg_match($validmask, $mask))
        $mask = 'yyyymmdd';

    switch ($mask) {
        case 'short': // 7/4/2003 
            $mask = "n/j/Y";
            break;
        case 'long':  // Friday, July 4, 2003 
            $mask = "l, F j, Y";
            break;
        default:
            $chars = (preg_match('/([[:space:]]|[[:punct:]])/', $mask)) ? preg_split('/([[:space:]]|[[:punct:]])/', $mask, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE) : preg_split('/(m*|d*|y*)/i', $mask, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
            foreach ($chars as $key => $char) {
                switch (TRUE) {
                    case eregi("m{3,}", $chars[$key]): // 'mmmm' = month string 
                        $chars[$key] = "F";
                        break;
                    case eregi("m{2}", $chars[$key]):  // 'mm'   = month as 01-12 
                        $chars[$key] = "m";
                        break;
                    case eregi("m{1}", $chars[$key]):  // 'm'    = month as 1-12 
                        $chars[$key] = "n";
                        break;
                    case eregi("d{3,}", $chars[$key]): // 'dddd' = day string 
                        $chars[$key] = "l";
                        break;
                    case eregi("d{2}", $chars[$key]):  // 'dd'   = day as 01-31 
                        $chars[$key] = "d";
                        break;
                    case eregi("d{1}", $chars[$key]):  // 'd'    = day as 1-31 
                        $chars[$key] = "j";
                        break;
                    case eregi("y{3,}", $chars[$key]): // 'yyyy' = 4 digit year 
                        $chars[$key] = "Y";
                        break;
                    case eregi("y{1,2}", $chars[$key]):// 'yy'   = 2 digit year 
                        $chars[$key] = "y";
                        break;
                }
            }

            $mask = implode('', $chars);
            break;
    }

    $when = date($mask, strtotime("$unit $time"));
    return $when;
}

/*
  Function to have options for shipping methods.
  Borrowed from UPS Choice v1.7
  Credit goes to Fritz Clapp
 */

function _selectOptions3254($select_array, $key_value, $key = '') {
    foreach ($select_array as $select_option) {
        $name = (($key) ? 'configuration[' . $key . '][]' : 'configuration_value');
        $string .= '<br><input type="checkbox" name="' . $name . '" value="' . $select_option . '"';
        $key_values = explode(",", $key_value);
        if (in_array($select_option, $key_values))
            $string .= ' checked="checked"';
        $string .= '> ' . $select_option;
    }
    return $string;
}

/*

 */

function _xmlCharCallback1235($m) {
    return "&#" . ord($m[0]) . ";";
}

function _xmlEnc1234($str) {
    return preg_replace_callback('/\W/', '_xmlCharCallback1235', $str);
}

?>
