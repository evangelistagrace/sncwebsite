<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

if( isset( $ftp_list ) && is_array( $ftp_list ) & !empty( $ftp_list )) {
	?>
	<table class="wp-list-table widefat fixed striped" style="margin-bottom:55px;">
	<thead>
		<tr>
			<th><?php _e( "Profile name",'wp-migration-duplicator' ); ?></th>
			<th><?php _e( "Server Host/IP",'wp-migration-duplicator' ); ?></th>
			<th><?php _e( "Actions",'wp-migration-duplicator' ); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php
	foreach( $ftp_list as $key =>$ftp_item ) {
		?>
		<tr>
			<td><?php echo $ftp_item['name']; ?></td>
			<td><?php echo $ftp_item['server']; ?></td>
			<td>
				<div class="wt_mgdp_data_dv">
					<span class="wt_mgdp_ftp_id"><?php echo $ftp_item['id']; ?></span>
					<span class="wt_mgdp_profilename"><?php echo $ftp_item['name']; ?></span>
					<span class="wt_mgdp_hostname"><?php echo $ftp_item['server']; ?></span>
					<span class="wt_mgdp_ftpuser"><?php echo $ftp_item['user_name']; ?></span>
					<span class="wt_mgdp_ftppassword"><?php echo $ftp_item['password']; ?></span>
					<span class="wt_mgdp_ftpport"><?php echo $ftp_item['port']; ?></span>
					<span class="wt_mgdp_ftpexport_path"><?php echo $ftp_item['export_path']; ?></span>
					<span class="wt_mgdp_ftpimport_path"><?php echo $ftp_item['import_path']; ?></span>
				</div>
				<a class="wt_mgdp_ftp_edit wt_mgdp_action_btn" data-id="<?php echo esc_attr($ftp_item['id']); ?>"><?php _e('Edit','wp-migration-duplicator');?></a> | <a class="wt_mgdp_ftp_delete wt_mgdp_action_btn" data-id="<?php echo esc_attr($ftp_item['id']); ?>"><?php _e('Delete','wp-migration-duplicator');?></a>
			</td>
		</tr>
		<?php	
	}
	?>
	</tbody>
	</table>
	<?php
} else {
	?>
	<h4 style="margin-bottom:55px;"><?php _e("No FTP profiles found.",'wp-migration-duplicator'); ?></h4>
	<?php
}