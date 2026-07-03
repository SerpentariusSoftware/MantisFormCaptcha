<?php
# Form Captcha Plugin - Configuration Save Handler

form_security_validate( 'plugin_FormCaptcha_config' );
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

/**
 * Sets plugin config option if value is different from current/default
 * @param string $p_name  option name
 * @param mixed  $p_value value to set
 * @return void
 */
function config_set_if_needed( $p_name, $p_value ) {
	if( $p_value != plugin_config_get( $p_name ) ) {
		plugin_config_set( $p_name, $p_value );
	}
}

$t_provider = gpc_get_string( 'provider', 'turnstile' );
if( !in_array( $t_provider, array( 'turnstile', 'hcaptcha', 'recaptcha' ) ) ) {
	$t_provider = 'turnstile';
}
config_set_if_needed( 'provider', $t_provider );

config_set_if_needed( 'turnstile_site_key', trim( gpc_get_string( 'turnstile_site_key', '' ) ) );
config_set_if_needed( 'hcaptcha_site_key', trim( gpc_get_string( 'hcaptcha_site_key', '' ) ) );
config_set_if_needed( 'recaptcha_site_key', trim( gpc_get_string( 'recaptcha_site_key', '' ) ) );
config_set_if_needed( 'turnstile_secret_key', trim( gpc_get_string( 'turnstile_secret_key', '' ) ) );
config_set_if_needed( 'hcaptcha_secret_key', trim( gpc_get_string( 'hcaptcha_secret_key', '' ) ) );
config_set_if_needed( 'recaptcha_secret_key', trim( gpc_get_string( 'recaptcha_secret_key', '' ) ) );

$t_flags = array(
	'enable_login',
	'enable_register',
	'enable_password_change',
	'enable_lost_password',
);
foreach( $t_flags as $t_flag ) {
	config_set_if_needed( $t_flag, gpc_get_bool( $t_flag ) ? ON : OFF );
}

form_security_purge( 'plugin_FormCaptcha_config' );

$t_redirect_url = plugin_page( 'config_page', true );
print_header_redirect( $t_redirect_url );
