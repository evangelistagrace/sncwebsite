<?php
if (!defined('ABSPATH')) {
	exit;
}
?>
<style type="text/css">
.wt_mgdp_string{ color:darkgreen; }
.wt_mgdp_builtin{ color:blue; }
.wt_mgdp_fn{ color:brown; }
.wt_mgdp_arg{ color:orange; }
.wt_mgdp_cmnt{ color:gray; }
.wt_mgdp_code_example{padding:20px; font-size:14px; background:#f6f6f6; box-shadow:inset 1px 1px 1px 0px #ccc; display:none;}
.wt_mgdp_code_example_readmore{ cursor:pointer; }
.wt_mgdp_code_indent{ padding-left:40px; display:inline-block; }
.wf_export_loader {
  display: inline-block;
  width: 20px;
  height: 20px;
  border: 3px solid rgba(69,89,89,.3);
  border-radius: 50%;
  border-top-color: #fff;
  animation: spin 1s ease-in-out infinite;
  -webkit-animation: spin 1s ease-in-out infinite;
  margin-top: 15%;
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
    <div style="display:inline-flex"><h3><?php _e('Export','wp-migration-duplicator');?></h3><div id="loading" class="wf_export_loader" style="display:none;"></div></div><input style="text-align:center; float : right" type="button" name="feedback-btn"  class="wt-button-red wt_sidebar_feedback" value="<?php _e('Report issue', 'wp-migration-duplicator') ?>"></div>
    <?php include WT_MGDP_PLUGIN_PATH . '/admin/partials/wp-migration-duplicator-report-issue.php'; ?>
	<p><?php _e(sprintf('Exports a copy of the wp-contents(folders and files) and the database in a zip format.To export only the database, select all in %s Exclude Folders/Files %s','<b>','</b>'),'wp-migration-duplicator');?>
		<br />
		<?php _e(sprintf('Note: In case of any error during export, try excluding large files(Eg: Backup files from other plugins) and try again. You can do this by using the filter %s.','<i>`wt_mgdp_exclude_files`</i>'),'wp-migration-duplicator'); ?>
		<a class="wt_mgdp_code_example_readmore" data-more-text="<?php _e('Read more');?>" data-less-text="<?php _e('Read less');?>"><?php _e('Read more');?></a>
	</p>
	<p class="wt_mgdp_code_example">
		<span class="wt_mgdp_cmnt"> // to exclude file/folder</span> <br />
		<span class="wt_mgdp_fn">add_filter</span>(<span class="wt_mgdp_string">'wt_mgdp_exclude_files'</span>, <span class="wt_mgdp_string">'wt_mgdp_exclude_files_fn'</span>);<br />
		<span class="wt_mgdp_builtin">function</span> <span class="wt_mgdp_fn">wt_mgdp_exclude_files_fn</span>(<span class="wt_mgdp_arg">$arr</span>)<br />
		{ <br />
		<span class="wt_mgdp_code_indent">
		$arr[]=<span class="wt_mgdp_string">'uploads/backup-guard'</span>; <span class="wt_mgdp_cmnt"> // add folder/file path relative to wp-content folder</span> <br />
		$arr[]=<span class="wt_mgdp_string">'ai1wm-backups'</span>; <br />
		<span class="wt_mgdp_builtin">return</span> $arr; 
		</span><br />
		} <br />
		<br />
		<br />
		<span class="wt_mgdp_cmnt"> // to exclude file types</span> <br />
		<span class="wt_mgdp_fn">add_filter</span>(<span class="wt_mgdp_string">'wt_mgdp_exclude_extensions'</span>, <span class="wt_mgdp_string">'wt_mgdp_exclude_extensions_fn'</span>);<br />
		<span class="wt_mgdp_builtin">function</span> <span class="wt_mgdp_fn">wt_mgdp_exclude_extensions_fn</span>(<span class="wt_mgdp_arg">$arr</span>)<br />
		{ <br />
		<span class="wt_mgdp_code_indent">
		$arr[]=<span class="wt_mgdp_string">'zip'</span>; <br />
		$arr[]=<span class="wt_mgdp_string">'png'</span>; <br />
		<span class="wt_mgdp_builtin">return</span> $arr; 
		</span><br />
		} <br />
	</p>
	
	<div style="width:100%; box-sizing:border-box; padding:3px; margin-bottom:0px;">
		<div class="wf_progress_bar_label"></div>
		
		<div class="wf_export_main" style="display:none;">
			<div class="wf_progress_bar_label"></div>
			<div class="wf_progress_bar">
				<div class="wf_progress_bar_inner">
					0%
				</div>
			</div>
		</div>

		<div class="wf_export_sub" style="display:none;">
			<div class="wf_progress_bar_label"></div>
			<div class="wf_progress_bar">
				<div class="wf_progress_bar_inner">
					0%
				</div>
			</div>
		</div>	
	</div>
	<table class="wf-form-table wt_mgdp_export_find_replace_tb">
		<tr class="">
			<td>
				<p style="margin-top:15px; float:left;">
                                 <?php _e('For a smooth migration, replace the text within the database file with the destination’s text.</br>','wp-migration-duplicator');?>
				 <?php _e(sprintf('For example, if you are migrating data from an existing domain (https://www.myolddomain.com) to a new domain (https://www.mynewdomain.com), use %sFind%s text as https://www.myolddomain.com and %sReplace%s with as https://www.mynewdomain.com.</br></br>','<b>','</b>','<b>','</b>'),'wp-migration-duplicator'); ?>
                                  <?php _e(sprintf('Find and replace %stext%s with %sanother text%s in the database.</br>','<b>&lt;','&gt;</b>','<b>&lt;','&gt;</b>'),'wp-migration-duplicator'); ?>
                                </p>
			</td>
		</tr>
		<tr class="wt_mgdp_export_find_replace_row">
			<td>
				<div style="float:left; width:200px; margin-right:15px;">
					<input type="text" name="find[]" placeholder="<?php _e('Find','wp-migration-duplicator'); ?>">
				</div>
				<div style="float:left; width:200px; margin-right:15px;">
					<input type="text" name="replace[]" placeholder="<?php _e('Replace with','wp-migration-duplicator'); ?>">
				</div>
				<div style="float:left; width:40px;">
					<button class="button wt_mgdp_export_find_replace_btn_add button-secondary" title="<?php _e('Add new row','wp-migration-duplicator'); ?>">
						<span class="dashicons dashicons-plus-alt" style="margin-top:2px;"></span>
					</button>
					<button class="button wt_mgdp_export_find_replace_btn_delete button-secondary" style=" display:none;" title="<?php _e('Delete row','wp-migration-duplicator'); ?>">
						<span class="dashicons dashicons-dismiss" style="margin-top:2px;"></span>
					</button>
				</div>
			</td>
		</tr>
	</table>
</div>
<div class="wt_mgdp_loader_info_box"></div>
<div class="wt-migrator-accordion-tab wt-migrator-accordion-export-storage-settings">
	<a  href="#"><?php echo __('Export storage options (defaulted to local)', 'wp-migration-duplicator'); ?></a>
	<div class="wt-migrator-accordion-content">
		<?php do_action('wt_migrator_after_export_page_content'); ?>
	</div>
</div>
<div class="wt-migrator-accordion-tab wt-migrator-accordion-exclude-settings" style="margin-bottom: 30px;">
	<a  href="#"><?php echo __('Exclude Folders/files', 'wp-migration-duplicator'); ?></a>
	<div class="wt-migrator-accordion-content">
             <table id="datagrid">
                        <!-- select all boxes -->
                        <tr>
                            <td style="padding: 10px;">
                                <a href="#" name = "usrselectall" id="usrselectall" onclick="return false;" ><?php _e('Select all','wp-migration-duplicator')?></a> &nbsp;/&nbsp;
                                <a href="#" id="usrunselectall" name = "usrunselectall" onclick="return false;"><?php _e('Unselect all','wp-migration-duplicator') ?></a>
                            </td>
                        </tr>
                    </table>
		<?php do_action('wt_migrator_exlcude_files'); ?>
	</div>
</div>
<div style="clear: both;"></div>
<div class="wf-plugin-toolbar bottom">
    <div class="left">
    </div>
    <div class="right">
         <input type="hidden" id="extension_zip_loaded" name="extension_zip_loaded" value=<?php $extension_zip_loaded = extension_loaded('zip')?'enabled':'disabled'; echo $extension_zip_loaded;?>>
         <input type="hidden" id="extension_zlib_loaded" name="extension_zlib_loaded" value=<?php $extension_zlib_loaded = extension_loaded('zlib')?'enabled':'disabled'; echo $extension_zlib_loaded;?>>
         <button name="wt_mgdp_export_btn" class="button button-primary" style="float:right;"><?php _e('Export','wp-migration-duplicator'); ?></button>
        <button name="wt_mgdp_export_stop_btn" class="button button-primary" style="float:right; display:none; margin-right:10px;"><?php _e('Stop export','wp-migration-duplicator'); ?></button>
        <span class="spinner" style="margin-top:11px;"></span>
    </div>
</div>