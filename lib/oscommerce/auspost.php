<?php

class auspost {

  function quote($method = '') {
    global $method, $order, $cart, $shipping_weight, $shipping_num_boxes, $total_weight;
    $frompcode = SHIPPING_ORIGIN_ZIP;
    $topcode = $order->delivery['postcode'];
    $country = $order->delivery['country']['iso_code_2'];

    $sweight = $shipping_weight;
    $quotes = array('methods' => array());
    $types = explode(",", $method['types']);
    foreach ($types as $type) {
      $url = "http://drc.edeliver.com.au/ratecalc.asp?Pickup_Postcode=$frompcode&Destination_Postcode=$topcode&Country=$country&Weight=$sweight&Service_Type=$type&Height=200&Width=200&Length=200&Quantity=1";
      $myfile = file($url);
      foreach($myfile as $vals) {
        $bits = explode("=", $vals);
        $$bits[0] = $bits[1];
      }
      if ($charge > 0) {
        $quotes['methods'][] = array('no_style' => true, 'id' => 'auspost', 'title' => 'Australia Post ' . ucwords(strtolower($type)), 'cost' => $charge);
      } elseif ($err_msg) {
        $potential_error = $err_msg;
      }
    }
    if (!count($quotes['methods']) && strlen($potential_error)) {
      if (!strstr($potential_error, "Invalid Destination Postcode")) _vae_error("Australia Post Shipping Error: " . $potential_error);
    }
    return $quotes;
  }

}
