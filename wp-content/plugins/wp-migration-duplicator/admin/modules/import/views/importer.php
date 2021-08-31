<?php
if (!defined('ABSPATH')) {
	exit;
}
?>
<style type="text/css">
	.wt_mgdp_import_log_main {
		display: none;
		font-weight: bold;
		padding-bottom: 5px;
	}

	.wt_mgdp_import_loglist_main {
		display: none;
		float: left;
		width: 100%;
		height: 200px;
		overflow: auto;
		padding: 10px 0px;
		margin-bottom: 20px;
		background: #fdfdfd;
		box-shadow: inset 0 0 3px #ccc;
	}

	.wt_mgdp_import_loglist_inner {
		float: left;
		width: 98%;
		height: auto;
		overflow: auto;
		margin: 0px 1%;
		font-style: italic;
	}
	.wt_mgdp_import_form label {
		font-weight: bold;
	}
        .wf_import_loader {
  display: inline-block;
  width: 20px;
  height: 20px;
  border: 3px solid rgba(69,89,89,.3);
  border-radius: 50%;
  border-top-color: #fff;
  animation: spin 1s ease-in-out infinite;
  -webkit-animation: spin 1s ease-in-out infinite;
  margin-top: -2%;
  margin-left: 8px;
}

@keyframes spin {
  to { -webkit-transform: rotate(360deg); }
}
@-webkit-keyframes spin {
  to { -webkit-transform: rotate(360deg); }
}
</style>
<div style="padding-top:15px; padding-bottom:35px;">
<div style="width:100%">
	 <div style="display:inline-flex"><h3 style="margin-top:0px;"><?php _e('Import: Files and DB', 'wp-migration-duplicator'); ?></h3><div id="loading" class="wf_import_loader" style="display:none;" ></div></div>  <input style="text-align:center; float : right" type="button" name="feedback-btn"  class="wt-button-red wt_sidebar_feedback" value="<?php _e('Report issue', 'wp-migration-duplicator') ?>"></div>
          <?php include WT_MGDP_PLUGIN_PATH . '/admin/partials/wp-migration-duplicator-report-issue.php'; ?>
         <p><?php _e('Import data via a zip file(contains files and DB from the server that is to be migrated) from your local/FTP/Gdrive/Amazon S3 locations.');?></p>
	<p><?php _e(sprintf('%sNote%s :- The current user will be logged out of the site after import if the same credentials does not exist in the imported database. In this case, use your login credentials from the imported site to log in successfully.','<b>','</b>')); ?></p>

        <table class="wf-form-table wt_mgdp_import_options" style="max-width:650px;">
		<tr class="wt_mgdp_import_er" style="display:none;">
			<td colspan="3" style="color:red;"></td>
		</tr>
		<tr>
			<th><?php _e('Import from', 'wp-migration-duplicator') ?><span class="wt-mgdp-tootip" data-wt-mgdp-tooltip="<?php _e('Import data via a zip file(containing data from the server to be migrated).', 'wp-migration-duplicator'); ?>"><span class="wt-mgdp-tootip-icon"></span></span></th>
			<td>
				<?php
				$import_options = Wp_Migration_Duplicator_Import::get_possible_import_methods();
				?>
				<div class="wt-migrator-select-container">
					<?php
					if (is_array($import_options)) {
						echo '<select name="wt_mgdb_import_option" data-option-type="import" >';
						foreach ($import_options as $value => $import_option) {
							echo '<option value="' . $value . '">' . $import_option . '</option>';
						}

						echo '</select>';
					}

					?>
					<span class="spinner"></span>
				</div> 

			</td>
		</tr>
	</table>
	<?php do_action('mgdp_after_import_form'); ?>
	<div class="child-form-item child-wt_mgdb_import_option wt_mgdb_import_option_local" style="display:block">
		<table class="wf-form-table wt_mgdp_import_form" style="max-width:650px;margin-bottom:20px; margin-top:10px;">
			<tr>
				<th></th>
				<td style="padding:0px 10px;">
					<input type="hidden" name="attachment_url" id="attachment_url">
                                            <input style="text-align:center;" type="button" name="upload-btn" id="upload-btn" class="button button-primary" value="<?php _e('Upload backup file', 'wp-migration-duplicator') ?>"> <p style="float: right;margin-right: 40px;"><?php printf( __( 'Upload file size: <strong>%s</strong>.', 'wp-migration-duplicator' ), esc_html( size_format(wp_max_upload_size(),0) ) ); ?></p>
					<span class="wt_mgdp_import_attachment_url"></span>

				</td>			
			</tr>
		</table>
            <div style=" width:98%;">
                <a href="https://www.webtoffee.com/increase-maximum-upload-file-size-in-wordpress-migrator/" target="_blank"><?php _e( 'How-to: Increase maximum upload file size', 'wp-migration-duplicator' ); ?></a><br/><br/>
            </div>

	</div>
	<div class="wt-migrator-import-section">
		<div class="wt_mgdp_import_log_main"></div>
		<div class="wt_mgdp_import_loglist_main">
			<div class="wt_mgdp_import_loglist_inner">

			</div>
		</div>
	</div>

	<div style="clear: both;"></div>
	
</div>

<div class="wf-plugin-toolbar bottom">
	<div class="left">
	</div>
	<div class="right">
               <input type="hidden" id="extension_zip_loaded_imp" name="extension_zip_loaded_imp" value=<?php $extension_zip_loaded = extension_loaded('zip')?'enabled':'disabled'; echo $extension_zip_loaded;?>>
                <input type="hidden" id="extension_zlib_loaded_imp" name="extension_zlib_loaded_imp" value=<?php $extension_zlib_loaded = extension_loaded('zlib')?'enabled':'disabled'; echo $extension_zlib_loaded;?>>
		<button name="wt_mgdp_import_btn" class="button button-primary" style="float:right;"><?php _e('Import', 'wf-woocommerce-packing-list'); ?></button>
		<button name="" class="button button-primary wt_mgdp__start_new_import" style="float:right; display:none;"><?php _e('Start new import', 'wf-woocommerce-packing-list'); ?></button>
		<span class="spinner" style="margin-top:11px;"></span>
	</div>
</div>