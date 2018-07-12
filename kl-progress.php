<?php
/*
Plugin Name: KL Progress
Plugin URI: https://github.com/educate-sysadmin/kl-progress
Description: Wordpress plugin to provide progress checkboxes functionality
Version: 0.1
Author: b.cunningham@ucl.ac.uk
Author URI: https://educate.london
License: GPL2
*/

function kl_progress_install() { 
	// TODO
	/*
	CREATE TABLE `wp_kl_progress` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `user` varchar(128) NOT NULL,
	  `milestone` varchar(256) NOT NULL,
	  `done` tinyint(4) NOT NULL DEFAULT '0',
	  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	  PRIMARY KEY (`id`)
	)
	*/	
}

$kl_progress_init = false;
function kl_progress_init() {
	global $kl_progress_init;
	// ...	
	$kl_progress_init = true;
}

function kl_progress_checkbox( $atts, $content = null ) {	
	global $wpdb;
	global $kl_progress_count;
	
	kl_progress_init();
	
	// add js ajax function
	add_action( 'wp_footer', 'kl_progress_js' ); 
	
	$attributes = shortcode_atts( 
		array( 
			//'username' => null, // rather compute 
			'milestone' => null, 
		), 
		$atts 
	);	
	
	if (!$attributes['milestone']) {
		$attributes['milestone'] = basename($_SERVER['REQUEST_URI']); 
	}
	
	// validate
	if (preg_match("/[A-Za-z\s\-_0-9\/]+/",$attributes['milestone']) === 0) {
		return false;
	}	
	
	$user = wp_get_current_user(); 
	$username = $user?$user->user_login:'visitor'; // || null	
	
	$output = '';
    $output .= '<div class="kl_progress kl_progress_checkbox">';
    $output .= '<form action="" method="post">';
  	$output .= '<input type="hidden" value="'.$attributes['milestone'].'" name="milestone" id="milestone_'.$kl_progress_count.'" />'; 
  	$output .= '<input type="checkbox" value="1" name="progressor" id="progressor_'.$kl_progress_count.'" class="progressor" ref="'.$kl_progress_count.'"';
  	$output .= '/>'; 
    $output .= '</form>';
    $output .= '</div>';
    
    return $output;

}
add_shortcode( 'kl_progress_checkbox', 'kl_progress_checkbox' );

add_action( 'wp_ajax_kl_progress', 'kl_progress' );
function kl_progress() {
	global $wpdb; 
	
	$user = wp_get_current_user(); 
	$username = $user?$user->user_login:'visitor'; // || null
	
	
	echo 'OK';
	wp_die(); // this is required to terminate immediately and return a proper response
}

/* js ajax function included by shortcode */
function kl_progress_js() { ?>
	
	<script type="text/javascript" >
	jQuery('.progressor').click(function() {

		var admin_ajax = '<?php echo admin_url( 'admin-ajax.php' ); ?>'

		var ajax_object = Array();
		ajax_object.ajax_url = admin_ajax;
		
		var data = {};
		data.action = 'kl_progress';

		jQuery.post(ajax_object.ajax_url, data, function(response) {			
			console.log(response);
		});
	});
	</script> <?php
}
