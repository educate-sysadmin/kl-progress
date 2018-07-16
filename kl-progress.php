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

require_once('kl-progress-options.php');

function kl_progress_install() { 
	// TODO
	/*
	CREATE TABLE `wp_kl_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(128) NOT NULL,
  `roles` varchar(64) NOT NULL,
  `milestone` varchar(256) NOT NULL,
  `request` varchar(256) NOT NULL,
  `category1` varchar(128) NOT NULL,
  `category2` varchar(128) NOT NULL,
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

// shortcode counter to cater for handling ajax for multiple checkboxes
$kl_progress_count = 0;

function kl_progress_checkbox( $atts, $content = null ) {	
	global $wpdb;
	global $kl_progress_count;
	
	kl_progress_init();
	
	// add js ajax function
	add_action( 'wp_footer', 'kl_progress_js' ); 
	
	// add js auto-tick function
	if (get_option('kl_progress_auto_tick')) {
		add_action( 'wp_footer', 'kl_progress_js_auto' ); 
	}
	
	$attributes = shortcode_atts( 
		array( 
			//'username' => null, // rather compute 
			'milestone' => null, 
			'category1' => null,
			'category2' => null,
			'readonly' => null,
			'visible' => "true", // only really useful with readonly option
			'class' => "", // optional class(es) to add
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
	if ($attributes['category1'] !== null && preg_match("/[A-Za-z\s\-_0-9\/]+/",$attributes['category1']) === 0) {
		return false;
	}	
	if ($attributes['category2'] !== null && preg_match("/[A-Za-z\s\-_0-9\/]+/",$attributes['category2']) === 0) {	
		return false;
	}
	
	if ($attributes['class'] !== null && $attributes['class'] !== "" && preg_match("/[A-Za-z\s\-_0-9]+/",$attributes['class']) === 0) {	
		return false;
	}	
	
	$user = wp_get_current_user(); 
	$username = $user?$user->user_login:'visitor'; // || null	
	
	$output = '';
	$class = "kl_progress kl_progress_checkbox";
	if ($attributes['class'] !== null && $attributes['class'] !== "") {
		$class .= ' '.$attributes['class'];
	}
    $output .= '<div class="'.$class.'">';
    $output .= '<form action="" method="post">';
  	$output .= '<input type="hidden" value="'.$attributes['milestone'].'" name="milestone" id="milestone_'.$kl_progress_count.'" />'; 
  	// include request else it defaults to wp ajax page
	$output .= '<input type="hidden" value="'.$_SERVER['REQUEST_URI'].'" name="request" id="request_'.$kl_progress_count.'" />'; 
	$output .= '<input type="hidden" value="'.$attributes['category1'].'" name="category1" id="category1_'.$kl_progress_count.'" />';   	
	$output .= '<input type="hidden" value="'.$attributes['category2'].'" name="category2" id="category2_'.$kl_progress_count.'" />';
  	//$output .= '<input type = "submit" value="submit" name = "submit" />'; // todo
	$class = "kl_progress progressor";
	if ($attributes['class'] !== null && $attributes['class'] !== "") {
		$class .= ' '.$attributes['class'];
	}  	
  	$output .= '<input type="checkbox" value="1" name="progressor" id="progressor_'.$kl_progress_count.'" class="'.$class.'" ref="'.$kl_progress_count.'"';
  	// get state from db
  	$sql = 'SELECT done FROM '.$wpdb->prefix.'kl_progress'.' WHERE user = "'.$username.'" AND milestone = "'.$attributes['milestone'].'" LIMIT 1;';
	$result = $wpdb->get_row( $sql );
	if ($result && $result->done == "1") { $output .= ' checked '; }
	// readonly?
	if (isset($attributes['readonly']) && $attributes['readonly'] !== null) {
		$output .= ' disabled="disabled" readonly="readonly" ';
	}
	// visible?
	if (isset($attributes['visible']) && $attributes['visible'] === "false") {
		$output .= ' style="display: none;" ';		
	}
  	$output .= '/>'; 
    $output .= '</form>';
    $output .= '</div>';
    
    // increment shortcode counter
    $kl_progress_count++;
    
    return $output;

}
add_shortcode( 'kl_progress_checkbox', 'kl_progress_checkbox' );

/* ajax php handler */
add_action( 'wp_ajax_kl_progress', 'kl_progress' );
function kl_progress() {
	global $wpdb; 
	
	$user = wp_get_current_user(); 
	$username = $user?$user->user_login:'visitor'; // || null
	
	// validate
	$ok = true;
	if (!$username) {
		$ok = false;
	}
	if (isset($_POST['progressor'])) {
		if ($_POST['progressor'] !== "0" && $_POST['progressor'] !== "1") {
			$ok = false;
		}		
	}
	if (preg_match("/[A-Za-z\s\-_0-9\/]+/",$_POST['milestone']) === 0) {
		$ok = false;
	}
	if (isset($_POST['request']) && $_POST['request'] != '') {
		if (preg_match("/[a-z\s\-_0-9\/]+/",$_POST['request']) === 0) {
			$ok = false;
		}
	}	
	if (isset($_POST['category1']) && $_POST['category1'] != '') {
		if (preg_match("/[A-Za-z\s\-_0-9\/]+/",$_POST['category1']) === 0) {
			$ok = false;
		}
	}
	if (isset($_POST['category2']) && $_POST['category2'] != '') {
		if (preg_match("/[A-Za-z\s\-_0-9\/]+/",$_POST['category2']) === 0) {
			$ok = false;
		}
	}		
	if (!$ok) {
		echo "ERROR";
		wp_die();
	}	
	// standardise 
	$_POST['progressor'] = isset($_POST['progressor'])?(int)$_POST['progressor']:0;
	$_POST['request'] = isset($_POST['request'])?$_POST['request']:null;
	
	// add roles and categories (if not posted), using kl-access-logs code and settings
	$roles = '';
	if (function_exists('klal_get_user_roles')) {
		if (get_option('klal_add_roles') && get_option('klal_add_roles') != '') {	    
			$roles = implode(",",klal_get_user_roles(get_option('klal_add_roles'), get_current_user_id()));
		}
	}
	$category1 = ''; $category2 = ''; 
	if ((!isset($_POST['category1']) || $_POST['category1'] == '') && function_exists('klal_get_categories') && $_POST['request']) {
		if (get_option('klal_add_category_1') && get_option('klal_add_category_1') != '') {
			$post_id = klal_get_post_id($_POST['request']);
			$category1 = implode(",",klal_get_categories( get_option('klal_add_category_1'), $post_id));
		}  
	} else {
		$category1 = isset($_POST['category1'])?$_POST['category1']:'';
	}
	if ((!isset($_POST['category2']) || $_POST['category2'] == '') && function_exists('klal_get_categories') && $_POST['request']) {
		if (get_option('klal_add_category_2') && get_option('klal_add_category_2') != '') {
			if (!$post_id) { $post_id = klal_get_post_id($_POST['request']); }
			$category2 = implode(",",klal_get_categories( get_option('klal_add_category_2'), $post_id));
		}
	} else {
		$category2 = isset($_POST['category2'])?$_POST['category2']:'';
	}
	
	// update db progress table appropriately:
	// remove any existing record
	$sql = 'DELETE FROM '.$wpdb->prefix.'kl_progress'.' WHERE user = %s AND milestone = %s;';
	$result = $wpdb->query( $wpdb->prepare( 
		$sql, 
		array($username,$_POST['milestone'])
	) );	
	// add record if checkbox checked 
	if ($_POST['progressor'] == 1) {
		$sql = 'INSERT INTO '.$wpdb->prefix.'kl_progress'.' (user, roles, milestone, request, category1, category2, done) VALUES( %s, %s, %s, %s, %s, %s, %d );';
		$result = $wpdb->query( $wpdb->prepare( 
			$sql, 
			array($username, $roles, $_POST['milestone'], $_POST['request'], $category1, $category2, $_POST['progressor'])
		) );
		if (!$result) {
			echo "ERROR";
			wp_die();		
		}
	}
	
	// add hook	
	$args = array(
		'user' => $username,
		'roles' => $roles,
		'milestone' => $_POST['milestone'],
		'request' => $_POST['request'],
		'category1' => $category1, 
		'category1' => $category2,
		'done' => $_POST['progressor']
	);
	do_action( 'kl_progress',  $args );
	
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
		// get values from appropriate checkbox form 
		var ref = this.getAttribute('ref');
		data.milestone = jQuery('#milestone_'+ref).val();
		data.request = jQuery('#request_'+ref).val();
		data.category1 = jQuery('#category1'+ref).val();
		data.category2 = jQuery('#category2'+ref).val();
		data.progressor = jQuery('#progressor_'+ref).is(':checked')?"1":"0";

		jQuery.post(ajax_object.ajax_url, data, function(response) {			
			console.log(response);
		});
	});
	</script> <?php
}

/* js to auto tick checkboxes */
function kl_progress_js_auto() { ?>
	
	<script type="text/javascript" >
		
		jQuery( window ).unload(function() {
			// only activate for pages with single checkbox
			if (jQuery('.progressor').length == 1) {
				// only check unchecked checkboxes
				jQuery('.progressor').not(':checked').trigger("click");
			}
		});
	</script> <?php
}
