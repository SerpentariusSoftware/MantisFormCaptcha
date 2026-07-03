<?php
/**
 * MantisBT - A PHP based bugtracking system
 *
 * MantisBT is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * MantisBT is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Form Captcha Plugin
 *
 * Adds a Cloudflare Turnstile / hCaptcha / Google reCAPTCHA verification
 * challenge to the login, registration, lost password and password change
 * forms, as a bot-protection factor on top of the credential check.
 *
 * The challenge widget is injected client-side into the relevant form,
 * and the response token is verified server-side (via the provider's
 * siteverify API) before the underlying MantisBT action script
 * (login.php, signup.php, lost_pwd.php, account_update.php) is allowed
 * to run.
 */
class FormCaptchaPlugin extends MantisPlugin {
	/**
	 * Supported captcha providers and everything specific to each one:
	 * the widget's CSS class, the POST field the client script populates
	 * with the response token, the client script and siteverify URLs, the
	 * plugin config option names holding the site/secret key, and the CSP
	 * allowances the provider's script/iframe/XHR calls need.
	 */
	private static $providers = array(
		'turnstile' => array(
			'widget_class' => 'cf-turnstile',
			'response_field' => 'cf-turnstile-response',
			'script_url' => 'https://challenges.cloudflare.com/turnstile/v0/api.js',
			'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
			'site_key_option' => 'turnstile_site_key',
			'secret_key_option' => 'turnstile_secret_key',
			'csp' => array(
				'script-src' => array( 'https://challenges.cloudflare.com' ),
				'frame-src' => array( "'self'", 'https://challenges.cloudflare.com' ),
				'connect-src' => array( "'self'", 'https://challenges.cloudflare.com' ),
			),
		),
		'hcaptcha' => array(
			'widget_class' => 'h-captcha',
			'response_field' => 'h-captcha-response',
			'script_url' => 'https://js.hcaptcha.com/1/api.js',
			'verify_url' => 'https://hcaptcha.com/siteverify',
			'site_key_option' => 'hcaptcha_site_key',
			'secret_key_option' => 'hcaptcha_secret_key',
			'csp' => array(
				'script-src' => array( 'https://js.hcaptcha.com', 'https://*.hcaptcha.com' ),
				'style-src' => array( 'https://*.hcaptcha.com' ),
				'frame-src' => array( "'self'", 'https://*.hcaptcha.com' ),
				'connect-src' => array( "'self'", 'https://*.hcaptcha.com' ),
			),
		),
		'recaptcha' => array(
			'widget_class' => 'g-recaptcha',
			'response_field' => 'g-recaptcha-response',
			'script_url' => 'https://www.google.com/recaptcha/api.js',
			'verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
			'site_key_option' => 'recaptcha_site_key',
			'secret_key_option' => 'recaptcha_secret_key',
			'csp' => array(
				'script-src' => array( 'https://www.google.com', 'https://www.gstatic.com' ),
				'frame-src' => array( "'self'", 'https://www.google.com' ),
				'connect-src' => array( "'self'", 'https://www.google.com' ),
			),
		),
	);

	/**
	 * Pages that render a target form: script => [ form id, enable flag ].
	 * Hooked on GET to inject the widget markup + client script.
	 */
	private static $forms = array(
		# MantisBT's default login flow is two steps: login_page.php collects
		# only the username and forwards to login_password_page.php, which
		# is the page that actually posts the password to login.php. The
		# widget has to live on the latter.
		'login_password_page.php' => array(
			'form_id' => 'login-form',
			'flag' => 'enable_login',
		),
		'signup_page.php' => array(
			'form_id' => 'signup-form',
			'flag' => 'enable_register',
		),
		'lost_pwd_page.php' => array(
			'form_id' => 'lost-password-form',
			'flag' => 'enable_lost_password',
		),
		'account_page.php' => array(
			'form_id' => 'account-update-form',
			'flag' => 'enable_password_change',
		),
	);

	/**
	 * Scripts that process a target form submission: script => [ enable flag, ... ].
	 * Hooked on POST to verify the captcha response before the script runs.
	 */
	private static $actions = array(
		'login.php' => array(
			'flag' => 'enable_login',
		),
		'signup.php' => array(
			'flag' => 'enable_register',
		),
		'lost_pwd.php' => array(
			'flag' => 'enable_lost_password',
		),
		'account_update.php' => array(
			'flag' => 'enable_password_change',
			# account_update.php also handles profile-only edits (email, real
			# name); only require the captcha when a password change is
			# actually being submitted.
			'password_only' => true,
		),
	);

	/**
	 * Form id currently being buffered for widget injection, or null.
	 * @var string|null
	 */
	private $active_form = null;

	/**
	 * Widget markup computed while we're still inside the on_core_ready()
	 * hook call. This MUST be precomputed here rather than re-derived
	 * inside inject_widget(): that method runs as a raw ob_start() flush
	 * callback, invoked outside of MantisBT's plugin hook dispatch, so
	 * plugin_get_current() (and therefore plugin_config_get()) has no
	 * "current plugin" context at that point and would silently resolve
	 * to the wrong config keys.
	 * @var string
	 */
	private $cached_widget_html = '';

	/**
	 * A method that populates the plugin information and minimum requirements.
	 * @return void
	 */
	function register() {
		$this->name = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );
		$this->page = 'config_page';

		$this->version = '1.0.0';
		$this->requires = array(
			'MantisCore' => '2.20.0',
		);

		$this->author = 'MantisBT Local';
		$this->contact = 'admin@mantis.local';
		$this->url = 'https://mantisbt.org';
	}

	/**
	 * Default plugin configuration.
	 * @return array
	 */
	function config() {
		return array(
			# 'turnstile' (Cloudflare Turnstile), 'hcaptcha', or 'recaptcha'
			'provider' => 'turnstile',

			'turnstile_site_key' => '',
			'turnstile_secret_key' => '',

			'hcaptcha_site_key' => '',
			'hcaptcha_secret_key' => '',

			'recaptcha_site_key' => '',
			'recaptcha_secret_key' => '',

			'enable_login' => ON,
			'enable_register' => ON,
			'enable_password_change' => ON,
			'enable_lost_password' => ON,
		);
	}

	/**
	 * Custom error strings for this plugin.
	 * @return array
	 */
	function errors() {
		return array(
			'no_response' => 'Please complete the verification challenge before submitting the form.',
			'verify_failed' => 'We could not verify that you are human. Please try again.',
		);
	}

	/**
	 * Register event hooks for plugin.
	 * @return array
	 */
	function hooks() {
		return array(
			'EVENT_CORE_HEADERS' => 'on_core_headers',
			'EVENT_CORE_READY' => 'on_core_ready',
			'EVENT_LAYOUT_RESOURCES' => 'on_layout_resources',
		);
	}

	/**
	 * Allow the configured provider's domains through CSP.
	 * @return void
	 */
	function on_core_headers() {
		# MantisBT core never sets frame-src or connect-src itself (only
		# default-src, style-src, script-src, img-src). Per the CSP spec, an
		# explicit directive no longer falls back to default-src, so each
		# provider's CSP list includes 'self' where needed, or same-origin
		# requests/frames that used to be allowed via the default-src
		# fallback would get blocked.
		$t_provider = $this->provider_config( plugin_config_get( 'provider' ) );
		foreach( $t_provider['csp'] as $t_directive => $t_values ) {
			foreach( $t_values as $t_value ) {
				http_csp_add( $t_directive, $t_value );
			}
		}
	}

	/**
	 * Main dispatch: on GET to a form page, start buffering output so the
	 * widget can be injected; on POST to an action script, verify the
	 * captcha response before letting the script continue.
	 * @return void
	 */
	function on_core_ready() {
		$t_script = basename( parse_url( $_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH ) );
		$t_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

		# Form-rendering pages are not necessarily GET: login_password_page.php
		# is reached via a POST from the username step (and via GET when
		# auth_reauthenticate() redirects to it), so widget injection isn't
		# gated on request method here - only on the script matching.
		if( isset( self::$forms[$t_script] ) ) {
			$t_form = self::$forms[$t_script];
			$t_widget = ( ON == plugin_config_get( $t_form['flag'] ) ) ? $this->widget_markup() : '';
			if( $t_widget !== '' ) {
				$this->active_form = $t_form['form_id'];
				$this->cached_widget_html = $t_widget;
				ob_start( array( $this, 'inject_widget' ) );
			}
			return;
		}

		if( $t_method === 'POST' && isset( self::$actions[$t_script] ) ) {
			$t_action = self::$actions[$t_script];

			if( ON != plugin_config_get( $t_action['flag'] ) ) {
				return;
			}

			# Skip enforcement if the provider isn't configured yet, so an
			# incomplete setup doesn't lock everyone out.
			if( $this->widget_markup() === '' ) {
				return;
			}

			if( !empty( $t_action['password_only'] )
				&& trim( (string)( $_POST['password'] ?? '' ) ) === ''
			) {
				return;
			}

			$this->verify_or_block();
		}
	}

	/**
	 * ob_start() callback: inject the widget markup right before the
	 * submit button of the target form, so it renders inline with it
	 * rather than stacked above the form's fields. Each target form has
	 * exactly one submit button, and it's always the first one to appear
	 * in the page source, so matching (and replacing) just the first
	 * occurrence in the whole buffer is sufficient.
	 * @param string $p_buffer Buffered page output.
	 * @return string
	 */
	function inject_widget( $p_buffer ) {
		if( $this->active_form === null || $this->cached_widget_html === '' ) {
			return $p_buffer;
		}

		$t_pattern = '/(<input\b[^>]*\btype=["\']submit["\'][^>]*\/?>)/i';
		$t_result = preg_replace( $t_pattern, $this->cached_widget_html . '$1', $p_buffer, 1 );

		return $t_result !== null ? $t_result : $p_buffer;
	}

	/**
	 * EVENT_LAYOUT_RESOURCES hook: load the provider's client script, but
	 * only on pages where we're actually injecting a widget.
	 * @return string
	 */
	function on_layout_resources() {
		if( $this->active_form === null ) {
			return '';
		}

		$t_provider = $this->provider_config( plugin_config_get( 'provider' ) );
		return '<script src="' . $t_provider['script_url'] . '" async defer></script>' . "\n";
	}

	/**
	 * Look up a provider's definition, falling back to Turnstile for an
	 * unrecognized/unset config value.
	 * @param string $p_key Provider key ('turnstile', 'hcaptcha', 'recaptcha').
	 * @return array
	 */
	private function provider_config( $p_key ) {
		return self::$providers[$p_key] ?? self::$providers['turnstile'];
	}

	/**
	 * Build the widget markup for the configured provider.
	 * Returns '' if the provider's site key hasn't been configured.
	 * @return string
	 */
	private function widget_markup() {
		$t_provider = $this->provider_config( plugin_config_get( 'provider' ) );
		$t_site_key = plugin_config_get( $t_provider['site_key_option'] );
		if( is_blank( $t_site_key ) ) {
			return '';
		}

		# display:inline-block (rather than the div default of block) lets
		# the widget sit on the same line as the submit button it's
		# injected next to, instead of forcing a line break before it.
		# size=compact is used for the same reason: the default/normal
		# widget size is ~300px wide, which doesn't leave enough room next
		# to the submit button in these forms' ~450px-wide containers.
		$t_style = 'display:inline-block;vertical-align:middle;margin-right:10px;';

		return '<div class="' . $t_provider['widget_class'] . '" data-sitekey="'
			. string_attribute( $t_site_key ) . '" data-size="compact" style="' . $t_style . '"></div>';
	}

	/**
	 * Read the response token from the POST body and verify it with the
	 * provider; block the request (halting execution) on failure.
	 * @return void
	 */
	private function verify_or_block() {
		$t_provider = $this->provider_config( plugin_config_get( 'provider' ) );
		$t_token = trim( (string)( $_POST[$t_provider['response_field']] ?? '' ) );

		if( $t_token === '' ) {
			trigger_error( 'plugin_' . $this->basename . '_no_response', ERROR );
		}

		if( !$this->verify_token( $t_provider, $t_token ) ) {
			trigger_error( 'plugin_' . $this->basename . '_verify_failed', ERROR );
		}
	}

	/**
	 * Call the provider's siteverify endpoint.
	 * @param array  $p_provider Provider definition, from provider_config().
	 * @param string $p_token    Response token submitted by the client widget.
	 * @return bool True if the provider confirmed the challenge was solved.
	 */
	private function verify_token( array $p_provider, $p_token ) {
		$t_secret = plugin_config_get( $p_provider['secret_key_option'] );
		if( is_blank( $t_secret ) ) {
			# Not configured; caller already checked widget_markup() before
			# reaching here, this is just a defensive fallback.
			return true;
		}

		$t_fields = array(
			'secret' => $t_secret,
			'response' => $p_token,
		);
		if( !empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$t_fields['remoteip'] = $_SERVER['REMOTE_ADDR'];
		}

		if( !function_exists( 'curl_init' ) ) {
			error_log( 'FormCaptcha: PHP cURL extension is required to verify captcha responses.' );
			return false;
		}

		$t_curl = curl_init( $p_provider['verify_url'] );
		curl_setopt_array( $t_curl, array(
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => http_build_query( $t_fields ),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_SSL_VERIFYPEER => true,
		) );
		$t_response = curl_exec( $t_curl );
		$t_curl_error = curl_error( $t_curl );
		curl_close( $t_curl );

		if( $t_response === false ) {
			error_log( 'FormCaptcha: siteverify request failed: ' . $t_curl_error );
			return false;
		}

		$t_data = json_decode( $t_response, true );
		return is_array( $t_data ) && !empty( $t_data['success'] );
	}
}
