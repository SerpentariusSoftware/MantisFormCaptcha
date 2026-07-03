# MantisBT Form Captcha Plugin

**Version 1.0.1**

Adds a Cloudflare Turnstile, hCaptcha, or Google reCAPTCHA verification
challenge to MantisBT's login, registration, lost-password, and
password-change forms, as a bot-protection factor on top of the normal
credential check.

The challenge widget is injected client-side right next to each form's
submit button, and the response token is verified server-side (via the
chosen provider's `siteverify` API) before the underlying MantisBT action
script (`login.php`, `signup.php`, `lost_pwd.php`, `account_update.php`) is
allowed to run. If a request fails verification, MantisBT halts it with a
standard error page — the protected script's own logic never executes.

Requirements
============
* MantisBT 2.20.0 or higher. **Developed and tested against MantisBT 2.28.4.**
* PHP with the `curl` extension enabled (used to call the provider's
  `siteverify` endpoint).
* A site key + secret key from whichever provider(s) you intend to use:
  * [Cloudflare Turnstile](https://developers.cloudflare.com/turnstile/get-started/)
  * [hCaptcha](https://docs.hcaptcha.com/#getting-started)
  * [Google reCAPTCHA](https://www.google.com/recaptcha/admin/create) (v2 Checkbox)

Setup
=====
1. Clone/extract this repository into `mantis/plugins/FormCaptcha/` (the
   folder name **must** be `FormCaptcha` — it doubles as the plugin's
   basename).
2. In MantisBT, go to **Manage → Manage Plugins** and install/enable
   **Form Captcha**.
   * The "Manage Plugins" link only appears in the Manage menu for accounts
     at the Administrator access level (`manage_plugin_threshold`,
     default `ADMINISTRATOR`); if you don't see it, that's why.
3. Open the plugin's config page (linked from Manage Plugins, or directly at
   `plugin.php?page=FormCaptcha/config_page`) and:
   * Pick a **Captcha Provider** (Turnstile, hCaptcha, or reCAPTCHA).
   * Enter that provider's **Site Key** and **Secret Key**.
   * Toggle which forms should enforce it (Login, Registration, Password
     Change, Lost Password — all on by default).
4. Save. The widget appears immediately on the enabled forms; if a
   provider's site key is left blank, that form silently skips the
   widget/enforcement instead of locking users out.

Notes on specific forms
========================
* **Login**: MantisBT's default login flow is two steps — the username-only
  page (`login_page.php`) forwards to `login_password_page.php`, which is
  the page that actually submits the password to `login.php`. The widget is
  placed on the latter, since that's the one whose submission is verified.
* **Password change**: MantisBT combines profile editing (email, real name)
  and password changes into a single form/submit on the Account page. The
  widget appears there, but server-side enforcement only triggers when the
  submission actually includes a new password — a plain profile edit isn't
  blocked.

Configuration reference
========================
| Option | Description |
| --- | --- |
| Captcha Provider | `turnstile`, `hcaptcha`, or `recaptcha` |
| Turnstile / hCaptcha / reCAPTCHA Site Key | Public key, embedded in the widget markup |
| Turnstile / hCaptcha / reCAPTCHA Secret Key | Private key, used server-side against the provider's `siteverify` API |
| Enable on Login Form | Enforce on `login_password_page.php` → `login.php` |
| Enable on Registration Form | Enforce on `signup_page.php` → `signup.php` |
| Enable on Password Change Form | Enforce on `account_page.php` → `account_update.php` (only when a password is actually being changed) |
| Enable on Lost Password Form | Enforce on `lost_pwd_page.php` → `lost_pwd.php` |

Content-Security-Policy
========================
MantisBT ships a fairly strict default CSP (`default-src 'self'`, plus
explicit `script-src`/`style-src`/`img-src`). This plugin adds the chosen
provider's script/frame/XHR domains to that policy automatically — including
re-adding `'self'` to the `frame-src`/`connect-src` directives, since MantisBT
core never sets those itself and an explicit directive no longer falls back
to `default-src` per the CSP spec (omitting `'self'` there would silently
break unrelated same-origin requests on every page).

Translations
============
Included: English, Spanish, German, Hungarian (`lang/strings_*.txt`).
Provider/brand names and technical field labels ("Site Key", "Secret Key")
are intentionally left untranslated across all languages.

License
=======
GPL-2.0 (or later), same as MantisBT itself — see [LICENSE](LICENSE).
