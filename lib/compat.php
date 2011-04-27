<?php

function verb_clubtime() {
  return vae_clubtime();
}

function verb_curday() {
  return vae_curday();
}

function verb_curmonth() {
  return vae_curmonth();
}

function verb_curyear() {
  return vae_curyear();
}

function verb_daterange($d) {
  return vae_daterange($d);
}

function verb_host() {
  return vae_host();
}

function verb_lowercase($d) {
  return vae_lowercase($d);
}

function verb_nextday($d) {
  return vae_nextday($d);
}

function verb_nextmonth($d) {
  return vae_nextmonth($d);
}

function verb_nextyear($d) {
  return vae_nextyear($d);
}

function verb_now() {
  return vae_now();
}

function verb_path() {
  return vae_path();
}

function verb_prevday($d) {
  return vae_prevday($d);
}

function verb_prevmonth($d) {
  return vae_prevmonth($d);
}

function verb_prevyear($d) {
  return vae_prevyear($d);
}

function verb_production() {
  return vae_production();
}

function verb_request_uri() {
  return vae_request_uri();
}

function verb_roman($num) {
  return vae_roman($num);
}

function verb_staging() {
  return vae_staging();
}

function verb_store_cart_count() {
  return vae_store_cart_count();
}

function verb_store_cart_discount() {
  return vae_store_cart_discount();
}

function verb_store_cart_shipping() {
  return vae_store_cart_shipping();
}

function verb_store_cart_subtotal() {
  return vae_store_cart_subtotal();
}

function verb_store_cart_tax() {
  return vae_store_cart_tax();
}

function verb_store_cart_total() {
  return vae_store_cart_total();
}

function verb_top() {
  return vae_top();
}

function verb_uppercase($d) {
  return vae_uppercase($d);
}

function verb_user() {
  return vae_user();
}

function verb_asset($id, $width = "", $height = "", $quality = "", $preserve_filename = false) {
  return vae_asset($id, $width, $height, $quality, $preserve_filename);
}

function verb_cache($key, $timeout = 3600, $function = "", $global = false) {
  return vae_cache($key, $timeout, $function, $global);
}

function verb_cdn_url() {
  return vae_cdn_url();
}

function verb_create($structure_id, $row_id, $data) {
  return vae_create($structure_id, $row_id, $data);
}

function verb_customer($id) {
  return vae_customer($id);
}

function verb_data_path() {
  return vae_data_path();
}

function verb_data_url() {
  return vae_data_url();
}

function verb_disable_verbml() {
  return vae_disable_vaeml();
}

function verb_file($id, $preserve_filename = false) {
  return vae_file($id, $preserve_filename);
}

function verb_flash($message, $type = 'msg') {
  return vae_flash($message, $type);
}

function verb_image($id, $width = "", $height = "", $image_size = "", $grow = "", $quality = "", $preserve_filename = false) {
  return vae_image($id, $width, $height, $image_size, $grow, $quality, $preserve_filename);
}

function verb_image_grey($image, $internal = false) {
  return vae_image_grey($image, $internal);
}

function verb_image_reflect($image, $reflection_size = 30, $opacity = 35, $internal = false) {
  return vae_image_reflect($image, $reflection_size, $opacity, $internal);
}

function verb_imagesize($d) {
  return vae_imagesize($d);
}

function verb_include($path, $once = false) {
  return vae_include($path, $once);
}

function verb_include_once($path) {
  return vae_include_once($path);
}

function verb_loggedin() {
  return vae_loggedin();
}

function verb_multipart_mail($from, $to, $subject, $text, $html) {
  return vae_multipart_mail($from, $to, $subject, $text, $html);
}

function verb_newsletter_subscribe($code, $email) {
  return vae_newsletter_subscribe($code, $email);
}

function verb_permalink($id) {
  return vae_permalink($id);
}

function verb_redirect($url) {
  return vae_redirect($url);
}

function verb_register_hook($name, $options_or_callback) {
  return vae_register_hook($name, $options_or_callback);
}

function verb_register_tag($name, $options) {
  return vae_register_tag($name, $options);
}

function verb_render_tags($tag, $context, $true = true) {
  return vae_render_tags($tag, $context, $true);
}

function verb_require($path) {
  return vae_require($path);
}

function verb_require_once($path) {
  return vae_require_once($path);
}

function verb_richtext($text, $options) {
  return vae_richtext($text, $options);
}

function verb_sizedimage($id, $size, $preserve_filename = false) {
  return vae_sizedimage($id, $size, $preserve_filename);
}

function verb_store_add_item_to_cart($id, $option_id = null, $qty = 1, $a = null, $notes = "") {
  return vae_store_add_item_to_cart($id, $option_id, $qty, $a, $notes);
}

function verb_store_add_shipping_method($options) {
  return vae_store_add_shipping_method($options);
}

function verb_store_cart_item($id) {
  return vae_store_cart_item($id);
}

function verb_store_cart_items() {
  return vae_store_cart_items();
}

function verb_store_clear_discount_code() {
  return vae_store_clear_discount_code();
}

function verb_store_create_coupon_code($data) {
  return vae_store_create_coupon_code($data);
}

function verb_store_create_tax_rate($data) {
  return vae_store_create_tax_rate($data);
}

function verb_store_current_user() {
  return vae_store_current_user();
}

function verb_store_current_user_tags($tag = null) {
  return vae_store_current_user_tags($tag);
}

function verb_store_destroy_coupon_code($id = "") {
  return vae_store_destroy_coupon_code($id);
}

function verb_store_destroy_tax_rate($id = "") {
  return vae_store_destroy_tax_rate($id);
}

function verb_store_destroy_all_tax_rates() {
  return vae_store_destroy_all_tax_rates();
}

function verb_store_discount_code($code = null, $force = false) {
  return vae_store_discount_code($code, $force);
}

function verb_store_find_coupon_code($code) {
  return vae_store_find_coupon_code($code);
}

function verb_store_handling_charge($amount) {
  return vae_store_handling_charge($amount);
}

function verb_store_orders($finders = null) {
  return vae_store_orders($finders);
}

function verb_store_payment_method() {
  return vae_store_payment_method();
}

function verb_store_recent_order($all = false) {
  return vae_store_recent_order($all);
}

function verb_store_remove_from_cart($cart_id) {
  return vae_store_remove_from_cart($cart_id);
}

function verb_store_shipping_method() {
  return vae_store_shipping_method();
}

function verb_store_tax_rate() {
  return vae_store_tax_rate();
}

function verb_store_total_weight($weight) {
  return vae_store_total_weight($weight);
}

function verb_store_update_cart_item($id, $data) {
  return vae_store_update_cart_item($id, $data);
}

function verb_store_update_coupon_code($id, $data) {
  return vae_store_update_coupon_code($id, $data);
}

function verb_store_update_tax_rate($id, $data) {
  return vae_store_update_tax_rate($id, $data);
}

function verb_store_update_order($order_id, $attributes = null) {
  return vae_store_update_order($order_id, $attributes);
}

function verb_store_update_order_status($order_id, $status) {
  return vae_store_update_order_status($order_id, $status);
}

function verb_style($r) {
  return vae_style($r);
}

function verb_template_mail($from, $to, $subject, $template, $text_yield = null, $html_yield = null) {
  return vae_template_mail($from, $to, $subject, $template, $text_yield, $html_yield);
}

function verb_text($text, $font_name = "", $font_size = "22", $color = "#000000", $kerning = 1, $padding = 5, $max_width = 10000) {
  return vae_text($text, $font_name, $font_size, $color, $kerning, $padding, $max_width);
}

function verb_tick($desc) {
  return vae_tick($desc);
}

function verb_update($id, $data) {
  return vae_update($id, $data);
}

function verb_users_current_user() {
  return vae_users_current_user();
}

function verb_video($id, $video_size = "") {
  return vae_video($id, $video_size);
}

function verb_watermark($image, $watermark_image, $vertical_align = "", $align = "", $vertical_padding = "", $horizontal_padding = "") {
  return vae_watermark($image, $watermark_image, $vertical_align, $align, $vertical_padding, $horizontal_padding);
}

function verb_zip_distance($from, $to, $zip_field = "zip") {
  return vae_zip_distance($from, $to, $zip_field);
}

function verb_zip_lookup($zips) {
  return vae_zip_lookup($zips);
}

function verb($query = null, $options = null, $context = "__") {
  return vae($query, $options, $context);
}

function verb_context($array = null) {
  return vae_context($array);
}

function verb_find($query = null, $options = null, $context = null) {
  return vae_find($query, $options, $context);
}

function verb_sql_credentials($username, $password) {
  return vae_sql_credentials($username, $password);
}
