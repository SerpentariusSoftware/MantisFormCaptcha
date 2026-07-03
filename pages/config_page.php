<?php
# Form Captcha Plugin - Configuration Page

access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

layout_page_header( plugin_lang_get( 'config_title' ) );

layout_page_begin( 'manage_overview_page.php' );

print_manage_menu( 'manage_plugin_page.php' );

$t_provider = plugin_config_get( 'provider' );
?>

<div class="col-md-12 col-xs-12">
<div class="space-10"></div>
<div class="form-container">
<form action="<?php echo plugin_page( 'config' ) ?>" method="post">
<fieldset>
<div class="widget-box widget-color-blue2">
<div class="widget-header widget-header-small">
	<h4 class="widget-title lighter">
		<?php print_icon( 'fa-shield', 'ace-icon' ); ?>
		<?php echo plugin_lang_get( 'config_title' ) ?>
	</h4>
</div>

<?php echo form_security_field( 'plugin_FormCaptcha_config' ) ?>
<div class="widget-body">
<div class="widget-main no-padding">
<div class="table-responsive">
<table class="table table-bordered table-condensed table-striped">

	<tr>
		<td class="category"><?php echo plugin_lang_get( 'provider' ) ?></td>
		<td>
			<select id="provider" name="provider" class="input-sm">
				<option value="turnstile" <?php echo $t_provider == 'turnstile' ? 'selected' : ''; ?>><?php echo plugin_lang_get( 'provider_turnstile' ) ?></option>
				<option value="hcaptcha" <?php echo $t_provider == 'hcaptcha' ? 'selected' : ''; ?>><?php echo plugin_lang_get( 'provider_hcaptcha' ) ?></option>
				<option value="recaptcha" <?php echo $t_provider == 'recaptcha' ? 'selected' : ''; ?>><?php echo plugin_lang_get( 'provider_recaptcha' ) ?></option>
			</select>
		</td>
	</tr>

	<tr>
		<td class="category"><?php echo plugin_lang_get( 'turnstile_site_key' ) ?></td>
		<td><input type="text" name="turnstile_site_key" class="input-sm" size="48"
			value="<?php echo string_attribute( plugin_config_get( 'turnstile_site_key' ) ) ?>" /></td>
	</tr>
	<tr>
		<td class="category"><?php echo plugin_lang_get( 'turnstile_secret_key' ) ?></td>
		<td><input type="password" name="turnstile_secret_key" class="input-sm" size="48"
			value="<?php echo string_attribute( plugin_config_get( 'turnstile_secret_key' ) ) ?>" autocomplete="off" /></td>
	</tr>

	<tr>
		<td class="category"><?php echo plugin_lang_get( 'hcaptcha_site_key' ) ?></td>
		<td><input type="text" name="hcaptcha_site_key" class="input-sm" size="48"
			value="<?php echo string_attribute( plugin_config_get( 'hcaptcha_site_key' ) ) ?>" /></td>
	</tr>
	<tr>
		<td class="category"><?php echo plugin_lang_get( 'hcaptcha_secret_key' ) ?></td>
		<td><input type="password" name="hcaptcha_secret_key" class="input-sm" size="48"
			value="<?php echo string_attribute( plugin_config_get( 'hcaptcha_secret_key' ) ) ?>" autocomplete="off" /></td>
	</tr>

	<tr>
		<td class="category"><?php echo plugin_lang_get( 'recaptcha_site_key' ) ?></td>
		<td><input type="text" name="recaptcha_site_key" class="input-sm" size="48"
			value="<?php echo string_attribute( plugin_config_get( 'recaptcha_site_key' ) ) ?>" /></td>
	</tr>
	<tr>
		<td class="category"><?php echo plugin_lang_get( 'recaptcha_secret_key' ) ?></td>
		<td><input type="password" name="recaptcha_secret_key" class="input-sm" size="48"
			value="<?php echo string_attribute( plugin_config_get( 'recaptcha_secret_key' ) ) ?>" autocomplete="off" /></td>
	</tr>

	<?php
	$t_flags = array(
		'enable_login',
		'enable_register',
		'enable_password_change',
		'enable_lost_password',
	);
	foreach( $t_flags as $t_flag ) {
		$t_value = plugin_config_get( $t_flag );
		?>
	<tr>
		<td class="category"><?php echo plugin_lang_get( $t_flag ) ?></td>
		<td>
			<select id="<?php echo $t_flag ?>" name="<?php echo $t_flag ?>" class="input-sm">
				<option value="1" <?php echo $t_value == ON ? 'selected' : ''; ?>><?php echo plugin_lang_get( 'option_on' ) ?></option>
				<option value="0" <?php echo $t_value == OFF ? 'selected' : ''; ?>><?php echo plugin_lang_get( 'option_off' ) ?></option>
			</select>
		</td>
	</tr>
	<?php } ?>

</table>
</div>
</div>
<div class="widget-toolbox padding-8 clearfix">
	<input type="submit" class="btn btn-primary btn-white btn-round" value="<?php echo plugin_lang_get( 'action_update' ) ?>" />
</div>
</div>
</div>
</fieldset>
</form>
</div>
</div>

<?php
layout_page_end();
