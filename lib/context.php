<?php

class Context {
    
  public $attributes = array();
  
  function __construct() {
  }
  
  function attr($attr, $a, $default = false) {
    if (isset($a[$attr])) return $a[$attr];
    if (isset($this->attributes[$attr])) return $this->attributes[$attr];
    return $default;
  }
  
  function get($attr) {
    return $this->attributes[$attr];
  }
  
  function required_attr($attr, $a, $type) {
    $out = $this->attr($attr, $a);
    if ($out == false) _verb_error("Tag <span class='c'>&lt;v:" . $type . "&gt;</span> is missing the required <span class='c'>$attr=</span> attribute.  Either add this attribute, or move this tag inside another tag that passes down this attribute.");
    return $out;
  }
  
  function set($new_attributes, $val = true) {
    $new = clone $this;
    $new->set_in_place($new_attributes, $val);
    return $new;
  }
  
  function set_in_place($new_attributes, $val = true) {
    if (!is_array($new_attributes)) $new_attributes = array($new_attributes => $val);
    $this->attributes = array_merge($this->attributes, $new_attributes);
    return $this;
  }
  
  function unsett($attr) {
    $new = clone $this;
    $new->unset_in_place($attr);
    return $new;
  }
  
  function unset_in_place($attr) {
    if (isset($this->attributes[$attr])) unset($this->attributes[$attr]);
  }
  
}
 
?>