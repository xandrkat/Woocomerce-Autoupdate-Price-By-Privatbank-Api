<?php
/**
 * @package Woocomerce Autoupdate Price By Privatbank Api
 * @version 1.0.0
 */
/*
Plugin Name: Woocomerce Autoupdate Price By Privatbank Api
Description: Обновление цены на сайте по Api Privatbank.
Author: Alexandr Katrazhenko
Version: 1.0.0
Author URI: https://katrazhenko.biz.ua/
*/
if (!defined('ABSPATH')) { exit;}

	add_filter( 'woocommerce_get_price',                      'get_change_price', PHP_INT_MAX - 100, 2 ); 
	add_filter( 'woocommerce_get_regular_price',              'get_change_price', PHP_INT_MAX - 100, 2 );
	add_filter( 'woocommerce_variation_prices_price',         'get_change_price', PHP_INT_MAX - 100, 2 );
	add_filter( 'woocommerce_variation_prices_regular_price', 'get_change_price', PHP_INT_MAX - 100, 2 );
	add_filter( 'woocommerce_variation_prices_sale_price',    'get_change_price', PHP_INT_MAX - 100, 2 );
	
	function get_change_price($change) {
		$current_exchange 	= floatval(get_option('usd_exchange_'.date("ymd")));
		//$exchange = round($current_exchange+(($current_exchange/100)*2.2),3);
		$finalprice 		= ($change*$current_exchange) * 1.022;
		$number 			= ceil($finalprice / 5) * 5;
		return ceil($number);
	}
	
	add_filter( 'woocommerce_currency_symbol', function(){ return ' &#36';}, 100, 2 );
	register_activation_hook(__FILE__,'woo_autoupdate_price_pl');
	register_deactivation_hook(__FILE__,'woo_autoupdate_price_pl');

function get_exchange() {
	$current_exchange 	= __DIR__.'/current_exchange/'.date("ymd").'.json';
	$yesterday = date("ymd")-1;
	$yesterday_exchange = __DIR__.'/current_exchange/'.$yesterday.'.json';
	$pb_exchange_api_url = "https://api.privatbank.ua/p24api/pubinfo?json&exchange&coursid=5";
	if (file_exists($yesterday_exchange) === true) {
		unlink($yesterday_exchange);
	}
	if (file_exists($current_exchange) === false) {
		$ch = curl_init($pb_exchange_api_url);
		$fp = fopen($current_exchange, "w");

		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_HEADER, 0);

		curl_exec($ch);
		curl_close($ch);
		fclose($fp);
		$exchange = json_decode(file_get_contents($current_exchange)); 
	} else {
		$exchange = json_decode(file_get_contents($current_exchange));
	}
	if($exchange[0]->ccy === 'USD'){
		$check_yesterday_usd_exchange_option_in_db = get_option('usd_exchange_'.$yesterday);
		if($check_yesterday_usd_exchange_option_in_db) {
			delete_option('usd_exchange_'.$yesterday);
		}
		$check_usd_exchange_option_in_db = get_option('usd_exchange_'.date("ymd"));
		if($check_usd_exchange_option_in_db === false) {
			add_option('usd_exchange_'.date("ymd"), $exchange[0]->buy);
		}
	}
}
get_exchange();