<?php 

$options = get_option( 'formula04_settings' );
$formula04_site_lock_label = isset(  $options['formula04_site_lock_label']  )  ? $options['formula04_site_lock_label'] :  'Formula04 Site Lock Label';


get_header(); ?>
 <div id="formula04-site-lock" class="formula04-site-lock-wrapper"> 
 <div class="formula04-site-lock-content">
 	<h3 class="locked_message"><?php _e($formula04_site_lock_label, 'formula04'); ?></h3>
  	<?php formula04_site_lock_password_form(); ?>  </div>
 </div>
<?php get_footer(); ?>
