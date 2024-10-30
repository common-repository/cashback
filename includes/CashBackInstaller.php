<?php

/**
 *
 * @package   cashback-wordpress
 * @author    Hotshopper <info@hotshopper.nl>
 * @license   GPL-2.0+
 * @link      https://www.hotshopper.nl
 *
 */
class CashBackInstaller {

    /**
     *
     */
    const VER    = '1.2';
    /**
     *
     */
    const DB_VER = 2;
	/**
	 * Installer constructor.
	 *
	 * @param $wpdb
	 */
	public function __construct( $wpdb ) {
		$this->wpdb = $wpdb;
	}

    static public function install() {
		global $wpdb;
		$installer = new CashBackInstaller( $wpdb );
		$installer->initOptions();
		$installer->updatePlugin();
		$installer->installTable();

		wp_schedule_event( time(), 'daily', 'c247_daily_update' );
		wp_schedule_event( time(), 'hourly', 'c247_hourly_update' );
		$installer->activateAboutPage();
	}


    static public function activateSteps(){
        unset($_SESSION['c247_post_process'],$_SESSION['c247_keywords_status']);
		global $wpdb;
		$posts = get_posts(array('numberposts'=> '-1'));
        $_SESSION['c247_total_posts'] = count($posts);
        update_option('c247_post_process',0);
        $_SESSION['c247_keywords_status'] = false;
		$installer = new CashBackInstaller( $wpdb );
		$installer->insertKeywords();
	}

    /**
     * @param $message
     * @param $errno
     */
    public static function br_trigger_error($message, $errno) {

		if(isset($_GET['action']) && $_GET['action'] == 'error_scrape') {
			echo "<pre>".$message."</pre>";
			exit;
		} else {
			trigger_error($message, $errno);
		}
	}

    public static function statusInstall(){

		$total_posts = $_SESSION['c247_total_posts'];
		$current_post = get_option('c247_post_process');
        $html = "";
        if($_SESSION['c247_keywords_status'] == true){
            $html .= "<p>- Scanning <span id=\"content\">{$current_post}</span>/{$total_posts} posts</p>";
            if($total_posts != $current_post){
                echo $html;
            } else{
                echo "complete";
            }
        }

		exit;
	}

    public function updateKeywords(){
		$this->wpdb->query( "TRUNCATE TABLE " . $this->wpdb->prefix . "c247_keywords;" );
		$this->insertKeywords();
		$this->scanPosts();
	}

    private function scanPosts(){
		$posts = get_posts(array('numberposts'=> '-1'));
		global $wpdb;
		$scanner = new CashBackContentScanner($wpdb);
		$c247_replace_existing_links = get_option('c247_replace_existing_links',false);
        $i=0;
		foreach($posts AS $key => $post){
			update_option('c247_post_process',++$i);
			set_time_limit(30);
			$aWords = $scanner->scanWords( $post->post_content ,$c247_replace_existing_links);
			add_post_meta($post->ID, 'c247_keywords', $aWords, true);
		}
	}

    private function initOptions(){
		update_option( 'c247_ver', self::VER );
		add_option('c247_db_ver', self::DB_VER );
		add_option('c247_site_logo','https://www.placehold.it/150/EFEFEF/AAAAAA&amp;text=no+image');
		add_option('c247_replace_existing_links',true);
		add_option('c247_create_new_links',true);
		add_option('c247_show_offers',true);
		add_option('c247_username','guest');
		add_option('c247_user_id', false);
		add_option('c247_registered',false);
		add_option('c247_profile_first_name','');
		add_option('c247_profile_gender','');
		add_option('c247_profile_email','');
		add_option('c247_site_title',get_bloginfo( 'name' ));
		add_option('c247_token',false);
	}

    private function updatePlugin(){
		if ( get_option( 'c247_db_ver' ) >= self::DB_VER ) {
			return;
		}
	}

    public static function redirect_settings_page() {
		// only do this if the user can activate plugins
		if ( ! current_user_can( 'manage_options' ) )
			return;

		// don't do anything if the transient isn't set
		if ( ! get_transient( 'c247_about_page_activated' ) )
			return;

		delete_transient( 'c247_about_page_activated' );
		wp_safe_redirect( admin_url( 'admin.php?page=c247-setup') );
		exit;
	}

    public function activateAboutPage() {
		set_transient( 'c247_about_page_activated', 1, 30 );
	}

    private function installTable() {
		$tablesql = "CREATE TABLE IF NOT EXISTS " . $this->wpdb->prefix . "c247_keywords (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `rid` INT(11) DEFAULT NULL,
  `keyword` VARCHAR(255) DEFAULT NULL,
  `url` VARCHAR(255) DEFAULT NULL,
  `added` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `keyword` (`keyword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";
		$res = $this->wpdb->query( $tablesql );
		if(!$res){
			$this->br_trigger_error('Unable to install tables, check database permissions.', E_USER_ERROR);
		}


		$tablesql = "CREATE TABLE IF NOT EXISTS " . $this->wpdb->prefix . "c247_statistics (
  `id` INT NOT NULL AUTO_INCREMENT,
  `page` VARCHAR(255) NULL DEFAULT NULL,
  `leads` INT(11) NULL DEFAULT NULL,
  `revenue` VARCHAR(100) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `page` (`page` ASC),
  INDEX `leads` (`leads` ASC)) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";
		$res = $this->wpdb->query( $tablesql );
		if(!$res){
			$this->br_trigger_error('Unable to install tables, check database permissions.', E_USER_ERROR);
		}
	}

    private function insertKeywords() {
	    $aData = CashBackApi::get("getaffiliatelinks");
		if ( ! empty( $aData ) ) {
			$aData = json_decode( $aData, true );
			if(!empty($aData)){

				foreach ( $aData AS $item ) {
					$this->wpdb->insert( $this->wpdb->prefix . "c247_keywords", array(
						'keyword' => $item['title'],
						'rid' => $item['rid'],
						'url'     => $item['url'],
						'added'   => date("Y-m-d H:i:s")
					) );
				}
                $_SESSION['c247_keywords_status'] = true;
			} else{
				$this->br_trigger_error('Keyword list is empty.', E_USER_ERROR);
			}
		} else{
			$this->br_trigger_error('Keyword list is empty.', E_USER_ERROR);
		}
	}

    static public function uninstall() {
		global $wpdb;
		$installer = new CashBackInstaller( $wpdb );
		$installer->deleteTable();
		$installer->deleteMeta();
		$installer->removeOptions();
		wp_clear_scheduled_hook('c247_daily_update');
		wp_clear_scheduled_hook('c247_hourly_update');
	}

    private function removeOptions(){
		delete_option( 'c247_ver' );
		delete_option('c247_db_ver');
		delete_option('c247_site_logo');
		delete_option('c247_username');
		delete_option('c247_user_id');
		delete_option('c247_registered');
		delete_option('c247_profile_first_name');
		delete_option('c247_profile_gender');
		delete_option('c247_profile_email');
		delete_option('c247_create_new_links');
		delete_option('c247_site_title');
		delete_option('c247_no_follow_links');
		delete_option('c247_replace_existing_links');
		delete_option('c247_website_id');
		delete_option('c247_token');
		delete_option('c247_total_posts');
		delete_option('c247_current_post');
		delete_option('c247_post_process');
	}

    private function deleteMeta(){
		$this->wpdb->query( "DELETE FROM " . $this->wpdb->prefix . "postmeta WHERE meta_key = 'c247_keywords';" );
		$this->wpdb->query( "DELETE FROM " . $this->wpdb->prefix . "postmeta WHERE meta_key = 'c247_disabled';" );
	}

    private function deleteTable() {
		$this->wpdb->query( "DROP TABLE " . $this->wpdb->prefix . "c247_keywords;" );
		$this->wpdb->query( "DROP TABLE " . $this->wpdb->prefix . "c247_statistics;" );
	}
}