<?php

function _vae_store_calculate_shipping_options($weight, $num_items, $subtotal, $zip, $country, $state, $city, $address, $handling) {
  global $shipping_weight, $shipping_num_boxes, $shipping_num_items, $shipping_subtotal, $order, $_VAE, $method, $origin_country;
  $shipping_weight = $weight;
  $shipping_subtotal = $subtotal;
  $shipping_num_boxes = 1;
  $shipping_num_items = $num_items;
  $origin_country = $_VAE['settings']['store_shipping_origin_country'];
  if (!strlen($origin_country)) $origin_country = "US";
  $res = array();
  _vae_store_oscommerce_load();
  $order = new tep_order($zip, $country, $state, $city, $address, $subtotal);
  foreach (_vae_store_shipping_methods() as $method) {
    if (!$_VAE['no_shipping_restrictions']) {
      if (strlen($method['destination_country'])) {
        if (strstr($method['destination_country'], "cont_")) {
          if (!_vae_store_continent_match(str_replace("cont_", "", $method['destination_country']), $country)) continue;
        } elseif ($method['destination_country'] == "US48") {
          if ($country != "US" || !in_array($state, $_VAE['us48'])) continue;
        } elseif (($method['destination_country'] != $country)) {
          continue;
        }
      }
      if (strlen($method['domestic_only'])) {
        if ($country && ($origin_country != $country)) {
          continue;
        }
      }
      if (strlen($method['international_only'])) {
        if (($origin_country == $country)) continue;
      }
      if (strlen($method['minimum_order_amount'])) {
        if ($subtotal < $method['minimum_order_amount']) continue;
      }
      if (strlen($method['maximum_order_amount'])) {
        if ($subtotal > $method['maximum_order_amount']) continue;
      }
      if (strlen($method['minimum_order_num'])) {
        if ($num_items < $method['minimum_order_num']) continue;
      }
      if (strlen($method['maximum_order_num'])) {
        if ($num_items > $method['maximum_order_num']) continue;
      }
      if (strlen($method['class']) && !is_array($method['class'])) {
        $bad = false;
        foreach ($_SESSION['__v:store']['cart'] as $id => $r) {
          if ($method['class'] != $r['shipping_class'])  $bad = true;
        }
        if ($bad) {
          continue;
        }
      }
    }
    if ($method['user']) {
      $quotes = array('methods' => array($method));
      $ext = "";
    } else {
      if (($method['method_name'] != "usps") && _vae_store_usps_only($country, $state, $address)) {
        continue; 
      }
      $max_weight_per_box = 44;
      require_once(dirname(__FILE__) . "/oscommerce/" . $method['method_name'] . ".php");
      $ext = $method['method_name'];
      $class = new $ext();
      $box_weights = $_SESSION['__v:store']['total_weight'];
      if (!$box_weights && ($weight > $max_weight_per_box )) {
        $remaining_weight = $weight;
        $box_weights = array();
        while ($remaining_weight > $max_weight_per_box) {
          $box_weights[] = $max_weight_per_box;
          $remaining_weight -= $max_weight_per_box;
        }
        $box_weights[] = $remaining_weight;
      }
      if ($box_weights) {
        $quotes = array('methods' => array());
        foreach ($box_weights as $box_weight) {
          $shipping_weight = $box_weight;
          $these_quotes = $class->quote();
          foreach ($these_quotes["methods"] as $r) {
            $gotit = false;
            foreach ($quotes["methods"] as $id => $r2) {
              if ($r2["title"] == $r['title']) {
                $quotes["methods"][$id]['cost'] += $r['cost'];
                $gotit = true;
                break;
              }
            }
            if (!$gotit) $quotes["methods"][] = $r;
          }
        }
      } else {
        $quotes = $class->quote();
      }
    }
    //if ($quotes["error"]) _vae_error(ucwords($method["method_name"]) . " Shipping Integration Error: " . $quotes["error"]);
    foreach ($quotes["methods"] as $r) {
      if (is_numeric($_VAE['settings']['store_shipping_pad_dollars_per_order'])) $r['cost'] += $_VAE['settings']['store_shipping_pad_dollars_per_order'];
      if (is_numeric($_VAE['settings']['store_shipping_pad_percent_dollars'])) $r['cost'] = ($r['cost'] + $handling) * (1 + ($_VAE['settings']['store_shipping_pad_percent_dollars']/100));
      if (is_numeric($method['pad_dollars_per_order'])) $r['cost'] += $method['pad_dollars_per_order'];
      if (is_numeric($method['pad_percent'])) $r['cost'] = ($r['cost'] + $handling) * (1 + ($method['pad_percent']/100));
      if (!$r['no_style']) $styled_ext = ($ext == "fedex" ? "FedEx" : strtoupper($ext));
      if (!isset($r['keep_titles'])) $r['title'] = ($styled_ext ? $styled_ext . " " : "") . $r['title'];
      if ($method['display_name']) $r['title'] = $method['display_name'];
      $r['secondary'] = ($method['secondary'] ? true : false);
      if (strlen($method["free_shipping_threshold"]) && ($method["free_shipping_threshold"] < _vae_store_compute_subtotal())) {
        $r['free'] = true;
      }
      $r['cost'] = str_replace(",", "", number_format($r['cost'], 2));
      $r['rate_group'] = $method['rate_group'];
      array_push($res, $r);
    }
  }
  usort($res, _vae_store_sort_shipping_methods);
  $final = array();
  $already_seen = array();
  foreach ($res as $r) {
    if ($r['rate_group']) {
      if ($already_seen[$r['rate_group']] && !$_SESSION['__v:user_id']) {
        continue;
      }
      $already_seen[$r['rate_group']] = true;
    }
    if ($r['free']) $r['cost'] = 0.00;
    array_unshift($final, $r);
  }
  return $final;
}

function _vae_store_continent_match($continent, $country) {
  foreach (explode("\n", file_get_contents(dirname(__FILE__) . "/../data/countries.txt")) as $line) {
    if (substr($line, 0, 5) == $continent . " " . $country) return true;
  }
  return false;
}

function _vae_store_sort_shipping_methods($a, $b) {
  if ($a['secondary'] && !$b['secondary']) return 1;
  if ($b['secondary'] && !$a['secondary']) return -1;
  return ($a['cost'] > $b['cost'] ? 1 : -1);
}

function _vae_store_oscommerce_load() {
  global $_VAE;
  require_once(dirname(__FILE__) . "/oscommerce/http_client.php");
  define("SHIPPING_ORIGIN_COUNTRY", "1");
  define("MODULE_SHIPPING_USPS_USERID", "533MISHK7183");
  define("MODULE_SHIPPING_USPS_PASSWORD", "533MISHK7183");
  define("MODULE_SHIPPING_USPS_SERVER", "production");
  define("MODULE_SHIPPING_USPS_TEXT_DAY", "day");
  define("MODULE_SHIPPING_USPS_TEXT_DAYS", "days");
  define("MODULE_SHIPPING_USPS_TEXT_WEEKS", "weeks");
  define("SHIPPING_ORIGIN_ZIP", $_VAE['settings']['store_shipping_origin_zip']);
  define("MODULE_SHIPPING_FEDEX1_WEIGHT", "LBS");
  define("MODULE_SHIPPING_FEDEX1_DROPOFF", 1);
  define('MODULE_SHIPPING_AIRBORNE_PACKAGE', "O");
  define('MODULE_SHIPPING_AIRBORNE_SHIPMENT_DAY_TYPE', "Ship in x number of days");
  define('MODULE_SHIPPING_AIRBORNE_DAYS_TO_SHIP', '2');
  define('MODULE_SHIPPING_AIRBORNE_SHIPMENT_DAY', 'Monday');
  define('MODULE_SHIPPING_AIRBORNE_CONTENTS_DESCRIPTION', 'Goods');
  define('MODULE_SHIPPING_AIRBORNE_DUTY_PAYMENT_TYPE', 'R');
}

function _vae_store_usps_only($country, $state, $address) {
  if ($country != "US") return false;
  if (preg_match('/^((P\.?O\.?(B\.?)?(\s+Box)?)|(Post\s+Office(\s+Box)?))/i', $address)) return true;
  if ($state == "AA" || $state == "AE" || $state == "AP") return true;
  return false;
}

class tep_order {
  function tep_order($zip, $country, $state, $city, $street_address, $total) {
    $this->info = array('total' => $total);
    $this->delivery = array('state' => $state, 'city' => $city, 'street_address' => $street_address, 'postcode' => $zip, 'country' => array('id' => ($country == "US" ? 1 : "9999"), 'iso_code_2' => $country));
  }
}
function tep_get_countries($a, $b) {
  return array('countries_iso_code_2' => "US");
}
function tep_image($a, $b) {
  return null;
}
function tep_not_null($a) {
  return ($a != null);
}
function tep_round_up($amount, $places) {
  if ($places < 0) { $places = 0; }
  $mult = pow(10, $places);
	return (ceil($amount * $mult) / $mult);
}


?>
