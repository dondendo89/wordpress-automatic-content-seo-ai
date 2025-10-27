# WP Gemini Content Generator v2.0.0

Professional AI-powered content generation plugin for WordPress using Google Gemini API.

## 🚀 Features

- **AI Content Generation**: Generate high-quality, SEO-optimized content using Google Gemini API
- **Meta Descriptions**: Create compelling meta descriptions for better SEO
- **Tag Generation**: Generate relevant tags automatically
- **Excerpt Creation**: Create engaging excerpts for posts
- **Bulk Processing**: Process multiple posts at once with background jobs
- **Gutenberg Integration**: Native Gutenberg editor support
- **Multi-language Support**: Generate content in multiple languages
- **WooCommerce Compatible**: Works with WooCommerce products
- **Professional UI**: Clean, modern admin interface

## 📋 Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- Google Gemini API key

## 🔧 Installation

1. Upload the plugin files to `/wp-content/plugins/wp-gemini-content-generator/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings > Gemini Content to configure your API key
4. Start generating content!

## ⚙️ Configuration

### API Key Setup
1. Get your API key from [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Enter the API key in Settings > Gemini Content
3. Configure your preferred language and content length

### Post Types
Select which post types to enable the content generator for:
- Posts
- Pages
- Custom Post Types
- WooCommerce Products

### Content Settings
- **Content Length**: Target word count (500-5000 words)
- **Excerpt Length**: Target character count (10-200 characters)
- **SEO Focus**: Comma-separated keywords to focus on
- **Batch Size**: Number of posts to process in bulk operations

## 🎯 Usage

### Individual Post Generation
1. Edit any post/page/product
2. Look for the "AI Content Generator" meta box
3. Click any generation button:
   - **Generate Content**: Creates full article content
   - **Generate Meta Description**: Creates SEO meta description
   - **Generate Tags**: Creates relevant tags
   - **Generate Excerpt**: Creates post excerpt
   - **Generate All**: Generates all content types at once

### Bulk Generation
1. Go to Settings > Gemini Content > Bulk Generation
2. Select post types and generation options
3. Choose batch size and force regenerate option
4. Click "Start Bulk Generation"
5. Monitor progress in real-time

### Gutenberg Integration
1. Open any post in the Gutenberg editor
2. Look for the "AI Content Generator" sidebar panel
3. Use the generation buttons directly in the editor
4. Content updates automatically without page refresh

## 🔒 Security Features

- **Nonce Verification**: All AJAX requests are protected with nonces
- **Capability Checks**: Only users with appropriate permissions can generate content
- **Input Sanitization**: All user inputs are properly sanitized
- **Output Escaping**: All outputs are properly escaped
- **API Key Protection**: API keys are stored securely

## 🌐 Multi-language Support

The plugin supports content generation in multiple languages:
- English (en)
- Italian (it)
- Spanish (es)
- French (fr)
- German (de)

## 🔌 Compatibility

### WordPress
- WordPress 6.0+
- Classic Editor
- Gutenberg Editor
- Block Editor

### Plugins
- Yoast SEO
- RankMath
- WooCommerce
- Elementor
- Most popular WordPress plugins

### Themes
- All WordPress themes
- Custom themes
- Page builders

## 📊 Performance

- **Background Processing**: Bulk operations run in the background
- **Batch Processing**: Large operations are split into manageable batches
- **Caching**: Efficient caching of API responses
- **Database Optimization**: Optimized database queries and table structure

## 🛠️ Development

### Code Structure
```
wp-gemini-content-generator/
├── wp-gemini-content-generator.php    # Main plugin file
├── includes/                          # Core classes
│   ├── class-wgc-core.php           # Core functionality
│   ├── class-wgc-admin.php          # Admin interface
│   ├── class-wgc-api.php            # API handling
│   ├── class-wgc-bulk-processor.php # Bulk processing
│   └── class-wgc-gutenberg.php      # Gutenberg integration
├── assets/                          # Frontend assets
│   ├── css/                        # Stylesheets
│   └── js/                         # JavaScript files
└── languages/                       # Translation files
```

### Hooks and Filters
- `wgc_before_content_generation`: Modify content before generation
- `wgc_after_content_generation`: Modify content after generation
- `wgc_api_response`: Modify API response
- `wgc_bulk_job_options`: Modify bulk job options

## 📝 Changelog

### Version 2.0.0
- Complete code refactoring for better maintainability
- Improved security with proper nonce verification
- Enhanced error handling and logging
- Better Gutenberg integration
- Optimized bulk processing
- Cleaner admin interface
- Improved performance and stability

## 🆘 Support

For support and documentation, please visit our support page.

## 📄 License

This plugin is licensed under the GPL-2.0-or-later license.

## 🙏 Credits

- Google Gemini API for AI content generation
- WordPress community for inspiration and support
