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

function _vae_build_xml($parent, $data, $method) {
  global $_VAE;
  if (!strlen($parent)) return "";
  $xml = "<" . $parent .">";
  foreach ($data as $k => $v) {
    if (strlen($k) && !strstr($k, " ") && (!isset($_VAE['safe_params'][$method]) || in_array($k, $_VAE['safe_params'][$method]))) {
      $xml .= "<" . $k . ">";
      if (is_array($v)) {
        foreach ($v as $item) {
          $xml .= _vae_build_xml("item", $item, $method . "/nested/" . $k);
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
  $url = "api/site/v1/content/create/" . $structure_id . "/" . $row_id;
  if ($data['publish'] === false) $url .= "?row[disabled]=1";
  $raw = _vae_rest($data, $url, "content", null, null, $hide_errors);
  if ($raw == false) return false;
  $data = _vae_array_from_rails_xml(simplexml_load_string($raw));
  return $data['id'];
  return true;
}

function _vae_destroy($row_id) {
  $raw = _vae_rest(null, "api/site/v1/content/destroy/" . $row_id, "content");
  if ($raw == false) return false;
  return true;
}

function _vae_proxy($url, $qs = "", $send_request_data = false, $yield = false) {
  global $_VAE;
  if ($_VAE['local_full_stack']) return "";
  $id = session_id();
  if ($yield) {
    _vae_long_term_cache_set("_proxy_yield_$id", $yield, 1);
    $qs .= "&__get_yield=1";
  }
  if ($send_request_data) {
    _vae_long_term_cache_set("_proxy_post_$id", serialize($_POST), 1);
    _vae_long_term_cache_set("_proxy_request_$id", serialize($_REQUEST), 1);
    $qs .= "&__get_request_data=1";
  }
  if (substr($url, 0, 1) == "/") $url = substr($url, 1);
  $qs .= "&__proxy=" . $id;
  $host = $_SERVER['HTTP_HOST'];
  $out = _vae_simple_rest(_vae_proto() . "127.0.0.1/" . $url . "?" . $qs, null, $host);
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
  $method_name = _vae_safe_method_name($method);
  $xml = _vae_build_xml($param, $data, $method_name);
  if (isset($_VAE['safe_params'][$method_name]) && $xml == "<" . $param . "></" . $param . ">") {
    $errors = array('No parameters were provided.');
  }
  if (count($errors) == 0) $ret = _vae_send_rest($method, $xml, $errors);
  $_VAE['errors'] = $errors;
  if (!$hide_errors && !(is_array($hide_errors) && in_array($_VAE['reststatus'],$hide_errors)) && _vae_flash_errors($errors, $tag['attrs']['flash'])) {
    return false;
  } elseif (!$hide_errors && !(is_array($hide_errors) && in_array($_VAE['reststatus'],$hide_errors)) && ($ret == false)) {
    _vae_flash("A network error occured.  Please try again.  If this error continues, please contact us.", 'err');
    if (!strstr($method, "content/create")) {
      _vae_honeybadger_send("VaeRailsAppRestApiError", $_VAE['resterror'], debug_backtrace());
    }
  }
  return $ret;
}

function _vae_safe_method_name($method) {
  return preg_replace('/\/[0-9]+/', '', $method);
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
  $url_base = $_VAE['config']['backlot_url'];
  $url_base = (preg_match('/\.dev$/', $url_base) ? $url_base : str_replace("http://", "https://", $url_base));
  $url = $url_base . "/" . $method . (strstr($method, "?") ? "&" : "?") . "secret_key=" . $_VAE['config']["secret_key"];
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
  $_VAE['reststatus'] = $status_code[0];
  if ($status_code[0] == "200" || $status_code[0] == "201") {
    $split_out_header = explode("\n\n", $response, 2);
    return $split_out_header[1];
  }
  foreach ($_VAE['unsafe_params'] as $bad) {
    $data = preg_replace("/<" . $bad . ">([^<]*)/", "<" . $bad . ">[FILTERED]", $data);
  }
  $url = preg_replace("/secret_key=([^<]*)/", "secret_key=[FILTERED]", $url);
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

function _vae_simple_rest($url, $post_data = null, $header = false, $follow_redirects = false) {
  global $_VAE;
  if ($_ENV['TEST'] && !$_SESSION['real_rest']) {
    $_VAE['rest_sent']++;
    if (!isset($_VAE['mock_rest'])) return "";
    return array_shift($_VAE['mock_rest']);
  }
  if (!strstr($url, "://")) {
    $url = $_VAE['config']['backlot_url'] . $url;
  }
  $ch = curl_init($url);
  if (strstr($url, "127.0.0.1") !== false) {
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
  }
  curl_setopt($ch, CURLOPT_TIMEOUT, 600);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  if ($post_data) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
  }
  if ($header) {
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Host: " . str_replace(array("https://", "http://", "/"), "", $header)));
  }
  if ($follow_redirects) {
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  }
  $res = curl_exec($ch);
  $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($http_code >= 300 || $http_code == 0) {
    foreach ($_VAE['unsafe_params'] as $bad) {
      $post_data = preg_replace("/<" . $bad . ">([^<]*)/", "<" . $bad . ">[FILTERED]", $post_data);
      $post_data = preg_replace("/" . $bad . "=([^<]*)/", $bad . "=[FILTERED]", $post_data);
    }
    $url = preg_replace("/secret_key=([^<]*)/", "secret_key=[FILTERED]", $url);
    _vae_honeybadger_send("VaeSimpleRestError", "Submitting to URL: $url\n\n-------------\n\nHost:\n\n$header\n\nData:\n\n$post_data\n\nResponse Code:\n\n$http_code\n\nResponse:\n\n$res\n\n-------------", debug_backtrace());
    return "";
  }
  return $res;
}

function _vae_update($id, $data, $update_frontend = true) {
  global $_VAE;
  $errors = array();
  if (_vae_rest($data, "api/site/v1/content/update/" . $id . ($update_frontend ? "" : "?no_hook=true"), "content", null, $errors, true) == false) {
    return false;
  }
  $_VAE['__vae_update_ct']++;
  if (!isset($_VAE['run_hooks'])) $_VAE['run_hooks'] = array();
  $_VAE['run_hooks'][] = array("content:updated", $id);
  return true;
}
