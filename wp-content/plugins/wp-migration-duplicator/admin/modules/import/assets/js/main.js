(function( $ ) {
	'use strict';
	$(function() {
		
		$('#upload-btn').click(function (e) {
            e.preventDefault();
            var image = wp.media({
                title: 'Upload backup file',
                multiple: false
            }).open()
                .on('select', function (e) {
                    var uploaded_image = image.state().get('selection').first();
                    var attachment_url = uploaded_image.toJSON().url;
                    var ext_arr=attachment_url.split('.');
                    var ext=ext_arr[ext_arr.length-1];
                    if(ext!="zip")
                    {
                    	wp_migration_duplicator_notify_msg.error(wp_migration_duplicator_import.labels.onlyzipfile);
                    	return false;
                    }
                    $('.wt_mgdp_import_attachment_url').html(attachment_url).css({'display':'block'});
                    $('[name="attachment_url"').val(attachment_url);
                    $('.wt_mgdp_import_er').hide().find('td').html('');
                });
        });

	    var wt_import=
	    {
	    	onPrg:0,
	    	Set:function()
	    	{
	    		$('[name="wt_mgdp_import_btn"]').click(function(){
					var extension_zip_loaded_imp  =  $('input[name="extension_zip_loaded_imp"]').val();
                                        var extension_zlib_loaded_imp  =  $('input[name="extension_zlib_loaded_imp"]').val();
					if(extension_zip_loaded_imp=='disabled' && extension_zlib_loaded_imp=='disabled'){ 
						wp_migration_duplicator_notify_msg.error(wp_migration_duplicator_import.labels.zip_disable);
						return true; 
					}
					if(wt_import.onPrg==1){ return false; }
					
					var import_method = $('select[name="wt_mgdb_import_option"]').val();
					if( 'local' == import_method ) {
						if($.trim($('[name="attachment_url"]').val())=='')
						{
							$('.wt_mgdp_import_er').show().find('td').html(wp_migration_duplicator_import.labels.backupfilenotempty);
							return false;
						}
					} else if( 'ftp' == import_method ) {
						var profile = $('select[name="wt_mgdb_import_ftp_profiles"]').val();
						var path = $('input[name="wt_mgdb_import_path"]').val();
						var ftp_file = $('input[name="wt_mgdb_import_ftp_file"]').val();
						if( 0 == profile ) {
							$('.wt_mgdp_import_er').show().find('td').html(wp_migration_duplicator_import.labels.noprofile);
							return false;
						}
						if( '' == path ) {
							$('.wt_mgdp_import_er').show().find('td').html(wp_migration_duplicator_import.labels.pathrequired);
							return false;
						}
					} else if( 'googledrive' == import_method ) {
						var filename = $('input[name="wt_mgdb_google_drive_file"]').val();
						if( '' == filename ) {
							$('.wt_mgdp_import_er').show().find('td').html(wp_migration_duplicator_import.labels.nofilename);
							return false;
						}
					}
					else if( 's3bucket' == import_method ) {
						var filename = $('input[name="wt_mgdb_s3bucket_file"]').val();
						if( '' == filename ) {
							$('.wt_mgdp_import_er').show().find('td').html(wp_migration_duplicator_import.labels.nofilename);
							return false;
						}
					}
					
	    			
	    			wt_import.onPrg=1;
	    			$('.spinner').css({'visiblity':'visible'});
	    			$('[name="wt_mgdp_import_btn"]').css({'opacity':'.5','cursor':'not-allowed'});
					$('.wt_mgdp_import_log_main, .wt_mgdp_import_loglist_main').show();
                                        $('.wf_import_loader').show();
					$('.wt_mgdp_import_form, .wt_info_box').hide();
					$('.wt_mgdp_import_loglist_inner').html('');
					wt_import.updateLog(wp_migration_duplicator_import.labels.connecting,wp_migration_duplicator_import.labels.connecting);
					wt_import.startImport('fetch_file',0,1);

				});

				$('.wt_mgdp__start_new_import').click(function(){
					$('.wt_mgdp_import_attachment_url, .wt_mgdp_import_loglist_inner').html('');
					$('.wt_mgdp_import_log_main, .wt_mgdp_import_loglist_main, .wt_mgdp__start_new_import').hide();
					$('.wt_mgdp_import_form, .wt_info_box').show();
					$('[name="wt_mgdp_import_btn"]').css({'opacity':1,'cursor':'pointer'}).show();
				});
	    	},
	    	updateLog:function(label,sub_label)
	    	{
	    		$('.wt_mgdp_import_log_main').html(label);
	    		$('.wt_mgdp_import_loglist_inner').append(sub_label);
	    	},
	    	restoreImportScreen:function()
	    	{
	    		wt_import.onPrg=0;
				$('.wt_mgdp__start_new_import').show();
				$('[name="wt_mgdp_import_btn"]').hide();   
                                $('.wf_import_loader').hide();
				$('.spinner').css({'visiblity':'hidden'});
	    	},
	    	startImport:function(sub_action,offset,limit)
	    	{
	    		var data={
					_wpnonce:wp_migration_duplicator_import.nonces.main,
		            action:"wt_mgdp_import",
		            sub_action:sub_action,
					attachment_url:$('[name="attachment_url"').val(),
					import_method: 	$('select[name="wt_mgdb_import_option"]').val(),
					ftp_profile: $('select[name="wt_mgdb_import_ftp_profiles"]').val(),
					ftp_path:$('input[name="wt_mgdb_import_path"').val(),
					ftp_file:$('input[name="wt_mgdb_import_ftp_file"').val(),
					google_drive_file:$('input[name="wt_mgdb_google_drive_file"').val(),
					wt_mgdb_dropbox_file:$('input[name="wt_mgdb_dropbox_file"').val(),
					wt_mgdb_s3bucket_file:$('input[name="wt_mgdb_s3bucket_file"').val(),
                                        offset :offset,
                                        limit:limit,

				};
				$.ajax({
					url:wp_migration_duplicator_import.ajax_url,
					type:'post',
					data:data,
            		dataType:'json',
            		success:function(data)
            		{	
            			wt_import.updateLog(data.label,data.sub_label);
            			if(data.status)
            			{
            				if(data.finished==0)
            				{
            					wt_import.startImport(data.step,data.offset,data.limit);
            				}else
            				{	
                                                $('.wf_import_loader').hide();
            					wt_import.restoreImportScreen();
            				}
            			}else
            			{
            				wp_migration_duplicator_notify_msg.error(data.msg);
            				wt_import.restoreImportScreen();
            			}
            		},
            		error:function()
            		{
//            			wt_import.restoreImportScreen();
//                                 $('.wt_mgdp_import_log_main, .wt_mgdp_import_loglist_main').hide();
//                                 $('.wt_mgdp_import_form, .wt_info_box').show();
            			wp_migration_duplicator_notify_msg.error(wp_migration_duplicator_export.labels.error);
            		}
				});
	    	}
	    }
	   	wt_import.Set(); 
	});
})(jQuery);