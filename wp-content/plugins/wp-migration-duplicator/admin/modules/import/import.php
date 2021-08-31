<?php

/**
 * Import section of the plugin
 *
 * @link       
 * @since 1.1.2     
 *
 * @package  Wp_Migration_Duplicator  
 */
if (!defined('ABSPATH')) {
	exit;
}
class Wp_Migration_Duplicator_Import
{
	public $module_id = '';
	public static $module_id_static = '';
	public $module_base = 'import';
	public $step_list = array(
		'fetch_file',
		'start_import',
                'merge_db',
		'import_db',
                'finalize_migration'
	);
	public $ajax_action_list = array(
		'fetch_file',
		'start_import',
                'merge_db',
		'import_db',
                'finalize_migration'
	);
	public $button_click_enabled = false;
	public $attachment_url;

	public function __construct()
	{
		$this->module_id = Wp_Migration_Duplicator::get_module_id($this->module_base);
		add_action('wp_ajax_wt_mgdp_import', array($this, 'ajax_main'), 1);

		add_filter('wt_mgdp_plugin_settings_tabhead', array($this, 'settings_tabhead'));
		add_action('wt_mgdp_plugin_out_settings_form', array($this, 'out_settings_form'));
		add_action('wt_mgdp_backups_action_column', array($this, 'restore_backup_btn'), 10, 3);
		add_action('wt_mgdp_backups_table_top', array($this, 'restore_notice'), 10, 2);
	}

	/**
	 * 	@since 1.1.2
	 *	Showing a notice on top of the backup list table
	 */
	public function restore_notice($backup_list, $offset)
	{
?>
		<div class="wt_warn_box">
			<?php _e('Restoring from backup will overwrite all files in the system, including the existing backups. It will basically take you to the system state during backup.', 'wp-migration-duplicator'); ?>
			<br />
			<?php _e('Do not restore unless you are sure about what you are doing.', 'wp-migration-duplicator'); ?>
		</div>
		<?php
	}


	/**
	 * 	@since 1.1.2
	 *	Showing a restore button on restore list table (if file exists)
	 */
	public function restore_backup_btn($backup, $file_exists, $file_url)
	{
		if ($file_exists && $backup['status'] == Wp_Migration_Duplicator::$status_complete) {
		?>
			<button data-file-url="<?php echo $file_url; ?>" data-id="<?php echo $backup['id_wtmgdp_log']; ?>" title="<?php _e('Restore', 'wp-migration-duplicator'); ?>" class="button button-secondary wt_mgdp_restore_backup" style=""><span class="dashicons dashicons-update-alt" style="margin-top:4px;"></span></button>
		<?php
		}
		if (!$this->button_click_enabled) {
		?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery('.wt_mgdp_restore_backup').unbind('click').click(function() {
						if (confirm('<?php _e('Are you sure?', 'wp-migration-duplicator'); ?>')) {
							var file_url = jQuery(this).attr('data-file-url');
							if (jQuery.trim(file_url) == "") {
								alert('<?php _e('Error', 'wp-migration-duplicator'); ?>');
								return false;
							}
							window.location.hash = "#wt-mgdp-import"; /* switching tab */
							jQuery('[name="attachment_url"]').val(file_url);
							jQuery('.wt_mgdp_import_attachment_url').html(file_url);
							jQuery('[name="wt_mgdp_import_btn"]').trigger('click');
						}
					});
				});
			</script>
<?php
			$this->button_click_enabled = true;
		}
	}

	/**
	 * All the possible Export Methods
	 * @since 1.1.8
	 */

	public static function get_possible_import_methods()
	{
		$import_methods = array(
			'local' => __('Local', 'wp-migration-duplicator')
		);

		return apply_filters('wt_mgdb_import_options', $import_methods);
	}

	/**
	 * @since 1.0.0
	 * Main ajax hook to handle all ajax requests
	 */
	public function ajax_main()
	{

		ini_set('memory_limit', '-1');
		set_time_limit(0);
		$action = sanitize_text_field($_POST['sub_action']);
		$out = array(
			'status' => false,
			'msg' => __('Error', 'wp-migration-duplicator'),
			'step_finished' => 0,
			'finished' => 0,
			'step' => $action,
			'label' => '',
			'sub_label' => '',
		);
		if (!Wp_Migration_Duplicator_Security_Helper::check_write_access(WT_MGDP_PLUGIN_FILENAME, $this->module_id)) {
			echo json_encode($out);
			exit();
		}
		if (in_array($action, $this->ajax_action_list) && method_exists($this, $action)) {
			$out = $this->{$action}($out);
		} else {
			//error
		}
                if(!isset($out['offset'])){
                    $out['offset'] =0;
                }
                 if(!isset($out['limit'])){
                    $out['limit'] =1;
                }
		if ($out['step_finished'] == 1) //step finished move to next step
		{
			$step_array_key = array_search($action, $this->step_list);
			if (isset($this->step_list[$step_array_key + 1])) //next step exists
			{
                                $out['offset'] =0;
				$out['step'] = $this->step_list[$step_array_key + 1];
			} else {
				$out['finished'] = 1;
				$out['label'] = '<span style="color:green; font-weight:bold;">' . __('Import completed.', 'wp-migration-duplicator') . '</span>';
			}
		}
		echo json_encode($out);
		exit();
	}

	/**
	 * @since 1.1.2
	 * import database
	 */
	private function import_db($out)
	{
               ini_set('mysql.connect_timeout', 3000);
               ini_set('default_socket_timeout', 3000); 
                if(file_exists(Wp_Migration_Duplicator::$backup_dir . '/import_db_path_details.json')){
                    $path_Array = json_decode(file_get_contents(Wp_Migration_Duplicator::$backup_dir . '/import_db_path_details.json'), true);
                }else{
                    Webtoffe_logger::write_log( 'Import','Path file missing.' );
		   return $out; //error
                }
                $offset = intval($_POST['offset']);
		$limit = intval($_POST['limit']);
                $total_checkpoints = count($path_Array);
		global $wpdb;
                 //Webtoffe_logger::write_log( 'Import','$offset'. serialize($_POST) );
		/*  check backup file exists
		$upload = wp_upload_dir();
		$db_directory_old = $upload['basedir'] ."/" . 'database.sql';
		$db_directory_new = Wp_Migration_Duplicator::$database_dir . "/" . 'database.sql';
		$filename = '';
		$file_found = false;
		if ( file_exists( $db_directory_new ) ) {
			$filename = $db_directory_new;
		} else if( file_exists( $db_directory_old ) ) {
			$filename = $db_directory_old;
		} else {
                        Webtoffe_logger::write_log( 'Import','Database backup file is missing. Unable to import database' );
			$out['msg'] = __('Database backup file is missing. Unable to import database', 'wp-migration-duplicator');
			$out['sub_label'] = '<br /><span style="color:red;">' . $out['msg'] . '</span>';
			return $out;
		} */
		/*  check DB connection is possible */
                
                $connection = @mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
		@mysqli_query($connection, "SET FOREIGN_KEY_CHECKS = 0;");
                @mysqli_query($connection, "SET SESSION sql_mode = ''");
                @mysqli_query($connection, "set session wait_timeout=40000");
              // @mysqli_query($this->dbh, "SET wait_timeout = ".mysqli_real_escape_string($this->dbh, $GLOBALS['DB_MAX_TIME']));
              //@mysqli_query($this->dbh, "SET max_allowed_packet = ".mysqli_real_escape_string($this->dbh, $GLOBALS['DB_MAX_PACKETS']));

		$mysql_version = substr(mysqli_get_server_info($connection), 0, 3); // Get Mysql Version
		if (mysqli_connect_errno()) {
                        Webtoffe_logger::write_log( 'Import','Unable to connect to database.' );
			$out['msg'] = __('Unable to connect to database.', 'wp-migration-duplicator');
			$out['sub_label'] = '<br /><span style="color:red;">' . $out['msg'] . '</span>';
			return $out;
		}
                if($offset==0){
                  Webtoffe_logger::write_log( 'Import','Database connection established.' );
                }
                
                
                
                if (count($path_Array) > 0) {
                    $real_offset = $offset;
                    $total_imported_checkpoints = $real_offset + 1;
                    if (isset($path_Array[$real_offset])) {
                        foreach ($path_Array[$real_offset] as $f_key => $filename) {
                            
                            if ( !file_exists( $filename ) ) {
                                Webtoffe_logger::write_log( 'Import','Database backup file is missing. ' );
                                continue;
                            }
                            $templine = '';
                            $error_count = 0;
                            $non_error_count = 0;
                            $fp = fopen($filename, 'r');
                            // Loop through each line
                            while (($line = fgets($fp)) !== false) {
                                    // Skip it if it's a comment
                                    if (((substr($line, 0, 2) == '--') && (!strstr($line, ')'))) || $line == '')
                                            continue;

                                    // Add this line to the current segment
                                    $templine .= $line;
                                    $templine = str_replace('wt_webtoffee_', $wpdb->prefix, $templine);
                                    if ($mysql_version >= 5.5) {
                                            $templine = str_replace('utf8mb4_unicode_520_ci', 'utf8mb4_unicode_ci', $templine);
                                    }

                                    // If it has a semicolon at the end, it's the end of the query
                                    if (substr(trim($line), -8, 8) == ';/*END*/') {
                                            if(strstr($templine,',;/*END*/')){
                                                    $templine = str_replace(',;/*END*/',';/*END*/',$templine);
                                            }
                                            $templine = trim($templine);
                                            // Perform the query
                                            if (!mysqli_query($connection, trim($templine))) {
                                                $error_count++;
                                                    Webtoffe_logger::error($connection->error);
                                                    Webtoffe_logger::error($filename);
                                                    if($error_count ==1){
                                                        //Webtoffe_logger::write_log( 'Import',$templine );
                                                        Webtoffe_logger::write_log( 'Import', 'Filename:- '.$filename.'----Row skipped---' .serialize($connection->error) );
                                                    }
                                            } else {
                                                    $non_error_count++;
                                            }
                                            // Reset temp variable to empty
                                            $templine = '';
                                    }
                            }

                           
                            fclose($fp);
                            unlink($filename);
                        }
                        if ($non_error_count == 0) {
                                    Webtoffe_logger::write_log( 'Import','checkpoint '.$total_imported_checkpoints.' import failed.' );
                                    $out['msg'] = __('checkpoint '.$total_imported_checkpoints.' import failed.', 'wp-migration-duplicator');
                                    $out['sub_label'] = '<br /><span style="color:red;">' . $out['msg'] . '</span>';
                            } else {
                                    if ($error_count > 0) {
                                            Webtoffe_logger::write_log( 'Import','Some queries in checkpoint '.$total_imported_checkpoints.' not executed properly' );
                                            $out['msg'] = __('Some queries in checkpoint '.$total_imported_checkpoints.' not executed properly', 'wp-migration-duplicator');
                                            $out['sub_label'] =  '<br />' . $out['msg'] . '<br />' . __('Checkpoint '.$total_imported_checkpoints.' Import completed.', 'wp-migration-duplicator');
                                    } else {
                                            Webtoffe_logger::write_log( 'Import',$total_imported_checkpoints. " out of " . $total_checkpoints .' Checkpoint imported.' );
                                            $out['msg'] = __($total_imported_checkpoints. " out of " . $total_checkpoints .' Checkpoints imported.', 'wp-migration-duplicator');
                                            $out['sub_label'] = '<br />' . $out['msg'];
                                            $out['label'] = '';
                                    }
                            }
                    }
                }
                    @mysqli_query($connection, "SET FOREIGN_KEY_CHECKS = 1;");
                    @mysqli_close($connection);
                    $new_offset = $offset + $limit;
                    
                    if ($total_checkpoints <= $new_offset) {
			$out['step_finished'] = 1;                     
	       	    }

			$out['status'] = true;
                        $out['step'] = 'import_db';
                        $out['offset'] = $new_offset;
                        $out['limit'] = $limit;
                        
               
		return $out;
	}


	/**
	 * fetch the file
	 * @since 1.1.8
	 */

	public function fetch_file($out)
	{     
            $import_id=Webtoffe_logger::create_history_entry('', 'Import');
            /* setting history_id in Log section */
            if($import_id!=0) //first batch then create a history entry
            {
		Webtoffe_logger::$history_id=$import_id;
            }
            $memory = size_format(ini_get('memory_limit'));
            $wp_memory = size_format(WP_MEMORY_LIMIT);                      
            Webtoffe_logger::write_log( 'Import','---[ New import started at '.date('Y-m-d H:i:s').' ] PHP Memory: ' . $memory . ', WP Memory: ' . $wp_memory );
  
		$options = Wp_Migration_Duplicator::get_webtoffee_migrator_option();
		$error_message = __('The specified file could not be found on your server ','wp-migration-duplicator');
		$import_result = array(
			'status' => false,
			'file' => '',
			'message' => $error_message
		);
		$import_method = Wp_Migration_Duplicator_Security_Helper::sanitize_item($_POST['import_method']);
		$attachment_url = Wp_Migration_Duplicator_Security_Helper::sanitize_item($_POST['attachment_url']);
		if( $attachment_url ) {
			$import_result['status'] = true;
			$import_result['file'] = $attachment_url;
			$import_result['message'] = __('File has fetched from the server, now importing the file....','wp-migration-duplicator');
                        Webtoffe_logger::write_log( 'Import','File fetched from the server, Import initiated....' );

		}
		$import_result = apply_filters('wt_migrator_get_import_attachment_url', $import_result,$import_method );
		$import_file = ( isset( $import_result['file'] ) ? $import_result['file'] : '' );

		if( $import_result['status'] === false ) {
			$error_message = ( isset( $import_result['message'] ) ? $import_result['message'] : '' );
			Webtoffe_logger::error($error_message);
			$out['msg'] = $error_message;
                        Webtoffe_logger::write_log( 'Import',$error_message );
			$out['sub_label'] = '<br /><span style="color:red;">' . $out['msg'] . '</span>';
			$out['status'] = false;
			return $out;
		}
		$options['import_attachment_url'] = $import_file;

		Wp_Migration_Duplicator::update_webtoffee_migrator_option( $options );
		$out['step_finished'] = 1;
		$out['status'] = true;
		$out['msg'] = ( isset( $import_result['message'] ) ? $import_result['message'] : '' );
		$out['label'] = ( isset( $import_result['message'] ) ? $import_result['message'] : '' );
		$out['sub_label'] = '<br/>'.( isset( $import_result['message'] ) ? $import_result['message'] : '' );
		return $out;
	}

	/**
	 * 	@since 1.1.8
	 * 	start the import (Import files and directories)
	 *
	 */
	private function start_import($out)
	{	
		$options = Wp_Migration_Duplicator::get_webtoffee_migrator_option();
		$extract_to = WP_CONTENT_DIR;
		$attachment_url = Wp_Migration_Duplicator_Security_Helper::sanitize_item( $options['import_attachment_url']);
		$parse_url = parse_url($attachment_url);
		$real_url = $_SERVER['DOCUMENT_ROOT'] . ($parse_url['path']);
		if (!strpos($real_url, '.zip')) {
                        Webtoffe_logger::write_log( 'Import','Please upload Zip file' );
			$out['msg'] = __("Please upload Zip file", 'wp-migration-duplicator');
			$out['sub_label'] = '<br /><span style="color:red;">' . $out['msg'] . '</span>';
			return $out;
		}
                $backup_folder_name = Wp_Migration_Duplicator::$database_dir;
                if (is_dir($backup_folder_name)) {
                    Webtoffe_logger::write_log( 'Import','Database backup directory already exist.' );
                    $dir = $backup_folder_name;
                    $di = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
                    $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
                    foreach ( $ri as $file ) {
                            $file->isDir() ?  rmdir($file) : unlink($file);
                    }
                    rmdir($dir);
                    Webtoffe_logger::write_log( 'Import','Old database backup directory removed.' );
                }

		/* extracting zip file */
		if(extension_loaded('zip')){
                        Webtoffe_logger::write_log( 'Import','Using ZipArchive module for zip file extracton' );
			$zip	=	new ZipArchive;
			if ($zip->open($real_url)) {
				$zip->extractTo($extract_to);
				$imported	= $zip->close();
                                Webtoffe_logger::write_log( 'Import','Zip file extracted suceessfully' );
				$out['status'] = true;
				$out['step_finished'] = 1;
				$out['label'] = __('Importing database....', 'wp-migration-duplicator');
                                Webtoffe_logger::write_log( 'Import','Importing database....' );
				$out['sub_label'] = '<br />' . __('Files imported.', 'wp-migration-duplicator') . '<br />' . __('Importing database....', 'wp-migration-duplicator');
			} else {
				$error_message = "Cannot open " . $real_url . " for writing.";
				Webtoffe_logger::error($error_message);
                                Webtoffe_logger::write_log( 'Import',$error_message );
				$out['status'] = false;
				$out['step_finished'] = 0;
				$out['label'] = __('Somehting went wrong!', 'wp-migration-duplicator');
				$out['sub_label'] = '<br />' . __('Can\'t open zip file.', 'wp-migration-duplicator');
			}
			
		}else{
                         Webtoffe_logger::write_log( 'Import','Using PclZip module for zip file extracton' );
			if (!class_exists('PclZip')) {
					include ABSPATH . 'wp-admin/includes/class-pclzip.php';
				 }
				$archive = new PclZip($real_url);
				if ($archive->extract(PCLZIP_OPT_PATH, $extract_to) == 0) {
                                        $error_message = "Cannot open " . $real_url . " for writing.";
                                        Webtoffe_logger::write_log( 'Import',$error_message );
					$out['status'] = false;
					$out['step_finished'] = 0;
					$out['label'] = __('Somehting went wrong!', 'wp-migration-duplicator');
					$out['sub_label'] = '<br />' . __('Can\'t open zip file.', 'wp-migration-duplicator');
				}else{
                                        Webtoffe_logger::write_log( 'Import','Zip file extracted suceessfully' );
                                        Webtoffe_logger::write_log( 'Import','Importing database....' );
					$out['status'] = true;
					$out['step_finished'] = 1;
					$out['label'] = __('Importing database....', 'wp-migration-duplicator');
					$out['sub_label'] = '<br />' . __('Files imported.', 'wp-migration-duplicator') . '<br />' . __('Importing database....', 'wp-migration-duplicator');

				}
				
		}
		return $out;
	}

	/**
	 *  @since 1.1.2
	 * 	Import tab head filter callback
	 **/
	public function settings_tabhead($arr)
	{
		$out = array();
		$added = 0;
		foreach ($arr as $k => $v) {
			$out[$k] = $v;
			if ($k == 'wt-mgdp-export') //add after export
			{
				$out['wt-mgdp-import'] = __('Import', 'wp-migration-duplicator');
				$added = 1;
			}
		}
		if ($added == 0) //no export menu, then add it as first item
		{
			$out = array_merge(array('wt-mgdp-import' => __('Import', 'wp-migration-duplicator')), $arr);
		}
		return $out;
	}

	/**
	 *  @since 1.1.2
	 * 	Import page tab content filter callback
	 **/
	public function out_settings_form($arr)
	{
		wp_enqueue_script($this->module_id, plugin_dir_url(__FILE__) . 'assets/js/main.js', array('jquery'), WP_MIGRATION_DUPLICATOR_VERSION);
		/* enque media library */
		wp_enqueue_media();
		$params = array(
			'nonces' => array(
				'main' => wp_create_nonce($this->module_id),
			),
			'ajax_url' => admin_url('admin-ajax.php'),
			'labels' => array(
				'error' => sprintf(__('An unknown error has occurred! Refer to our %stroubleshooting guide%s for assistance.'), '<a href="'.WT_MGDP_PLUGIN_DEBUG_BASIC_TROUBLESHOOT.'" target="_blank">', '</a>'),
				'success' => __('Success', 'wp-migration-duplicator'),
				'finished' => __('Finished', 'wp-migration-duplicator'),
				'sure' => __("You can't undo this action. Are you sure?", 'wp-migration-duplicator'),
				'saving' => __("Saving", 'wp-migration-duplicator'),
				'connecting' => __("Connecting....", 'wp-migration-duplicator'),
				'backupfilenotempty' => __("Please upload a backup file.", 'wp-migration-duplicator'),
				'onlyzipfile' => __("Please upload a zip file.", 'wp-migration-duplicator'),
				'noprofile' => __("Please select FTP profile.", 'wp-migration-duplicator'),
				'pathrequired' => __("Please specify the path.", 'wp-migration-duplicator'),
				'nofilename' => __("Please specify the file name.", 'wp-migration-duplicator'),
				'zip_disable' => __("Before import Please enable ZipArchive extension in server", 'wp-migration-duplicator'),
			)
		);
		wp_localize_script($this->module_id, $this->module_id, $params);

		$view_file = plugin_dir_path(__FILE__) . 'views/importer.php';
		$params = array();
		Wp_Migration_Duplicator_Admin::envelope_settings_tabcontent('wt-mgdp-import', $view_file, '', $params, 0);
	}
        
     private function merge_db($out) {
         $db_path= WP_CONTENT_DIR . '/migrator_database';
        if (is_dir($db_path)) {        
	  $import_db_path = glob($db_path.'/*'.'.sql');
          $dir_arrr = self::wt_split_file_path($import_db_path);
        }
        $dir_arrr = array_filter($dir_arrr);
        if(empty($dir_arrr)){
            $out['msg'] = "db files not exist";
            Webtoffe_logger::write_log( 'Import',"db files not exist");
            $out['status'] = false;
            return $out;
        }
        if( file_exists(Wp_Migration_Duplicator::$backup_dir . '/import_db_path_details.json')){
            @unlink(Wp_Migration_Duplicator::$backup_dir . '/import_db_path_details.json');
        }
        $ffp = fopen(Wp_Migration_Duplicator::$backup_dir . '/import_db_path_details.json', "w");
        if (is_resource($ffp)) {
            fwrite($ffp, json_encode($dir_arrr));
        }
        fclose($ffp);
        $out['step_finished'] = 1;
        $out['status'] = true;
        Webtoffe_logger::write_log('Import', 'Db file initialized successfull');
        return $out;
    }

         public static function wt_split_file_path($fileData) {

             $indexed_db_files = array();
             $b_array = array();
             $i = 0;
             $time_smp = round(microtime(true) * 1000);
             foreach ($fileData as $fd_key => $fd_value) {
                $exp_arr = explode('_webtoffee_',basename($fd_value));
                 $index = $exp_arr[0]?$exp_arr[0]:$fd_key;
                 $indexed_db_files[$index] = $fd_value;
             }
             ksort($indexed_db_files);

             $b_array = array_diff($fileData,$indexed_db_files);
             if(!empty($b_array)){
                 $time_smp = round(microtime(true) * 1000);
                foreach ($b_array as $b_key => $b_value) {
                    $i = $i+1;
                      $ind = $time_smp+$i;
                     $indexed_db_files[$ind] = $b_value;
                 }
             }
            unset($fileData);

            $fileData=$indexed_db_files;
            $arr_count = count($fileData);
            $size_over_array = array();
            $size_less_array = array();
            $new_path = array();
            $sum_size = 0;
            $iteration =0;
            $split_directory_size = apply_filters('wt_mgdp_import_directory_split_size',1000000);
            $split_directory_item_count = apply_filters('wt_mgdp_import_directory_split_count',5);
            foreach ($fileData as $key => $value) {

                if (filesize($value) > $split_directory_size ) {
                    if(!empty($new_path)){
                        $size_less_array[] = $new_path;
                        $new_path = array();
                        $sum_size =0;
                    }
                    $size_less_array[][] = $value;
                } else {

                    $sum_size = $sum_size + filesize($value);
                    if ($sum_size < $split_directory_size && count($new_path)<$split_directory_item_count) {
                        $new_path[] = $value;
                        unset($fileData[$key]);
                    } else {
                        $size_less_array[] = $new_path;
                        unset($new_path, $sum_size);
                        $new_path[] = $value;
                        $sum_size = filesize($value);
                    }
                }
                if ($iteration + 1 >= $arr_count) {
                    $size_less_array[] = $new_path;
                }
                $iteration = $iteration+1;
            }
//             $size_less_array = array_merge($size_less_array, ($size_over_array));
            return $size_less_array;
    }
    
    /**
	 * fetch the file
	 * @since 1.1.8
	 */

	public function finalize_migration($out) {
            global $wpdb;
            $old_version_flag = TRUE;
            $connection = @mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            @mysqli_query($connection, "SET FOREIGN_KEY_CHECKS = 0;");
            @mysqli_query($connection, "SET SESSION sql_mode = ''");
            @mysqli_query($connection, "set session wait_timeout=40000");
    //      @mysqli_query($this->dbh, "SET wait_timeout = ".mysqli_real_escape_string($this->dbh, $GLOBALS['DB_MAX_TIME']));
    //      @mysqli_query($this->dbh, "SET max_allowed_packet = ".mysqli_real_escape_string($this->dbh, $GLOBALS['DB_MAX_PACKETS']));

            $mysql_version = substr(mysqli_get_server_info($connection), 0, 3); // Get Mysql Version
            if (mysqli_connect_errno()) {
                Webtoffe_logger::write_log('Import', 'Unable to connect to database.');
                $out['msg'] = __('Unable to connect to database.', 'wp-migration-duplicator');
                $out['sub_label'] = '<br /><span style="color:red;">' . $out['msg'] . '</span>';
                return $out;
            }
            $tbl_file_name = Wp_Migration_Duplicator::$database_dir . "/webtofee_tables.json";
            if (file_exists($tbl_file_name)) {
                $tbl_Array = json_decode(file_get_contents($tbl_file_name), true);
            } else {
                 if(file_exists(Wp_Migration_Duplicator::$backup_dir . '/import_db_path_details.json')){
                    $path_Array = json_decode(file_get_contents(Wp_Migration_Duplicator::$backup_dir . '/import_db_path_details.json'), true);
                }
                if(count($path_Array) == 1){
                    $old_version_flag = FALSE;
                }else{
                    Webtoffe_logger::write_log('Export', 'Table json file missing.');
                    return $out; //error
                }
            }
           if($old_version_flag == TRUE) {
				$sql = '';
				Webtoffe_logger::write_log('Import', 'Table renaming started.');
                foreach ($tbl_Array as $table) {
                        $new_table = str_replace('webtoffee_', $wpdb->prefix, $table);
						if (mysqli_num_rows(mysqli_query($connection, "SHOW TABLES LIKE '$table'"))) {
							$drop_sql = 'DROP TABLE IF EXISTS ' . $new_table . ';';
							if (!mysqli_query($connection, $drop_sql)) {
								Webtoffe_logger::write_log('Import', 'DB rename faild.connection errer---' . serialize($connection->error) . '---');
							}
							unset($drop_sql);
							$sql_rename = 'RENAME TABLE ' . $table . ' TO ' . $new_table . ';';
							if (!mysqli_query($connection, $sql_rename)) {
								Webtoffe_logger::write_log('Import', 'DB rename faild.connection errer---' . serialize($connection->error) . '---');
							}
							unset($sql_rename);
						}
                }
                Webtoffe_logger::write_log('Import', 'Tables renamed successfully');
           }
           
            Webtoffe_logger::write_log('Import', 'Import process completed.');
            unlink(Wp_Migration_Duplicator::$backup_dir . '/import_db_path_details.json');
            $database_directory = Wp_Migration_Duplicator::$database_dir;
            if (file_exists($database_directory)) {
                Wp_Migration_Duplicator::wt_mgt_delete_files($database_directory);
            }
            Webtoffe_logger::write_log('Import', '---[ Import Ended at ' . date('Y-m-d H:i:s') . ' ] --- ');
            delete_option('wp_mgdp_log_id');
            $out['msg'] = "Sucess";
            $out['status'] = true;
            $out['step_finished'] = 1;
            return $out;
        }

}
new Wp_Migration_Duplicator_Import();
