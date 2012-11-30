<?php

function vae_clubtime() {
  $a = strtotime(strftime("%B %d, %Y"));
  if (strftime("%H") < 3) $a -= 86400;
  return $a;
}

function vae_curday() {
  return strftime("%Y-%m-%d");
}

function vae_curmonth() {
  return strftime("%Y-%m");
}

function vae_curyear() {
  return strftime("%Y");
}

function vae_daterange($d) {
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

function vae_host() {
  if ($_REQUEST['__host']) return $_REQUEST['__host'];
  if (isset($_SESSION['__v:pre_ssl_host'])) return $_SESSION['__v:pre_ssl_host'];
  return $_SERVER['HTTP_HOST'];
}

function vae_lowercase($d) {
  return strtolower($d);
}

function vae_nextday($d) {
  if (!strlen($d)) $d = curday();
  return strftime("%Y-%m-%d", strtotime(strftime("%Y-%m-%d", strtotime($d)))+86400);
}

function vae_nextmonth($d) {
  if (!strlen($d)) $d = curmonth();
  return strftime("%Y-%m", strtotime(strftime("%Y-%m", strtotime($d)))+86400*32);
}

function vae_nextyear($d) {
  if (!strlen($d)) $d = curyear();
  return $d + 1;
}

function vae_now() {
  return strtotime(strftime("%B %d, %Y"));
}

function vae_path() {
  return substr($_SERVER['PATH_INFO'], 1);
}

function vae_prevday($d) {
  if (!strlen($d)) $d = curday();
  return strftime("%Y-%m-%d", strtotime(strftime("%Y-%m-%d", strtotime($d)))-86400);
}

function vae_prevmonth($d) {
  if (!strlen($d)) $d = curmonth();
  return strftime("%Y-%m", strtotime(strftime("%Y-%m", strtotime($d)))-86400*3);
}

function vae_prevyear($d) {
  if (!strlen($d)) $d = curyear();
  return $d - 1;
}

function vae_production() {
  global $_VAE;
  return (!(vae_staging()));
}

function vae_request_uri() {
  return $_SERVER['REQUEST_URI'];
}

function vae_roman($num) {
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

function vae_staging() {
  global $_VAE;
  if (strstr($_SERVER['DOCUMENT_ROOT'], ".verb/releases/")) return false;
  if ($_SERVER['HTTP_HOST'] == $_VAE['settings']['subdomain'] . "-staging." . $_VAE['settings']['domain_ssl']) return true;
  if ($_SERVER['HTTP_HOST'] == $_VAE['settings']['subdomain'] . "." . $_VAE['settings']['domain_site']) return true;
  if ($_SERVER['HTTP_HOST'] == $_VAE['settings']['subdomain'] . ".vaesite.com") return true;
  if ($_SERVER['HTTP_HOST'] == $_VAE['settings']['subdomain'] . ".verbsite.com") return true;
  return false;
}

function vae_store_cart_count() {
  _vae_session_deps_add('__v:store');
  $count = 0;
  if (count($_SESSION['__v:store']['cart'])) {
    foreach ($_SESSION['__v:store']['cart'] as $r) {
      $count += $r['qty'];
    }
  }
  return $count;
}

function vae_store_cart_discount() {
  return number_format(_vae_store_compute_discount(), 2);
}

function vae_store_cart_shipping() {
  return number_format(_vae_store_compute_shipping(), 2);
}

function vae_store_cart_subtotal() {
  return number_format(_vae_store_compute_subtotal(), 2);
}

function vae_store_cart_tax() {
  return number_format(_vae_store_compute_tax(), 2);
}

function vae_store_cart_total() {
  return number_format(_vae_store_compute_total(), 2);
}

function vae_store_loggedin() {
  return ($_SESSION['__v:store']['loggedin'] ? true : false);
}

function vae_top() {
  global $_VAE;
  return ($_VAE['context'] ? $_VAE['context']->id() : "");
}

function vae_uppercase($d) {
  return strtoupper($d);
}

function vae_user() {
  global $_VAE;
  _vae_session_deps_add('__v:logged_in');
  if (!isset($_SESSION['__v:logged_in']['id'])) {
    return _vae_render_redirect("/");
  }
  return $_SESSION['__v:logged_in']['id'];
}

?>