<?php
		GLOBAL $wpdb, $SULlyVersion, $SULlyUtils;
	
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-tabs');
		
		wp_register_style("jquery-ui-css", plugin_dir_url(__FILE__) . "../css/jquery-ui-1.10.4.custom.css");
		wp_enqueue_style("jquery-ui-css");
		wp_register_style("jquery-ui-tabs-css", plugin_dir_url(__FILE__) . "../css/jquery-ui-tabs.css");
		wp_enqueue_style("jquery-ui-tabs-css");
	
		$messages = array();
		
		// set the default number of days old to delete items for.
		$deletedays = 90;
		
		// If we're deleting old entries, do so now...
		if( isset( $_GET['SULlyDeleteAction'] ) )
			{
			if( !isset( $_GET['SULlyActionsDeleteOld'] ) ) { $_GET['SULlyOptionsDeleteOld'] = $deletedays; }

			$deletedays = $_GET['SULlyActionsDeleteOld'];

			$TableName = $wpdb->prefix . "SULly";

			// This is a bit of a hack, $wpdb doesn't return the right (aka any) count for a delete statement
			// so we have to count the rows in the table before and after we execute the delete to actually
			// the actual number of rows we deleted.
			$CountRows = $wpdb->get_results( 'SELECT COUNT(*) FROM ' . $TableName, ARRAY_N );
			$StartingRows = $CountRows[0][0];

			$SQLStatement = "DELETE FROM $TableName WHERE time < DATE_SUB(NOW(), INTERVAL $deletedays DAY);";

			$results = $wpdb->get_results( $SQLStatement );
			
			$CountRows = $wpdb->get_results( 'SELECT COUNT(*) FROM ' . $TableName, ARRAY_N );
			
			$NumRows = $StartingRows - $CountRows[0][0];
			
			$messages[] = "<div class='updated settings-error'><p><strong>$NumRows records over " . $deletedays . " days old have been deleted.</strong></p></div>\n";
			}
		
		// If we're re-processing the database entries, do so now...
		if( isset( $_GET['SullyReRunUnknownAction'] ) )
			{
			$TableName = $wpdb->prefix . "SULly";
			
			$wpdb->get_results( "UPDATE $TableName SET `type`='' WHERE `type`='F' OR `type`='U'" );

			SULlyStoreResult( null, null, null, true );

			$messages[] = "<div class='updated settings-error'><p><strong>Re-processed unknown and failed database entries!</strong></p></div>\n";
			}
			
		// If the user wants to recreate the SULly tables and options, do so.
		if( isset( $_GET['SULlyRecreateAction'] ) )
			{
			SULlySetup();

			delete_option( "SULly_Removed" );

			$messages[] = "<div class='updated settings-error'><p><strong>Table and settings recreated!</strong></p></div>\n";
			}
			
		// If the user wants to delete the SULly tables and options, do so.
		if( isset( $_GET['SULlyRemoveAction'] ) )
			{
			$TableName = $wpdb->prefix . "SULly";

			// This is a bit of a hack, $wpdb doesn't return the right (aka any) count for a delete statement
			// so we have to count the rows in the table before and after we execute the delete to actually
			// the actual number of rows we deleted.
			$wpdb->get_results( 'DROP TABLE ' . $TableName );
			
			delete_option( 'EntriesToDisplay' );
			delete_option( 'PageEntriesToDisplay' );
			delete_option( 'SULly_System_Settings' );

			// We add this option here so SULly won't do anything but the admin menu will still be available
			// in case they want recreate it later.  This option is removed during the uninstall process.
			update_option( 'SULly_Removed', "true" );
			
			$messages[] = "<div class='updated settings-error'><p><strong>Table and settings removed!</strong></p></div>\n";
			}
			
		// Save the options if the user click save.
		if( array_key_exists( 'SULlyOptions', $_POST ) AND isset( $_POST['SULlyUpdateOptions'] ) ) 
			{
			if( !isset( $_POST['SULlyOptions']['WidgetDisplayLines'] ) ) { $_POST['SULlyOptions']['WidgetDisplayLines'] = 10; }
			if( !isset( $_POST['SULlyOptions']['PageDisplayLines'] ) ) { $_POST['SULlyOptions']['PageDisplayLines'] = 10; }
			if( !isset( $_POST['SULlyOptions']['SendEmailNotifications'] ) ) { $_POST['SULlyOptions']['SendEmailNotifications'] = ''; }
			if( !isset( $_POST['SULlyOptions']['DisableWPEmailNotifications'] ) ) { $_POST['SULlyOptions']['DisableWPEmailNotifications'] = ''; }
				
			$SULlyUtils->update_option( 'EntriesToDisplay', $_POST['SULlyOptions']['WidgetDisplayLines'] );
			$SULlyUtils->update_option( 'PageEntriesToDisplay', $_POST['SULlyOptions']['PageDisplayLines'] );
			$SULlyUtils->update_option( 'SendEmailNotifications', $_POST['SULlyOptions']['SendEmailNotifications'] );
			$SULlyUtils->update_option( 'DisableWPEmailNotifications', $_POST['SULlyOptions']['DisableWPEmailNotifications'] );
			
			$messages[] = "<div id='setting-error-settings_updated' class='updated settings-error'><p><strong>Settings saved.</strong></p></div>\n";
			}

		// Retrieve the options.
		$SULlyOptions['WidgetDisplayLines'] = $SULlyUtils->get_option( 'EntriesToDisplay' );
		$SULlyOptions['PageDisplayLines'] = $SULlyUtils->get_option( 'PageEntriesToDisplay' );
		$SULlyOptions['SendEmailNotifications'] = (bool)$SULlyUtils->get_option( 'SendEmailNotifications' );
		$SULlyOptions['DisableWPEmailNotifications'] = (bool)$SULlyUtils->get_option( 'DisableWPEmailNotifications' );
		
	?>
<div class="wrap">

<script type="text/javascript">jQuery(document).ready(function() { jQuery("#tabs").tabs(); jQuery("#tabs").tabs("option", "active",0);} );</script>
<h2>System Update Logger Option</h2>

<?php foreach( $messages as $message ) { echo $message; } ?>

	<div id="tabs">
		<ul>
			<li><a href="#fragment-0"><span><?php _e('Options');?></span></a></li>
			<li><a href="#fragment-1"><span><?php _e('Database');?></span></a></li>
			<li><a href="#fragment-2"><span><?php _e('Uninstall');?></span></a></li>
			<li><a href="#fragment-3"><span><?php _e('About');?></span></a></li>
		</ul>

		<div id="fragment-0">
			<form method="post" action="options-general.php?page=sully.php">
<?php
		$options = array();
		$options['SULlyOptions[WidgetDisplayLines]']			= array( 'type' => 'text', 'desc' => __('Number of entries to display in the Dashboard Widget'), 'setting' => $SULlyOptions['WidgetDisplayLines'], 'size' => 3 );
		$options['SULlyOptions[PageDisplayLines]']				= array( 'type' => 'text', 'desc' => __('Number of entries to display in the Dashboard Page'), 'setting' => $SULlyOptions['PageDisplayLines'], 'size' => 3 );
		$options['SULlyOptions[SendEmailNotifications]']		= array( 'type' => 'bool', 'desc' => __('Send Administrator an email when an installation or update happens'), 'setting' => $SULlyOptions['SendEmailNotifications'] );
		$options['SULlyOptions[DisableWPEmailNotifications]']	= array( 'type' => 'bool', 'desc' => __('Disable WordPress email when an update happens'), 'setting' => $SULlyOptions['DisableWPEmailNotifications'] );

		echo $SULlyUtils->generate_options_table( $options ); 
?>				
				<div class="submit"><input type="submit" class="button-primary" name="SULlyUpdateOptions" value="<?php _e('Update Options'); ?>" /></div>
			</form>
		</div>
	
		<div id="fragment-1">

			<div style="font-size: 16px;">**WARNING** No further confirmation will be given after you press the delete button, make sure you REALLY want to delete the old records before continuing.</div>
			<div>&nbsp;</div>
			<div><?php _e('Delete records older than '); ?>:&nbsp;<input name="SULlyActionsDeleteOld" type="text" id="SULlyActionsDeletOld" size="3" maxlength="3" value="<?php echo $deletedays; ?>" /> days <input type="button" id="SullyDeleteAction" class="button-primary" name="SULlyDeleteAction" value="<?php _e('Delete'); ?>" onclick="if( confirm('Ok, last chance, really delete records over ' + document.getElementById('SULlyActionsDeletOld').value + ' days?') ) { window.location = 'options-general.php?page=sully.php&SULlyDeleteAction=TRUE&SULlyActionsDeleteOld=' + document.getElementById('SULlyActionsDeletOld').value; }"/></div>
			<div>&nbsp;<hr />&nbsp;</div>
			<div><?php _e('Re-process entries listed as Unknown or Failed'); ?>:&nbsp;<input type="button" id="SullyReRunUnknownAction" class="button-primary" name="SULlyReRunUnknownAction" value="<?php _e('Reprocess Unknown'); ?>" onclick="window.location = 'options-general.php?page=sully.php&SullyReRunUnknownAction=TRUE';"/></div>
		
		</div>
		
		<div id="fragment-2">

<?php if( get_option( "SULly_Removed" ) != 'true' )
		{ 
?>
			<div style="font-size: 16px;">**WARNING** No further confirmation will be given after you press the delete button, make sure you REALLY want to remove the database table and settings!</div>
			<div>&nbsp;</div>
			<div><?php _e('Remove the database table and all settings:')?>&nbsp;<input type="button" class="button-primary" id="SullyRemoveAction" name="SULlyRemoveAction" value="<?php _e('Remove'); ?>" onclick="if( confirm('Ok, last chance, really remove the database table?') ) { window.location = 'options-general.php?page=sully.php&SULlyRemoveAction=TRUE'}"/></div>
<?php
		}
	else
		{
?>
			<div><?php _e('Recreate database table and settings:')?>&nbsp;<input type="button" id="SullyRecreateAction" name="SULlyRecreateAction" value="<?php _e('Recreate') ?> &raquo;" onclick="window.location = 'options-general.php?page=sully.php&SULlyRecreateAction=TRUE'"/></div>
<?php 
		}
?>
		
		</div>
	
		<div id="fragment-3">
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<td scope="row" align="center"><img src="<?php echo plugins_url('sully/images/logo-250.png'); ?>"></td>
					</tr>

					<tr valign="top">
						<td scope="row" align="center"><h2><?php echo sprintf(__('SULly - System Update Logger V%s'), $SULlyVersion); ?></h2></td>
					</tr>

					<tr valign="top">
						<td scope="row" align="center"><p>by <a href="https://toolstack.com">Greg Ross</a></p></td>
					</tr>

					<tr valign="top">
						<td scope="row" align="center"><hr /></td>
					</tr>

					<tr valign="top">
						<td scope="row" colspan="2"><h2><?php _e('Rate and Review at WordPress.org'); ?></h2></td>
					</tr>
					
					<tr valign="top">
						<td scope="row" colspan="2"><?php _e('Thanks for installing SULly, I encourage you to submit a ');?> <a href="http://wordpress.org/support/view/plugin-reviews/sully" target="_blank"><?php _e('rating and review'); ?></a> <?php _e('over at WordPress.org.  Your feedback is greatly appreciated!');?></td>
					</tr>
					
					<tr valign="top">
						<td scope="row" colspan="2"><h2><?php _e('Support'); ?></h2></td>
					</tr>

					<tr valign="top">
						<td scope="row" colspan="2">
							<p><?php _e("Here are a few things to do submitting a support request:"); ?></p>

							<ul style="list-style-type: disc; list-style-position: inside; padding-left: 25px;">
								<li><?php echo sprintf( __('Have you read the %s?' ), '<a title="' . __('FAQs') . '" href="https://wordpress.org/plugins/sully/faq/" target="_blank">' . __('FAQs'). '</a>');?></li>
								<li><?php echo sprintf( __('Have you search the %s for a similar issue?' ), '<a href="http://wordpress.org/support/plugin/sully" target="_blank">' . __('support forum') . '</a>');?></li>
								<li><?php _e('Have you search the Internet for any error messages you are receiving?' );?></li>
								<li><?php _e('Make sure you have access to your PHP error logs.' );?></li>
							</ul>

							<p><?php _e('And a few things to double-check:' );?></p>

							<ul style="list-style-type: disc; list-style-position: inside; padding-left: 25px;">
								<li><?php _e('Have you double checked the plugin settings?' );?></li>
								<li><?php _e('Are you getting a blank or incomplete page displayed in your browser?  Did you view the source for the page and check for any fatal errors?' );?></li>
								<li><?php _e('Have you checked your PHP and web server error logs?' );?></li>
							</ul>

							<p><?php _e('Still not having any luck?' );?> <?php echo sprintf(__('Then please open a new thread on the %s.' ), '<a href="http://wordpress.org/support/plugin/sully" target="_blank">' . __('WordPress.org support forum') . '</a>');?></p>
						</td>
					</tr>

				</tbody>
			</table>

		</div>
	</div>
</div>
