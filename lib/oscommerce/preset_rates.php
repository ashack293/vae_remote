<?php

class preset_rates {
  function quote($method = '') {
    global $method, $shipping_num_items, $shipping_subtotal, $shipping_weight, $order;
    $cost = 0;
    if (is_numeric($method['per_order'])) {
      $cost += $method['per_order'];
    }
    if (is_numeric($method['per_item'])) {
      $cost += $shipping_num_items * $method['per_item'];
    }
    if (is_numeric($method['per_lb'])) {
      $cost += $shipping_weight * $method['per_lb'];
    }
    if (is_numeric($method['percentage'])) {
      $cost += $shipping_subtotal * ($method['percentage'] / 100);
    }
    return array('methods' => array(array('title' => 'Standard Shipping', 'cost' => $cost, 'keep_titles' => true)));
  }
}

?>
