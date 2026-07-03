# Changelog

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

