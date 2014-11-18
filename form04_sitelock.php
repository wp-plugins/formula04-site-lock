<?php /*
Plugin Name: Formula04 Site Lock
Plugin Script: form04_sitelock.php
Plugin URI: http://formula04.com/form04_sitelock
Description: Put a sitewide password on your site that will force users to enter said password before viewing content.
Version: 0.1
Author: VerB
Author URI: https://profiles.wordpress.org/verb_form04
Template by: http://www.formula04.com

=== RELEASE NOTES ===
2014-11-12 - v1.0 - first version
*/

// uncomment next line if you need functions in external PHP script;
// include_once(dirname(__FILE__).'/some-library-in-same-folder.php');

// ------------------
// form04_sitelock_showhtml will generate the (HTML) output
function form04_sitelock_showhtml($param1 = 0, $param2 = "test") {
global $wpdb;
// generate $form04_sitelock_html based on ...
// (your code)
// content will be added when function 'form04_sitelock_showhtml()' is called
return $form04_sitelock_html;
}





//----------------------------
// ADD custom button to TINY MCE Editor
//----------------------------

// Hooks your functions into the correct filters
function formula04_site_lock_tinymce_add_mce_button() {
	// check user permissions
	if ( !current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) ) {
		return;
	}
	// check if WYSIWYG is enabled
	if ( 'true' == get_user_option( 'rich_editing' ) ) {
		add_filter( 'mce_external_plugins', 'my_add_tinymce_plugin' );
		add_filter( 'mce_buttons', 'my_register_mce_button' );
	}
}
//add_action('admin_head', 'formula04_site_lock_tinymce_add_mce_button');

// Declare script for new button
function my_add_tinymce_plugin( $plugin_array ) {
	//echo" <br /><hr /><pre style='background-color:black; color:white;'>".htmlspecialchars(print_r($plugin_array,true))."</pre>";
	$plugin_array['my_mce_button'] = plugins_url('/tinymce/plugin.js',__file__);
	return $plugin_array;
}

// Register new button in the editor
function my_register_mce_button( $buttons ) {
	array_push( $buttons, 'my_mce_button' );
	return $buttons;
}


//----------------------------
// END ADD custom button to TINY MCE Editor
//----------------------------


//----------------------------
//Add Formula04_site_lock Shortcode
//----------------------------

function formula04_site_lock_password_form_shortcode( $atts ) {
      $atts = shortcode_atts( array(
 	      'foo' => 'no foo',
 	      'baz' => 'default baz'
      ), $atts, 'formula04' );

      return formula04_site_lock_password_form();
}
add_shortcode( 'f04sitelockform', 'formula04_site_lock_password_form_shortcode' );





//----------------------------
//END Formula04_site_lock Add Shortcode
//----------------------------




//Intial Function that loads all site-locks crap.
add_action( 'init', 'formula04_site_lock' );
function formula04_site_lock() {
	
   $options = get_option( 'formula04_settings' );
   //echo" <br /><hr /><pre style='background-color:black; color:white;'>".htmlspecialchars(print_r($options,true))."</pre>";
   
   $site_lock_activated = isset($options['formula04_site_lock_onoff']) && $options['formula04_site_lock_onoff'] === '1' ? 'on' : 'off';
   $password = isset($options['formula04_site_lock_password']) ? $options['formula04_site_lock_password'] : false;
    
   //If no options have been set then this plugin should not be activated anyways.		
   if(  !$options  ||  $site_lock_activated != 'on' ): 	
   else:
   
   //If site lock is activated then add button to editor area.	
    add_action('admin_head', 'formula04_site_lock_tinymce_add_mce_button');
   
   //Are we logged in to Formula04 Site Lock	 
   $logged_in = formula04_site_lock_logged_in_check();
    
   //We are not logged in.
 	if(  !$logged_in  ):
 	 //Do we have a valid page to redirect to?
	 $redirect_location = formula04_site_lock_get_redirect_location();
   	 
	 //If there is no set redirect location.
	 if(  !$redirect_location  || $redirect_location  == get_home_url()  ):
	 	// echo 'NO Valid Redirect Set';
	 	 //Redirect to Homepage and Show Login Template
		 add_filter( 'template_include', 'formula04_site_lock_template_redirect' );
		 //return get_home_url();		 
	 else:
	 endif; //if(!$redirect_location):	 
   
   else:   
   endif;//if(!$logged_in):
      
   //Password Attempt
   $pass_try = isset($_POST['formula04-site-lock-pass-try'])  && !empty($_POST['formula04-site-lock-pass-try']) ? $_POST['formula04-site-lock-pass-try'] : false;   
   
   //Do we already have a password cookie set
   if(isset( $_COOKIE['formula04-site-lock'])):
   	//We Do Lets See It it's a winner.
   	if( md5($_COOKIE['formula04-site-lock']) ==  md5($password) ):
		//This browser is already logged in
		return;
	endif;
	else:
	//We don't have this cookie already set, so are submitting a password?
	if(!$pass_try):
		//If not we need to set our cookie for the first timmmmeeeeeeeeeee
		setcookie( 'formula04-site-lock', 'rockoutwithyourcockout', time() + 3600, '/', '' );
	else:
	
	 	if( md5($pass_try) ==  md5($password) ):
			//This User Just Entered The Correct Password
			setcookie( 'formula04-site-lock', md5($password), time() + 3600, '/', '' );
			return;
	   else:	   
	 	   setcookie( 'formula04-site-lock', 'rockoutwithyourcockout', time() + 3600, '/', '' );
	   endif; //if( md5($pass_try) ==  md5($password) ): 
	   	
	endif;
	
	  
   endif;//if(isset( $_COOKIE['formula04-site-lock'])):
   
   
   //We have a password try to
   if($pass_try): 
	   if( md5($pass_try) ==  md5($password) ):
			//This User Just Entered The Correct Password
			setcookie( 'formula04-site-lock', md5($password), time() + 3600, '/', '' );
			return;
	   else:  
	   endif;  
   endif;//if($pass_try): 
   
     
   
   
   //If we make it to this point we are still locked out
   //Add action to redirect.
    add_action( 'template_redirect', 'formula04_site_lock_redirect' );

	endif;// if(  !$options  ||  $site_lock_activated != 'on' ):
}//formula04_site_lock



function formula04_site_lock_get_plugin_directory(){
		$dir = plugin_dir_path( __FILE__ );
		return $dir;
	
}



/**
 * Register formula04_site_lock style sheet.
 */
function register_plugin_styles() {
	wp_register_style( 'formula04_site_lock_css', plugins_url( 'form04_sitelock/formula04_site_lock.css' ) );
	wp_enqueue_style( 'formula04_site_lock_css' );
}
	


function formula04_site_lock_template_redirect( $original_template ) {
		
	$options = get_option( 'formula04_settings' );
	
	if(  isset($options['formula04_site_lock_redirect_location'])  &&  $options['formula04_site_lock_redirect_location'] ==  'use_plugin_template' ):
		$dir = plugin_dir_path( __FILE__ );
		$template = 'formula04_site_lock_template.php';
		//echo $dir . 'templates/'.$template;
		add_action( 'wp_enqueue_scripts', 'register_plugin_styles' );
		
		return $dir . 'templates/'.$template;
	
	else:
	
	
	
	
	endif;
	
	
	return $original_template;
}








function formula04_site_lock_logged_in_check() {
	//Get Our Options
	 $options = get_option( 'formula04_settings' );
     $password = isset($options['formula04_site_lock_password']) ? $options['formula04_site_lock_password'] : false;
	 
	 //Do we already have a password cookie set
  	 if(  isset( $_COOKIE['formula04-site-lock'])  ):
	 
	 
   		//We Do Lets See It it's a winner.
   		 if( ($_COOKIE['formula04-site-lock']) ==  md5($password) ):
			//This browser is already logged in
			return true;
		endif;  
    endif;//if(isset( $_COOKIE['formula04-site-lock'])):
	
	return false;
}

 
function formula04_site_lock_redirect( $original_template ) {
	$logged_in = formula04_site_lock_logged_in_check();
	global $post;
	if(  $logged_in  ):
		//Do Nothing We Are All Good
	else:
	   //We are not logged in.
	   //Are we on a whitelisted page?
	  $whitelisted = formula04_site_lock_get_whitelisted();
	  
	  $redirect_location = formula04_site_lock_get_redirect_location();
   	 
	
	 //If there is no set redirect location.
	 //if(!$redirect_location):
	 //endif;
	  
	  
	  //$id = $post->ID  && is_single($post) ? $post->ID : false;
	  $id = $post->ID;
	 

	 
	  
	  //ITs whitelisted do nothing
	  if(  !$redirect_location  &&  is_home()):
	  //Homepage and there is no place to redirect too.
	  //echo "Homepage and there is no place to redirect too.";
	  
	   //Are we on a whitelisted page || Or is this the home page AND has no redirect page been set 
	  elseif($whitelisted && in_array($id, $whitelisted)  ):
	  
	  
	  elseif( preg_replace("(^https?://)", "", trailingslashit($redirect_location) ) ==   preg_replace("(^https?://)", "", trailingslashit( $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] ) )   ):
	  	add_filter( 'template_include', 'formula04_site_lock_template_redirect' );	  
	  		//We Are on a whitelisted Page/The Home Page And No redirect page has been set, so Do nothing
			//echo 'Whitelisted';
	  else:	  
			//We Need To Redirect to our page.
			//echo 'Restricted';
			
	
			
			wp_redirect( formula04_site_lock_get_redirect_location()/*, $status*/ );
			exit;
			
	  endif;
	  
	  //echo" <br /><hr /><pre style='background-color:black; color:white;'>".htmlspecialchars(print_r($whitelisted,true))."</pre>";
	  //echo" <br /><hr /><pre style='background-color:black; color:white;'>".htmlspecialchars(print_r($post,true))."</pre>";
	
	
	endif;

}//formula04_site_lock_redirect


function formula04_site_lock_get_whitelisted(){
	
	//Get Our Options
	$options = get_option( 'formula04_settings' );
   //Get An Array of all our Whitelisted ID's otherwise we are false
    $whitelisted = isset($options['formula04_site_white_listed']) ? $options['formula04_site_white_listed'] : false;
		
	return $whitelisted;


}//formula04_site_lock_get_whitelisted




function formula04_site_lock_get_redirect_location(){
	
	//Get Our Options
	$options = get_option( 'formula04_settings' );
	$redirect_page = isset($options['formula04_site_lock_redirect_location']) ? $options['formula04_site_lock_redirect_location'] : false;
		
	//	echo $redirect_page;
		
	//If the $redirect_page is numeric it is a post ID
	if(is_numeric($redirect_page)):
		return(get_permalink($redirect_page));
	else:
		return get_home_url();				
	endif;
	
	//return home_url(add_query_arg(array(),$wp->request));

}//formula04_site_lock_get_redirect_location



function formula04_site_lock_password_form(){?>	
<form method="post">
<span class="formula04_d_block"><?php _e( 'Enter Password', 'formula04' ); ?></span>
  <input type="password" name="formula04-site-lock-pass-try" class="formula04_site_lock_password formula04_d_block" /> 
 <?php  wp_nonce_field( 'formula04-site-lock-password-attempt' );  ?>
  <input type="submit" value="Submit" class="formula04_site_lock_submit formula04_d_block" />
</form>
<?php }//formula04_site_lock_password_form


//----------------------------
//BACKEND STUFF
//----------------------------
// Hook for adding admin menus
add_action('admin_menu', 'formula_04_settings_menu');
// action function for above hook
function formula_04_settings_menu() {
    // Add a new submenu under Settings:
    add_options_page(__('Formula 04 Site Lock','formula04'), __('F04 Site Lock','formula04'), 'manage_options', 'formula04-site-lock', 'formula_04_settings_page');
}
// mt_settings_page() displays the page content for the Test settings submenu
function formula_04_settings_page() {
    echo "<h2>" . __( 'Formula 04 Site Lock Settings', 'formula04' ) . "</h2>";
	?>
	
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





// Load the heartbeat JS
function edd_heartbeat_enqueue( $hook_suffix ) {
    // Make sure the JS part of the Heartbeat API is loaded.
    wp_enqueue_script( 'heartbeat' );
    add_action( 'admin_print_footer_scripts', 'edd_heartbeat_footer_js', 20 );
}
add_action( 'admin_enqueue_scripts', 'edd_heartbeat_enqueue' );


function wptuts_heartbeat_settings( $settings ) {
    $settings['autostart'] = false;
    return $settings;
}
add_filter( 'heartbeat_settings', 'wptuts_heartbeat_settings' );




// Inject our JS into the admin footer
function edd_heartbeat_footer_js() {
    global $pagenow;
 
    // Only proceed if on site lock admin page
	if( 'options-general.php' != $pagenow   ||  $_GET['page'] != 'formula04-site-lock'  ):
        return;
	endif;
?>
    <script>
    (function($){
		//For Testing
		wp.heartbeat.interval( 'fast' );
				
		//Stop heartbeat.
		/* $(window)
		 .off('blur.wp-heartbeat-focus')
		 .off('focus.wp-heartbeat-focus')
		 .trigger('unload.wp-heartbeat');
		*/
		
		//wp.heartbeat.interval( 'fast' );
		
		
		
	    // Hook into the heartbeat-send
        $(document).on('heartbeat-send', function(e, data) {
			     var $form_data = data['formula04-site-lock-admin-data'] = $("form.formula04-site-lock-admin-options-form").serialize();
				//console.log($form_data); 
       	});
 
		
		
		$("form.formula04-site-lock-admin-options-form :input").change(function() {
			var $form_data = $("form.formula04-site-lock-admin-options-form").serialize();
			
			wp.heartbeat.interval( 'fast' );	
  			//wp.heartbeat.scheduleNextTick()
			//wp.heartbeat.start();
			//wp.heartbeat.interval( 'fast' );
			//$(document).trigger( 'heartbeat-send', [$form_data] );
			//$(window).trigger('heartbeat-connection-restored');
		});
		 		
 
 
 
        // Listen for the custom event "heartbeat-tick" on $(document).
        $(document).on( 'heartbeat-tick', function(e, data) {
 		
 		console.log('heartbeat-tick');
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
 
            // Log the response for easy proof it works
           // console.log( data['edd-payment-count'] );
 
            // Update sale count and bold it to provide a highlight
            //$('.edd_dashboard_widget .b.b-sales').text( data['edd-payment-count'] ).css( 'font-weight', 'bold' );
 
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
function edd_heartbeat_received( $response, $data ) {
 
 	
 
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
		$response['formula04_site_lock_redirect_location'] = isset($admin_data['formula04_settings']['formula04_site_lock_redirect_location']) ? $admin_data['formula04_settings']['formula04_site_lock_redirect_location'] : false;	
			
		//Has the user set a PW
		$formula04_site_lock_pw = !empty($admin_data['formula04_settings']['formula04_site_lock_password'])  || !isset($admin_data['formula04_settings']['formula04_site_lock_password'])? false : true;
		
		//If no password has been set.
		if($formula04_site_lock_pw):
			$response['formula04-site-lock-admin-messages'][] =  "<div class='error formula04-admin-notice'>".__('You have not set a formula04 site lock password.  This means your site <strong>CANNOT</strong> be unlocked from the frontend using the password form.', 'formula04')."</div>";
		endif;	 
        	
		//WhiteListed
		$formula04_site_lock_white_listed =  !isset($admin_data['formula04_settings']['formula04_site_white_listed']) || empty($admin_data['formula04_settings']['formula04_site_white_listed']) ? false : $admin_data['formula04_settings']['formula04_site_white_listed'];		
		
		if(!$formula04_site_lock_white_listed):
			$response['formula04-site-lock-admin-messages'][] =  "<div class='error formula04-admin-notice'>".__('You have not whitelisted any pages.  Pages must be whitelisted before they can be selcted as a Redirection Location', 'formula04')."</div>";
		else:
			foreach($formula04_site_lock_white_listed as  $key => $value):
				$response['formula04_site_white_listed'][get_the_title( $value )] = $value;
			endforeach;		
		endif;	
						
			
		if( !$response['formula04-site-lock-admin-messages'] ):
		
			$response['formula04-site-lock-admin-messages'][] = '<h4>Everything is all good</h4>';
			$response['formula04-site-lock-admin-messages'][] = '<h4>Everything is all good Sike</h4>';
			
		endif;	
		  
    }
    return $response;
}
add_filter( 'heartbeat_received', 'edd_heartbeat_received', 10, 2 );

//ADD SETTINGS PAGE(S)

//add_action( 'admin_menu', 'formula04_add_admin_menu' );
add_action( 'admin_init', 'formula04_settings_init' );
function formula04_settings_init(  ) { 

	register_setting( 'F04SiteLockSet', 'formula04_settings' );

	add_settings_section(
		'formula04_SiteLockSet_section', 
		__( 'Use this section to customize Formula 04 Site Lock ', 'formula04' ), 
		'formula04_settings_section_callback', 
		'F04SiteLockSet'
	);
	
	add_settings_field( 
		'formula04_site_lock_password', 
		__( 'Formula04 Site Lock Password', 'formula04' ), 
		'formula04_site_lock_password_render', 
		'F04SiteLockSet', 
		'formula04_SiteLockSet_section' 
	);	
	
	

	add_settings_field( 
		'formula04_site_lock_onoff', 
		__( 'Activate Formula04 Site Lock', 'formula04' ), 
		'formula04_site_lock_onoff_render', 
		'F04SiteLockSet', 
		'formula04_SiteLockSet_section' 
	);
	
	
	add_settings_field( 
		'formula04_site_white_listed', 
		__( 'White Listed Pages', 'formula04' ), 
		'formula04_site_lock_white_listed_render', 
		'F04SiteLockSet', 
		'formula04_SiteLockSet_section' 
	);
	
	
	add_settings_field( 
		'formula04_site_lock_label', 
		__( 'Formula04 Site Lock Label', 'formula04' ), 
		'formula04_site_lock_label_render', 
		'F04SiteLockSet', 
		'formula04_SiteLockSet_section' 
	);	
	
	
	
	
	add_settings_field( 
		'formula04_site_lock_redirect_location', 
		__( 'Formula04 Site Lock Redirection Location', 'formula04' ), 
		'formula04_site_lock_redirect_location_render', 
		'F04SiteLockSet', 
		'formula04_SiteLockSet_section' 
	);		

	
	
/*	add_settings_field( 
		'formula04_quickwindow_template', 
		__( 'Pop Up Window Content Setting', 'formula04' ), 
		'formula04_site_lock_content_render', 
		'F04SiteLockSet', 
		'formula04_SiteLockSet_section' 
	);
*/
	

}

function formula04_site_lock_onoff_render(  ) { 
	$options = get_option( 'formula04_settings' );?>
	<input type='checkbox' name='formula04_settings[formula04_site_lock_onoff]' <?php echo $options  && isset($options['formula04_site_lock_onoff']) && $options['formula04_site_lock_onoff'] ? 'checked' : '';  /* checked( $options['formula04_use_f04_css'], 1 );*/ ?> value='1'>
	<span class=""><?php _e('Activate', 'formula04'); ?></span>    
	<?php
}




function formula04_site_lock_get_all_page_options(   ){
	
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
	
	
}

function formula04_site_lock_white_listed_render(  ) { 
		$options = get_option( 'formula04_settings' );
		//echo" <br /><hr /><pre style='background-color:black; color:white;'>".htmlspecialchars(print_r($options,true))."</pre>";
		$whitelisted_pages = isset(  $options['formula04_site_white_listed']  ) ?  $options['formula04_site_white_listed'] : false;
		//echo" <br /><hr /><pre style='background-color:black; color:white;'>".htmlspecialchars(print_r($whitelisted_pages,true))."</pre>";
		$master_array = formula04_site_lock_get_all_page_options(   );
		
		foreach($master_array as  $post_type => $posts):?>
			<h3 style=" text-transform:capitalize; border-bottom:1px dotted;"><?php echo $post_type; ?></h3>
            <?php //Cycle through all posts of this type
			foreach($posts as  $key => $post_value):?>
                <span class="one_whitelist_option" style="padding-right:10px; font-size:.8em;">
                    <input data-the_title="<?php echo get_the_title( $post_value->ID ); ?>" type="checkbox" name="formula04_settings[formula04_site_white_listed][<?php echo $post_value->ID ?>]"  value="<?php echo $post_value->ID ?>"  <?php echo $whitelisted_pages && in_array($post_value->ID, $whitelisted_pages) ?  'checked="checked"' : ''; ?>><?php echo $post_value->post_title ?> 
                </span>
	  		<?php 
			endforeach;		
		endforeach;//foreach($master_array as  $post_type => $posts):
		//echo" <br /><hr /><pre style='background-color:black; color:white;'>".htmlspecialchars(print_r(,true))."</pre>";
}//formula04_site_lock_white_listed_render

/*function formula04_site_lock_content_render(  ) { 
	$options = get_option( 'formula04_settings' );
	$f04_quick_window_template = isset(  $options['f04_quick_window_template']  );?>
    
    <select name='formula04_settings[formula04_quickwindow_template]'>
        <option value="formula04_quickwindow_template"  <?php echo isset($options['formula04_quickwindow_template']) && $options['formula04_quickwindow_template'] ==  'formula04_quickwindow_template'? 'selected' : '';?>  >FORMULA04 Default/Custom Template</option>
        <option value="woocommerce_single"  <?php echo isset($options['formula04_quickwindow_template']) && $options['formula04_quickwindow_template'] ==  'woocommerce_single'? 'selected' : '';?>>Product Page Template from WooCommerce</option>
    </select>
    <span class=""><?php _e('Select the quick window content template', 'formula04'); ?></span>
<?php
}*/

function formula04_settings_section_callback(  ) { ?>
	<hr />
	<?php 
	//echo __( 'This section description', 'formula04' );
}

function formula04_site_lock_label_render(  ) { 
	$options = get_option( 'formula04_settings' );
	$formula04_site_lock_label = isset(  $options['formula04_site_lock_label']  )  ? $options['formula04_site_lock_label'] :  'Formula04 Site Lock Label';?>
    <input type="text" value="<?php echo $formula04_site_lock_label; ?>" name="formula04_settings[formula04_site_lock_label]" />
 <?php
 }//formula04_site_lock_label_render



function formula04_site_lock_password_render(  ) { 
	$options = get_option( 'formula04_settings' );
	$formula04_site_lock_password = isset(  $options['formula04_site_lock_password']  )  ? $options['formula04_site_lock_password'] :  '';?>
    <input type="text" value="<?php echo $formula04_site_lock_password; ?>" name="formula04_settings[formula04_site_lock_password]" />
 <?php
 }//formula04_site_lock_password_render
 
function formula04_site_lock_redirect_location_render(  ) { 
	$options = get_option( 'formula04_settings' );
	$whitelisted = formula04_site_lock_get_whitelisted();
	?>
    <select name="formula04_settings[formula04_site_lock_redirect_location]" id="formula04_site_lock_redirect_location_select">
    	<option class="constant" value="use_plugin_template" <?php echo $options  && isset($options['formula04_site_lock_redirect_location']) && $options['formula04_site_lock_redirect_location'] == 'use_plugin_template'  ? 'selected': '';  /* checked( $options['formula04_use_f04_css'], 1 );*/ ?>>Use Plugin/Custom Template</option>
    <?php 
		if($whitelisted  && !empty($whitelisted)):
		 foreach($whitelisted as  $post_id => $post_id_as_well):?>
		<option value="<?php echo $post_id ?>" <?php echo $options  && isset($options['formula04_site_lock_redirect_location']) && $options['formula04_site_lock_redirect_location'] == $post_id  ? 'selected': '';  /* checked( $options['formula04_use_f04_css'], 1 );*/ ?>><?php echo get_the_title($post_id) ?></option>
	<?php endforeach;
		endif;
	?>
    </select>
<?php
}//END SETTINGS PAGE

//----------------------------
// Uninstalling 
//----------------------------

register_uninstall_hook(__FILE__, 'formula04_site_lock_uninstall');
function formula04_site_lock_uninstall(){
	delete_option( 'formula04_settings' );
}