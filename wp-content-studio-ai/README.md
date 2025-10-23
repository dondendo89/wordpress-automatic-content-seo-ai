# WP Content Studio AI

Generate long, SEO-friendly descriptions (2000+ characters) for WordPress posts and pages using AI content APIs. Perfect for SEO optimization and content automation.

## Key Features
- **AI-Powered Content Generation**: Uses AI content APIs for high-quality content
- **SEO Optimized**: Generates 2000+ character descriptions for better SEO
- **Multi-Language Support**: Generate content in multiple languages
- **Bulk Processing**: Update multiple posts/pages at once with background processing
- **WooCommerce Integration**: Special product descriptions for e-commerce
- **Customizable Settings**: Control content generation with various options
- **Secure & Safe**: WordPress-native security with nonces and capability checks
- **Easy to Use**: Simple one-click generation from post editor

## Requirements
- WordPress 6.0+
- PHP 7.4+
- Google Gemini API key (get it at https://aistudio.google.com/u/7/api-keys)
- cURL extension enabled
- JSON extension enabled

## Compatibility
- **WordPress**: 6.0, 6.1, 6.2, 6.3, 6.4, 6.5+
- **PHP**: 7.4, 8.0, 8.1, 8.2, 8.3+
- **Themes**: Compatible with all WordPress themes
- **Plugins**: Tested with popular plugins (Yoast SEO, Elementor, WooCommerce)

## Installation
1. Zip the plugin folder `wp-content-studio-ai/`.
2. In WordPress Admin, go to Plugins → Add New → Upload Plugin.
3. Upload the zip, install, and activate.

## Setup
1. Go to Settings → Content Studio AI.
2. Paste your Google Gemini API key and click Save.
3. (Optional) Use the Bulk Update section to process multiple posts/pages.

## Usage (Per Post/Page)
- Edit any Post or Page.
- In the side meta box "Content Studio AI", click "Generate Long Description".
- The generated text (minimum 2000 characters) will be appended to the end of the content.

## Bulk Generation
- Settings → Content Studio AI → "Run Bulk Generation".
- Adjust batch size if needed. The tool skips already processed posts/pages.

## Notes
- The plugin appends content and adds a marker: `<!-- AI Generated Description -->`.
- Posts with meta `_wgc_generated` are considered processed.

## Troubleshooting

### Common Issues

**Plugin not working after activation:**
- Check if your server has cURL and JSON extensions enabled
- Verify your API key is valid and has sufficient credits
- Check WordPress error logs for any PHP errors

**Bulk generation not working:**
- Ensure you have selected post types in settings
- Check if posts already have generated content (use "Force regenerate" option)
- Verify your server can handle background processing

**Content not generating:**
- Verify your API key is correct
- Check if you have sufficient API credits
- Ensure your server can make external HTTP requests

### Error Messages
- **"Missing API key"**: Set your API key in Settings → Content Studio AI
- **"No posts found to process"**: Check post type selection and existing content
- **"API Error"**: Verify your API key and credits

## Uninstall
- Deleting the plugin via WordPress will remove all stored options and settings.

## Support & Licensing
- **License**: GPL-2.0-or-later
- **Support**: Check documentation or contact support
- **Updates**: Regular updates with new features and bug fixes

## Disclaimer
- This plugin is independent and not affiliated with any specific AI provider.
- You must comply with your AI provider’s API Terms.
- Content is generated via external API calls from your server.
- Ensure you have consent and comply with applicable laws when generating content.



