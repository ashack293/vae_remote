<?php

class fedex {
  var $code, $title, $description, $sort_order, $tax_class, $fedex_key, $fedex_pwd, $fedex_act_num, $fedex_meter_num, $country;

  function fedex() {
    global $method, $origin_country;
    $this->fedex_key        = $method['key'];
    $this->fedex_pwd        = $method['keypwd'];
    $this->fedex_act_num    = $method['account'];
    $this->fedex_meter_num  = $method['meter_number'];
    $this->country          = $origin_country;
  }

  function quote($method = '') {  
    global $shipping_weight, $shipping_num_boxes, $cart, $order, $method;

    $path_to_wsdl = dirname(__FILE__) . "/../../vendor/fedex/RateService_v9.wsdl";
    ini_set("soap.wsdl_cache_enabled", "0");
    $client = new SoapClient($path_to_wsdl, array('trace' => 1));

    $vdomestic = explode(",", $method['domestic_types']);
    $vinternational = explode(",", $method['international_types']);
    
    $this->types = array();
    if (in_array('01', $vinternational)) {
      $this->types['INTERNATIONAL_PRIORITY'] = array();
      $this->types['EUROPE_FIRST_INTERNATIONAL_PRIORITY'] = array();
    }
    if (in_array('03', $vinternational)) {
      $this->types['INTERNATIONAL_ECONOMY'] = array();
    }  
    if (in_array('05', $vdomestic)) {
      $this->types['STANDARD_OVERNIGHT'] = array();
    }
    if (in_array('06', $vdomestic)) {
      $this->types['FIRST_OVERNIGHT'] = array();
    }
    if (in_array('01', $vdomestic)) {
      $this->types['PRIORITY_OVERNIGHT'] = array();
    }
    if (in_array('03', $vdomestic)) {
      $this->types['FEDEX_2_DAY'] = array();
    }
    _vae_debug("comparing " . $order->delivery['country']['iso_code_2'] . " to " . $this->country);
    if ((in_array('92', $vdomestic) && $order->delivery['country']['iso_code_2'] == $this->country) || (in_array('92', $vinternational) && $order->delivery['country']['iso_code_2'] != $this->country)) {
      $this->types['FEDEX_GROUND'] = array();
      $this->types['GROUND_HOME_DELIVERY'] = array();
    }
    if (in_array('92', $vinternational)) {
      $this->types['INTERNATIONAL_GROUND'] = array();
    }
    if (in_array('20', $vdomestic)) {
      $this->types['FEDEX_EXPRESS_SAVER'] = array();
    }
    $street_address = $order->delivery['street_address'];
    $street_address2 = $order->delivery['suburb'];
    $city = $order->delivery['city'];
    $state = $order->delivery['state'];
    if ($state == "QC") $state = "PQ";
    $postcode = str_replace(array(' ', '-'), '', $order->delivery['postcode']);
    $country_id = $order->delivery['country']['iso_code_2'];
    $totals = $shipping_subtotal;
    $this->insurance = 0;
    $request['WebAuthenticationDetail'] = array('UserCredential' =>
                                          array('Key' => $this->fedex_key, 'Password' => $this->fedex_pwd));
    $request['ClientDetail'] = array('AccountNumber' => $this->fedex_act_num, 'MeterNumber' => $this->fedex_meter_num);
    $request['TransactionDetail'] = array('CustomerTransactionId' => ' *** Rate Request v9 using PHP ***');
    $request['Version'] = array('ServiceId' => 'crs', 'Major' => '9', 'Intermediate' => '0', 'Minor' => '0');
    $request['ReturnTransitAndCommit'] = true;
    $request['RequestedShipment']['DropoffType'] = 'REGULAR_PICKUP';
    $request['RequestedShipment']['ShipTimestamp'] = date('c');
    $request['RequestedShipment']['PackagingType'] = 'YOUR_PACKAGING';
    $request['RequestedShipment']['TotalInsuredValue']=array('Ammount'=> 0, 'Currency' => "USD");
    $request['WebAuthenticationDetail'] = array('UserCredential' => array('Key' => $this->fedex_key, 'Password' => $this->fedex_pwd));
    $request['ClientDetail'] = array('AccountNumber' => $this->fedex_act_num, 'MeterNumber' => $this->fedex_meter_num);
    $request['RequestedShipment']['Shipper'] = array('Address' => array(
                                                     'StreetLines' => array($method['address_1'], $method['address_2']), // Origin details
                                                     'City' => $method['city'],
                                                     'StateOrProvinceCode' => $method['state'],
                                                     'PostalCode' => $method['postal'],
                                                     'CountryCode' => $this->country));          
    $request['RequestedShipment']['Recipient'] = array('Address' => array (
                                                       'StreetLines' => array($street_address, $street_address2), // customer street address
                                                       'City' => $city, //customer city
                                                       'PostalCode' => $postcode, //customer postcode
                                                       'CountryCode' => $country_id,
                                                       'Residential' => ($order->delivery['company'] != '' ? false : true))); //customer county code
    if (in_array($country_id, array('US', 'CA'))) {
      $request['RequestedShipment']['Recipient']['StateOrProvinceCode'] = $state;
    }
    $request['RequestedShipment']['ShippingChargesPayment'] = array('PaymentType' => 'SENDER',
                                                                    'Payor' => array('AccountNumber' => $this->fedex_act_num,
                                                                    'CountryCode' => $this->country));
    $request['RequestedShipment']['RateRequestTypes'] = 'LIST';
    $request['RequestedShipment']['PackageDetail'] = 'INDIVIDUAL_PACKAGES';
    $request['RequestedShipment']['RequestedPackageLineItems'] = array();
    if ($shipping_weight == 0) $shipping_weight = 0.1;
    for ($i=0; $i<$shipping_num_boxes; $i++) {
      $request['RequestedShipment']['RequestedPackageLineItems'][] = array('Weight' => array('Value' => $shipping_weight,
                                                                                             'Units' => "LB"));
    }
    $request['RequestedShipment']['PackageCount'] = $shipping_num_boxes;
    if ($method['deliver_on_saturday'] == '1') {
      $request['RequestedShipment']['ServiceOptionType'] = 'SATURDAY_DELIVERY';
    }

		//_vae_debug($request);
    $response = $client->getRates($request);
    //_vae_debug($response);

    if ($response->HighestSeverity != 'FAILURE' && $response->HighestSeverity != 'ERROR' && is_array($response->RateReplyDetails) || is_object($response->RateReplyDetails)) {
      if (is_object($response->RateReplyDetails)) {
        $response->RateReplyDetails = get_object_vars($response->RateReplyDetails);
      }
      $this->quotes = array('id' => "fedex",
                            'module' => "FedEx");
      $methods = array();
      foreach ($response->RateReplyDetails as $rateReply) {
        if (array_key_exists($rateReply->ServiceType, $this->types)) {
          if (MODULE_SHIPPING_FEDEX_WEB_SERVICES_RATES=='LIST') {
            foreach ($rateReply->RatedShipmentDetails as $ShipmentRateDetail) {
              if ($ShipmentRateDetail->ShipmentRateDetail->RateType=='PAYOR_LIST_PACKAGE') {
                $cost = $ShipmentRateDetail->ShipmentRateDetail->TotalNetCharge->Amount;
                $cost = (float)round(preg_replace('/[^0-9.]/', '',  $cost), 2);
              }
            }
          } else {
            $cost = $rateReply->RatedShipmentDetails[0]->ShipmentRateDetail->TotalNetCharge->Amount;
            $cost = (float)round(preg_replace('/[^0-9.]/', '',  $cost), 2);
          }
          $methods[] = array('id' => str_replace('_', '', $rateReply->ServiceType),                                                   
                             'title' => str_replace("Fedex ", "", ucwords(strtolower(str_replace('_', ' ', $rateReply->ServiceType)))),     
                             'cost' => $cost);
        }
      }
      $this->quotes['methods'] = $methods;     
    } else {
      $message = 'Error in processing transaction.<br /><br />';
      foreach ($response -> Notifications as $notification) {
        if(is_array($response -> Notifications)) {
          $message .= $notification->Severity;
          $message .= ': ';
          $message .= $notification->Message . '<br />';
        } else {
          $message .= $notification->Message . '<br />';
        }
      }
      $this->quotes = array('module' => $this->title,
                            'error'  => $message);
    }
    return $this->quotes;
  }
}
