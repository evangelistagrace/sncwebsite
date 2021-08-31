(function( $ ) {
	'use strict';
	$(function() {

		$('.wt_mgdp_code_example_readmore').click(function(){
			if($('.wt_mgdp_code_example').is(':visible'))
			{
				$(this).html($(this).attr('data-more-text')); 
			}else
			{
				$(this).html($(this).attr('data-less-text'));
			}
			$('.wt_mgdp_code_example').toggle();
		});

		var export_find_replace=
	    {
	    	Set:function()
	    	{
	    		$(document).on('click','.wt_mgdp_export_find_replace_btn_add',function(){
	    			var htrw=$(this).parents('.wt_mgdp_export_find_replace_row');
	    			var clone_td=htrw.clone();
	    			clone_td.find('[name="find[]"]').val('');
	    			clone_td.find('[name="replace[]"]').val('');
	    			clone_td.appendTo('.wt_mgdp_export_find_replace_tb');
	    			htrw.find('.wt_mgdp_export_find_replace_btn_delete').show();
	    			$(this).hide();
	    		});
	    		$(document).on('click','.wt_mgdp_export_find_replace_btn_delete',function(){
	    			$(this).parents('.wt_mgdp_export_find_replace_row').remove();
	    		});
	    	}
	    }
	    export_find_replace.Set();

	    var wt_export=
	    {
	    	find_arr:new Array(),
			replace_arr:new Array(),
			exclude_arr: new Array(),
			ftp_profile : '',
			ftp_path : '',
			ftp_file:'',
			google_drive_filename: '',
			dropbox_filename: '',
			s3bucket_file_name:'',
	    	onPrg:0,
	    	stopNow:0,
	    	export_id:0,
			export_option:'local',
			
	    	Set:function()
	    	{

                                 $('[name="usrselectall"]').click(function(){// Iterate each checkbox
                                        $(document.querySelectorAll('#wt_exclude_folders ul:nth-child(1) > li > input[type=checkbox]')).prop('checked', true);
                                 });

                                $('[name="usrunselectall"]').click(function(){
                                    $('#wt_exclude_folders :checkbox').prop('checked', false);
                                });

	    		$('[name="wt_mgdp_export_btn"]').click(function(){	
                                        var extension_zip_loaded  =  $('input[name="extension_zip_loaded"]').val();
                                        var extension_zlib_loaded  =  $('input[name="extension_zlib_loaded"]').val();
                                        if(extension_zip_loaded=='disabled' && extension_zlib_loaded=='disabled'){ 
                                            wp_migration_duplicator_notify_msg.error(wp_migration_duplicator_export.labels.zip_disable);
                                            return true; 
                                        }
                            
					if(wt_export.onPrg==1){ return false; }
					
					var migration_option = $('select[name="wt_mgdb_export_option"]').val();
					if( 'ftp' == migration_option ) {
						var profile  =  $('select[name="wt_mgdb_export_ftp_profiles"]').val();
						var path  =  $('input[name="wt_mgdb_export_path"]').val();
						if( 0 == profile  ) { 
							wp_migration_duplicator_notify_msg.error( wp_migration_duplicator_export.labels.choose_profile );
							return false;
						} else if( '' == path ) {
							wp_migration_duplicator_notify_msg.error( wp_migration_duplicator_export.labels.specify_path );
							return false;
						}
					}
	    			wt_export.onPrg=1;
	    			wt_export.stopNow=0;
                                $(window).scrollTop(0);
	    			$('.spinner, [name="wt_mgdp_export_stop_btn"]').show();
	    			$('[name="wt_mgdp_export_btn"]').css({'opacity':'.5','cursor':'not-allowed'});
	    			wt_export.getFindandReplaceVal();
	    			wt_export.getExcludeArray();
					wt_export.getFTPDetailsArray();
					wt_export.getGoogleDriveDetailsArray();
					wt_export.getDropboxArray();
					wt_export.getAmazonS3Array();
					$('.wf_export_sub').show();
                                        $('.wf_export_loader').show();
					$('.wf_export_main').show();
					wt_export.updateProgressBar(0,0,wp_migration_duplicator_export.labels.connecting,wp_migration_duplicator_export.labels.connecting);
					wt_export.startExport(0,1,0,'start_export',0,10000);
				});
				$('[name="wt_mgdp_export_stop_btn"]').click(function(){
					if(confirm(wp_migration_duplicator_export.labels.sure))
					{
						wt_export.stopNow=1;
						wt_export.updateProgressBarlabel(wp_migration_duplicator_export.labels.stopping, wp_migration_duplicator_export.labels.stopping);
						wt_export.stopExport();
					}
				});
	    	},
	    	stopExport:function()
	    	{		
				var data={
					_wpnonce:wp_migration_duplicator_export.nonces.main,
		            action:"wt_mgdp_export",
		            sub_action:'stop_export',
		            export_id:this.export_id,
				};
				$('[name="wt_mgdp_export_stop_btn"]').hide();
                                $('.wf_export_sub').hide();
                                $('.wf_export_loader').hide();
                                $('.wf_export_main').hide();
				$.ajax({
					url:wp_migration_duplicator_export.ajax_url,
					type:'post',
					data:data,
            		dataType:'json',
            		success:function(data)
            		{
            			wt_export.resetOnprg();
            			if(data.status)
            			{
            				wt_export.updateProgressBarlabel(wp_migration_duplicator_export.labels.stopped,wp_migration_duplicator_export.labels.stopped);
            				$('[name="wt_mgdp_export_btn"]').html(wp_migration_duplicator_export.labels.startnewexport);
            			}else
            			{
            				wp_migration_duplicator_notify_msg.error(data.msg);
            				wt_export.updateProgressBarlabel(data.msg, data.msg);
            			}
            		},
            		error:function()
            		{
            			wt_export.resetOnprg();
            			wp_migration_duplicator_notify_msg.error(wp_migration_duplicator_export.labels.failedtostop);
            			wt_export.updateProgressBarlabel(wp_migration_duplicator_export.labels.failedtostop, wp_migration_duplicator_export.labels.failedtostop);
            		}
            	});
            },
            deleteExportFile:function()
	    	{	
                 
				var data={
					_wpnonce:wp_migration_duplicator_export.nonces.main,
		            action:"wt_mgdp_export",
		            sub_action:'delete_export_file',
		            export_id:this.export_id,
				};
   
				$.ajax({
					url:wp_migration_duplicator_export.ajax_url,
					type:'post',
					data:data,
            		dataType:'json',
            		success:function(data)
            		{
                            wt_export.resetOnprg();
            			if(data.status)
            			{
                                    $('.wf_export_sub').hide();
                                    $('.wf_export_loader').hide();
                                    $('.wf_export_main').hide();
            			}
            		},
            		error:function()
            		{
            			$('.wf_export_sub').hide();
                                $('.wf_export_loader').hide();
                                $('.wf_export_main').hide();
                         }
            	});
            },
            set_export_progress_info:function(msg)
            {
                    $('.wt_mgdp_loader_info_box').show().html(msg);
            },
            resetOnprg:function()
            {
    			wt_export.onPrg=0;
				$('.spinner, [name="wt_mgdp_export_stop_btn"]').hide();
				$('.wt_mgdp_export_find_replace_tb').show();
				$('[name="wt_mgdp_export_btn"]').css({'opacity':'1','cursor':'pointer'});
            },
	    	getFindandReplaceVal:function()
	    	{
	    		$('[name="find[]"]').each(function(){
	    			wt_export.find_arr.push($(this).val());
	    		});
	    		$('[name="replace[]"]').each(function(){
	    			wt_export.replace_arr.push($(this).val());
	    		});
	    		$('.wt_mgdp_export_find_replace_tb').hide();
	    	},
	    	resetSubProgressbar:function(sub_percent,sub_label)
	    	{
	    		wf_progress_bar.Reset(sub_percent,$('.wf_export_sub'),sub_label)
	    	},
	    	updateProgressBar:function(percent,sub_percent,label,sub_label)
	    	{
	    		wf_progress_bar.Set(percent,$('.wf_export_main'),label);
				wf_progress_bar.Set(sub_percent,$('.wf_export_sub'),sub_label);
	    	},
	    	updateProgressBarlabel:function(label,sub_label)
	    	{
	    		wf_progress_bar.updateLabel($('.wf_export_main'),label);
				wf_progress_bar.updateLabel($('.wf_export_sub'),sub_label);
			},
			getExcludeArray:function () {
				$('input[name="mgdp-exclude-file"]:checked').each(function() {
					wt_export.exclude_arr.push($(this).val());

				});
			},
			getFTPDetailsArray:function() {
				wt_export.export_option = $('select[name="wt_mgdb_export_option"]').val();
				if( 'ftp' === wt_export.export_option ) {
					wt_export.ftp_profile =  $('select[name="wt_mgdb_export_ftp_profiles"]').val();
					wt_export.ftp_path =   $('input[name="wt_mgdb_export_path"]').val();
					wt_export.ftp_file =   $('input[name="wt_mgdb_export_file"]').val();
					
					
				}
			},
			getGoogleDriveDetailsArray:function() {
				wt_export.export_option = $('select[name="wt_mgdb_export_option"]').val();
				if( 'googledrive' === wt_export.export_option ) {
					wt_export.google_drive_filename =   $('input[name="wt_mgdb_google_drive_file_name"]').val();
					
				}
			},
			getDropboxArray:function() {
				wt_export.export_option = $('select[name="wt_mgdb_export_option"]').val();
				if( 'dropbox' === wt_export.export_option ) {
					wt_export.dropbox_filename =   $('input[name="wt_mgdb_dropbox_file_name"]').val();
					
				}
			},
			getAmazonS3Array:function() {
				wt_export.export_option = $('select[name="wt_mgdb_export_option"]').val();
				if( 's3bucket' === wt_export.export_option ) {
					wt_export.s3bucket_file_name =   $('input[name="wt_mgdb_s3bucket_file_name"]').val();
					
				}
			},
	    	startExport:function(offset,limit,export_id,sub_action,table_offset,table_limit)
	    	{
		        this.export_id=export_id;
				var data={
					_wpnonce:wp_migration_duplicator_export.nonces.main,
		            action:"wt_mgdp_export",
		            offset:offset,
		            limit:limit,
                            t_offset:table_offset,
		            t_limit:table_limit,
					sub_action:sub_action,
		            find:this.find_arr,
					replace:this.replace_arr,
					exclude:this.exclude_arr,
					export_option:this.export_option,
					ftp_profile:this.ftp_profile,
					ftp_path:this.ftp_path,
					ftp_file:this.ftp_file,
					google_drive_file_name:this.google_drive_filename,
					dropbox_file_name:this.dropbox_filename,
					s3bucket_file_name:this.s3bucket_file_name,
					export_id:export_id,
				};

				$.ajax({
					url:wp_migration_duplicator_export.ajax_url,
					type:'post',
					data:data,
            		dataType:'json',
            		success:function(data)
            		{	
            			if(data.status)
            			{
            				if(wt_export.stopNow==1)
            				{
            					wt_export.updateProgressBarlabel(wp_migration_duplicator_export.labels.stopped,wp_migration_duplicator_export.labels.stopped);
            					return false;
            				}
            				wt_export.updateProgressBar(data.percent,data.sub_percent,data.percent_label,data.sub_percent_label);
            				if(data.step_finished==1 && data.finished==0) /* reset the sub progress bar. Prevent resetting when all done  */
            				{
            					setTimeout(function(){
            						wt_export.resetSubProgressbar(0,wp_migration_duplicator_export.labels.connecting);
            					},300);
            				}
            				if(data.finished==0)
            				{
            					wt_export.startExport(data.offset,data.limit,data.export_id,data.step,data.t_offset,data.t_limit);
            				}else
            				{
            					wt_export.resetOnprg();
            					$('.wf_export_sub').hide();
                                                $('.wf_export_loader').hide();
								$('[name="wt_mgdp_export_btn"]').html(wp_migration_duplicator_export.labels.startnewexport);
								if( 'local' === data.export_option ) {
									 wt_export.set_export_progress_info(data.msg);
								}
            				}
            			}else
            			{
            				wp_migration_duplicator_notify_msg.error(data.msg);
            			}
            		},
            		error:function()
            		{
                                wt_export.deleteExportFile();
            			wp_migration_duplicator_notify_msg.error(wp_migration_duplicator_export.labels.error);
            		}
				});
	    	}
	    }
		wt_export.Set();


		var wt_export_exclude = {
			Set:function() {
			$(".mgdp-file-tree").find("ul").hide();
			
			$(".mgdp-directory a").click( function( e ) {
				e.preventDefault();
				if( $(this).prev('input[name="mgdp-exclude-file"]').is(":checked") ) {
					return;
				}
				$(this).parent().find("ul:first").slideToggle("medium");
				$(this).closest('.mgdp-directory').toggleClass('active');
				if( $(this).parent().attr('className') == "mgdp-directory" ) return false;
			});


			$('.mgdp-directory > input').on('change',function() {
				if( $(this).is(":checked") ) {
					$(this).parent().find("ul:first").hide("medium");
					$(this).parent().find("ul:first input").attr('disabled','disabled');
					$(this).next('a').css('cursor','default');
					$(this).parent().find("ul:first input").removeAttr('checked');

				} else {
					$(this).parent().find("ul:first input").removeAttr('disabled');
					$(this).next('a').css('cursor','pointer');
				}
			});
			}
		}
		wt_export_exclude.Set();

	});
})(jQuery);