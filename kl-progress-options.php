<?php
/*
KL Progress
Author: b.cunningham@ucl.ac.uk
Author URI: https://educate.london
License: GPL2
*/

// create custom plugin settings menu
add_action('admin_menu', 'kl_progress_plugin_create_menu');

function kl_progress_plugin_create_menu() {
    //create options page
    add_options_page('KL Progress', 'KL Progress', 'manage_options', __FILE__, 'kl_progress_plugin_settings_page' , __FILE__ );

    //call register settings function
    add_action( 'admin_init', 'register_kl_progress_plugin_settings' );	
}

function register_kl_progress_plugin_settings() {
    //register our settings
    register_setting( 'kl_progress-plugin-settings-group', 'kl_progress_auto_tick' );	    
}

function kl_progress_plugin_settings_page() {
?>
    <div class="wrap">
    <h1>KL Progress</h1>

    <form method="post" action="options.php">
    <?php settings_fields( 'kl_progress-plugin-settings-group' ); ?>
    <?php do_settings_sections( 'kl_progress-plugin-settings-group' ); ?>
    <table class="form-table">
        
        <tr valign="top">
        <th scope="row">Auto-tick</th>
        <td>
			<input type="checkbox" name="kl_progress_auto_tick" value="true" <?php if ( get_option('kl_progress_auto_tick') ) echo ' checked '; ?> />        	
        	<p><small>Auto-tick checkboxes when user leaves page. Only functions if one checkbox on page (to allow checkboxed contents-type pages). See js in kl-progress.php for manual replication if required.</small></p>
        </td>
        </tr>        
                                    
    </table>
    
    <?php submit_button(); ?>
    </form>

</div>
<?php } 
