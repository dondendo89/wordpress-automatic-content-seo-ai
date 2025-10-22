# WP Gemini Content Generator

Generate long, SEO-friendly descriptions (2000+ characters) for WordPress posts and pages using Google Gemini.

## Features
- Per-post/page button to generate and insert a long description using the title
- Settings page to store Gemini API key (password field)
- Bulk generation from Settings with adjustable batch size
- Secure nonces, capability checks, and WordPress-native AJAX
- English-only strings, ready for localization via `Text Domain: wp-gemini-content-generator`

## Requirements
- WordPress 6.0+
- PHP 7.4+
- A valid Google Gemini API key

## Installation
1. Zip the plugin folder `wp-gemini-content-generator/`.
2. In WordPress Admin, go to Plugins → Add New → Upload Plugin.
3. Upload the zip, install, and activate.

## Setup
1. Go to Settings → Gemini Generator.
2. Paste your Gemini API key and click Save.
3. (Optional) Use the Bulk Update section to process multiple posts/pages.

## Usage (Per Post/Page)
- Edit any Post or Page.
- In the side meta box "Gemini Content Generator", click "Generate Long Description".
- The generated text (minimum 2000 characters) will be appended to the end of the content.

## Bulk Generation
- Settings → Gemini Generator → "Run Bulk Generation".
- Adjust batch size if needed. The tool skips already processed posts/pages.

## Notes
- The plugin appends content and adds a marker: `<!-- Gemini Generated Description -->`.
- Posts with meta `_wgc_generated` are considered processed.

## Uninstall
- Deleting the plugin via WordPress will remove the stored option `wgc_gemini_api_key`.

## Support & Licensing
- License: GPL-2.0-or-later.
- Include this README and the license when distributing.



