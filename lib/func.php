<?php

function verb_clubtime() {
  $a = strtotime(strftime("%B %d, %Y"));
  if (strftime("%H") < 3) $a -= 86400;
  return $a;
}

function verb_curday() {
  return strftime("%Y-%m-%d");
}

function verb_curmonth() {
  return strftime("%Y-%m");
}

function verb_curyear() {
  return strftime("%Y");
}

function verb_daterange($d) {
  if (!strlen($d)) return array(1, 9999999999);
  $date = str_replace("/", "-", str_replace(array("'", '"'), "", $d));
  $sections = explode("-", $date);
  $y = substr($sections[0], 0, 4);
  $m = substr($sections[1], 0, 2);
  $d = substr($sections[2], 0, 2);
  if (count($sections) == 1) {
    $start = strtotime($y . "-01-01");
    $end = strtotime(($y + 1) . "-01-01");
  } elseif (count($sections) == 2) {
    $start = strtotime($y . "-" . $m . "-01");
    $m++;
    if ($m > 12) { $m = 01; $y++; }
    $end = strtotime($y . "-" . $m . "-01");
  } else {
    $start = strtotime($y . "-" . $m . "-" . $d);
    $end = $start + (60*60*24);
  }
  return array($start, $end);
}

function verb_host() {
  if ($_REQUEST['__host']) return $_REQUEST['__host'];
  if (isset($_SESSION['__v:pre_ssl_host'])) return $_SESSION['__v:pre_ssl_host'];
  return $_SERVER['HTTP_HOST'];
}

function verb_lowercase($d) {
  return strtolower($d);
}

function verb_nextday($d) {
  if (!strlen($d)) $d = curday();
  return strftime("%Y-%m-%d", strtotime(strftime("%Y-%m-%d", strtotime($d)))+86400);
}

function verb_nextmonth($d) {
  if (!strlen($d)) $d = curmonth();
  return strftime("%Y-%m", strtotime(strftime("%Y-%m", strtotime($d)))+86400*32);
}

function verb_nextyear($d) {
  if (!strlen($d)) $d = curyear();
  return $d + 1;
}

function verb_now() {
  return strtotime(strftime("%B %d, %Y"));
}

function verb_path() {
  return substr($_SERVER['PATH_INFO'], 1);
}

function verb_prevday($d) {
  if (!strlen($d)) $d = curday();
  return strftime("%Y-%m-%d", strtotime(strftime("%Y-%m-%d", strtotime($d)))-86400);
}

function verb_prevmonth($d) {
  if (!strlen($d)) $d = curmonth();
  return strftime("%Y-%m", strtotime(strftime("%Y-%m", strtotime($d)))-86400*3);
}

function verb_prevyear($d) {
  if (!strlen($d)) $d = curyear();
  return $d - 1;
}

function verb_production() {
  global $_VERB;
  return (!(verb_staging()));
}

function verb_request_uri() {
  return $_SERVER['REQUEST_URI'];
}

function verb_roman($num) {
  $n = intval($num);
  $result = '';
  $lookup = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);
  foreach ($lookup as $roman => $value)  {
    $matches = intval($n / $value);
    $result .= str_repeat($roman, $matches);
    $n = $n % $value;
  }
  return $result;
}

function verb_staging() {
  global $_VERB;
  if (strstr($_SERVER['DOCUMENT_ROOT'], ".verb/releases/")) return false;
  if ($_SERVER['HTTP_HOST'] == $_VERB['settings']['subdomain'] . "-staging." . $_VERB['settings']['domain_ssl']) return true;
  if ($_SERVER['HTTP_HOST'] == $_VERB['settings']['subdomain'] . $_VERB['settings']['domain_site']) return true;
  if ($_SERVER['HTTP_HOST'] == $_VERB['settings']['subdomain'] . ".verbsite.com") return true;
  return false;
}

function verb_store_cart_count() {
  _verb_session_deps_add('__v:store');
  $count = 0;
  if (count($_SESSION['__v:store']['cart'])) {
    foreach ($_SESSION['__v:store']['cart'] as $r) {
      $count += $r['qty'];
    }
  }
  return $count;
}

function verb_store_cart_discount() {
  return number_format(_verb_store_compute_discount(), 2);
}

function verb_store_cart_shipping() {
  return number_format(_verb_store_compute_shipping(), 2);
}

function verb_store_cart_subtotal() {
  return number_format(_verb_store_compute_subtotal(), 2);
}

function verb_store_cart_tax() {
  return number_format(_verb_store_compute_tax(), 2);
}

function verb_store_cart_total() {
  return number_format(_verb_store_compute_total(), 2);
}

function verb_top() {
  global $_VERB;
  return ($_VERB['context'] ? $_VERB['context']->id() : "");
}

function verb_uppercase($d) {
  return strtoupper($d);
}

function verb_user() {
  global $_VERB;
  _verb_session_deps_add('__v:logged_in');
  if (!isset($_SESSION['__v:logged_in']['id'])) {
    return _verb_render_redirect("/");
  }
  return $_SESSION['__v:logged_in']['id'];
}

?>