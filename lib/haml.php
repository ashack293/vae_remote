<?php

function _vae_haml($haml) {
  $haml = str_replace(array("&lt;", "&gt;"), array("__ESCD__LT", "__ESCD__GT"), $haml);
  $client = _vae_thrift();
  $html = $client->haml($haml);
  $html = str_replace(array("&lt;", "&gt;", "__ESCD__LT", "__ESCD__GT"), array("<", ">", "&lt;", "&gt;"), $html);
  return $html;
}

function _vae_sass($sass, $header = true, $include_directory = null, $scss = false) {
  global $_VAE;
  if ($include_directory == null) $include_directory = dirname($_SERVER['SCRIPT_FILENAME']);
  $cache_key = "sass2" . $_SERVER['DOCUMENT_ROOT'] . md5($sass . $include_directory);
  list($css, $deps) = memcache_get($_VAE['memcached'], $cache_key);
  if (isset($deps) && count($deps)) {
    foreach ($deps as $filename => $hash) {
      if (@md5_file($filename) != $hash) {
        unset($css);
      }
    }
  }
  if (!strlen($css)) {
    $client = _vae_thrift();
    if ($scss) {
      $css = $client->scss($sass, $include_directory);
    } else {
      $css = $client->sass($sass, $include_directory);
    }
    $deps = _vae_sass_deps_check($sass, $include_directory);
    memcache_set($_VAE['memcached'], $cache_key, array($css, $deps));
  }
  if ($header) Header("Content-Type: text/css");
  return $css;
}

function _vae_sass_deps($sass, $include_directory) {
  global $_VAE;
  
  if ($include_directory == null) $include_directory = dirname($_SERVER['SCRIPT_FILENAME']);
  $cache_key = "sass2" . $_SERVER['DOCUMENT_ROOT'] . md5($sass . $include_directory).".map";

  $deps = array();
  preg_match_all('/@import (.*)/', $sass, $matches, PREG_SET_ORDER);
  if (count($matches)) {
    foreach ($matches as $match) {
      $filename = str_replace(array("'", '"',';'), "", $match[1]);
      if (!strstr($filename, ".") || strstr($filename, ".sass") || strstr($filename, ".scss")) {
        $inc_dir = (substr($filename, 0, 1) == "/" ? "" : $include_directory . "/");
        if (!strstr($filename, ".") && !stristr($filename,'vendor/') && !stristr($filename,'vendors/') && !stristr($filename,'compass')) {
          $tmp_filename = (strrchr($filename,"/") == false) ? "_". $filename : substr($filename, 0, strpos($filename,strrchr($filename,"/")) + 1 ) . "_" . substr(strrchr($filename,"/"),1);
          if (file_exists($inc_dir . $tmp_filename . ".scss")) {
            $filename = $tmp_filename . ".scss";
          }elseif (file_exists($inc_dir . $filename . ".scss")) {
            $filename = $filename . ".scss";
          } else {
            $filename = $filename . ".sass";
          }
        }
        $filename = $inc_dir . $filename;
        $sass = @file_get_contents($filename);
        $deps[$filename] = md5($sass);
        $deps = array_merge($deps, _vae_sass_deps($sass, $include_directory));
      }
    }
  }

  _vae_kvstore_write($cache_key,serialize($deps));

  return $deps;
}

function _vae_sass_deps_check($sass, $include_directory){
  if ($include_directory == null) $include_directory = dirname($_SERVER['SCRIPT_FILENAME']);
  $cache_key = "sass2" . $_SERVER['DOCUMENT_ROOT'] . md5($sass . $include_directory).".map";
  $deps = unserialize(_vae_kvstore_read($cache_key));

  if (isset($deps) && $deps && count($deps) > 0) {
    foreach ($deps as $filename => $hash) {
      if (@md5_file($filename) != $hash) {
        return _vae_sass_deps($sass, $include_directory);
      }
    }
  }else{
    return _vae_sass_deps($sass, $include_directory);
  }

  return $deps;
}

function _vae_sass_ob($sass, $header = true) {
  try {
    if (substr($_SERVER['SCRIPT_FILENAME'], -5) == ".scss") {
      return _vae_sass($sass, $header, null, true);
    } else {
      return _vae_sass($sass, $header);
    }
  } catch (Exception $e) {
    return _vae_render_error($e);
  }
}
 
?>