<div class="wrap">
	<h2>Carrot quest</h2>
	<div id="message"
		 class="updated notice is-dismissible" style="<?php if ( ! isset( $_REQUEST['carrotquest_plugin_form_submit'] ) || '' === $message ) { esc_attr_e( 'display:none' ); } ?>">
		<p><?php esc_html_e( $message ); ?></p>
	</div>
	<div class="notice notice-info">
		<p><?php esc_html_e( 'You can look up parameters "API Key", "API Secret" and "User Auth Key" in "Settings > Developers" section of your Carrot quest account administrative panel', 'carrotquest' ); ?></p>
	</div>
	<form method="post" action="plugins.php?page=carrotquest">
		<?php wp_nonce_field( 'carrotquest_plugin_settings', 'carrotquest_plugin_nonce' ); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'API Key', 'carrotquest' ); ?></th>
				<td><input type="text" class="regular-text code" name="carrotquest_api_key"
						   value="<?php echo esc_textarea( get_option( 'carrotquest_api_key' ) ); ?>"/></td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'API Secret', 'carrotquest' ); ?></th>
				<td><input type="text" class="regular-text code" name="carrotquest_api_secret" value="<?php echo esc_textarea( get_option( 'carrotquest_api_secret' ) ); ?>"/></td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'User Auth Key', 'carrotquest' ); ?></th>
				<td><input type="text" class="regular-text code" name="carrotquest_auth_key" value="<?php echo esc_textarea( get_option( 'carrotquest_auth_key' ) ); ?>"/></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'User authorization', 'carrotquest' ); ?></th>
				<td>
					<input type="checkbox" name="carrotquest_auth"
						<?php echo esc_textarea( get_option( 'carrotquest_auth' ) ? 'checked' : '' ); ?>/>
					<label for="carrotquest_auth"><?php esc_html_e( 'Send customer ID to Carrot quest as User ID', 'carrotquest' ); ?></label>
				</td>
			</tr>
		</table>

		<p class="submit">
			<input type="hidden" name="carrotquest_plugin_form_submit" value="submit"/>
			<input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes' ); ?>"/>
		</p>

	</form>
</div>
