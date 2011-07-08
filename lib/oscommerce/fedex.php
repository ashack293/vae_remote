<?php

class fedex {
  var  $intl, $meter, $quotes, $server;

  function fedex() {
    global $method;
    $this->domestic_types = array(
      '01' => 'Priority (by 10:30AM, later for rural)',
      '03' => '2 Day Air',
      '05' => 'Standard Overnight (by 3PM, later for rural)',
      '06' => 'First Overnight', 
      '20' => 'Express Saver (3 Day)',
      '90' => 'Home Delivery',
      '92' => 'Ground Service'
    );
    $this->international_types = array(
      '01' => 'International Priority (1-3 Days)',
      '03' => 'International Economy (4-5 Days)',
      '06' => 'International First',
      '90' => 'Home Delivery',
      '92' => 'Ground Service'
    );
    $vdomestic = explode(",", $method['domestic_types']);
    $vinternational = explode(",", $method['international_types']);
    foreach ($this->domestic_types as $id => $desc) {
      if (!in_array($id, $vdomestic)) unset($this->domestic_types[$id]);
    }
    foreach ($this->international_types as $id => $desc) {
      if (!in_array($id, $vinternational)) unset($this->international_types[$id]);
    }
  }
  
  function error($msg) {
    _vae_error("FedEx Shipping Module Error: " . $msg);
  }

  function quote() {
    global $shipping_weight;
    $countries_array = tep_get_countries(SHIPPING_ORIGIN_COUNTRY, true);
    $this->country = $countries_array['countries_iso_code_2'];
    $this->_setWeight(($shipping_weight < 1 ? 1 : $shipping_weight));
    $fedexQuote = $this->_getQuote();
    $this->quotes = array();
    if (is_array($fedexQuote) && !isset($fedexQuote['error'])) {
      foreach ($fedexQuote as $type => $cost) {
        if ($this->intl === false) {
          $service_descr = $this->domestic_types[substr($type, 0, 2)];
        } else {
          $service_descr = $this->international_types[substr($type, 0, 2)];
        }
        $this->quotes['methods'][] = array('id' => substr($type, 0, 2), 'title' => $service_descr, 'cost' => $cost);
      }
    }
    return $this->quotes;
  }

  function _setWeight($pounds) {
    $this->pounds = sprintf("%01.1f", $pounds);
  }

  function _AccessFedex($data) {
    $this->server = 'gateway.fedex.com/GatewayDC';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, 'https://' . $this->server);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Referer: Store",
                                               "Host: " . $this->server,
                                               "Accept: image/gif,image/jpeg,image/pjpeg,text/plain,text/html,*/*",
                                               "Pragma:",
                                               "Content-Type:image/gif"));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $reply = curl_exec($ch);
    curl_close ($ch);
    return $reply;
  }

  function _getMeter() {
    global $method;
    $hash = md5(serialize($method));
    if ($cached = _vae_read_file("_vae_store_shipping_fedex_meter_number.txt")) {
      $cached_data = unserialize($cached);
      if ($hash == $cached_data['hash'] || $cached_data['hash'] == "manual") {
        $this->meter = $cached_data;
        return true;
      }
    }
    $data = '0,"211"';
    $data .= '10,"' . $method['account'] . '"';
    $data .= '4003,"' . $method['store_owner'] . '"';
    $data .= '4007,"' . $method['store_name'] . '"'; // Subscriber company name
    $data .= '4008,"' . $method['address_1'] . '"'; // Subscriber Address line 1
    $data .= '4009,"' . $method['address_2'] . '"'; // Subscriber Address Line 2
    $data .= '4011,"' . $method['city'] . '"'; // Subscriber City Name
    $data .= '4012,"' . $method['state'] . '"'; // Subscriber State code
    $data .= '4013,"' . $method['postal'] . '"'; // Subscriber Postal Code
    $data .= '4014,"' . $this->country . '"'; // Subscriber Country Code
    $data .= '4015,"' . $method['phone'] . '"'; // Subscriber phone number
    $data .= '99,""'; // End of Record, required
    $fedexData = $this->_AccessFedex($data);
    $meterStart = strpos($fedexData,'"498,"');
    if ($meterStart === false) {
      if (strlen($fedexData) == 0) {
        $this->error_message = "We couldn't register you for a FedEx Meter Number.  It looks like the FedEx servers may be down.  FedEx Error #1.";
      } else {
        $fedexData = $this->_ParseFedex($fedexData);
        $this->error_message = "We couldn't register you for a FedEx Meter Number.  Double check your settings.  FedEx Error #" . $fedexData['2'] . ': ' . $fedexData['3'] . ".   (Hash: $hash)";
      }
      $this->error($this->error_message);
      return false;
    }
    $meterStart += 6;
    $meterEnd = strpos($fedexData, '"', $meterStart);
    $this->meter = array('hash' => $hash, 'data' => substr($fedexData, $meterStart, $meterEnd - $meterStart));
    _vae_write_file("_vae_store_shipping_fedex_meter_number.txt", serialize($this->meter));
    return true;
  }

  function _ParseFedex($data) {
    $current = 0;
    $length = strlen($data);
    $resultArray = array();
    while ($current < $length) {
      $endpos = strpos($data, ',', $current);
      if ($endpos === false) { break; }
      $index = substr($data, $current, $endpos - $current);
      $current = $endpos + 2;
      $endpos = strpos($data, '"', $current);
      $resultArray[$index] = substr($data, $current, $endpos - $current);
      $current = $endpos + 1;
    }
    return $resultArray;
  }
   
  function _getQuote() {
    global $order, $method;
    if ($this->_getMeter() === false) return false;
    $data = '0,"25"'; // TransactionCode
    $data .= '10,"' . $method['account'] . '"'; // Sender fedex account number
    $data .= '498,"' . $this->meter['data'] . '"';
    $data .= '8,"' . $method['state'] . '"'; // Sender state code
    $orig_zip = str_replace(array(' ', '-'), '', $method['postal']);
    $data .= '9,"' . $orig_zip . '"'; // Origin postal code
    $data .= '117,"' . $this->country . '"'; // Origin country
    $dest_zip = str_replace(array(' ', '-'), '', $order->delivery['postcode']);
    $data .= '17,"' . $dest_zip . '"'; // Recipient zip code
    if ($order->delivery['country']['iso_code_2'] == "US" || $order->delivery['country']['iso_code_2'] == "CA" || $order->delivery['country']['iso_code_2'] == "PR") {
      $state = $order->delivery['state'];
      if ($state == "QC") $state = "PQ";
      $data .= '16,"' . $state . '"';
    }
    $data .= '50,"' . $order->delivery['country']['iso_code_2'] . '"'; // Recipient country
    $data .= '75,"' . MODULE_SHIPPING_FEDEX1_WEIGHT . '"'; // Weight units
    $data .= '1116,"I"'; // Dimension units
    $data .= '1401,"' . $this->pounds . '"'; 
    $data .= '1529,"1"'; // Quote discounted rates
    $data .= '440,"N"'; // Residential address
    $data .= '1273,"01"'; // Package type
    $data .= '1333,"' . MODULE_SHIPPING_FEDEX1_DROPOFF . '"'; // Drop of drop off or pickup
    $data .= '99,""'; // End of record
    $fedexData = $this->_AccessFedex($data);
    if (strlen($fedexData) == 0) {
      $this->error_message = 'No data returned from Fedex, perhaps the Fedex site is down';
      return false;
    }
    $fedexData = $this->_ParseFedex($fedexData);
    $i = 1;
    if ($state == "VI" || $state == "GU" || $state == "PR" || $state == "MH") {
      $this->intl = true;
    } elseif ($this->country == $order->delivery['country']['iso_code_2']) {
      $this->intl = false;
    } else {
      $this->intl = true;
    }
    $rates = NULL;
    while (isset($fedexData['1274-' . $i])) {
      if (($this->intl && isset($this->international_types[$fedexData['1274-' . $i]])) || (!$this->intl && isset($this->domestic_types[$fedexData['1274-' . $i]]))) {
        if (isset($fedexData['3058-' . $i])) {
          $rates[$fedexData['1274-' . $i] . $fedexData['3058-' . $i]] = $fedexData['1419-' . $i];
        } else {
          $rates[$fedexData['1274-' . $i]] = $fedexData['1419-' . $i];
        }
      }
      $i++;
    }
    return ((sizeof($rates) > 0) ? $rates : false);
  }
}

?>
