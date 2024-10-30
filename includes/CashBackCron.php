<?php

/**
 * @author    Hotshopper <info@hotshopper.nl>
 * @license   GPL-2.0+
 * @date 2016.
 * @link      https://www.hotshopper.nl
 */
class CashBackCron {

	/**
	 * Cron constructor.
	 */
	public function __construct() {
	}

	public static function HourlyCron(){
		$adminPanel = new CashBackAdminPanel();
		$adminPanel->importReferences();
	}

	public static function DailyCron(){
		global $wpdb;
		$installer = new CashbackInstaller($wpdb);
		$installer->updateKeywords();
	}
}