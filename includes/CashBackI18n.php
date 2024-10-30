<?php
/**
 * @author    Hotshopper <info@hotshopper.nl>
 * @license   GPL-2.0+
 * @date 2016.
 * @link      https://www.hotshopper.nl
 */


/**
 * Class Affiliate_i18n
 * @package Affiliate\i18n
 */
class CashBackI18n {
	/**
	 * @var
	 */
	private $domain;

	
	public static function loadPluginTextdomain() {

		load_plugin_textdomain(
			'cashback',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}

	/**
	 * @param $domain
	 */
	public function setDomain( $domain ) {
		$this->domain = $domain;
	}
}