# Changelog

## 1.0.4

- Fixes the 1.0.3 Turnstile desktop-sizing change never actually taking
  effect: it used an inline `<script>`, which MantisBT's default CSP
  (`script-src 'self'`, no `'unsafe-inline'`, no nonce) silently blocks.
  The size-detection logic now ships as a same-origin plugin file
  (`files/turnstile-size.js`, served via `plugin_file()`), which
  `script-src 'self'` already allows.

## 1.0.3

- Cloudflare Turnstile widget now switches from `compact` (150x140, suited
  to narrow/mobile layouts) to `normal` (300x65) on viewports at least
  768px wide, instead of always rendering as `compact`. Fixes the widget
  looking like an oddly tall block next to the submit button on desktop.
  Only affects the Turnstile provider; hCaptcha and reCAPTCHA are
  unchanged.

## 1.0.2

- Fixes `verify.php` (the account-activation / lost-password-reset
  confirmation page, which posts to `account_update.php`) missing the
  captcha widget entirely: it wasn't in the plugin's list of pages to
  inject into, so users landing there from a signup or lost-password email
  would get blocked by `account_update.php`'s enforcement with no widget
  to solve. Also handles that page's submit control being a `<button>`
  rather than an `<input>`, which the injection logic didn't previously
  match.

## 1.0.1

- Updates plugin author and website metadata.

## 1.0.0

Initial release.

- Adds a captcha verification widget to MantisBT's login, registration,
  lost-password, and password-change forms.
- Supports three providers, selectable per-site: Cloudflare Turnstile,
  hCaptcha, and Google reCAPTCHA (v2 Checkbox).
- Widget renders inline next to each form's submit button (compact size)
  rather than stacked above the form.
- Server-side verification against the provider's `siteverify` API before
  the underlying action script (`login.php`, `signup.php`, `lost_pwd.php`,
  `account_update.php`) is allowed to run.
- Password-change enforcement only triggers when a password is actually
  being submitted, not on a plain profile edit (email/real name), since
  MantisBT shares one form/submit for both.
- Per-form enable/disable toggles, plus automatic skip (no widget, no
  enforcement) when a provider's site key hasn't been configured yet, so an
  incomplete setup can't lock users out.
- Automatically extends MantisBT's Content-Security-Policy header with the
  configured provider's script/frame/connect domains (including restoring
  `'self'` on directives MantisBT core doesn't set itself).
- Admin configuration page under Manage Plugins for provider selection, site
  and secret keys, and per-form toggles.
- Translations: English, Spanish, German, Hungarian.

