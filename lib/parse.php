<?php

function _verb_contains_yield(&$tag) {
  $tags = &$tag['tags'];
  $count = count($tags);
  for ($i = 0; $i < $count; $i++) {
    $tag = &$tags[$i];
    if (count($tag['tags'])) {
      if (_verb_contains_yield($tag)) return true;
    }
    if ($tag['type'] == 'yield') {
      return true;
    }
  }
  return false;
}

function _verb_find_fragment($parent_tag, $fragment) {
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

function _verb_merge_yield(&$parse_tree, &$nested_tags, &$render_context) {
  $tags = &$parse_tree['tags'];
  $count = count($tags);
  for ($i = 0; $i < $count; $i++) {
    $tag = &$tags[$i];
    if (count($tag['tags']) && $tag['type'] != 'nested_divider') {
      _verb_merge_yield($tag, $nested_tags, $render_context);
    }
    if ($tag['type'] == 'flash') {
      $render_context->set_in_place("has_flash_tag" . $tag['attrs']['flash']);
    }
    if ($tag['type'] == 'yield') {
      if (strlen($tag['attrs']['for'])) {
        $fragment = _verb_find_fragment($nested_tags, $tag['attrs']['for']);
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

function _verb_parse_verbml($verbml, $filename = null, $nested_tags = null, $render_context = null) {
  global $_VERB;
  $cache_key = "verbml" . $filename . $_SERVER['DOCUMENT_ROOT'] . md5($verbml);
  if ($render_context == null) $render_context = new Context();
  if (isset($_VERB['verbml_cache'][$cache_key])) $cached = $_VERB['verbml_cache'][$cache_key];
  elseif ($_REQUEST['__debug'] != "parse") $cached = memcache_get($_VERB['memcached'], $cache_key);
  if ($cached) return _verb_merge_yield($cached, $nested_tags, $render_context);
  if (substr($filename, -5) == ".haml" || substr($filename, -9) == ".haml.php") {
    require_once(dirname(__FILE__) . "/haml.php");
    $verbml = _verb_haml($verbml);
    _verb_tick("parse Haml");
  }
  $parser = new VerbMLParser($verbml, $filename);
  $res = $parser->parse();
  if ($res === false) return false;
  $par_tag = array('tags' => $res);
  memcache_set($_VERB['memcached'], $cache_key, $par_tag);
  $_VERB['verbml_cache'][$cache_key] = $par_tag;
  return _verb_merge_yield($par_tag, $nested_tags, $render_context);
}

function _verb_parser_mask_errors($errno, $error_string) {
  global $_VERB;
  $e = str_replace(array(
      "DOMDocument::loadXML() [<a href='domdocument.loadxml'>domdocument.loadxml</a>]: ", 
      " in tag verbml line 1 in Entity", 
      " and verbml in Entity", 
      " in Entity", 
      ), "", $error_string);
  if (!isset($_VERB['parser_errors'])) $_VERB['parser_errors'] = "";
  $_VERB['parser_errors'] .= "<li>" . $e . "</li>";
}

class VerbMLParser {
  
  function __construct($verbml, $filename) {
    $this->filename = $filename;
    $this->verbml = $verbml;
  }
  
  function dom_to_verb($node) {
    global $_VERB;
    $out = array();
    foreach ($node->childNodes as $child) {
      if ($child->nodeType == XML_TEXT_NODE) {
        $out[] = array('innerhtml' => $this->fix($child->nodeValue));
      } elseif ($child->nodeType == XML_ELEMENT_NODE) {
        $attrs = array();
        foreach ($child->attributes as $attrName => $attrNode) {
          $attrs[$attrName] = $this->fix($attrNode->nodeValue);
        }
        if (count($_VERB['tags'][$child->nodeName]['required'])) {
          foreach ($_VERB['tags'][$child->nodeName]['required'] as $req) {
            if (!strlen($attrs[$req])) return _verb_error("Tag <span class='c'>&lt;v:" . _verb_h($child->nodeName) . "&gt;</span> is missing the required <span class='c'>" . _verb_h($req) . "=</span> attribute.", "", $this->filename);
          }
        }
        $tags = $this->dom_to_verb($child);
        if ($tags === false) return false;
        $out[] = array('type' => $child->nodeName, 'tags' => $tags, 'filename' => $this->filename, 'attrs' => $attrs);
      } else {
        return _verb_error("VerbML Parser encountered an unexpected node.");
      }
    }
    return $out;
  }
  
  function fix($str) {
    return str_replace(array("A" . $this->i, "L" . $this->i, "B" . $this->i), array("&", "<", "]]>"), $str);
  }
  
  function parse() {
    global $_VERB;
    $this->i = "_99" . substr(md5(rand()), 0, 6);
    $verbml = str_replace(array("<", "&","]]>"), array("L" . $this->i, "A" . $this->i, "B" . $this->i), $this->verbml);
    $verbml = preg_replace_callback("/" . "L" . $this->i . "(|\\/)v:([^> ]*)/", create_function('$m', 'return "<" . $m[1] . str_replace(":", "_", $m[2]);'), $verbml);
    $dom = new DomDocument();
    $xml = "<verbml>" . $verbml . "</verbml>";
    set_error_handler('_verb_parser_mask_errors');
    if ($dom->loadXML($xml) == FALSE) {
      return _verb_error("Could not parse VerbML document.  Please make sure all your VerbML tags are closed properly.  This information may help:<ul>" . $_VERB['parser_errors'] . "</ul>");
    }
    restore_error_handler();
    return $this->dom_to_verb($dom->firstChild);
  }
  
}

?>