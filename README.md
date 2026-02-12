# Featured Image Creator AI

Auto-generate stunning AI-powered featured images using OpenAI (DALL-E 3, GPT-4), Google Gemini, or Stability AI. Supports bulk generation, scheduling, and multiple formats.

![WordPress Plugin Version](https://img.shields.io/badge/version-1.0.3-blue.svg)
![WordPress Compatibility](https://img.shields.io/badge/wordpress-5.8%2B-blue.svg)
![PHP Version](https://img.shields.io/badge/php-7.4%2B-purple.svg)
![License](https://img.shields.io/badge/license-GPLv2%2B-green.svg)

## Features

- 🎨 **Multiple AI Providers** - Support for **OpenAI (DALL-E 3, GPT models)**, **Google Gemini**, and **Stability AI (Stable Diffusion 3, SeeDream)**
- 🔑 **Bring Your Own API Key** - Complete control and transparency over API usage
- ⚡ **Single & Batch Generation** - Generate images one at a time, or bulk regenerate your entire library
- ✨ **Bulk Regeneration** - Regenerate all featured images in one click
- 🎚️ **Image Quality Control** - Choose between Standard, HD, or Low quality generation (OpenAI)
- 🖼️ **Multiple Formats** - Support for PNG, JPG, and WEBP (All Providers)
- 🎯 **Customizable Prompts** - Tailor the image generation to your brand
- 🔒 **Secure** - API keys are encrypted before storage
- 📏 **Flexible Dimensions** - Configure image size (default: 1024x675px)
- ✅ **WordPress Standards** - Built following WordPress.org coding standards

## Supported Models & Options

| Provider | Models | Quality Options | Output Formats |
|----------|--------|-----------------|----------------|
| **OpenAI** | DALL-E 3, GPT Image 1, GPT Image 1 (Mini), GPT Image 1.5, GPT Image Latest | Standard, HD, Low | PNG, JPG, WEBP (Auto-converted) |
| **Stability AI** | Stable Diffusion 3, SeeDream 4.5 | N/A | PNG, JPG, WEBP (Native) |
| **Google Gemini** | Imagen 3.0 | N/A | PNG |

## Installation

### From WordPress.org (Recommended)

1. Go to **Plugins > Add New** in your WordPress admin
2. Search for "AI Featured Image Generator"
3. Click **Install Now** and then **Activate**

### Manual Installation

1. Download the latest release from [GitHub Releases](https://github.com/gunjanjaswal/Featured-Image-Creator-AI/releases)
2. Upload the plugin folder to `/wp-content/plugins/`
3. Activate the plugin through the **Plugins** menu in WordPress

### From Source

```bash
git clone https://github.com/gunjanjaswal/Featured-Image-Creator-AI.git
cd Featured-Image-Creator-AI
# Copy to your WordPress plugins directory
cp -r . /path/to/wordpress/wp-content/plugins/featured-image-creator-ai/
```

## Configuration

1. Navigate to **Settings > AI Featured Images**
2. Select your preferred AI provider
3. Get your API key:
   - **OpenAI**: [OpenAI Platform](https://platform.openai.com/api-keys)
   - **Google Gemini**: [Google AI Studio](https://aistudio.google.com/app/apikey)
   - **Stability AI**: [Stability AI Platform](https://platform.stability.ai/account/keys)
4. Enter your API key and save
5. (Optional) Customize the prompt template
6. Start generating images!

## Usage

### Generate for Single Post

1. Edit any post
2. Look for the **AI Featured Image** meta box in the sidebar
3. Click **Generate Featured Image**
4. Wait for the image to be generated and automatically set

### Bulk Generation

#### Method 1: Bulk Action

1. Go to **Posts > All Posts**
2. Select posts without featured images
3. Choose **Generate Featured Images** from bulk actions
4. Click **Apply**

#### Method 2: Dedicated Page

1. Go to **Tools > AI Featured Images**
2. View the count of posts without featured images
3. Click **Generate All Featured Images**
4. Monitor the progress as images are generated

## API Costs

This plugin supports multiple AI providers. Pricing varies:

### OpenAI DALL-E 3
- **Standard Quality**: ~$0.04 per image (1024x1024)
- **HD Quality**: ~$0.08 per image (1024x1024)
- [Current Pricing](https://openai.com/pricing)

### Google Gemini (Imagen)
- Check [Google AI Studio](https://aistudio.google.com/) for current pricing
- Free tier available with limitations

### Stability AI (Stable Diffusion 3)
- ~$0.04 per image
- [Current Pricing](https://platform.stability.ai/pricing)

The plugin uses standard quality by default for all providers.

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- API key from your chosen provider (OpenAI, Google Gemini, or Stability AI)
- `openssl` PHP extension (for API key encryption)

## File Structure

```
ai-featured-image-generator/
├── ai-featured-image-generator.php  # Main plugin file
├── includes/
│   ├── class-admin-notices.php      # Admin notification system
│   ├── class-bulk-generator.php     # Batch processing
│   ├── class-image-generator.php    # Core image generation
│   ├── class-post-meta-box.php      # Post editor integration
│   ├── class-security.php           # Security utilities
│   ├── class-settings.php           # Settings page
│   └── api/
│       ├── class-api-interface.php     # API provider interface
│       ├── class-openai-provider.php   # OpenAI DALL-E 3
│       ├── class-gemini-provider.php   # Google Gemini (Imagen)
│       └── class-stability-provider.php # Stability AI (SD3)
├── assets/
│   ├── css/
│   │   └── admin.css                # Admin styles
│   └── js/
│       └── admin.js                 # Admin JavaScript
├── readme.txt                        # WordPress.org readme
└── README.md                         # This file
```

## Hooks & Filters

### Filters

```php
// Modify the prompt before sending to API
add_filter('aifig_image_prompt', function($prompt, $post_id) {
    // Your custom logic
    return $prompt;
}, 10, 2);

// Modify image dimensions
add_filter('aifig_image_dimensions', function($dimensions) {
    return array('width' => 1200, 'height' => 800);
});
```

### Actions

```php
// After image is generated
add_action('aifig_image_generated', function($attachment_id, $post_id) {
    // Your custom logic
}, 10, 2);

// Before batch generation starts
add_action('aifig_batch_start', function($post_ids) {
    // Your custom logic
});
```

## Development

### Setting Up Development Environment

```bash
# Clone the repository
git clone https://github.com/gunjanjaswal/Featured-Image-Creator-AI.git
cd Featured-Image-Creator-AI

# Create a symlink to your WordPress plugins directory
ln -s $(pwd) /path/to/wordpress/wp-content/plugins/featured-image-creator-ai
```

### Coding Standards

This plugin follows [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/).

```bash
# Install PHP_CodeSniffer
composer global require "squizlabs/php_codesniffer=*"

# Check coding standards
phpcs --standard=WordPress .

# Auto-fix issues
phpcbf --standard=WordPress .
```

## Security

- API keys are encrypted using AES-256-CBC before storage
- All user inputs are sanitized and validated
- Nonce verification on all AJAX requests
- Capability checks on all admin actions
- Prepared statements for database queries

## Privacy

This plugin sends post titles to your chosen AI provider's API to generate images. Please review:

**OpenAI**
- [OpenAI Terms of Service](https://openai.com/terms/)
- [OpenAI Privacy Policy](https://openai.com/privacy/)

**Google Gemini**
- [Google Terms of Service](https://policies.google.com/terms)
- [Google Privacy Policy](https://policies.google.com/privacy)

**Stability AI**
- [Stability AI Terms](https://stability.ai/terms-of-service)
- [Stability AI Privacy](https://stability.ai/privacy-policy)

No other data is sent to external services. API keys are stored encrypted in your WordPress database.

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Support

- **WordPress.org Support Forum**: [Plugin Support](https://wordpress.org/support/plugin/featured-image-creator-ai/)
- **GitHub Issues**: [Report a Bug](https://github.com/gunjanjaswal/Featured-Image-Creator-AI/issues)
- **Email**: hello@gunjanjaswal.me

If you find this plugin helpful, consider [buying me a coffee](https://buymeacoffee.com/gunjanjaswal) ☕

## License

This plugin is licensed under the GPL v2 or later.

```
Featured Image Creator AI
Copyright (C) 2024  Gunjan Jaswal

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```

## Credits

- Developed by [Gunjan Jaswal](https://www.gunjanjaswal.me)
- Powered by:
  - [OpenAI DALL-E 3](https://openai.com/dall-e-3)
  - [Google Gemini (Imagen)](https://ai.google.dev/)
  - [Stability AI](https://stability.ai/)

## Changelog

### 1.0.3
- Enable Output Format selection (PNG/JPG/WEBP) for OpenAI models (images are automatically converted)
- Updated documentation with full list of supported models and options
- Improved compatibility with GPT Image 1 (Mini)

### 1.0.2
- Added new image generation models: GPT Image 1, GPT Image 1 (Mini), GPT Image 1.5, GPT Image Latest
- Added SeaDream 4.5 support for Stability AI
- Added image quality settings (Standard/HD/Low) and output format selection (PNG/JPG/WEBP)
- Added Bulk Regeneration feature to regenerate images for all posts
- Fixed image dimension error for custom models
- Improved code quality and security (nonces, escaping)

### 1.0.1 (2026-01-10)
- Added automatic featured image generation for scheduled posts
- Fixed author name spelling
- When a scheduled post is published without a featured image, one is automatically generated

### 1.0.0 (2024-12-26)
- Initial release
- OpenAI DALL-E 3 integration
- Google Gemini (Imagen) integration
- Stability AI (Stable Diffusion 3) integration
- Single post image generation
- Bulk generation for posts without featured images
- Customizable prompt templates
- Encrypted API key storage
- WordPress.org standards compliance
