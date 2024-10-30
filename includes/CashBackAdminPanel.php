<?php

/**
 * @author    Hotshopper <info@hotshopper.nl>
 * @license   GPL-2.0+
 * @date 2016.
 * @link      https://www.hotshopper.nl
 */
class CashBackAdminPanel {
	/**
	 * @var
	 */
	private static $instance;

	/**
	 * AdminPanel constructor.
	 */
	public function __construct() {
		$this->pluginPath = dirname( __FILE__ );

		// Set Plugin URL
		$this->pluginUrl = plugins_url(null,__FILE__);
	}

	/**
	 * @return CashBackAdminPanel
	 */
	static function GetInstance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function adminInit() {
		$isRegisterd = get_option('c247_registered');
		add_menu_page( 'Cashback', 'Cashback', 'manage_options', 'c247-dashboard', array(
			'CashBackAdminPanel',
			'adminDashboard'
		),'dashicons-share' );
		add_submenu_page('c247-dashboard', 'How It Works', 'How It Works', 'manage_options', 'c247-dashboard' );
		if(!empty($isRegisterd)){
			add_submenu_page( 'c247-dashboard', 'My Profile', 'My Profile', 'manage_options', 'c247-profile', array(
				'CashBackAdminPanel',
				'adminProfile'
			) );
			add_submenu_page( 'c247-dashboard', 'Statistics', 'Statistics', 'manage_options', 'c247-statistics', array(
				'CashBackAdminPanel',
				'adminStatistics'
			) );
			add_submenu_page( 'c247-dashboard', 'Settings', 'Settings', 'manage_options', 'c247-settings', array(
				'CashBackAdminPanel',
				'adminSettings'
			) );

			add_submenu_page( 'c247-dashboard', 'Logout', 'Logout', 'manage_options', 'c247-logout', array(
				'CashBackAdminPanel',
				'adminLogout'
			) );
		}
		add_submenu_page( null, 'Login', 'Login', 'manage_options', 'c247-login', array(
			'CashBackAdminPanel',
			'adminLogin'
		) );
		add_submenu_page( null, 'Register', 'Register', 'manage_options', 'c247-register', array(
			'CashBackAdminPanel',
			'adminRegister'
		) );
		add_submenu_page( null, 'RefreshStatistics', 'RefreshStatistics', 'manage_options', 'c247-statistics-refresh', array(
			'CashBackAdminPanel',
			'adminRefreshStatistics'
		) );
		add_submenu_page( null, 'Setup', 'Setup', 'manage_options', 'c247-setup', array(
			'CashBackAdminPanel',
			'AdminSetupBox'
		) );

		if(!empty($_GET['post'])){
			add_meta_box("c247-meta-box", "Cashback for Wordpress", array('AdminPanel','adminFilterBox'), array("post","page"), "normal", "high", null);
		}
	}

	public static function AdminSetupBox(){

        $site = get_current_site();
        setcookie('c247_keywords_status',false,0,'/',$site->domain,is_ssl(),true);
        setcookie('c247_total_posts',0,0,'/',$site->domain,is_ssl(),true);
		?>
		<h3><?php _e('Running installation','cashback'); ?></h3>

		<p>- Installing tables and keywords</p>
		<div id="content">

		</div>


		<script type="text/javascript">
			
			var content = jQuery("#content").html();
			//Lets start it

			jQuery.post(ajaxurl+'?action=c247_status_install', function(response) {
				if(response === 'complete'){
					window.location.href = '<?php echo admin_url( 'admin.php?page=c247-dashboard') ?>';
					return false;
				}
				jQuery("#content").html(response);
			});
			jQuery.get(ajaxurl+'?action=c247_install', function() {
				window.setInterval(function(content){
					jQuery.post(ajaxurl+'?action=c247_status_install', function(response) {
						if(response === 'complete'){
							window.location.href = '<?php echo admin_url( 'admin.php?page=c247-dashboard') ?>';
							return false;
						}
						jQuery("#content").html(response);
					});
				}, 1000);
			});

		</script>
		<?php

	}

	public static function adminFilterBox($object){
		$aWords = array();
		$replaceExistingLinks = get_option( 'c247_replace_existing_links', '' );
		$disableAllLinks = get_post_meta( $object->ID, 'c247_disabled', true );
		$meta = get_post_meta($object->ID, 'c247_keywords',true);
		if(!empty($meta)){
			$aWords = $meta;
		}

		?>

		<input id="c247_filterPost" type="checkbox" <?php checked( get_post_meta( $object->ID, 'c247_disabled', true ), 1 ); ?> name="c247-disabled"> <label id="label_c247_filterPost" for="c247_filterPost"><?php _e('Disable all 24/7 Discount links for this '.$object->post_type,'cashback'); ?></label><br/>
		<input id="c247_disable_offers" type="checkbox" <?php checked( get_post_meta( $object->ID, 'c247_disable_offers', true ), 1 ); ?> name="c247_disable_offers"> <label id="label_c247_disable_offers" for="c247_disable_offers"><?php _e('Disable offers for this '.$object->post_type,'cashback'); ?></label>
		<div id="linkList" style="<?php if($disableAllLinks == true){ ?>display:none;<?php } ?> ">
			<h3><?php _e('Disable specific links','cashback'); ?></h3>
			<table  class="wp-list-table widefat fixed striped posts">
				<thead>
				<tr>
					<td id="cb" class="manage-column column-cb check-column"></td>
					<th scope="col" id="title" class="manage-column column-title column-primary sortable desc"><span><?php _e('Keyword','cashback'); ?></span></th>
					<?php if($replaceExistingLinks == true){ ?>
						<th scope="col" id="author" class="manage-column"><?php _e('Url','cashback'); ?></th>
					<?php } ?>
					<th scope="col" id="author" class="manage-column"><?php _e('24/7 Discount Url','cashback'); ?></th>
				</tr>
				</thead>

				<tbody >
				<?php
				if(!empty($aWords)){
					foreach($aWords AS $word){
						?>
						<tr class="">
							<th scope="row" class="check-column">
								<label class="screen-reader-text" for="c247_keyword_<?php echo $word['id']; ?>">Disable</label>
								<input <?php if(isset($word['disabled']) && $word['disabled'] == true) { echo "checked='checked'";} ?> id="c247_keyword_<?php echo $word['id']; ?>" type="checkbox" name="c247_disabled_keywords[]" value="<?php echo $word['id']; ?>">
							</th>
							<td class="column-primary page-title" data-colname="Title">
								<strong><?php echo $word['keyword']; ?></strong>
							</td>
							<?php if($replaceExistingLinks == true){ ?>
								<td class="column-primary page-title" data-colname="Link">
									<strong><?php if(!empty($word['old_url'])) { echo $word['old_url']; } ?></strong>
								</td>
							<?php } ?>
							<td class="column-primary page-title" data-colname="Link">
								<strong><?php echo $word['url']; ?></strong>
							</td>
						</tr>
						<?php
					}
				}
				?>

				</tbody>
			</table>
		</div>
		<script type="text/javascript">

			jQuery('#c247_filterPost').click(function(){
				var element = jQuery(this);
				var list = jQuery('#linkList');
				if(element.attr('checked')){
					list.hide();
				} else{
					list.show();
				}

			});
		</script>
		<?php
	}

	public static function adminProcessPost( $post_id ) {
		global $wpdb;

		if(empty($_POST)){
			return $post_id;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if ( 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return $post_id;
			}

		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}
		}
		if ( isset( $_POST['c247_disable_offers'] ) ) {
			$showOffer =  '1';
		} else {
			$showOffer = '';
		}
		if ( isset( $_POST['c247-disabled'] ) ) {
			$value =  '1';
		} else {
			$value = '';
		}
		$replaceExistingLinks = get_option( 'c247_replace_existing_links', '' );
		$scanner = new CashBackContentScanner($wpdb);
		if(!empty($_POST['c247_disabled_keywords'])){
			$disabled = sanitize_text_field($_POST['c247_disabled_keywords']);
		} else{
			$disabled = array();
		}
		$aWords = $scanner->scanWords( $_POST['content'] ,$replaceExistingLinks,$disabled);
		$old_meta = get_post_meta($post_id, 'c247_keywords', true);
		if($old_meta !== false){
			update_post_meta($post_id, 'c247_keywords', $aWords);
		} else {
			add_post_meta($post_id, 'c247_keywords', $aWords, true);
		}

		update_post_meta( $post_id, 'c247_disabled', $value);
		update_post_meta( $post_id, 'c247_disable_offers', $showOffer);
	}

	public static function adminLogin(){
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		?>
		<div class="wrap">
			<h2>Login</h2>
			<div class="error" style="display: none;"><p><strong><?php _e( 'Unable to connect to API', 'cashback' ); ?></strong></p></div>
			<form name="login_form" class="validate" id="login_form" method="post" action="" novalidate="novalidate">
				<table class="form-table">
					<tr class="form-required">
						<th scope="row"><label for="c247_email"><?php _e('E-mail Address','cashback'); ?></label></th>
						<td><input id="c247_email" class="regular-text ltr" type="text" name="username" value=""></td>
					</tr>
					<tr class="form-required">
						<th scope="row"><label for="c247_password"><?php _e('Password','cashback'); ?></label></th>
						<td><input id="c247_password" class="regular-text ltr" type="password" name="password" value=""></td>
					</tr>

				</table>
				<p class="submit">
					<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Login' ) ?>"/>
				</p>
			</form>
			<div id="login_loading" style="float: left; margin-left:auto;margin-right:auto; display: none;"><img src="<?php echo admin_url(); ?>/images/spinner-2x.gif"></div>
			<script type="text/javascript">
				jQuery('#login_form').submit(function(event){
					jQuery.ajax({
						data: jQuery('#login_form').serialize(),
						type: 'post',
						dataType: 'json',
						url: ajaxurl+'?action=c247_process_login',
						success: function(data) {
							jQuery('.error').hide();
							jQuery('#login_form').find('input').parent().parent().removeClass('form-invalid');
							if(data.error === true){
								jQuery(data.reasons).each(function(index,element){
									if(element === 'email-empty'){
										jQuery('#c247_email').parent().parent().addClass('form-invalid');
									}
									if(element === 'password-empty'){
										jQuery('#c247_password').parent().parent().addClass('form-invalid');
									}
									if(element === 'user-not-found'){
										jQuery('#c247_email').parent().parent().addClass('form-invalid');
										jQuery('#c247_password').parent().parent().addClass('form-invalid');
									}
									if(element === 'api-error'){
										jQuery('.error').show();
										jQuery('.error > p > strong').html(data.curl_error);
									}
									if(element === 'website-exists'){
										jQuery('.error').show();
										jQuery('.error > p > strong').html('Website linked to another user.');
									}
								});
							}
							if(data.error !== true){
								window.location.href = '<?php echo admin_url( 'admin.php?page=c247-dashboard'); ?>'
							}
						}
					});
					event.preventDefault();
					return false;
				});
			</script>
		</div>
		<?php
	}

	public static function adminLogout(){
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		global $pagenow;
		$page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : false);
		if($pagenow=='admin.php' && $page=='c247-logout'){

			CashbackApi::call('logoutUser',array('token' => get_option('c247_token')),'DELETE');
			update_option('c247_token',false);
			update_option('c247_username','guest');
			update_option('c247_user_id',false);
			update_option('c247_registered', false);
			update_option('c247_username',false);
			update_option('c247_profile_first_name',false);
			update_option('c247_profile_last_name',false);
			update_option('c247_profile_email',false);
			update_option('c247_website_id',false);
			$url = admin_url( 'admin.php?page=c247-dashboard');;
			wp_redirect($url,302);
			exit;
		}
	}

	public static function adminProcessLogin(){
		$_POST['url'] = get_home_url();
		$_POST['type'] = "wordpress";
		$call = CashbackApi::post('login',$_POST);
		$response = json_decode($call,true);
		if($response['error'] !== true){
			if (get_option('c247_token') !== false) {
				update_option('c247_token', $response['token'], 'yes');
			} else {
				add_option('c247_token', $response['token'], '', 'yes');
			}
			if (get_option('c247_username') !== false) {
				update_option('c247_username', $response['userName'], 'yes');
			} else {
				add_option('c247_username', $response['userName'], '', 'yes');
			}
			if (get_option('c247_user_id') !== false) {
				update_option('c247_user_id', $response['userId'], 'yes');
			} else {
				add_option('c247_user_id', $response['userId'], '', 'yes');
			}
			if (get_option('c247_website_id') !== false) {
				update_option('c247_website_id', $response['websiteId'], 'yes');
			} else {
				add_option('c247_website_id', $response['websiteId'], '', 'yes');
			}
			if (get_option('c247_registered') !== false) {
				update_option('c247_registered', true, 'yes');
			} else {
				add_option('c247_registered', true, '', 'yes');
			}

			//Retrieve profile
			$subcall = CashbackApi::get('account/profile/',array('id' => $response['userId'],'token' => $response['token'],'shortProfile' => true));
		    $response = json_decode($subcall,true);
			if(!empty($response)){
				if (get_option('c247_profile_first_name') !== false) {
					update_option('c247_profile_first_name', $response['first_name'], 'yes');
				} else {
					add_option('c247_profile_first_name', $response['first_name'], '', 'yes');
				}
				if (get_option('c247_profile_gender') !== false) {
					update_option('c247_profile_gender', $response['gender'], 'yes');
				} else {
					add_option('c247_profile_gender', $response['gender'], '', 'yes');
				}
				if (get_option('c247_profile_email') !== false) {
					update_option('c247_profile_email', $response['email'], 'yes');
				} else {
					add_option('c247_profile_email', $response['email'], '', 'yes');
				}
			}
		}
		echo $call;
		exit;
	}

	public static function adminRegister(){
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		?>
		<div class="wrap">
			<h2>Register</h2>
			<div class="error" style="display: none;"><p><strong><?php _e( 'Unable to connect to API', 'cashback' ); ?></strong></p></div>
			<form name="register_form" class="validate" id="register_form" method="post" action="" novalidate="novalidate">
				<table class="form-table">
					<tr>
						<th scope="row"><?php _e('Website Name','cashback'); ?></th>
						<td><?php echo get_home_url(); ?></td>
					</tr>
					<tr class="form-required">
						<th scope="row"><label for="c247_gender"><?php _e('Gender','cashback'); ?></label></th>
						<td><label for="c247_gender_"><input id="c247_gender" class="ltr" type="radio" name="gender" aria-required="true" value="male"> <span style="display:inline-block; min-width: 10em;">Male</span> <input id="c247_gender_female" class="ltr" type="radio" name="gender" aria-required="true" value="female"> Female</label></td>
					</tr>
					<tr class="form-required">
						<th scope="row"><label for="c247_first_name"><?php _e('First Name','cashback'); ?></label></th>
						<td><input id="first_name" class="regular-text ltr" type="text" name="firstName" aria-required="true" value=""></td>
					</tr>
					<tr class="form-required">
						<th scope="row"><label for="c247_email"><?php _e('E-mail Address','cashback'); ?></label></th>
						<td><input id="c247_email" type="text" class="regular-text ltr" name="email" value=""></td>
					</tr>
					<tr class="form-required">
						<th scope="row"><label for="c247_password"><?php _e('Password','cashback'); ?></label></th>
						<td><input id="c247_password" class="regular-text ltr" type="password" name="password" value=""></td>
					</tr>
					<tr class="form-required">
						<th scope="row"><label for="c247_confirm_password"><?php _e('Confirm Password','cashback'); ?></label></th>
						<td><input id="c247_confirm_password" class="regular-text ltr" type="password" name="confirmPassword" value=""></td>
					</tr>
					<tr class="form-required">
						<th scope="row"></th>
						<td><label for="c247_tos"><input id="c247_tos" class="ltr" type="checkbox" name="tos"> <?php _e('I agree with the <a target="_blank" href="https://www.247discount.nl/algemene-voorwaarden/">terms and conditions</a>','cashback'); ?></label></td>
					</tr>
				</table>
				<p class="submit"><input name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e('Register','cashback'); ?>" type="submit"></p>
			</form>
		</div>
		<div id="register_loading" style="float: left; margin-left:auto;margin-right:auto; display: none;"><img src="/wp-admin/images/spinner-2x.gif"></div>
		<script type="text/javascript">
			jQuery('#register_form').submit(function(event){
				jQuery.ajax({
					data: jQuery('#register_form').serialize(),
					type: 'post',
					dataType: 'json',
					url: ajaxurl+'?action=c247_process_register',
					success: function(data) {
						jQuery('#website_exists').hide();
						jQuery('#register_form').find('input').parent().parent().removeClass('form-invalid');
						if(data.error === true){
							jQuery('.error').hide();
							jQuery(data.reasons).each(function(index,element){
								if(element === 'missing-required-fields'){
									jQuery('#register_form').find('input').each(function(index,element){
										if(jQuery(element).val() === ''){
											jQuery(element).parent().parent().addClass('form-invalid');
										}
									});
								}
								if(element === 'missing-email'){
                                    jQuery('.error').show();
                                    jQuery('.error > p > strong').html('Fill in your e-mail.');
									jQuery('#c247_email').parent().parent().addClass('form-invalid');
									return false;
								}
								if(element === 'account-exists'){
                                    jQuery('.error').show();
                                    jQuery('.error > p > strong').html('Your already a member of 24/7 Discount.');
									jQuery('#c247_email').parent().parent().addClass('form-invalid');
                                    return false;
								}
                                if(element === 'missing-passwords'){
                                    jQuery('.error').show();
                                    jQuery('.error > p > strong').html('Fill in your password.');
                                    jQuery('#c247_password').parent().parent().addClass('form-invalid');
                                    jQuery('#c247_confirm_password').parent().parent().addClass('form-invalid');
                                    return false;
                                }
								if(element === 'password-mismatch'){
                                    jQuery('.error').show();
                                    jQuery('.error > p > strong').html('Passwords do not match.');
									jQuery('#c247_password').parent().parent().addClass('form-invalid');
									jQuery('#c247_confirm_password').parent().parent().addClass('form-invalid');
                                    return false;
								}
								if(element === 'password-invalid'){
                                    jQuery('.error').show();
                                    jQuery('.error > p > strong').html('Passwords are to be equal or longer than 6 characters.');
									jQuery('#c247_password').parent().parent().addClass('form-invalid');
									jQuery('#c247_confirm_password').parent().parent().addClass('form-invalid');
                                    return false;
								}
								if(element === 'website-exists'){
									jQuery('.error').show();
									jQuery('.error > p > strong').html('Website already exists.');
									jQuery('#website_exists').show();
                                    return false;
								}
								if(element === 'terms-noagree'){
                                    jQuery('.error').show();
                                    jQuery('.error > p > strong').html('Please agree with our terms of servie.');
									jQuery('#c247_tos').parent().parent().addClass('form-invalid');
                                    return false;
								}
								if(element === 'missing-gender'){
                                    jQuery('.error').show();
                                    jQuery('.error > p > strong').html('Fill in your gender.');
									jQuery('#c247_gender').parent().parent().addClass('form-invalid');
                                    return false;
								}
								if(element === 'missing-firstName'){
                                    jQuery('.error').show();
                                    jQuery('.error > p > strong').html('Fill in your name.');
									jQuery('#first_name').parent().parent().addClass('form-invalid');
                                    return false;
								}
								if(element === 'api-error'){
									jQuery('.error').show();
									jQuery('.error > p > strong').html(data.curl_error);
								}
							});
						}
						if(data.success === true){
							window.location.href = '<?php echo admin_url( 'admin.php?page=c247-dashboard'); ?>'
						}
					}
				});
				event.preventDefault();
				return false;
			});
		</script>
		<?php
	}

	public static function adminDashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		$isRegisterd = get_option('c247_registered');
		?>
		<div class="wrap">
		<h2>How It Works</h2>

			<h3>What is 24/7 Discount?</h3>
			<p>Members of <a target="_blank" href="https://www.247discount.nl">24/7 Discount</a> receive cashback at over 2,500 shops. Aside from famous brands such as Zalando, Bol.com and Wehkamp many small retailers are connected to 24/7 Discount. With over 130,000 active users, one of the leading cashback websites in the Netherlands and the number of users is still growing rapidly.</p>
			<h3>Cashback for Wordpress</h3>
			<p>After installing Cashback for Wordpress, hyperlinks to the webshops mentioned in your blog posts will be created automatically. For instance, if you have written a blog about Zalando, the Plugin will automatically create a hyperlink to the Zalando page on 24/7 Discount, so your visitors can benefit from an additional discount.</p>
			<p>Every person enrolling to 24/7 Discount via your blog will start aggregating discounts on their account. You will also benefit from this, since a fixed fee of 1 euro per new user and a 20% commission fee on their aggregated discount will be given.</p>
			<p>On average our current members save more than 100 euro per year. The income you generate with your blogs will therefore grow steadily once more members subscribe via your blogs.</p>
			<h3>Advantages of the plugin</h3>
			<ul>
			<li>- Create added value for your visitors. Increase satisfaction rates by providing cash back on their online purchases.</li>
			<li>- Generate additional revenue with your blog. We do not only provide a commission fee, but also a fixed fee of 1 euro per new user.</li>
			<li>- Save time. Our software automatically generates the needed links to specific keywords.</li>
			</ul>
			<h3>Installation guidelines</h3>
			<p>After activating the plugin you can login with your 24/7 Discount login credentials. If you do not yet have an account you can easily enroll via the plugin. After logging in the software is ready to be used. Whenever you publish a new blog you can see which keywords were converted to include links. 24/7 Discount will never adjust the content of the blog itself. If you decide to remove the plugin the original content of your blogs will be restored.</p>

			<?php if($isRegisterd == false){ ?>
			<p class="submit">
				<a href="<?php echo admin_url( 'admin.php?page=c247-login'); ?>" class="button-primary"><?php esc_attr_e( 'Login' ) ?></a>
				<a href="<?php echo admin_url( 'admin.php?page=c247-register'); ?>" class="button-primary"><?php esc_attr_e( 'Register' ) ?></a>
			</p>
			<?php } ?>

			<h3>Statistics</h3>
			<p>After clicking in the menu on “statistics” an overview can be found of the number of new users per post and the income generated via these posts. With these statistics you can easily optimize your future content.</p>

			<h3>Payments</h3>
			<p>Visit <a target="_blank" href="https://www.247discount.nl">24/7 Discount</a> and login with the same login credentials used for the plugin. Click on “payments” and select the amount you would like to receive. You can request payment when your account balance reaches 25 euro or more. Within 7 days you will receive your payment on your bank account.</p>

		</div>
		<?php
	}

	/**
	 *
	 */
	public static function adminProfile() {

	//must check that the user has the required capability
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	$isRegistered = get_option('c247_registered');

	echo '<div class="wrap">';
	echo "<h2>" . __( 'My Profile', 'cashback' ) . "</h2>";
	// settings form
	?>
	<style type="text/css">#TB_window { background-color: #F1F1F1; }</style>
	<div class="wrap">

	<?php if($isRegistered != false && get_option( 'c247_token' )){
	$site_logo = get_option( 'c247_site_logo' );
	$password_incorrect = false;
	$password_mismatch = false;
	$accountExists = false;
	$error = false;
	$apiError = false;

	if ( isset( $_POST['profile_update'] ) && $_POST['profile_update'] == 'Y' ) {
		$_POST['prefix'] = 'c247_profile_';
		$_POST['token'] = get_option('c247_token');
		if(empty($_POST['first_name']) || empty($_POST['email'])){
			?>
			<div class="error"><p><strong><?php _e( 'Missing required fields!', 'cashback' ); ?></strong></p></div>
			<?php
		} else{
			$call = CashbackApi::call('wordpress/profile/',$_POST,'PUT');
			$result = json_decode($call,true);

			if($result['error'] == true){
				$error = true;
				if(!empty($result['curl_error'])){
					$apiError = $result['curl_error'];
				}

				foreach($result['reasons'] AS $reason){
					if($reason == 'password-incorrect'){
						$password_incorrect = true;
					}
					if($reason == 'password-mismatch'){
						$password_mismatch = true;
					}
					if($reason == 'account-exists'){
						$accountExists = true;
					}
				}
			} else{
				update_option("c247_profile_first_name",sanitize_text_field($_POST['first_name']));
				update_option("c247_profile_gender",sanitize_text_field($_POST['gender']));
				update_option("c247_profile_email",sanitize_email($_POST['email']));


				$call = null;
				$site_logo = $_POST['site_logo'];
				if(!empty($site_logo)){
					$call = CashBackApi::call( 'wordpress/updatelogo/',array('id' => get_option('c247_website_id'), 'file' => $site_logo,'token' => get_option('c247_token')),'PUT' );
				}

				if($call != 'false'){
					update_option( 'c247_site_logo', $site_logo );
				}
				//Update site title
				CashBackApi::call('wordpress/updatesitetitle/',array('id' => get_option('c247_website_id'),'site_title' => $_POST['site_title'],'token' => get_option('c247_token')),'PUT');
				update_option("c247_site_title",sanitize_text_field($_POST['site_title']));
				?>
				<div class="updated"><p><strong><?php _e( 'Changes saved.', 'cashback' ); ?></strong></p></div>
			<?php
			}

		}
	}
	$user_id = get_option("c247_user_id");
	$first_name = get_option("c247_profile_first_name");
	$site_name = get_option("c247_site_title");
	$gender = get_option("c247_profile_gender");
	$email = get_option("c247_profile_email");
	?>
	<script type="text/javascript">
		var media_uploader = null;

		function open_media_uploader_image() {
			media_uploader = wp.media({
				frame: "post",
				state: "insert",
				multiple: false
			});

			media_uploader.on("insert", function () {
				var json = media_uploader.state().get("selection").first().toJSON();
				var image_url = json.url;
				jQuery("#upload_image").val(image_url);
				jQuery("#upload_preview").attr('src', image_url);
			});

			media_uploader.open();
		}
	</script>
	<?php
	if(!empty($apiError)){
	?>
	<div class="error"><p><strong><?php echo $apiError; ?></strong></p></div>
	<?php
	}
	?>

	<form name="register_form" class="validate" id="register_form" method="post" action="" novalidate="novalidate" enctype="multipart/form-data">
		<input type="hidden" name="profile_update" value="Y">
		<input type="hidden" name="user_id" value="<?php if(!empty($user_id)){echo $user_id;} ?>">
		<table class="form-table">
			<tr>
				<th>Status</th>
				<td>
					<?php if(!empty($isRegistered)){ echo "<span style='color: green; font-weight: bold;'>Connected</span>";} else{ echo "<span style='color: red; font-weight: bold;'>Not connected</span>";} ?>
				</td>
			</tr>
		</table>
		<h3><?php _e('Personal Info','cashback'); ?></h3>
		<table class="form-table">
			<tr class="form-required">
				<th scope="row"><label for="c247_profile_gender"><?php _e('Gender','cashback'); ?></label></th>
				<td><input id="c247_profile_gender" class="ltr" <?php if($gender == 'male'){ ?> checked="checked"<?php } ?> type="radio" name="gender" aria-required="true" value="male"> <span style="display:inline-block; min-width: 10em;">Male</span> <input id="c247_profile_gender" <?php if($gender == 'female'){ ?> checked="checked"<?php } ?> class="ltr" type="radio" name="gender" aria-required="true" value="female"> Female</td>
			</tr>
			<tr class="form-required">
				<th scope="row"><label for="c247_first_name"><?php _e('First Name','cashback'); ?></label></th>
				<td><input id="c247_first_name" type="text" class="regular-text ltr" name="first_name" value="<?php if(!empty($first_name)){echo $first_name;} ?>"></td>
			</tr>
			<tr class="form-required<?php if($accountExists == true){ echo " form-invalid";} ?>">
				<th scope="row"><label for="c247_email"><?php _e('E-mail Address','cashback'); ?></label></th>
				<td><input id="c247_email" type="text" class="regular-text ltr" name="email" value="<?php if(!empty($email)){echo $email;} ?>"></td>
			</tr>
		</table>

	<h3><?php _e( "Website Info", 'c247-website' ); ?></h3>

		<input type="hidden" name="update_image" value="Y">
		<input type="hidden" id="upload_image" name="site_logo" value="<?php if ( ! empty( $site_logo ) ) {
			echo $site_logo;
		} ?>">
		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row"><label for="c247_site_title"><?php _e('Site Name','cashback'); ?></label></th>
				<td><input id="c247_site_title" type="text" class="regular-text ltr" name="site_title" value="<?php if(!empty($site_name)){echo $site_name;} ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="c247_website_name"><?php _e('Site Url','cashback'); ?></label></th>
				<td>
					<?php echo get_home_url(); ?>
				</td>
			</tr>
			<tr class="user-profile-picture">
				<th><?php _e('Site Logo','cashback'); ?></th>
				<td>
					<img style="width: 150px; height: 150px;" id='upload_preview' src='<?php if ( ! empty( $site_logo ) ) {
						echo $site_logo;
					} else {
						echo 'https://www.placehold.it/150/EFEFEF/FFFFFF&amp;text=no+image';
					} ?>' alt='site_logo'><br/>
					<button onclick="open_media_uploader_image(); return false;" class="button-secondary"><?php _e('Change image','cashback'); ?></button>
				</td>
			</tr>
			</tbody>
		</table>
		<h3><?php _e('Change Password','cashback'); ?></h3>
		<table class="form-table">
			<tr class="form-required<?php if($password_incorrect == true){ echo " form-invalid";} ?>">
				<th scope="row"><label for="c247_password"><?php _e('Current Password','cashback'); ?></label></th>
				<td><input id="c247_password" type="password" class="regular-text ltr" name="current_password" value="<?php if($password_incorrect == false && $error == true){ echo $_POST['c247_profile_current_password'];} ?>"></td>
			</tr>
			<tr class="form-required<?php if($password_mismatch == true){ echo " form-invalid";} ?>">
				<th scope="row"><label for="c247_password"><?php _e('New Password','cashback'); ?></label></th>
				<td><input id="c247_password" type="password" class="regular-text ltr" name="password" value="<?php if($password_mismatch == false && $error == true){ echo $_POST['c247_profile_password'];} ?>"></td>
			</tr>
			<tr class="form-required<?php if($password_mismatch == true){ echo " form-invalid";} ?>">
				<th scope="row"><label for="c247_password"><?php _e('Confirm Password','cashback'); ?></label></th>
				<td><input id="c247_password" type="password" class="regular-text ltr" name="confirm_password" value="<?php if($password_mismatch == false && $error == true){ echo $_POST['c247_profile_confirm_password'];} ?>"></td>
			</tr>
		</table>
		<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes' ) ?>"/>
		</p>
	</form>
	<?php
	}
	?>
	</div>
	<?php
	}

	public static function adminSettings(){
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		if ( ! get_option( 'c247_token' ) ) {
			wp_die( __( 'No token found.' ) );
		}
		if(isset($_POST['settings_update']) && $_POST['settings_update'] == 'Y'){
			if(!empty($_POST['c247_existing_links'])){
				$replaceExistingLinks = true;
			} else{
				$replaceExistingLinks = false;
			}
			if(!empty($_POST['c247_show_offers'])){
				$showOffers = true;
			} else{
				$showOffers = false;
			}
			if(!empty($_POST['c247_create_new_links'])){
				$createNewLinks = true;
			} else{
				$createNewLinks = false;
			}
			if (get_option('c247_create_new_links') !== false) {
				update_option('c247_create_new_links', $createNewLinks, 'yes');
			} else {
				add_option('c247_create_new_links', $createNewLinks, '', 'yes');
			}
			if (get_option('c247_replace_existing_links') !== false) {
				update_option('c247_replace_existing_links', $replaceExistingLinks, 'yes');
			} else {
				add_option('c247_replace_existing_links', $replaceExistingLinks, '', 'yes');
			}
			if (get_option('c247_show_offers') !== false) {
				update_option('c247_show_offers', $showOffers, 'yes');
			} else {
				add_option('c247_show_offers', $showOffers, '', 'yes');
			}
			?>
			<div class="updated"><p><strong><?php _e( 'Settings saved.', 'cashback' ); ?></strong></p></div>
			<?php
		}
		$existingLinks = get_option( 'c247_replace_existing_links' );
		$createNewLinks = get_option( 'c247_create_new_links');
		$showOffers = get_option( 'c247_show_offers');
		?>
	<div class="wrap">
		<h2><?php _e( 'Settings', 'cashback' ); ?></h2>
		<form name="setttings_form" class="validate" id="settings_form" method="post" action="" novalidate="novalidate">
			<input type="hidden" name="settings_update" value="Y">
			<table class="form-table">
				<tr>
					<th scope="row"><label for="c247_create_new_links"><?php _e('Create new links','cashback'); ?> </label></th>
					<td><input type="checkbox" <?php if($createNewLinks == true){ echo "checked='checked'"; } ?> id="c247_create_new_links" name="c247_create_new_links" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="c247_replace_existing_links"><?php _e('Replace existing links','cashback'); ?> </label></th>
					<td><input type="checkbox" <?php if($existingLinks == true){ echo "checked='checked'"; } ?> id="c247_replace_existing_links" name="c247_existing_links" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="c247_replace_no_follow_links"><?php _e('Show offers','cashback'); ?> </label></th>
					<td><input type="checkbox" <?php if($showOffers == true){ echo "checked='checked'"; } ?> id="c247_show_offers" name="c247_show_offers" /></td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes' ) ?>"/>
			</p>
		</form>
		</div>
		<?php
	}

	public static function importReferences(){
		global $wpdb;
		$url = str_replace(array('https://','http://'),'',get_home_url());
		$wpdb->query( "TRUNCATE ".$wpdb->prefix . "c247_statistics;");
		$call = CashBackApi::get( 'wordpress/statistics/',array('referrer' => $url,'token' => get_option('c247_token')) );
		if(!empty($call)){
			$result = json_decode($call,true);
			if(!empty($result)){
				if($result['error'] == true){
					return $result['curl_error'];
				} else{
					foreach($result AS $item){
						$properties = json_decode( $item['properties'],true );
						$check = $wpdb->get_row("SELECT id, `page`,leads,revenue FROM ".$wpdb->prefix . "c247_statistics WHERE `page` = '".$properties['http_referer']."'",ARRAY_A);
						if(empty($check)){
							$wpdb->insert( $wpdb->prefix . "c247_statistics", array('page' => $properties['http_referer'],'leads' => '1','revenue' => $item['revenue']));
						} else{
							$leads = $check['leads'] + 1;
							$commission = ($check['revenue'] + $item['revenue']);
							$wpdb->update( $wpdb->prefix . "c247_statistics", array('leads' => $leads,'revenue' => $commission),array('id' => $check['id'])  );
						}
					}
				}

			}

		}
		return true;
	}

	public static function adminRefreshStatistics(){
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		global $pagenow;
		$page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : false);
		if($pagenow=='admin.php' && $page=='c247-statistics-refresh'){
			$adminPanel = new CashBackAdminPanel();
			$import = $adminPanel->importReferences();
			if($import !== true){
				$url = admin_url( 'admin.php?page=c247-statistics&import='.urlencode($import));
			} else{
				$url = admin_url( 'admin.php?page=c247-statistics');
			}
			wp_redirect($url,302);
			exit;
		}

	}

	public static function adminStatistics() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		if ( ! get_option( 'c247_token' ) ) {
			wp_die( __( 'No token found.' ) );
		}
		$adminPanel = new CashBackAdminPanel();
		global $wpdb;
		$orderby = 'page';
		$order = 'ASC';
		if(!empty($_GET['orderby'])){
			$orderby = sanitize_sql_orderby($_GET['orderby']);
		}
		if(!empty($_GET['orderby'])) {
			$order = strtoupper($_GET['order']);
		}
		//c247_statistics
		$aStatistics = $wpdb->get_results("SELECT `page`, leads, revenue FROM ".$wpdb->prefix."c247_statistics ORDER BY {$orderby} {$order}; ",ARRAY_A);
		$import = $_GET['import'];
		if(empty($aStatistics) && empty($import)){
			$import = $adminPanel->importReferences();
			if($import == true){
				$aStatistics = $wpdb->get_results("SELECT `page`, leads, revenue FROM ".$wpdb->prefix."c247_statistics ORDER BY {$orderby} {$order}; ",ARRAY_A);
			}
		}
		if($order == 'ASC'){
			$order = 'desc';
		}
		if($order == 'DESC'){
			$order = 'asc';
		}
		?>
		<div class="wrap">
		<div class="icon32" id="icon-options-general"></div>
		<h2><?php _e('Statistics','cashback'); ?></h2>
		<br/><br/>
		<?php
		if($import !== true){
			?>
			<div class="error"><p><strong><?php echo $import; ?></strong></p></div>
			<?php
		}
		?>
		<div id="aal_panel3">
		<table class="wp-list-table widefat fixed striped posts">
			<thead>
			<tr>
				<td id="cb" class="manage-column column-cb check-column"></td>
				<th scope="col" id="page" class="manage-column column-title column-primary sortable desc"><span><?php _e('Page','cashback'); ?></span></th>
				<th scope="col" id="leads" class="manage-column"><a href="<?php echo admin_url( 'admin.php?page=c247-statistics&orderby=leads&order='.$order); ?>"><?php _e('New users','cashback'); ?></a></th>
				<th scope="col" id="revenue" class="manage-column"><a href="<?php echo admin_url( 'admin.php?page=c247-statistics&orderby=revenue&order='.$order); ?>"><?php _e('Commission','cashback'); ?></a></th>
			</tr>
			</thead>

			<tbody id="the-list">
			<?php
			if(!empty($aStatistics)){
				foreach($aStatistics AS $statistic){
					?>
					<tr class="">
						<td></td>
						<td class="column-primary page-title" data-colname="Title">
							<strong><?php echo $statistic['page']; ?></strong>
						</td>
						<td class="column-primary page-title" data-colname="Link">
							<strong><?php echo $statistic['leads']; ?></strong>
						</td>
						<td class="column-primary page-title" data-colname="Link">
							<strong>&euro; <?php echo money_format( "%.2n", $statistic['revenue'] ); ?></strong>
						</td>
					</tr>
					<?php
				}
			}
			?>

			</tbody>
		</table>


		<div>

			<br/>
			<p><a class="button-primary" href="<?php echo admin_url( 'admin.php?page=c247-statistics-refresh') ?>">Refresh statistics</a></p>
		</div>
		<?php
	}

	public function adminProcessRegister(){
		$_POST['prefix'] = 'c247_';
		$_POST['url'] = get_home_url();
		$_POST['skipWebsite'] = 'no';
		$call = CashBackApi::post('register/',$_POST);
		$response = json_decode($call,true);
		if($response['success'] == true){
			if (get_option('c247_token') !== false) {
				update_option('c247_token', $response['token'], 'yes');
			} else {
				add_option('c247_token', $response['token'], '', 'yes');
			}
			if (get_option('c247_username') !== false) {
				update_option('c247_username', $response['username'], 'yes');
			} else {
				add_option('c247_username', $response['username'], '', 'yes');
			}
			if (get_option('c247_user_id') !== false) {
				update_option('c247_user_id', $response['userId'], 'yes');
			} else {
				add_option('c247_user_id', $response['userId'], '', 'yes');
			}
			if (get_option('c247_website_id') !== false) {
				update_option('c247_website_id', $response['websiteId'], 'yes');
			} else {
				add_option('c247_website_id', $response['websiteId'], '', 'yes');
			}
			if (get_option('c247_registered') !== false) {
				update_option('c247_registered', true, 'yes');
			} else {
				add_option('c247_registered', true, '', 'yes');
			}
			if (get_option('c247_profile_first_name') !== false) {
				update_option('c247_profile_first_name', $response['first_name'], 'yes');
			} else {
				add_option('c247_profile_first_name', $response['first_name'], '', 'yes');
			}
			if (get_option('c247_profile_gender') !== false) {
				update_option('c247_profile_gender', $response['gender'], 'yes');
			} else {
				add_option('c247_profile_gender', $response['gender'], '', 'yes');
			}
			if (get_option('c247_profile_email') !== false) {
				update_option('c247_profile_email', $response['username'], 'yes');
			} else {
				add_option('c247_profile_email', $response['username'], '', 'yes');
			}
		}
		echo $call;
		exit;
	}
}