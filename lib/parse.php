<?php

function _vae_contains_yield(&$tag) {
  $tags = &$tag['tags'];
  $count = count($tags);
  for ($i = 0; $i < $count; $i++) {
    $tag = &$tags[$i];
    if (count($tag['tags'])) {
      if (_vae_contains_yield($tag)) return true;
    }
    if ($tag['type'] == 'yield') {
      return true;
    }
  }
  return false;
}

function _vae_find_fragment($parent_tag, $fragment) {
  $tags = &$parent_tag['tags'];
  $count = count($tags);
  for ($i = 0; $i < $count; $i++) {
    $tag = &$tags[$i];
    if ($tag['type'] == 'fragment' && $tag['attrs']['for'] == $fragment) {
      return $tag;
    }
  }
  return false;
}

function _vae_merge_yield(&$parse_tree, &$nested_tags, &$render_context) {
  $tags = &$parse_tree['tags'];
  $count = count($tags);
  for ($i = 0; $i < $count; $i++) {
    $tag = &$tags[$i];
    if (count($tag['tags']) && $tag['type'] != 'nested_divider') {
      _vae_merge_yield($tag, $nested_tags, $render_context);
    }
    if ($tag['type'] == 'flash') {
      $render_context->set_in_place("has_flash_tag" . $tag['attrs']['flash']);
    }
    if ($tag['type'] == 'yield') {
      if (strlen($tag['attrs']['for'])) {
        $fragment = _vae_find_fragment($nested_tags, $tag['attrs']['for']);
        if ($fragment !== false && count($fragment['tags'])) {
          array_splice($tags, $i, 1, $fragment['tags']);
          $i += count($fragment['tags']) - 1;
          $count += count($fragment['tags']) - 1;
        }
      } else {
        array_splice($tags, $i, 0, $nested_tags['tags']);
        $i += count($nested_tags['tags']) ;
        $count += count($nested_tags['tags']);
      }
    }
  }
  return array($parse_tree, $render_context);
}

function _vae_parse_vaeml($vaeml, $filename = null, $nested_tags = null, $render_context = null) {
  global $_VAE;
  $cache_key = "vaeml" . $filename . $_SERVER['DOCUMENT_ROOT'] . md5($vaeml);
  if ($render_context == null) $render_context = new Context();
  if (isset($_VAE['vaeml_cache'][$cache_key])) $cached = $_VAE['vaeml_cache'][$cache_key];
  elseif ($_REQUEST['__debug'] != "parse") $cached = memcache_get($_VAE['memcached'], $cache_key);
  if ($cached) return _vae_merge_yield($cached, $nested_tags, $render_context);
  if (substr($filename, -5) == ".haml" || substr($filename, -9) == ".haml.php") {
    require_once(dirname(__FILE__) . "/haml.php");
    $vaeml = _vae_haml($vaeml);
    _vae_tick("parse Haml");
  }
  $parser = new VaeMLParser($vaeml, $filename);
  $res = $parser->parse();
  if ($res === false) return false;
  $par_tag = array('tags' => $res);
  memcache_set($_VAE['memcached'], $cache_key, $par_tag);
  $_VAE['vaeml_cache'][$cache_key] = $par_tag;
  return _vae_merge_yield($par_tag, $nested_tags, $render_context);
}

function _vae_parser_mask_errors($errno, $error_string) {
  global $_VAE;
  $e = str_replace(array(
      "DOMDocument::loadXML() [<a href='domdocument.loadxml'>domdocument.loadxml</a>]: ", 
      " in tag vaeml line 1 in Entity", 
      " and vaeml in Entity", 
      " in Entity", 
      ), "", $error_string);
  if (!isset($_VAE['parser_errors'])) $_VAE['parser_errors'] = "";
  $_VAE['parser_errors'] .= "<li>" . $e . "</li>";
}

class VaeMLParser {
  
  function __construct($vaeml, $filename) {
    $this->filename = $filename;
    $this->vaeml = $vaeml;
  }
  
  function dom_to_vae($node) {
    global $_VAE;
    $out = array();
    foreach ($node->childNodes as $child) {
      if ($child->nodeType == XML_TEXT_NODE) {
        $out[] = array('innerhtml' => $this->fix($child->nodeValue));
      } elseif ($child->nodeType == XML_ELEMENT_NODE) {
        $attrs = array();
        foreach ($child->attributes as $attrName => $attrNode) {
          $attrs[$attrName] = $this->fix($attrNode->nodeValue);
        }
        if (count($_VAE['tags'][$child->nodeName]['required'])) {
          foreach ($_VAE['tags'][$child->nodeName]['required'] as $req) {
            if (!strlen($attrs[$req])) return _vae_error("Tag <span class='c'>&lt;v:" . _vae_h($child->nodeName) . "&gt;</span> is missing the required <span class='c'>" . _vae_h($req) . "=</span> attribute.", "", $this->filename);
          }
        }
        $tags = $this->dom_to_vae($child);
        if ($tags === false) return false;
        $out[] = array('type' => $child->nodeName, 'tags' => $tags, 'filename' => $this->filename, 'attrs' => $attrs);
      } else {
        return _vae_error("VaeML Parser encountered an unexpected node.");
      }
    }
    return $out;
  }
  
  function fix($str) {
    return str_replace(array("A" . $this->i, "L" . $this->i, "B" . $this->i), array("&", "<", "]]>"), $str);
  }
  
  function parse() {
    global $_VAE;
    $this->i = "_99" . substr(md5(rand()), 0, 6);
    $vaeml = str_replace(array("<", "&","]]>"), array("L" . $this->i, "A" . $this->i, "B" . $this->i), $this->vaeml);
    $vaeml = preg_replace_callback("/" . "L" . $this->i . "(|\\/)v:([^> ]*)/", create_function('$m', 'return "<" . $m[1] . str_replace(":", "_", $m[2]);'), $vaeml);
    $dom = new DomDocument();
    $xml = "<vaeml>" . $vaeml . "</vaeml>";
    set_error_handler('_vae_parser_mask_errors');
    if ($dom->loadXML($xml) == FALSE) {
      return _vae_error("Could not parse VaeML document.  Please make sure all your VaeML tags are closed properly.  This information may help:<ul>" . $_VAE['parser_errors'] . "</ul>");
    }
    restore_error_handler();
    return $this->dom_to_vae($dom->firstChild);
  }
  
}

?>