<?php
 /*
Plugin Name: Formula04 Site Lock
Plugin Script: form04_sitelock.php
Plugin URI: http://formula04.com/form04_sitelock
Description: Put a sitewide password on your site that will force users to enter said password before viewing content.
Version: 1.0
Tested up to: 4.1.1
Author: VerB
Author URI: https://profiles.wordpress.org/verb_form04
Template by: http://www.formula04.com

=== RELEASE NOTES ===
2014-11-12 - v.01 - first version

2014-11-12 - v1.0 - first real version
*/

if(!class_exists( 'Form04_SiteLock'  )):
class Form04_SiteLock {
	protected $form04_sitelock_options; 

	/* __construct function.
     *
     * @access public
     * @param mixed $product
     */
	public function __construct()
    {
		//Keep all our Formula04 SiteLock Settings in one easy accessible variable.
		$this->form04_sitelock_options = get_option( 'form04_sitelock_settings' ); 
		
		
		//Enqueue Admin scripts.
        add_action('admin_enqueue_scripts', array(
            &$this,
            'form04_admin_enqueue'
        ));
		
		//Add scripts to admin footer		
        add_action('admin_footer', array(
            &$this,
            'form04_admin_scripts_func'
        ));
		//----------------------------
		// UNIVERSAL FUNCTIONS
		//----------------------------
			
		//Add our custom product type
		add_action('plugins_loaded', array(
			&$this,
			'form04_plugins_loaded'
		));		
		
		//Enqueue scripts fron front end.
		add_action('wp_enqueue_scripts', array(
			&$this,
			'form04_enqueue_scripts'
		), 30);       
				
	   //add_action( 'admin_menu', 'formula04_add_admin_menu' );
		add_action( 'admin_init',  array(
			&$this, 
			'form04_settings_init'
		));
				
		add_action( 'admin_menu',  array(
			&$this, 
			'form04_sitelock_settings_menu'
		));		
		
		
		//HEARTBEAT
		add_action( 'admin_enqueue_scripts', array(&$this, 'edd_heartbeat_enqueue') );
		add_filter( 'heartbeat_settings', array(&$this,'wptuts_heartbeat_settings') );
		add_filter( 'heartbeat_received', array(&$this,'edd_heartbeat_received'), 10, 2 );
		//END HEARTBEAT
			
		
		//---------------------------
		// END UNIVERSAL FUNCTIONS
		//----------------------------
		
		
		//If we are activated run our site bouncer
		if( isset(  $this->form04_sitelock_options['formula04_site_lock_onoff'])  && $this->form04_sitelock_options ['formula04_site_lock_onoff'] == 1 ):
		  //Add scripts  directly to header  front-end
		  add_action('template_redirect', array(
			  &$this,
			  'site_bouncer'
		  ), 1);
		  
		//Add shortcode that dispalys the form
		add_shortcode( 'f04sitelockform', array(&$this, 'formula04_site_lock_password_form_shortcode') );
			  
		  
		endif;///if( $form04_sitelock_options['formula04_site_lock_onoff'] == 1 ):		

	}//__construct



//Fire duruing admin admin enqueue
public function form04_admin_enqueue(){

}//form04_admin_enqueue



public function form04_admin_scripts_func(){

}//end 


//Fire during plugin loading
public function form04_plugins_loaded(){

}//end form04_plugins_loaded


public function form04_enqueue_scripts(){

}//end form04_enqueue_scripts


//Is user logged in.
public function site_bouncer(){
	//function should not be ran unless we are activated.	
	//Are we logged in according to site lock?
	$form04_sitelock_loggedin = $this->formula04_site_lock_logged_in_check();
	//If we are logged in according to site lock // we are all done
	
	
	
	if( $form04_sitelock_loggedin ):
		return;
	endif;
		
	//So we are not logged in, First lets check if we are submitting a login request?
	//This could be fired on a whitelist page, or maybe not(in the future) who knows.  Either was, do it first.
	$pass_try = isset(  $_POST['formula04-site-lock-pass-try']  )  && !empty($_POST['formula04-site-lock-pass-try']) ? sanitize_text_field( $_POST['formula04-site-lock-pass-try'] ) : false;   
   
    //User has submitted a password.
	if($pass_try):
		$pass_try_result = $this->validate_password( $pass_try );
		//did the password validate;
		if($pass_try_result):
			$this->cookie_monster(  md5( $pass_try )   );
			//Cool they were logged in successfully, now do we need to redirect them.
			$redirect = isset($_GET['redirect_to']) ? sanitize_text_field( $_GET['redirect_to'] ) : false;
			//They do have a redirect lets send them on their way.
			
			    if($redirect):
				  wp_redirect($redirect);
				  exit;
				endif;
		else:
		set_transient('form04_sitelock_login_error', 'Invalid Password', 5);
			$this->cookie_monster();
		endif;		
	else:	
	//User is not logged in
	//User did not submit a password
	//serve them cookies cookie monster!
		$this->cookie_monster();
	endif;//if($pass_try):

	
	
	
	global $post;
	
	
	//we aint logged in
	//We aint submitting no damn password
	//maybe thats cause we are on a whitelisted page?
	$are_we_on_whitelisted = $this->check_white_list();
	if($are_we_on_whitelisted):
		return;
	else:
	endif;
	
	
	
	//we aint logged in
	//We aint submitting no damn password
	//Aint this aint no damn white listed page.	
	$form04_sitelock_options = $this->form04_sitelock_options;
	$redirect_location =  $this->formula04_site_lock_get_redirect_location();

	//Lets make sure they are not logged in one more time for good measure. 
	if ( $this->formula04_site_lock_logged_in_check()  ):
		return;
    else:
	 wp_redirect(  $redirect_location );
	exit;				  
    endif;	

	return false;
	
	//Are we on front page.
	if( get_the_ID() ==  get_option( 'page_on_front' ) 
	||
      in_array( $GLOBALS['pagenow'], array( 'wp-login.php' ) )	
	):
		//echo 'We are on homepage or login page.';
	else:
		  
		  if ( $this->formula04_site_lock_logged_in_check()  ):
		  //	echo 'Logged IN';
		  else:
		  	//The user is not logged in, where should we send them?
			$redirect_location =  $this->formula04_site_lock_get_redirect_location();
			if( $redirect_location ):
			
			else:
				$redirect_location = $whitelist_super_page;
			endif;
			//echo 'Not ON Homepage and not logged in';
			 wp_redirect(  $redirect_location );
		    exit;	
			  
		  endif;	
	endif;	
}//site_bouncer



public function check_white_list($post = false){
	 global $wp_query;
	 $form04_sitelock_options = $this->form04_sitelock_options; 
	 $white_listed = $form04_sitelock_options['formula04_site_white_listed'];
	
	 //Do we already have a post object set
	 if( !$post ):
		  global $post;
	 endif;	
	
	 //When checking on post or page or attachment;
	 if( $wp_query->is_singular ):
		$post_id =  $post->ID;
	  
		 //is our post ID in the whitelist array?
		 if(  isset(  $white_listed[$post_id]   )  &&  $white_listed[ $post_id  ] ==  $post_id   ):
			return true;
		 endif;		  	
	 endif;// if( $wp_query->is_singular ):
	 
    
	
	/*	
	  if( in_array( $GLOBALS['pagenow'], array( 'wp-login.php' ) ) ) :
	   return true;
	  endif;
	 
	*/
	
	
	return false;
	
	

}//check_white_list 


private function validate_password($password_attempt){
	
		$form04_sitelock_options = 	$this->form04_sitelock_options;
		$site_password = $form04_sitelock_options['formula04_site_lock_password'];
		
		//If our site password is equal to their password attemp.
		if($site_password === $password_attempt):
			return true;
		else:
			return false;
		endif;
			
}//validate_password

public function cookie_monster( $cookie_value = 'bethechangeyouwanttosee', $action = 'add', $cookie_name = 'formula04-site-lock'){
	 if(  isset( $_COOKIE[$cookie_name]  )  ):
	 	unset($_COOKIE[$cookie_name]);
	 endif;
	setcookie(  $cookie_name, $cookie_value, time() + 3600, '/', '' /**/);		


}



//Little function smunction to check if we are logged in
//according to our system, screw what wordpress thinks! Just kidding WP I love you baby.
private function formula04_site_lock_logged_in_check() {
	//Get Our Options
	
	$form04_sitelock_options = 	$this->form04_sitelock_options;
	//Get password from options
	$password = isset($form04_sitelock_options['formula04_site_lock_password']) ? $form04_sitelock_options['formula04_site_lock_password'] : false;
	 
	 //Do we already have a password cookie set
  	 if(  isset( $_COOKIE['formula04-site-lock'])  ):
		//We Do Lets See It it's a winner.
   		 if( ($_COOKIE['formula04-site-lock']) ==  md5($password) ):
			//This user is already logged in
			return true;
		endif;  
    endif;//if(isset( $_COOKIE['formula04-site-lock'])):
	
	return false;
}//formula04_site_lock_logged_in_check()

//Output our password form.
public function formula04_site_lock_password_form(){
if($this->formula04_site_lock_logged_in_check()):
	return '<strong>Whoop-Dee-Doo,  You are Logged in!</strong>';
else:	
ob_start();?>	
<form method="post">
<?php if(  get_transient('form04_sitelock_login_error' ) ):?>
		<div class="error form04_sitelock_error"><?php echo get_transient('form04_sitelock_login_error' ); ?></div>
		<?php endif ?><span class="formula04_d_block formula04_site_lock_label"><?php _e( $this->form04_sitelock_options['formula04_site_lock_label'], 'formula04' ); ?></span>
  <input type="password" name="formula04-site-lock-pass-try" class="formula04_site_lock_password formula04_d_block" /> 
 <?php  wp_nonce_field( 'formula04-site-lock-password-attempt' );  ?>
  <input type="submit" value="Submit" class="formula04_site_lock_submit formula04_d_block" />
</form><?php
$form = ob_get_contents();
ob_end_clean();
return $form;
endif;
?>
<?php }//formula04_site_lock_password_form


public function formula04_site_lock_get_redirect_location(){
	global $post;
	global $wp_query;
	$where_to_go = false;
	$url = false;
	
	$form04_sitelock_options = $this->form04_sitelock_options;
	
	$whitelist_location = $form04_sitelock_options['formula04_site_lock_redirect_location'];
	$whitelisted = $this->formula04_site_lock_get_whitelisted();
	
	//Lets see what we should be doing with our $whitelist_location;
	if($whitelisted && !empty($whitelisted)):
	
	
	//If it is numeric it is probably a page ID
  	if(  is_numeric($whitelist_location)  ):
		
		//Lets make sure it is in our whitelist array.
		if (  in_array($whitelist_location, $form04_sitelock_options )  ):
   			$whitelist_location = get_permalink($whitelist_location);	
		else:
		//The location given for whitelisting is not whitelisting, so try to go to a white listed page
		//This should never happen.
			$whitelist_in_error = array_shift( array_values( $whitelisted )  );
			
			$whitelist_location = get_permalink($whitelist_in_error[0]);
		
			
		endif;
		
		
		
		
	endif;
	
	else:
	//No site is white listed at all.  So we direct to the homepage.
	
	
	endif;
	
	
	
		
	//we need to go back to a single page.
	//true: is_single(), is_page() or is_attachment(). 
	if( $wp_query->is_singular ):
		$url = get_permalink($post->ID);	
	endif;
	
	if($url):
		$whitelist_location = add_query_arg('redirect_to', $url, $whitelist_location);
	endif;
	
	return $whitelist_location;
	
}//formula04_site_lock_get_redirect_location


//----------------------------
//Setting Section Functions
//----------------------------
//----------------------------
//BACKEND STUFF
//----------------------------

// action function for above hook
public function form04_sitelock_settings_menu() {
    // Add a new submenu under Settings:
    add_options_page(
		__('Formula 04 Site Lock','formula04'), 
		__('F04 Site Lock','formula04'), 
		'manage_options', 'formula04-site-lock', 
		array(&$this, 'form04_sitelock_settings_page') 
	 );
}
// mt_settings_page() displays the page content for the Test settings submenu
public function form04_sitelock_settings_page() {
    echo "<h2>" . __( 'Formula 04 Site Lock Settings', 'formula04' ) . "</h2>";?>
    <div id="formula04_dialog_box">
    </div>
    
    <form action='options.php' method='post' class="formula04-site-lock-admin-options-form">		
		<?php
		settings_fields( 'F04SiteLockSet' );
		do_settings_sections( 'F04SiteLockSet' );
		submit_button();
		?>
		
	</form>
	<?php 
	
}//formula_04_settings_page



public function form04_settings_init() { 

	register_setting( 'F04SiteLockSet', 'form04_sitelock_settings' );

	add_settings_section(
		'formula04_SiteLockSet_section', 
		__( 'Use this section to customize Formula 04 Site Lock ', 'formula04' ), 
		array(&$this, 'form04_sitelock_settings_section_callback'), 
		'F04SiteLockSet'
	);
	
	add_settings_field( 
		'formula04_site_lock_password', 
		__( 'Formula04 Site Lock Password', 'formula04' ), 
		array(&$this, 'formula04_site_lock_password_render'), 
		'F04SiteLockSet', 
		'formula04_SiteLockSet_section' 
	);	
	
	

	add_settings_field( 
		'formula04_site_lock_onoff', 
		__( 'Activate Formula04 Site Lock', 'formula04' ), 
		array(&$this, 'formula04_site_lock_onoff_render'), 
		'F04SiteLockSet', 
		'formula04_SiteLockSet_section' 
	);
	
	
	add_settings_field( 
		'formula04_site_white_listed', 
		__( 'White Listed Pages', 'formula04' ), 
		array(&$this, 'formula04_site_lock_white_listed_render'), 
		'F04SiteLockSet', 
		'formula04_SiteLockSet_section' 
	);
	
	
	add_settings_field( 
		'formula04_site_lock_label', 
		__( 'Formula04 Site Lock Label', 'formula04' ), 
		array(&$this, 'formula04_site_lock_label_render'), 
		'F04SiteLockSet', 
		'formula04_SiteLockSet_section' 
	);		

	add_settings_field( 
		'formula04_site_lock_redirect_location', 
		__( 'Formula04 Site Lock Redirection Location', 'formula04' ), 
		array(&$this, 'formula04_site_lock_redirect_location_render'), 
		'F04SiteLockSet', 
		'formula04_SiteLockSet_section' 
	);			

}//form04_sitelock_settings_init()



public function form04_sitelock_settings_section_callback(  ) { ?>
	<hr />
	<?php 
	//echo __( 'This section description', 'formula04' );
}

public function formula04_site_lock_label_render(  ) { 
	$options = get_option( 'form04_sitelock_settings' );
	$formula04_site_lock_label = isset(  $options['formula04_site_lock_label']  )  ? $options['formula04_site_lock_label'] :  'Formula04 Site Lock Label';?>
    <input type="text" value="<?php echo $formula04_site_lock_label; ?>" name="form04_sitelock_settings[formula04_site_lock_label]" />
 <?php
 }//formula04_site_lock_label_render



public function formula04_site_lock_password_render(  ) { 
	$options = get_option( 'form04_sitelock_settings' );
	$formula04_site_lock_password = isset(  $options['formula04_site_lock_password']  )  ? $options['formula04_site_lock_password'] :  '';?>
    <input type="text" value="<?php echo $formula04_site_lock_password; ?>" name="form04_sitelock_settings[formula04_site_lock_password]" />
 <?php
 }//formula04_site_lock_password_render
 
public function formula04_site_lock_redirect_location_render(  ) { 
	$options = get_option( 'form04_sitelock_settings' );
	$whitelisted =$this->formula04_site_lock_get_whitelisted();
	?>
    <select name="form04_sitelock_settings[formula04_site_lock_redirect_location]" id="formula04_site_lock_redirect_location_select">
    	<?php /*?><option class="constant" value="use_plugin_template" <?php echo $options  && isset($options['formula04_site_lock_redirect_location']) && $options['formula04_site_lock_redirect_location'] == 'use_plugin_template'  ? 'selected': '';   checked( $options['formula04_use_f04_css'], 1 ); ?>>Use Plugin/Custom Template</option><?php */?>
    <?php 
		if($whitelisted  && !empty($whitelisted)):
		 foreach($whitelisted as  $post_id => $post_id_as_well):?>
		<option value="<?php echo $post_id ?>" <?php echo $options  && isset($options['formula04_site_lock_redirect_location']) && $options['formula04_site_lock_redirect_location'] == $post_id  ? 'selected': '';  /* checked( $options['formula04_use_f04_css'], 1 );*/ ?>><?php echo get_the_title($post_id) ?></option>
	<?php endforeach;
		endif;
	?>
    </select>
<?php
}

public function formula04_site_lock_onoff_render(  ) { 
	$options = get_option( 'form04_sitelock_settings' );?>
	<input type='checkbox' name='form04_sitelock_settings[formula04_site_lock_onoff]' <?php echo $options  && isset($options['formula04_site_lock_onoff']) && $options['formula04_site_lock_onoff'] ? 'checked' : '';  /* checked( $options['formula04_use_f04_css'], 1 );*/ ?> value='1'>
	<span class=""><?php _e('Activate', 'formula04'); ?></span>    
	<?php //formula04_site_lock_onoff_render
}


public function formula04_site_lock_get_all_page_options(){
		//Get ALL POSTS
		$master_array = array();
		$all_posts = get_posts(); 
		foreach($all_posts  as  $key => $post):
			$master_array['posts'][] = $post;	
		endforeach;	
				
		//Get ALL PAGES
		$all_pages = get_pages(); 
		foreach($all_pages  as  $key => $post):
			$master_array['pages'][] = $post;?>		
<?php   endforeach;

		return $master_array;
}//formula04_site_lock_get_all_page_options

public function formula04_site_lock_white_listed_render(  ) { 
		$options = get_option( 'form04_sitelock_settings' );
		//echo" <br /><hr /><pre style='background-color:black; color:white;'>".htmlspecialchars(print_r($options,true))."</pre>";
		$whitelisted_pages = isset(  $options['formula04_site_white_listed']  ) ?  $options['formula04_site_white_listed'] : false;
		//echo" <br /><hr /><pre style='background-color:black; color:white;'>".htmlspecialchars(print_r($whitelisted_pages,true))."</pre>";
		$master_array = $this->formula04_site_lock_get_all_page_options(   );
		
		foreach($master_array as  $post_type => $posts):?>
			<h3 style=" text-transform:capitalize; border-bottom:1px dotted;"><?php echo $post_type; ?></h3>
            <?php //Cycle through all posts of this type
			foreach($posts as  $key => $post_value):?>
                <span class="one_whitelist_option" style="padding-right:10px; font-size:.8em;">
                    <input data-the_title="<?php echo get_the_title( $post_value->ID ); ?>" type="checkbox" name="form04_sitelock_settings[formula04_site_white_listed][<?php echo $post_value->ID ?>]"  value="<?php echo $post_value->ID ?>"  <?php echo $whitelisted_pages && in_array($post_value->ID, $whitelisted_pages) ?  'checked="checked"' : ''; ?>><?php echo $post_value->post_title ?> 
                </span>
	  		<?php 
			endforeach;		
		endforeach;//foreach($master_array as  $post_type => $posts):
		//echo" <br /><hr /><pre style='background-color:black; color:white;'>".htmlspecialchars(print_r(,true))."</pre>";
}//formula04_site_lock_white_listed_render
//----------------------------
//END Setting Section Functions
//----------------------------
public function formula04_site_lock_get_whitelisted(){
	
	//Get Our Options
	$options = get_option( 'form04_sitelock_settings' );
   //Get An Array of all our Whitelisted ID's otherwise we are false
    $whitelisted = isset($options['formula04_site_white_listed']) ? $options['formula04_site_white_listed'] : false;
	return $whitelisted;
}//formula04_site_lock_get_whitelisted


//----------------------------
//Add Formula04_site_lock Shortcode
//----------------------------
public function formula04_site_lock_password_form_shortcode( $atts ) {
      $atts = shortcode_atts( array(
 	      'love' => 'all you need',
 	      'free' => 'yourself;'
      ), $atts, 'formula04' );

      return $this->formula04_site_lock_password_form();
}

//----------------------------
//END Formula04_site_lock Add Shortcode
//----------------------------




// Load the heartbeat JS
public function edd_heartbeat_enqueue( $hook_suffix ) {
    // Make sure the JS part of the Heartbeat API is loaded.
    wp_enqueue_script( 'heartbeat' );
    add_action( 'admin_print_footer_scripts', array(&$this, 'edd_heartbeat_footer_js'), 20 );
}
public function wptuts_heartbeat_settings( $settings ) {
    $settings['autostart'] = false;
    return $settings;
}
// Inject our JS into the admin footer
public function edd_heartbeat_footer_js() {
    global $pagenow;
 
    // Only proceed if on site lock admin page
	if(isset( $_GET['page'])):
	if( 'options-general.php' != $pagenow   ||  $_GET['page'] != 'formula04-site-lock'  ):
        return;
	endif;
	endif;
	?>
    <script>
    (function($){
		//For Testing
		wp.heartbeat.interval( 'fast' );
			
	    // Hook into the heartbeat-send
        $(document).on('heartbeat-send', function(e, data) {
			     var $form_data = data['formula04-site-lock-admin-data'] = $("form.formula04-site-lock-admin-options-form").serialize();
				//console.log($form_data); 
       	});
 
		
		
		$("form.formula04-site-lock-admin-options-form :input").change(function() {
			var $form_data = $("form.formula04-site-lock-admin-options-form").serialize();
			wp.heartbeat.interval( 'fast' );	
  			
		});
		 		
 
 
 
        // Listen for the custom event "heartbeat-tick" on $(document).
        $(document).on( 'heartbeat-tick', function(e, data) {
 		
 		
            // Only proceed if our EDD data is present
            if ( ! data['formula04-site-lock-admin-messages'] )
                return;
				
			
			var $formula04_site_lock_redirect_location = data['formula04_site_lock_redirect_location'];	
			//alert($formula04_site_lock_redirect_location );
			
			//Add Admin Messages
			var $messages = data['formula04-site-lock-admin-messages'];	
			jQuery('#formula04_dialog_box').html('');
			var $messagesLength = $messages.length;
			for (var i = 0; i < $messagesLength; i++) {
				jQuery('#formula04_dialog_box').append($messages[i]);
    		//Do something
			}
			
			var $formula04_site_lock_redirect_location = data['formula04_site_lock_redirect_location'];	
				console.log(data);		
			//Add Whitelisted and Redirection Location
			var $whitelisted = data['formula04_site_white_listed'];	
			jQuery('#formula04_site_lock_redirect_location_select option:not(.constant)').remove();
			
			if($whitelisted){
				//console.log($whitelisted);
				var $whitelistedLength = $whitelisted.length;
				
				//alert($whitelistedLength );
				for (var title in $whitelisted) {
					
					//alert($formula04_site_lock_redirect_location)
					
				if($formula04_site_lock_redirect_location == $whitelisted[title]){
					var $selected  = 'selected';	
				}else{
					var $selected  = '';	
				}
					
					var $one_option = '<option value="'+$whitelisted[title]+ '"'+$selected+'>'+title+'</option>';
					jQuery('#formula04_site_lock_redirect_location_select').prepend($one_option);
				//Do something
				}//for (var i = 0; i < $whitelistedLength; i++) {
				
				
			}else{
				
							
				
			}
			
			
			wp.heartbeat.interval( 'slow' );	
 			
            // Return font-weight to normal after 2 seconds
            setTimeout(function(){
               // $('.edd_dashboard_widget .b.b-sales').css( 'font-weight', 'normal' );;
            }, 2000);
 
        });
    }(jQuery));
    </script>
<?php
}
// Modify the data that goes back with the heartbeat-tick
public function edd_heartbeat_received( $response, $data ) {
 
 	//Some heart beat form validation. 
    // Make sure we only run our query if the edd_heartbeat key is present
    if( $data['formula04-site-lock-admin-data'] && !empty($data['formula04-site-lock-admin-data']) ) {
 			
		//Declare Variable
		$response['formula04-site-lock-admin-messages'] = false;	
		$formula04_site_lock_redirect_location =  isset($data['formula04_site_lock_redirect_location']) ? $data['formula04_site_lock_redirect_location'] : false;	
		//We have some site lock admin data to look at.
		//Take our serialized form string and convert into an array.
		$admin_data = array();
		parse_str($data['formula04-site-lock-admin-data'], $admin_data);
		//$response['formula04-site-lock-admin-messages'][] = " <br /><hr /><pre style='background-color:black; color:white;'>".htmlspecialchars(print_r($admin_data,true))."</pre>"; 	
		
		
		
		//Has use set a redirect Location.
		$response['formula04_site_lock_redirect_location'] = isset($admin_data['form04_sitelock_settings']['formula04_site_lock_redirect_location']) ? $admin_data['form04_sitelock_settings']['formula04_site_lock_redirect_location'] : false;	
			
		//Has the user set a PW
		$formula04_site_lock_pw = !empty($admin_data['form04_sitelock_settings']['formula04_site_lock_password'])  || !isset($admin_data['form04_sitelock_settings']['formula04_site_lock_password'])? false : true;
		$formula04_site_lock_onoff = !empty($admin_data['form04_sitelock_settings']['formula04_site_lock_onoff'])  || !isset($admin_data['form04_sitelock_settings']['formula04_site_lock_onoff'])? $admin_data['form04_sitelock_settings']['formula04_site_lock_onoff'] : false;
		
		if($formula04_site_lock_onoff == 1):
		$active_text = 'Formula04 SiteLock is Active.  ';
		else:
		$active = '';
		endif;
		
		
		//If no password has been set.
		if($formula04_site_lock_pw):
			$response['formula04-site-lock-admin-messages'][] =  "<div class='error formula04-admin-notice'>".__($active_text.'You have not set a formula04 site lock password.  This means your site <strong>CANNOT</strong> be unlocked from the frontend using the password form.', 'formula04')."</div>";
		endif;	 
        	
		//WhiteListed
		$formula04_site_lock_white_listed =  !isset($admin_data['form04_sitelock_settings']['formula04_site_white_listed']) || empty($admin_data['form04_sitelock_settings']['formula04_site_white_listed']) ? false : $admin_data['form04_sitelock_settings']['formula04_site_white_listed'];		
		
		if(!$formula04_site_lock_white_listed):
			$response['formula04-site-lock-admin-messages'][] =  "<div class='error formula04-admin-notice'>".__('You have not whitelisted any pages.  Pages must be whitelisted before they can be selcted as a Redirection Location. Please Whitelist some at least one page and hit "Save Changes"', 'formula04')."</div>";
		else:
			foreach($formula04_site_lock_white_listed as  $key => $value):
				$response['formula04_site_white_listed'][get_the_title( $value )] = $value;
			endforeach;		
		endif;	
						
			
		if( !$response['formula04-site-lock-admin-messages'] ):
		
			$response['formula04-site-lock-admin-messages'][] = '<h4>Everything is all good</h4>';
			//$response['formula04-site-lock-admin-messages'][] = '<h4>Everything is all good Sike</h4>';
			
		endif;	
		  
    }
    return $response;
}










  static function form04_sitelock_activate(){
	  
	  
  }//form04_sitelock_activate
  
  static function form04_sitelock_deactivate(){
	  
	  
  }//form04_sitelock_deactivate
  
  static function form04_sitelock_uninstall(){
	  delete_option( 'form04_sitelock_settings' );
	  
  }//form04_sitelock_uninstall

  
} // end of class Vegetable
endif;
new  Form04_SiteLock;

register_activation_hook( __FILE__, array( 'Form04_SiteLock', 'form04_sitelock_activate' ) );	
register_deactivation_hook( __FILE__, array( 'Form04_SiteLock', 'form04_sitelock_deactivate' ) );
register_uninstall_hook( __FILE__, array( 'Form04_SiteLock', 'form04_sitelock_uninstall' ) );	