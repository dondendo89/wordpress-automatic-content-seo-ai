# Changelog

## 1.0.1 – Security, Compliance & Marketplace Readiness
- Security: Added nonce verification to `ajax_bulk_status`.
- JS: Updated `assets/js/admin.js` to send nonce for bulk status.
- Maintenance: Fixed deactivation hook to clear `wgc_bulk_process_job` events.
- Uninstall: Removed `wgc_ai_studio_api_key` option for full cleanup.
- Branding: Removed all visible references to “Google” across PHP, docs, README, marketplace descriptions, and `.pot`.
- Docs: Refreshed `documentation/index.html` and README for clarity and neutrality.
- i18n: Updated `languages/wp-content-studio-ai.pot` with new generic strings.
- Packaging: Regenerated `wp-content-studio-ai.zip` including all changes.

## 1.0.0 – Initial Release
- Core content generation.
- Multi-language support.
- WooCommerce product descriptions.
- Bulk generation.
- Emoji & icons option.
- WordPress-native security and capability checks.