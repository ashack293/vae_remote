<?php
 
function _vae_array_from_rails_xml($xml, $is_array = false, $transform = null) {
  $r = array();
  if ($is_array) {
    foreach ($xml as $x) {
      $r[(int)$x->id] = _vae_array_from_rails_xml($x, false, $transform);
    }
  } else {
    foreach ($xml as $k => $v) {
      if (count($v->children())) {
        $val = _vae_array_from_rails_xml($v->children(), true, $transform);
      } else {
        $val = (string)$v;
        if ($v->attributes()->type == "boolean") {
          $val = ($val == "true");
        }
      }
      $k = (string)$k;
      $k = (isset($transform[$k]) ? $transform[$k] : str_replace("-", "_", $k));
      $r[$k] = $val;
    }
  }
  return $r;
}
 
function _vae_build_xml($parent, $data) {
  if (!strlen($parent)) return "";
  $xml = "<" . $parent .">";
  foreach ($data as $k => $v) {
    if (strlen($k) && !strstr($k, " ")) {
      $xml .= "<" . $k . ">";
      if (is_array($v)) {
        foreach ($v as $item) {
          $xml .= _vae_build_xml("item", $item);
        }
      } else {
        $xml .= htmlspecialchars($v);
      }
      $xml .= "</" . $k . ">";
    }
  }
  $xml .= "</" . $parent . ">";  
  return $xml;
}

function _vae_create($structure_id, $row_id, $data, $hide_errors = false) {
  global $_VAE;
  $url = "content/create/" . $structure_id . "/" . $row_id;
  if ($data['publish'] === false) $url .= "?row[disabled]=1";
  $raw = _vae_rest($data, $url, "content", null, null, $hide_errors);
  if ($raw == false) return false;
  $data = _vae_array_from_rails_xml(simplexml_load_string($raw));
  return $data['id'];
  return true;
}

function _vae_destroy($row_id) {
  $raw = _vae_rest(null, "content/destroy/" . $row_id, "content");
  if ($raw == false) return false;
  return true;
}

function _vae_proxy($url, $qs = "", $send_request_data = false, $yield = false) {
  global $_VAE;
  $id = md5(rand());
  memcache_set($_VAE['memcached'], "_proxy_$id", serialize($_SESSION));
  if ($yield) {
    memcache_set($_VAE['memcached'], "_proxy_yield_$id", $yield);
    $qs .= "&__get_yield=1";
  }
  if ($send_request_data) {
    memcache_set($_VAE['memcached'], "_proxy_post_$id", serialize($_POST));
    memcache_set($_VAE['memcached'], "_proxy_request_$id", serialize($_REQUEST));
    $qs .= "&__get_request_data=1";
  }
  if (substr($url, 0, 1) == "/") $url = substr($url, 1);
  $qs .= "&__proxy=" . $id;
  $host = ($_SESSION['__v:pre_ssl_host'] ? $_SESSION['__v:pre_ssl_host'] : $_SERVER['HTTP_HOST']);
  $out = _vae_simple_rest("http://" . $host . "/" . $url . "?" . $qs);
  $out = str_replace("src=\"http", "__SAVE1__", $out);
  $out = str_replace("src='http", "__SAVE2__", $out);
  $out = str_replace("src=\"", "src=\"http://" . $host . "/", $out);
  $out = str_replace("src='", "src='http://" . $host . "/", $out);
  $out = str_replace("__SAVE1__", "src=\"http", $out);
  $out = str_replace("__SAVE2__", "src='http", $out);
  return $out;
}

function _vae_rest($data, $method, $param, $tag = null, $errors = null, $hide_errors = false) {
  global $_VAE;
  if ($errors == null) $errors = array();
  if ($tag) _vae_merge_data_from_tags($tag, $data, $errors);
  if (count($errors) == 0) $ret = _vae_send_rest($method, _vae_build_xml($param, $data), $errors);
  $_VAE['errors'] = $errors;
  if (!$hide_errors && _vae_flash_errors($errors, $tag['attrs']['flash'])) {
    return false;
  } elseif (!$hide_errors && ($ret == false)) {
    _vae_flash("A network error occured.  Please try again.  If this error continues, please contact us.", 'err');
    if (!strstr($method, "content/create")) {
      _vae_report_error("REST API Error", $_VAE['resterror']);
    }
  }
  return $ret;
}

function _vae_send_rest($method, $data, &$errors) {
  global $_VAE;
  if ($_ENV['TEST']) {
    $_VAE['rest_sent']++;
    if (isset($_VAE['mock_rest_error'])) {
      if (strlen($_VAE['mock_rest_error'])) $errors[] = $_VAE['mock_rest_error'];
      return false;
    }
    if (isset($_VAE['mock_rest']) && strlen($mock = array_shift($_VAE['mock_rest']))) return $mock;
    return '<success></success>';
  }
  $url = str_replace("http://", "https://", $_VAE['config']["backlot_url"]) . "/" . $method . (strstr($method, "?") ? "&" : "?") . "secret_key=" . $_VAE['config']["secret_key"];
  $curl = curl_init($url);
  curl_setopt($curl, CURLOPT_POST, true);
  curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
  curl_setopt($curl, CURLOPT_HEADER, true);
  curl_setopt($curl, CURLOPT_HTTPHEADER, Array("Content-Type: application/xml", "Accept: application/xml"));
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
  $response = curl_exec($curl);
  curl_close($curl);
  $response = str_replace(array("HTTP/1.1 100 Continue\n", "HTTP/1.1 100"), "", str_replace("\r", "", $response));
  $status_code = array();
  preg_match('/\d\d\d/', $response, $status_code);
  if ($status_code[0] == "200" || $status_code[0] == "201") {
    $split_out_header = explode("\n\n", $response, 2);
    return $split_out_header[1];
  }
  foreach (array('cc_number','cc_cvv','password','cc_month','cc_year','cc_start_month','cc_start_year') as $bad) {
    $data = preg_replace("/<" . $bad . ">([^<]*)/", "<" . $bad . ">[FILTERED]", $data);
  }
  $_VAE['resterror'] = "Submitting to URL: $url\n\n-------------\n\nData:\n\n$data\n\nResponse:\n\n$response\n\n-------------";
  preg_match_all("|<error>(.*)</error>|U", $response, $out, PREG_SET_ORDER);
  if (count($out)) {
    foreach ($out as $r) {
      $errors[] = $r[1];
    }
  } elseif ($status_code[0] == "422") {
    $split_out_header = explode("\n\n", $response, 2);
    $errors[] = $split_out_header[1];
  }
  return false;
}

function _vae_master_rest($method, $post_data = null) {
  global $_VAE;
  if ($post_data == null) $post_data = array();
  $post_data['secret_key'] = $_VAE['config']['secret_key'];
  $post_data['method'] = $method;
  return _vae_simple_rest("http://" . $_VAE['settings']['subdomain'] . ".vaesite.com/", $post_data);
}

function _vae_simple_rest($url, $post_data = null) {
  global $_VAE;
  if ($_ENV['TEST'] && !$_SESSION['real_rest']) {
    $_VAE['rest_sent']++;
    if (!isset($_VAE['mock_rest'])) return "";
    return array_shift($_VAE['mock_rest']);
  }
  if (!strstr($url, "://")) $url = $_VAE['config']['backlot_url'] . $url;
  if ($post_data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $res = curl_exec($ch);
    curl_close($ch); 
    return $res;
  }
  return file_get_contents($url);
}

function _vae_update($id, $data) {
  global $_VAE;
  $_VAE['__vae_update_ct']++;
  $errors = array();
  if (_vae_rest($data, "content/update/" . $id . (($_VAE['__vae_update_ct'] % 20) ? "?no_hook=true" : ""), "content", null, $errors, true) == false) {
    return false;
  }
  if (!isset($_VAE['run_hooks'])) $_VAE['run_hooks'] = array();
  $_VAE['run_hooks'][] = array("content:updated", $id);
  return true;
}

?>