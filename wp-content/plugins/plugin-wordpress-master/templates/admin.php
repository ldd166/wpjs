<?php
/**
 * Admin page template
 *
 * @package JsDelivrCdn.
 */

$ajax_nonce = wp_create_nonce( JSDELIVRCDN_PLUGIN_NAME );

$options = get_option( JsDelivrCdn::PLUGIN_SETTINGS );

?>
<div class="wrap">
	<h1 class="jsdelivrcdn-main-settings-header">jsDelivr CDN Settings</h1>

	<form id="jsdelivrcdn_settings_form" method="post" action="options.php" data-ajaxnonce="<?php echo esc_attr( $ajax_nonce ); ?>">
		<?php
			settings_fields( JsDelivrCdn::PLUGIN_SETTINGS );
			do_settings_sections( 'main_settings' );
		?>
		<div class="buttons-wrapper">
			<button id="select_all" class="button button-primary" >Select All</button>
			<button id="unselect_all" class="button button-primary" >Unselect All</button>
			<button id="reload" class="button button-primary" ><span class="dashicons dashicons-update spin hidden"></span> Reload</button>
		</div>
		<table class="widefat" cellspacing="0" id="jsdelivrcdn-settings-table">
			<thead>
			<tr>
				<th class="source-number">№</th>
				<th class="source-action">Active</th>
				<?php if ( $options[ JsDelivrCdn::ADVANCED_MODE ] ) { ?>
				<th class="source-name">Name</th>
				<th class="source-version">Version</th>
				<?php } ?>
				<th class="source-origin">Origin URL</th>
				<th class="source-jsdelivr">jsDelivr URL</th>
				<?php if ( $options[ JsDelivrCdn::ADVANCED_MODE ] ) { ?>
				<th class="source-action">Action</th>
				<?php } ?>
			</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
		<div class="buttons-wrapper">

			<?php if ( $options[ JsDelivrCdn::ADVANCED_MODE ] ) { ?>
				<button id="delete_source_list" class="button button-primary"><span class="dashicons dashicons-update spin hidden"></span> Delete All</button>
				<button id="clear_source_list" class="button button-primary"><span class="dashicons dashicons-update spin hidden"></span> Clear All</button>
			<?php } ?>
			<button id="jsdelivr_analyze" class="button button-primary"><span class="dashicons dashicons-update spin hidden"></span> Analyze</button>
			<button type="submit" name="submit" id="submit" class="button button-primary" style="float:right" ><span class="dashicons dashicons-update spin hidden"></span> Save Active</button>
		</div>
	</form>
</div>
<?php
