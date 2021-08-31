<?php

/**
 * Export section of the plugin
 *
 * @link       
 * @since 1.1.2     
 *
 * @package  Wp_Migration_Duplicator  
 */
if (!defined('ABSPATH')) {
	exit;
}
class Wp_Migration_Duplicator_Export
{
	public $module_id = '';
	public static $module_id_static = '';
	public $module_base = 'export';
	public $export_id = 0;
	public $step_list = array(
		'start_export',
		'export_db',
                'split_db',
		'export_files',
	);
	public $ajax_action_list = array(
		'start_export',
		'stop_export',
		'export_db',
                'split_db',
		'export_files',
                'delete_export_file',
	);

	public function __construct()
	{
		$this->module_id = Wp_Migration_Duplicator::get_module_id($this->module_base);
		add_action('wp_ajax_wt_mgdp_export', array($this, 'ajax_main'), 1);

		add_filter('wt_mgdp_plugin_settings_tabhead', array($this, 'settings_tabhead'));
		add_action('wt_mgdp_plugin_out_settings_form', array($this, 'out_settings_form'));
		add_action('wt_mgdp_backups_head', array($this, 'backup_page_export_btn'), 10, 2);

		add_action('wt_migrator_exlcude_files', array($this, 'exclude_unwanted_modules'), 11, 0);
		add_action('admin_enqueue_scripts', array($this, 'add_export_module_css'));
	}

	/**
	 * 	@since 1.1.2
	 *	Showing an export button on top of the backup list table
	 */
	public function backup_page_export_btn($backup_list, $offset)
	{
?>
		<button class="button button-primary wt_mgdp_create_backup" style="float:right;"><?php _e('Goto Export', 'wp-migration-duplicator'); ?></button>
		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery(document).on('click', '.wt_mgdp_create_backup', function() {
					window.location.hash = "#wt-mgdp-export"; /* switching tab */
				});
			});
		</script>
<?php
	}

	/**
	 * 	@since 1.1.2 	Main ajax hook to handle all ajax requests
	 *	@since 1.1.5 	User role checking enabled
	 */
	public function ajax_main()
	{

		$action = Wp_Migration_Duplicator_Security_Helper::sanitize_item($_POST['sub_action']);
		$out = array(
			'status' => false,
			'msg' => __('Error', 'wp-migration-duplicator'),
			'step_finished' => 0,
			'finished' => 0,
			'step' => $action,
			'sub_percent' => 0,
			'percent' => 0,
			'percent_label' => '',
			'sub_percent_label' => '',
		);
		/**
		 *	@since 1.1.5
		 *	User role checking enabled
		 */
		if (!Wp_Migration_Duplicator_Security_Helper::check_write_access(WT_MGDP_POST_TYPE, $this->module_id)) {
			echo json_encode($out);
			exit();
		}
		if (in_array($action, $this->ajax_action_list) && method_exists($this, $action)) {
			$this->export_id = (isset($_POST['export_id']) ? intval($_POST['export_id']) : 0);
			$out = $this->{$action}($out);
		} else {
			//error
		}
		$current_step_index = (int) array_search($action, $this->step_list);
		$single_step_percent = (100 / count($this->step_list));
		$main_percent = $single_step_percent * $current_step_index;
		$out['percent'] = round($main_percent + (($out['sub_percent'] / 100) * $single_step_percent));
		$out['export_id'] = $this->export_id;

		if ($out['step_finished'] == 1) //step finished move to next step
		{
			$step_array_key = array_search($action, $this->step_list);
			if (isset($this->step_list[$step_array_key + 1])) //next step exists
			{
				$out['step'] = $this->step_list[$step_array_key + 1];
				$out['offset'] = 0;
				$out['limit'] = 1;
			} else {
				$out['finished'] = 1;
				$out['percent'] = 100;
				$out['sub_percent'] = 100;
				$out['percent_label'] = '<span style="color:green;">' . __('Export completed', 'wp-migration-duplicator') . '</span>';
			}
		}
		echo json_encode($out);
		exit();
	}

	/**
	 * 	@since 1.1.2
	 *	Stop the current export
	 */
	private function stop_export($out)
	{
                if($_POST['export_id'] && !extension_loaded('zip')){
                $export_log = Wp_Migration_Duplicator::get_log_by_id($_POST['export_id']);
                $log_data = json_decode($export_log['log_data'], true);
                $backup_file_name = $log_data['backup_file'];
                $backup_name_arr =  explode(".", $backup_file_name);
                $backup_folder_name = $backup_name_arr[0] ? $backup_name_arr[0]:'temp';
                $dir = Wp_Migration_Duplicator::$backup_dir . '/' . $backup_folder_name;
                $di = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
                $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
                foreach ( $ri as $file ) {
                        $file->isDir() ?  rmdir($file) : unlink($file);
                }
                rmdir($dir);
            }
		//update log status
		$to_db_arr = array('status' => Wp_Migration_Duplicator::$status_stopped);
		$to_db_where_arr = array('id_wtmgdp_log' => $this->export_id);
		Wp_Migration_Duplicator::update_log($to_db_arr, $to_db_where_arr);
		$out['status'] = true;
		return $out;
	}
        
        private function delete_export_file($out)
	{
            if($_POST['export_id'] && !extension_loaded('zip')){
                $export_log = Wp_Migration_Duplicator::get_log_by_id($_POST['export_id']);
                $log_data = json_decode($export_log['log_data'], true);
                $backup_file_name = $log_data['backup_file'];
                $backup_name_arr =  explode(".", $backup_file_name);
                $backup_folder_name = $backup_name_arr[0] ? $backup_name_arr[0]:'temp';
                $dir = Wp_Migration_Duplicator::$backup_dir . '/' . $backup_folder_name;
                $di = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
                $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
                foreach ( $ri as $file ) {
                        $file->isDir() ?  rmdir($file) : unlink($file);
                }
                rmdir($dir);
            }

		$out['status'] = true;
		return $out;
	}

	/**
	 * @since 1.1.4
	 * Get list of extensions to be excluded while exporting. via filter
	 * 
	 */
	private function get_exclude_extensions()
	{
		$to_exclude_extensions = array('DS_Store');
		$to_exclude_extensions = apply_filters('wt_mgdp_exclude_extensions', $to_exclude_extensions);
		$to_exclude_extensions = (!is_array($to_exclude_extensions) ? array() : $to_exclude_extensions);
		return $to_exclude_extensions;
	}

	/**
	 * @since 1.1.2
	 * Get list of items(files and folders) to be excluded while exporting. via filter
	 * 
	 */
	private function get_exclude_items()
	{
		/* filter to exclude items via filter only items that are directly under `wp-content` */
		$to_exclude_items = array('ai1wm-backups', 'updraft', 'uploads/backup-guard');
		$to_exclude_items = apply_filters('wt_mgdp_exclude_files', $to_exclude_items);
		$to_exclude_items = (!is_array($to_exclude_items) ? array() : $to_exclude_items);
		$must_exclude_items = array('.', '..', 'webtoffee_migrations');
		$to_exclude_items = array_unique(array_merge($to_exclude_items, $must_exclude_items));

		return $to_exclude_items;
	}

	/**
	* @since 1.1.2
	* Start export, checks files and DB count and also exports DB via `export_db` method, Ajax sub function
	* @since 1.1.4 file extension exclusion checking added
	*/
	private function start_export($out)
	{
 
            $export_id=Webtoffe_logger::create_history_entry('', 'Export');
            /* setting history_id in Log section */
            if($export_id!=0) //first batch then create a history entry
            {
		Webtoffe_logger::$history_id=$export_id;
            }
            $memory = size_format(ini_get('memory_limit'));
            $wp_memory = size_format(WP_MEMORY_LIMIT);                      
            $error_message = '---[ New export started at '.date('Y-m-d H:i:s').' ] PHP Memory: ' . $memory . ', WP Memory: ' . $wp_memory;
            Webtoffe_logger::write_log( 'Export','---[ New export started at '.date('Y-m-d H:i:s').' ] PHP Memory: ' . $memory . ', WP Memory: ' . $wp_memory );
            Webtoffe_logger::write_log( 'Export','Backup process initiated..' );
            Webtoffe_logger::write_log( 'Export','Checking database connection' );
  
		global $wpdb;
		$mysqli = $this->get_mysqli();
		if (!$mysqli) {
                        Webtoffe_logger::write_log( 'Export','Failed to connect to MySQL. Please check the log file for more details.' );
			$out['status'] = false;
			$out['msg'] = __('Failed to connect to MySQL. Please check the log file for more details.', 'wp-migration-duplicator');
			return $out;
		}
                Webtoffe_logger::write_log( 'Export','Database connection successfully established' );
		$tables_arr = array();
		$queryTables = $mysqli->query('SHOW TABLES');
		while ($row = $queryTables->fetch_row()) {
			$tables_arr[] = $row[0];
		}
                $exclude_table_list = array($wpdb->prefix.'wtmgdp_log',$wpdb->prefix.'wt_mgdp_action_history');
                $target_tables =array_diff($tables_arr,$exclude_table_list);
                $limit_tables_arr = self::wt_table_export_limit_desider($target_tables,$mysqli);
                $tables_arr = $limit_tables_arr;
		$to_exclude_items	=	$this->get_exclude_items();
		$exclude	=	(isset($_POST['exclude']) && is_array($_POST['exclude']) ? Wp_Migration_Duplicator_Security_Helper::sanitize_item($_POST['exclude'],'text_arr') : array());
		$to_exclude_items	= array_unique(array_merge($to_exclude_items, $exclude));
		/**
		 * @since 1.1.4  take all file extensions to exclude
		 */
		$to_exclude_extensions = $this->get_exclude_extensions();
		$file_arr = array();
		$dir_arr = array();
                Webtoffe_logger::write_log( 'Export','Database backup process initialized...' );
                //check db directory already exist
                $backup_folder_name = Wp_Migration_Duplicator::$database_dir;
                if (is_dir($backup_folder_name)) {
                    Webtoffe_logger::write_log( 'Export','Database backup directory already exist.' );
                    $dir = $backup_folder_name;
                    $di = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
                    $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
                    foreach ( $ri as $file ) {
                            $file->isDir() ?  rmdir($file) : unlink($file);
                    }
                    rmdir($dir);
                    Webtoffe_logger::write_log( 'Export','Old database backup directory removed.' );
                }
		//directories & files in wp-content
		$database_directory = Wp_Migration_Duplicator::$database_dir;
		if (!is_dir($database_directory)) {
			$oldmask = umask(0);
			$directory_status = mkdir($database_directory, 0755);
			umask($oldmask);
			if (!$directory_status) {
				$error_message = 'Unable to create database directory. Please check write permission for `wp-content` folder.';
                                Webtoffe_logger::write_log( 'Export',$error_message );
				Webtoffe_logger::error($error_message);
				$out['status'] = false;
				$out['msg'] = __($error_message, 'wp-migration-duplicator');
				return $out;
			} else {
				//add an index file to block directory listing
				$fh = fopen($database_directory . '/index.php', "w");
				if (is_resource($fh)) {
					fwrite($fh, '<?php // Silence is golden');
				}
				fclose($fh);
			}
		}
		$files = scandir(WP_CONTENT_DIR);
		foreach ($files as $file) {
			if (!in_array($file, $to_exclude_items)) {
				$full_path = WP_CONTENT_DIR . '/' . $file;
				if (is_dir($full_path)) {
					$dir_arr[] = $file;
				} else {
					if (!$this->is_need_to_exclude_extension($to_exclude_extensions, $file)) {
						$file_arr[] = $file;
					}
				}
			}
		}
		if (!is_dir(Wp_Migration_Duplicator::$backup_dir)) {
			$oldmask = umask(0);
			$backup_directory = mkdir(Wp_Migration_Duplicator::$backup_dir, 0775);
			umask($oldmask);
			if (!$backup_directory) {
				$error_message = 'Unable to create backup directory. Please check write permission for `wp-content` folder.';
                                Webtoffe_logger::write_log( 'Export',$error_message );
				Webtoffe_logger::error($error_message);
				$out['status'] = false;
				$out['msg'] = __($error_message, 'wp-migration-duplicator');
				return $out;
			} else {
				//add an index file to block directory listing
				$fh = fopen(Wp_Migration_Duplicator::$backup_dir . '/index.php', "w");
				if (is_resource($fh)) {
					fwrite($fh, '<?php // Silence is golden');
				}
				fclose($fh);
			}
		}

                $itteration_file_path = array();
                $file_path = array();
                $dir_arrr = array();
                foreach ($dir_arr as $fkey => $file_name) {
                    $itteration_file_path[] = self::wt_gets_complete_file_path($file_name);
                }
                foreach ($itteration_file_path as $key => $value) { 
                  if (is_array($value)) { 
                    $file_path = array_merge($file_path, ($value)); 
                  }
                }
                $dir_arrr = self::wt_split_file_path($file_path);
                
                Webtoffe_logger::write_log( 'Export','Database backup directory successfully created' );
		$find = (isset($_POST['find']) && is_array($_POST['find']) ? $_POST['find'] : array());
		$replace = (isset($_POST['replace']) && is_array($_POST['replace']) ? $_POST['replace'] : array());

		/* ---------  */
		$find[] = $wpdb->prefix . 'capabilities';
		$replace[] = 'wt_webtoffee_capabilities';

		$find[] = $wpdb->prefix . 'user_level';
		$replace[] = 'wt_webtoffee_user_level';

		$find[] = $wpdb->prefix . 'user-settings';
		$replace[] = 'wt_webtoffee_user-settings';

		$find[] = $wpdb->prefix . 'user-settings-time';
		$replace[] = 'wt_webtoffee_user-settings-time';

		$find[] = $wpdb->prefix . 'dashboard_quick_press_last_post_id';
		$replace[] = 'wt_webtoffee_dashboard_quick_press_last_post_id';

		$find[] = $wpdb->prefix . 'user_roles';
		$replace[] = 'wt_webtoffee_user_roles';
		/* ---------  */

		$find = Wp_Migration_Duplicator_Admin::sanitize_array($find);
		$replace = Wp_Migration_Duplicator_Admin::sanitize_array($replace);

		$tme = time();
		$log_data = array('tables' => $tables_arr, 'files' => $file_arr, 'dirs' => $dir_arr, 'find' => $find, 'replace' => $replace, 'backup_file' => date('Y-m-d-h-i-sa', $tme) . '.zip');

		$data_arr = array(
			'log_name' => date('Y-m-d h:i:s A'),
			'log_data' => json_encode($log_data),
			'status' => Wp_Migration_Duplicator::$status_incomplete,
			'log_type' => 'export',
			'created_at' => $tme,
			'updated_at' => $tme,
		);
                if($target_tables){
                   $target_tables_save=array();
      
                    $target_tables_save = str_replace($wpdb->prefix, 'webtoffee_', $target_tables);

                    $tbl_file_name = Wp_Migration_Duplicator::$database_dir."/webtofee_tables.json";
                   if( file_exists($tbl_file_name)){
                       @unlink($tbl_file_name);
                   }
                    $fp = fopen($tbl_file_name, "w");
                    if (is_resource($fp)) {
                        fwrite($fp, json_encode($target_tables_save));
                    }
                     fclose($fp);                     
                     unset($target_tables_save);
                }
		$this->export_id = Wp_Migration_Duplicator::create_log($data_arr);
                if( file_exists(Wp_Migration_Duplicator::$backup_dir . '/path_details'.$this->export_id.'.json')){
                      @unlink(Wp_Migration_Duplicator::$backup_dir . '/path_details'.$this->export_id.'.json');
                  }
                $ffp = fopen(Wp_Migration_Duplicator::$backup_dir . '/path_details'.$this->export_id.'.json', "w");
                if (is_resource($ffp)) {
                    fwrite($ffp, json_encode($dir_arrr));
                }
                fclose($ffp);             
                Webtoffe_logger::write_log( 'Export','Database backup process started..' );
		return $this->export_db($out);
	}

	private function is_need_to_exclude_extension($to_exclude_extensions, $file_path)
	{	
		
		$file_arr = explode('.', $file_path);
		$ext = end($file_arr);
		return in_array($ext, $to_exclude_extensions);
	}

	/**
	 * @since 1.1.2
	 * Checking the {file/its parent folder} is in the exclude list
	 * @param array $to_exclude_items list of items to exclude
	 * @param string $sub_real_path current file path
	 */
	private function is_need_to_exclude($to_exclude_items, $sub_real_path)
	{
		$is_need_to_exclude = 0;
		/* checking files in exclude list */
		$str_reminder1 = str_replace($to_exclude_items, '', $sub_real_path);
		if ($str_reminder1 != $sub_real_path) /* found then need to verfify its on front of the string */ {
			foreach ($to_exclude_items as $excl) {
				if (strpos($sub_real_path, $excl) === 0) {
					$is_need_to_exclude = 1; //skipping the file
				}
			}
		}
		return $is_need_to_exclude;
	}

	/**
	* @since 1.1.2
	* Export files recrusively, Ajax sub function
	* @param array $out array of output
	*/
	public function export_files($out)
	{

		$export_log = $this->get_check_export_log();
		if (!$export_log) {
                        Webtoffe_logger::write_log( 'Export','export_log error' );
			return $out; //error
		}
                if(file_exists(Wp_Migration_Duplicator::$backup_dir . '/path_details'.$this->export_id.'.json')){
                    $path_Array = json_decode(file_get_contents(Wp_Migration_Duplicator::$backup_dir . '/path_details'.$this->export_id.'.json'), true);
                }else{
                    Webtoffe_logger::write_log( 'Export','Path file missing.' );
		   return $out; //error
                }

		$offset = intval($_POST['offset']);
		$limit = intval($_POST['limit']);
		$log_data = json_decode($export_log['log_data'], true);
                set_time_limit(0);
                ini_set('max_execution_time', -1);
                ini_set('memory_limit', -1);
		if(extension_loaded('zip')){
		$zip = new ZipArchive();
		$backup_file_name = $log_data['backup_file'];
		$backup_file = Wp_Migration_Duplicator::$backup_dir . '/' . $backup_file_name;
		if ($offset == 0) { //offset is zero then create or overwrite the file(if exists)
                    Webtoffe_logger::write_log( 'Export','Files and directories prepared for backup...' );
                    Webtoffe_logger::write_log( 'Export','Using ZipArchive module to create the backup' );
			$zip_opened = $zip->open($backup_file, ZipArchive::CREATE | ZipArchive::OVERWRITE);
		} else {
			$zip_opened = $zip->open($backup_file);
		}
		if ($zip_opened !== true) {
			$error_message = "Cannot open " . $backup_file . " for writing.";
			Webtoffe_logger::error($error_message);
                        Webtoffe_logger::write_log( 'Export',$error_message );
			$out['status'] = false;
			$out['msg'] = __($error_message, 'wp-migration-duplicator');
			return $out;
		}
		$to_exclude_items =	$this->get_exclude_items();
		$exclude = (isset($_POST['exclude']) && is_array($_POST['exclude']) ? Wp_Migration_Duplicator_Security_Helper::sanitize_item($_POST['exclude'],'text_arr') : array());
		$to_exclude_items = array_unique(array_merge($to_exclude_items, $exclude));
		/**
		 * @since 1.1.4  take all file extensions to exclude
		 */
		$to_exclude_extensions = $this->get_exclude_extensions();

		/* we take all files as one entry */
		$total_files = count($log_data['files']);
		$total_dirs = count($path_Array);
                if ($offset == 0) { 
                    Webtoffe_logger::write_log( 'Export','Starting compression process...' );
                    Webtoffe_logger::write_log( 'Export','Total files:'. $total_files .' Total directorys:'.$total_dirs);
                }
		$total_items = ($total_files > 0 ? 1 : 0) + count($path_Array);
		if ($offset == 0 && $total_files > 0) //first export files (if exists)
		{
			foreach ($log_data['files'] as $file) {
				$full_path = WP_CONTENT_DIR . '/' . $file;
				if (!in_array($file, $to_exclude_items)) {
					$zip->addFile($full_path, $file);
				}
			}
			$out['sub_percent_label'] = __($total_files . " file(s) exported.", 'wp-migration-duplicator');
                        Webtoffe_logger::write_log( 'Export',$total_files . " files exported.");
		} else //export directories
		{
			if (count($path_Array) > 0) {
				//if file exists then real offset will be leasser than one
				$real_offset = ($total_files > 0 ? $offset - 1 : $offset);
                                $total_exported_directories = $real_offset + 1;
				if (isset($path_Array[$real_offset])) {
                                    foreach ($path_Array[$real_offset] as $name => $file) {
                                        $relativePath = str_replace(WP_CONTENT_DIR,"",$file);
                                        $sub_real_path = $dir_to_add_rel_path . '/' . $relativePath;

                                        /* checking files in exclude list */
                                        $is_need_to_exclude = $this->is_need_to_exclude($to_exclude_items, $sub_real_path);
                                        if ($is_need_to_exclude == 0) {
                                                if (!$this->is_need_to_exclude_extension($to_exclude_extensions, $sub_real_path)) {
                                                        $zip->addFile($file, $relativePath);
                                                }
                                        }
                                    }
                                    $out['sub_percent_label'] = __($total_exported_directories . " out of " . $total_dirs . " directory checkpoint exported.", 'wp-migration-duplicator');
                                    Webtoffe_logger::write_log( 'Export',$total_exported_directories . " out of " . $total_dirs . " directory checkpoint exported.");
					
				} else {
					$out['status'] = true;
					$out['step_finished'] = 1;
					return $out;
				}
			} else {
				$out['status'] = true;
				$out['step_finished'] = 1;
				return $out;
			}
		}
		$zip->close();
	    }else{
			
                        $backup_file_name = $log_data['backup_file'];
			$backup_name_arr =  explode(".", $backup_file_name);
			$backup_folder_name = $backup_name_arr[0] ? $backup_name_arr[0]:'temp';
			$backup_file_name = $backup_folder_name.'.zip';
			$backup_file_path = Wp_Migration_Duplicator::$backup_dir . '/' . $backup_folder_name;
			$to = Wp_Migration_Duplicator::$backup_dir . '/';
                
                        if ($offset == 0) { 
                                 Webtoffe_logger::write_log( 'Export','Files and directories prepared for backup...' );
                                 Webtoffe_logger::write_log( 'Export','Using PclZip module to create the backup' );
                                if(!is_dir($backup_file_path)){
                                 $directory_errors = mkdir($backup_file_path, 0777);  
                                  Webtoffe_logger::write_log( 'Export', " Webtofee backup directory created sucessfully");
                                }
                                if (!$directory_errors) {
                                        $directory_error_message = 'Unable to create backup directory. Please check write permission for `wp-content` folder.';
                                        Webtoffe_logger::error($directory_error_message);
                                        Webtoffe_logger::write_log( 'Export',$directory_error_message );
                                        $out['status'] = false;
                                        $out['msg'] = __($directory_error_message, 'wp-migration-duplicator');
                                        return $out;
                                }
                        } 

                        $to_exclude_items =	$this->get_exclude_items();
                        $exclude = (isset($_POST['exclude']) && is_array($_POST['exclude']) ? Wp_Migration_Duplicator_Security_Helper::sanitize_item($_POST['exclude'],'text_arr') : array());
                        $to_exclude_items = array_unique(array_merge($to_exclude_items, $exclude));
                        /**
                         * @since 1.2.2  take all file extensions to exclude
                         */
                        $to_exclude_extensions = $this->get_exclude_extensions();

                        /* we take all files as one entry */
                        $total_files = count($log_data['files']);
                        $total_dirs = count($log_data['dirs']);
                        if ($offset == 0) { 
                            Webtoffe_logger::write_log( 'Export','Total files:'. $total_files .' Total directorys:'.$total_dirs);
                        }
                        $total_items = ($total_files > 0 ? 1 : 0) + count($log_data['dirs']);
                        if ($offset == 0 && $total_files > 0) //first export files (if exists)
                        {
                                foreach ($log_data['files'] as $file) {
                                        $full_path = WP_CONTENT_DIR . '/' . $file;
                                        $newfile = $backup_file_path . '/' . $file;
                                        if (!in_array($file, $to_exclude_items)) {
                                                copy($full_path, $newfile);
                                        }
                                }
                                $out['sub_percent_label'] = __($total_files . " file(s) exported.", 'wp-migration-duplicator');
                                Webtoffe_logger::write_log( 'Export',$total_files . " files exported." );
                        } else //export directories
                        {
                                if (count($log_data['dirs']) > 0) {
                                        //if file exists then real offset will be leasser than one
                                        $real_offset = ($total_files > 0 ? $offset - 1 : $offset);
                                        if (isset($log_data['dirs'][$real_offset])) {
                                                $full_path = WP_CONTENT_DIR . '/' . $log_data['dirs'][$real_offset];
                                                $newfile = $backup_file_path . '/' . $log_data['dirs'][$real_offset];;
                                                $total_exported_directories = $real_offset + 1;
                                                 if (!is_dir($newfile)) {
                                                        $this->full_copy($full_path, $newfile);
                                                }
                                                $out['sub_percent_label'] = __($total_exported_directories . " out of " . $total_dirs . " directories exported.", 'wp-migration-duplicator');
                                                 Webtoffe_logger::write_log( 'Export',$total_exported_directories . " out of " . $total_dirs . " directories exported." );
                                        } else {
                                                $out['status'] = true;
                                                $out['step_finished'] = 1;
                                                return $out;
                                        }
                                } else {
                                        $out['status'] = true;
                                        $out['step_finished'] = 1;
                                        return $out;
                                }
                        }
                

	}
		$new_offset = $offset + $limit;
		if ($total_items <= $new_offset) {
			if(!extension_loaded('zip')){
                             Webtoffe_logger::write_log( 'Export',"Compressing..." );
			   if (!class_exists('PclZip')) {
				   include ABSPATH . 'wp-admin/includes/class-pclzip.php';
				}
			   $dir = Wp_Migration_Duplicator::$backup_dir . '/';
			   $backup_file =Wp_Migration_Duplicator::$backup_dir . '/' .$backup_file_name;
			   $archive = new PclZip($backup_file);
			   $archive->add($backup_file_path, PCLZIP_OPT_REMOVE_PATH, $backup_file_path);
			   $dir =$backup_file_path;
                           Webtoffe_logger::write_log( 'Export', " Closing files and archives.");
			   $di = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
			   $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
			   foreach ( $ri as $file ) {
				   $file->isDir() ?  rmdir($file) : unlink($file);
			   }
			   rmdir($backup_file_path);
                           unlink(Wp_Migration_Duplicator::$backup_dir . '/path_details'.$this->export_id.'.json');
                           Webtoffe_logger::write_log( 'Export', " Webtofee backup directory removed sucessfully");
		   }
			//delete the database.sql file.
			$database_directory = Wp_Migration_Duplicator::$database_dir;
			if (file_exists($database_directory)) {
                                @unlink(Wp_Migration_Duplicator::$database_dir."/webtofee_tables.json");
                                @unlink(Wp_Migration_Duplicator::$backup_dir . '/path_details'.$this->export_id.'.json');
				Wp_Migration_Duplicator::wt_mgt_delete_files($database_directory);                             
                                 Webtoffe_logger::write_log( 'Export', " Database backup directory removed sucessfully");
			}
			//add an index file to block directory listing
			$to_db_arr = array('status' => Wp_Migration_Duplicator::$status_complete);
			$to_db_where_arr = array('id_wtmgdp_log' => $this->export_id);
			Wp_Migration_Duplicator::update_log($to_db_arr, $to_db_where_arr);

			$out['step_finished'] = 1;
			$out['backup_file'] = html_entity_decode(Wp_Migration_Duplicator_Admin::generate_backup_file_url($backup_file_name)); 
                        $msg = __('Export file processing completed');
                        Webtoffe_logger::write_log( 'Export','Backup completed successfully!' );
                        $msg.='<span class="wt_mgdp_popup_close" style="line-height:10px;width:auto" onclick="popup_handler.hide_export_info_box();">X</span>';                           
                        $msg.='<span class="wt_mgdp_info_box_finished_text" style="font-size: 10px; display:block">';
                        $msg.=__('You can manage exports from Backups section.');
                        $msg.='<a class="button button-secondary" style="margin-top:10px;" onclick="popup_handler.hide_export_info_box();" target="_blank" href="'.$out['backup_file'].'" >'.__('Download file').'</a></span>';
                        
                        $out['msg']=$msg;
                                //content_url() . Wp_Migration_Duplicator::$backup_dir_name . "/" . $backup_file_name;
			$out['backup_file_name'] = $backup_file_name;
			$out['sub_percent_label'] = __(sprintf("%d files and %d directories exported", $total_files, $total_dirs), 'wp-migration-duplicator');
			$out['percent_label'] = __(sprintf("%d files, %d directories, %d database tables exported", $total_files, $total_dirs, count($log_data['tables'])), 'wp-migration-duplicator');
                        Webtoffe_logger::write_log( 'Export','---[ Export Ended at '.date('Y-m-d H:i:s').' ] --- ' );
                        delete_option('wp_mgdp_log_id');
		} else {
			$out['percent_label'] = __("Exporting files and directories", 'wp-migration-duplicator');
		}
		$out['status'] = true;
		$out['step'] = 'export_files';
		$out['offset'] = $new_offset;
		$out['limit'] = $limit;
                $out['t_offset'] = 0;
		$out['t_limit'] = 0;
		$total_steps = ceil($total_items / $limit);
		$out['sub_percent'] = round((100 / $total_steps) * (($offset / $limit) + 1));
		$out['export_option'] = 'local';
		if (isset($out['backup_file']) && '' !=  $out['backup_file']) {
			return apply_filters('wtmgdp_export_output', $out);;
		}
		return $out;
	}

	public function full_copy( $source, $target ) {
		if ( is_dir( $source ) ) {
			@mkdir( $target,0777 );
			$d = dir( $source );
			while ( FALSE !== ( $entry = $d->read() ) ) {
				if ( $entry == '.' || $entry == '..' ) {
					continue;
				}
				$Entry = $source . '/' . $entry; 
				if ( is_dir( $Entry ) ) {
					$this->full_copy( $Entry, $target . '/' . $entry );
					continue;
				}
				copy( $Entry, $target . '/' . $entry );
			}

			$d->close();
		}else {
			copy( $source, $target );
		}
		return true;
	}

	/**
	* @since 1.1.2
	* Get export DB entry
	* @param array $out array of output
	* @return array $export_log export details
	*/
	private function get_check_export_log()
	{
		if ($this->export_id == 0) {
			return false; //error
		}
		$export_log = Wp_Migration_Duplicator::get_log_by_id($this->export_id);
		if (empty($export_log)) {
			return false; //no record found
		}
		return $export_log;
	}

	/**
	* @since 1.1.2
	* Get mysqli connection object
	* @return object $mysqli mysqli object
	*/
	private function get_mysqli()
	{
		$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
		if ($mysqli->connect_errno) {
			$error = "Failed to connect to MySQL: " . $mysqli->connect_error;
			Webtoffe_logger::error($error);
			Webtoffe_logger::write_log( 'Export',$error );
			return false;
		}
		$mysqli->select_db(DB_NAME);
		$mysqli->query("SET NAMES 'utf8'");
		return $mysqli;
	}

	/**
	* @since 1.1.2
	* Export database,  Ajax sub function
	* @param array $out array format for output
	*/
	public function export_db($out)
	{
		global $wpdb;
		$export_log = $this->get_check_export_log();
		if (!$export_log) {
                    Webtoffe_logger::write_log( 'Export','export_log error' );
                    return $out; //error
		}              
		$offset = intval($_POST['offset']);
                $limit = intval($_POST['limit']);
                $t_offset = intval($_POST['t_offset']);
                $t_limit = apply_filters('wt_mgdp_export_table_row_size',intval($_POST['t_limit']));
                if($offset == 0){
                Webtoffe_logger::write_log( 'Export','Limit: '.$limit.' Offset '.$offset );
                }
		$log_data = json_decode($export_log['log_data'], true);
		$find = $log_data['find']; //taking find and replace from db
		$replace = $log_data['replace'];
                $exclude_table_list = array($wpdb->prefix.'wtmgdp_log',$wpdb->prefix.'wt_mgdp_action_history');
		set_time_limit(0);
                ini_set('max_execution_time', -1);
                ini_set('memory_limit', -1);
		$upload = wp_upload_dir();
		$upload_dir = $upload['basedir'];
		$download_path = $upload_dir . '/.';
		$mysqli = $this->get_mysqli();
		if (!$mysqli) {
			$out['status'] = false;
			$error_message = 'Failed to connect to MySQL. Please check the log file for more details.';
                        Webtoffe_logger::write_log( 'Export',$error_message );
			$out['msg'] = __($error_message, 'wp-migration-duplicator');
			Webtoffe_logger::error($error_message);
			return $out;
		}
		$target_tables = $log_data['tables']; //taking table list from db

		$total_tables = count($target_tables);
                if($offset == 0){
                Webtoffe_logger::write_log( 'Export','Total '.$total_tables. ' database tables checkpoints ready for export' );                
                }
		$out['total_tables'] = $total_tables;
		if ($total_tables <= $offset) {
                        Webtoffe_logger::write_log( 'Export','Database export competed' );
			$out['status'] = true;
			$out['step_finished'] = 1;                      
			return $out;
		}
  
                //$target_tables=array_diff($target_tables,$exclude_table_list);
               
		//$list_arr = array_chunk($target_tables, $limit);
                $list_arr = $target_tables;
		$content = '';
		$total_exported_tables = $offset;
		$current_offset_pos = $offset;//($offset / $limit);
                $file_name = "database.sql";
		$database_directory = Wp_Migration_Duplicator::$database_dir;
		$directory_error = false;
		$directory_status = true;
		if (!is_dir($database_directory)) {
			$oldmask = umask(0);
			$directory_status = mkdir($database_directory, 0777);
			umask($oldmask);
		}
		if ($directory_status) {
			$fwrite_mode = ($offset == 0 ? "w" : "a");
			$fp = fopen($database_directory . '/' . $file_name, $fwrite_mode);
			if (!$fp) {
			
				$directory_error = true;
			}
		} else {
			$directory_error = true;
		}
		if ($directory_error) {

			$error_message = 'Unable to create backup directory. Please check write permission for `wp-content` folder.';
			Webtoffe_logger::error($error_message);
                        Webtoffe_logger::write_log( 'Export',$error_message );
			$out['status'] = false;
			$out['msg'] = __($error_message, 'wp-migration-duplicator');
			return $out;
		}  
                
				$autoloadFuncs = spl_autoload_functions();
                if(!empty($autoloadFuncs)){
                    foreach($autoloadFuncs as $unregisterFunc)
                    {
                        if(is_callable($unregisterFunc)){
                            spl_autoload_unregister($unregisterFunc);
                        }
                    }
                }
              
		foreach ($list_arr as $offset_pos => $tb_arr) {
			if ($offset_pos == $current_offset_pos) {
				foreach ($tb_arr as $table) {
                                        Webtoffe_logger::write_log( 'Export'," Table ".$table. " exporting .." );
                                        $sub_percent_tbl_label ='';
					$Texport = FALSE;
                                        $chunks_req	= $mysqli->query('SELECT count(*) FROM `'.$table.'`');                                        
                                        $chunks_res = mysqli_fetch_array($chunks_req);
                                        $chunks = intval($chunks_res[0]);
                                        if ($chunks >= $t_offset) {
                                             $limit_str = 'LIMIT '. $t_offset . ','.$t_limit ;
                                             $this->wt_saveDatabaseFields($mysqli, $table, $fp, $limit_str,$chunks,$t_offset,$t_limit,$find, $replace);
                                        }    
                                        $new_t_offset = $t_offset + $t_limit;
                                        if ($chunks <= $new_t_offset) {
                                                $total_exported_tables++;
                                                $Texport = TRUE;
                                                $new_t_offset = 0;
                                        }                                       
                                        if($new_t_offset > 0){
                                            $sub_percent_tbl_label = "Exporting table " .$table. " please wait.. ".$new_t_offset . " out of " . $chunks . " rows exported.";
                                         }
				}
				break;
                        }else{
                             $Texport = TRUE;
                             $new_t_offset = 0;
                        }
		}

		$new_offset = $offset;
                if(!empty($autoloadFuncs)){
                  foreach($autoloadFuncs as $registerFunc)
                    {
                      if(is_callable($registerFunc)){
                            spl_autoload_register($registerFunc);
                      }
                      
                    }
                }
                if($Texport == TRUE){
		  $new_offset = $offset + $limit;
                }
		if ($total_tables <= $new_offset) {
			$out['step_finished'] = 1;
		}
                
		$out['status'] = true;
		$out['step'] = 'export_db';
		$out['offset'] = $new_offset;
		$out['limit'] = $limit;
                $out['t_offset'] = $new_t_offset;
		$out['t_limit'] = $t_limit;
		$total_steps = ceil($total_tables / $limit);
		$out['sub_percent'] = round((100 / $total_steps) * (($offset / $limit) + 1));
                
                $sub_percent_label = $new_offset . " out of " . $total_tables . " tables checkpoint exported.";
                if($sub_percent_tbl_label){
                    $sub_percent_label = $sub_percent_label ." ". $sub_percent_tbl_label;
                }
                Webtoffe_logger::write_log( 'Export',$sub_percent_label );
		$out['sub_percent_label'] = __($sub_percent_label, 'wp-migration-duplicator');
		$out['percent_label'] = __("Exporting database", 'wp-migration-duplicator');
                if ($total_tables <= $new_offset) {
                    Webtoffe_logger::write_log( 'Export',$sub_percent_label );
                    Webtoffe_logger::write_log( 'Export','Database backup process completed' );
                }
		return $out;
	}
        
        public function wt_saveDatabaseFields(&$mysqli, $table, &$file1, $limit,$chunks,$t_offset,$t_limit,$find, $replace) {
          
            global $wpdb;        
            $saveDatabaseFields_row_size = apply_filters('wt_saveDatabaseFields_row_size',1000);
            $result	= $mysqli->query('SELECT * FROM `'.$table.'` ' . $limit);
            $fields_amount = $result->field_count;
            $rows_num = $mysqli->affected_rows;		
            $tal_counter = ($t_offset / $saveDatabaseFields_row_size);
            $tal_counter = ($tal_counter <= 0)? 1 : $tal_counter+1;
            $tbl_name = str_replace($wpdb->prefix, 'webtoffee_', $table);            
              if($t_offset == 0 &&($chunks<=0 || !isset($chunks) || empty($chunks)) ){
                  $time_smp = round(microtime(true) * 1000);
                  $tbl_file_name = str_replace($wpdb->prefix, '', $table);
                $file_name = $time_smp."_webtoffee_db_table_".$tbl_file_name."_".$tal_counter.".sql";
                $database_directory = Wp_Migration_Duplicator::$database_dir;
                $file = fopen($database_directory . '/' . $file_name, 'w');
                $res = $mysqli->query('SHOW CREATE TABLE ' . $table);
                $TableMLine = $res->fetch_row();
                $table = str_replace($wpdb->prefix, 'webtoffee_', $table);
                $TableMLine[1] = $TableMLine[1] = str_replace($wpdb->prefix, 'webtoffee_', $TableMLine[1]);
                $content = "\n\n" . "DROP TABLE IF EXISTS `$table` ;/*END*/ " . "\n\n" . "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";/*END*/\r\nSET time_zone = \"+00:00\";/*END*/\r\n\r\n\r\n/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;/*END*/\r\n/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;/*END*/\r\n/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;/*END*/\r\n/*!40101 SET NAMES utf8 */;/*END*/\r\n--\r\n-- Database: `" . DB_NAME . "`\r\n--\r\n\r\n\r\n" . "\n\n" . $TableMLine[1] . ";/*END*/\n\n";
                fwrite($file, "$content");
    
               }
            
            for ($i = 0, $st_counter = 0 ,$row_counter = 0; $i < $fields_amount; $i++, $st_counter = 0) {
              while ($row = $result->fetch_row())	{
                  
                  if($row_counter==0){
                        $time_smp = round(microtime(true) * 1000);
                        $tbl_file_name = str_replace($wpdb->prefix, '', $table);
                        $file_name = $time_smp."_webtoffee_db_table_".$tbl_file_name."_".$tal_counter.".sql";
                        $database_directory = Wp_Migration_Duplicator::$database_dir;
			$file = fopen($database_directory . '/' . $file_name, 'w');
                  }
                 if($t_offset == 0 && $st_counter == 0){
                    $res = $mysqli->query('SHOW CREATE TABLE ' . $table);
                    $TableMLine = $res->fetch_row();
                    $table = str_replace($wpdb->prefix, 'webtoffee_', $table);
                    $TableMLine[1] = $TableMLine[1] = str_replace($wpdb->prefix, 'webtoffee_', $TableMLine[1]);
                    $content = "\n\n" . "DROP TABLE IF EXISTS `$table` ;/*END*/ " . "\n\n" . "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";/*END*/\r\nSET time_zone = \"+00:00\";/*END*/\r\n\r\n\r\n/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;/*END*/\r\n/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;/*END*/\r\n/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;/*END*/\r\n/*!40101 SET NAMES utf8 */;/*END*/\r\n--\r\n-- Database: `" . DB_NAME . "`\r\n--\r\n\r\n\r\n" . "\n\n" . $TableMLine[1] . ";/*END*/\n\n";
                    fwrite($file, "$content");              
                }
                
                $table = str_replace($wpdb->prefix, 'webtoffee_', $table);
                fwrite($file, "\nINSERT INTO `" . $table . "` VALUES");
                
                fwrite($file, "\n(");
                for ($j = 0; $j < $fields_amount; $j++) {
                    
                  $row[$j] = $this->webtoffee_serialize($find, $replace, $row[$j]); 
                  $row[$j] = addslashes($row[$j]);

                  if (isset($row[$j])) 
                      fwrite($file, '"'.$row[$j].'"');
                  else
                      fwrite($file, '""');

                  if ($j<($fields_amount-1))
                      fwrite($file, ',');
                }
                $row_counter=$row_counter+1;
                
                if($row_counter==$saveDatabaseFields_row_size || $st_counter+1==$rows_num){
                     fwrite($file, ");/*END*/");
                     $row_counter = 0;
                     $tal_counter =$tal_counter + 1;
                }else{
                    fwrite($file, ");/*END*/");
                }
                
//                  if ((($st_counter+1)%100==0 && $st_counter!=0) || $st_counter+1==$rows_num) {
//                     fwrite($file, "");
//                   } else fwrite($file, ",");

                
                $st_counter = $st_counter + 1;

              }
            }
//            if ($chunks <= $new_t_offset && $chunks > 0) { 
//              fwrite($file, ";/*END*/");
//            } 

          }
          
          public function split_db($out) {
              
             $file_name = "database.sql";
	    $database_directory = Wp_Migration_Duplicator::$database_dir;     
            if(file_exists(Wp_Migration_Duplicator::$backup_dir . '/path_details'.$this->export_id.'.json')){
                $paths_array = json_decode(file_get_contents(Wp_Migration_Duplicator::$backup_dir . '/path_details'.$this->export_id.'.json'), true);
            }else{
                Webtoffe_logger::write_log( 'Export','Path file missing.' );
               return $out; //error
            }
           // $paths_array[] = WP_CONTENT_DIR."/webtofee_tables.json";
            if(file_exists($database_directory . '/' . $file_name)){
           
                    unlink($database_directory . '/' . $file_name);
                    $db_files = self::wt_gets_complete_file_path(basename($database_directory));
                    $db_dir_arrr = self::wt_split_file_path($db_files);
                    foreach ($db_dir_arrr as $key => $db_arr_value) {
                        $paths_array[] = $db_arr_value;
                    }
                
            }else{
               Webtoffe_logger::write_log( 'Export','DB file missing.' ); 
            }
             file_put_contents(Wp_Migration_Duplicator::$backup_dir . '/path_details'.$this->export_id.'.json', '');
            $ffp = fopen(Wp_Migration_Duplicator::$backup_dir . '/path_details'.$this->export_id.'.json', "w");
            if (is_resource($ffp)) {
                fwrite($ffp, json_encode($paths_array));
            }else{
                 Webtoffe_logger::write_log( 'Export','DB path write error .' );
            }
            fclose($ffp);
            $out['step_finished'] = 1;
            Webtoffe_logger::write_log( 'Export','DB split compleited.' );
            $out['status'] = true;
            $out['step'] = 'split_db';
            $out['offset'] = 0;
            $out['limit'] = 0;
            $out['t_offset'] = 0;
            $out['t_limit'] = 0;
            return $out;
        }

    /**
	 * @since 1.0.0
	 * Serailize and unserailze db data for find and replace
	 * 
	 */
	//TODO Helper Fucntions to be moved using namespacing
	public function webtoffee_serialize($search = '', $replace = '', $data = '', $serialised = FALSE)
	{                           
		if (is_string($data) && ($unserialized = @unserialize($data)) !== FALSE) {
			$data = $this->webtoffee_serialize($search, $replace, $unserialized, TRUE);
		} elseif (is_array($data)) {
			$_tmp = array();
			foreach ($data as $key => $value) {
				$_tmp[$key] = $this->webtoffee_serialize($search, $replace, $value, FALSE);
			}

			$data = $_tmp;
			unset($_tmp);
		} elseif (is_object($data)) {
                    
                    if ( ! ( $data instanceof __PHP_Incomplete_Class ) ) {
					$_tmp = $data; // new instance
                                        $props = get_object_vars($data);
					foreach ( $props as $key => $value ) {
						if ( ! empty( $_tmp->$key ) ) {
							$_tmp->$key = $this->webtoffee_serialize($search, $replace, $value, FALSE);
						}
					}

					$data = $_tmp;
                                        unset($_tmp);
                        }

		} else {
			if (is_string($data)) {
				$data = str_replace($search, $replace, $data);
			}
		}
		if ($serialised) {
			return maybe_serialize($data);
		}             
		return $data;
	}

	/**
	 *  @since 1.1.2
	 * 	Export tab head filter callback
	 **/
	public function settings_tabhead($arr)
	{
		return array_merge(array('wt-mgdp-export' => __('Export', 'wp-migration-duplicator')), $arr);
	}

	/**
	 *  @since 1.1.2
	 * 	Export page tab content filter callback
	 **/
	public function out_settings_form($arr)
	{
		wp_enqueue_script($this->module_id, plugin_dir_url(__FILE__) . 'assets/js/main.js', array('jquery'), WP_MIGRATION_DUPLICATOR_VERSION);
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
				'connecting' => __("Connecting", 'wp-migration-duplicator'),
				'stopped' => __("Stopped", 'wp-migration-duplicator'),
				'stopping' => __("Stopping", 'wp-migration-duplicator'),
				'failedtostop' => __("Failed to stop export", 'wp-migration-duplicator'),
				'startnewexport' => __("Start new export", 'wp-migration-duplicator'),
				'choose_profile' => __("Please choose FTP profile", 'wp-migration-duplicator'),
				'specify_path' => __("Specify the export path", 'wp-migration-duplicator'),
				'zip_disable' => __("Before export Please enable ZipArchive extension in server", 'wp-migration-duplicator'),
			)
		);
		wp_localize_script($this->module_id, $this->module_id, $params);

		$view_file = plugin_dir_path(__FILE__) . 'views/exporter.php';
		$params = array();
		Wp_Migration_Duplicator_Admin::envelope_settings_tabcontent('wt-mgdp-export', $view_file, '', $params, 0);
	}


	/**
	 * Exlcude unwanted files/folders
	 * @since 1.0.0
	 */
	function exclude_unwanted_modules()
	{
		require_once  plugin_dir_path(__FILE__) . "wt-filetree.php";
		$wp_content = ABSPATH . 'wp-content';
		$folders =  new Wt_File_Tree($wp_content);
		echo '<div class="wt_exclude_folders" id ="wt_exclude_folders" >';
		echo $folders->php_file_tree($wp_content, $this->get_exclude_items());
		echo '</div>';
	}

	function add_export_module_css()
	{
		wp_register_style($this->module_id, plugin_dir_url(__FILE__) . 'assets/css/export.css', __FILE__);
		wp_enqueue_style($this->module_id);
	}
         public static function wt_gets_complete_file_path($dir) {
               $path = WP_CONTENT_DIR . '/' . $dir;
                $objects = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                foreach ($objects as $file => $object) {
                    $basename = $object->getBasename();
                    if ($basename == '.' or $basename == '..') {
                        continue;
                    }
                    if ($object->isDir()) {
                        continue;
                    }
                    $fileData[] = $object->getPathname();
                }
		return $fileData;
	}
         public static function wt_split_file_path($fileData) {
             
            $arr_count = count($fileData);
            $size_over_array = array();
            $size_less_array = array();
            $new_path = array();
            $sum_size = 0;
            $split_directory_size = apply_filters('wt_mgdp_export_directory_split_size',50000000);
            $split_directory_item_count = apply_filters('wt_mgdp_export_directory_split_count',1000);
            foreach ($fileData as $key => $value) {

                if (filesize($value) > $split_directory_size ) {
                    $size_over_array[] = $value;
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
                if ($key + 1 >= $arr_count) {
                    $size_less_array[] = $new_path;
                }
            }
            foreach ($size_over_array as $o_key => $o_value) {
                Webtoffe_logger::write_log( 'Export','File '. basename($o_value). ' ignored.Size- '.esc_html( size_format(filesize($o_value),0)));
            }
             
            return $size_less_array;
    }
    
      public static function wt_table_export_limit_desider($tables,&$mysqli) {
          
          $table_array = array();
          $new_arr = array();
          $sum_size = 0;
          $arr_count = count($tables);
          $row_count = apply_filters('wt_mgdp_export_table_row_count',1000);
          foreach ($tables as $key => $table) {
           
              $result = $mysqli->query('SELECT COUNT(*) FROM `'.$table.'` ');
              $rows = mysqli_fetch_row($result);
              $rowcount= $rows[0] ? $rows[0] :0;
                if ($rowcount >= $row_count ) {
                    $table_array[][] = $table;
                }else {
                    $sum_size = $sum_size + $rowcount;
                    if ($sum_size < $row_count) {
                        $new_arr[] = $table;
                    }else {
                        $table_array[] = $new_arr;
                        unset($new_arr, $sum_size);
                        $new_arr[] = $table;
                        $sum_size = $rowcount;
                    }

                } 
                 if ($key + 1 >= $arr_count) {
					$table_array[] = $new_arr;
					unset($new_arr);
                }
		  }
		  $table_array = array_filter($table_array);
            return $table_array;
          
      }

}
new Wp_Migration_Duplicator_Export();
