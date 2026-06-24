# Featured Image Creator AI

Auto-generate stunning AI-powered featured images using OpenAI (DALL-E 3, GPT-4), Google Gemini, or Stability AI. Style presets, text/logo overlays, image variations, auto alt text, social/Open Graph sizes, bulk generation, scheduling, and multiple formats.

![WordPress Plugin Version](https://img.shields.io/badge/version-1.2.0-blue.svg)
![WordPress Compatibility](https://img.shields.io/badge/wordpress-5.8%2B-blue.svg)
![PHP Version](https://img.shields.io/badge/php-7.4%2B-purple.svg)
![License](https://img.shields.io/badge/license-GPLv2%2B-green.svg)

## Features

- 🎨 **Multiple AI Providers** - Support for **OpenAI (DALL-E 3, GPT models)**, **Google Gemini**, and **Stability AI (Stable Diffusion 3, SeeDream)**
- 🖌️ **Style Presets** *(new in 1.1.0)* - One-click looks: Photographic, Flat Illustration, Digital Art, 3D Render, Watercolor, Minimal, Isometric, Cyberpunk, Paper-cut, Corporate, Retro
- 🏷️ **Text & Logo Overlay** *(new in 1.1.0)* - Burn an auto-wrapped headline and/or logo/watermark onto the image (Imagick/GD + bundled Poppins font)
- 🔀 **Image Variations** *(new in 1.1.0)* - Generate several options and pick your favorite from a grid; unchosen images are auto-removed
- ♿ **Auto Alt Text** *(new in 1.1.0)* - Describe the generated image with the provider's vision model for SEO + accessibility (title fallback)
- 📣 **Social & Open Graph Images** *(new in 1.2.0)* - Create Facebook/OG (1200×630), Twitter, square and Pinterest sizes from one image (local crop, no extra API cost); auto-sets the OG image for Yoast / Rank Math
- 🔑 **Bring Your Own API Key** - Complete control and transparency over API usage
- ⚡ **Single & Batch Generation** - Generate images one at a time, or bulk regenerate your entire library
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

> **Auto Alt Text** uses a vision model where available — OpenAI (`gpt-4o-mini`) and Gemini describe the image; Stability AI falls back to the post title.

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

### Styles

Set a default look under **Settings > AI Enhancements** (Photographic, Flat Illustration, Watercolor, Cyberpunk, and more). Override it per image from the **Style** dropdown in the post editor — no prompt engineering needed.

### Text & Logo Overlay

Enable **Settings > Text & Logo Overlay** to burn the post title (and your logo) onto every image. Configure font weight, size, color, vertical position, a readability scrim, and a logo/watermark corner. Use `{title}` in the headline text for the post title. Rendering is local (Imagick or GD) — no extra API cost.

### Variations

In the post editor, click **Generate Options** to create several images at once and pick your favorite from the grid. Unchosen images are removed automatically. The number of options (2–8) is set under **Settings > AI Enhancements**. Each option uses one API credit.

### Auto Alt Text

Enable **Settings > AI Enhancements > Auto Alt Text** to describe each generated image with the provider's vision model (OpenAI `gpt-4o-mini` / Gemini) and save it as the image's alt text. Stability AI falls back to the post title.

### Social & Open Graph Images

Enable **Settings > Social & Open Graph Images** and tick the sizes you want (Open Graph 1200×630, Twitter, square, Pinterest). Each generated image is cropped locally into those sizes and added to the media library — no extra API credits. With **Open Graph Image** enabled, the share image is set for Yoast SEO / Rank Math, or output as `og:image` / `twitter:image` tags when no SEO plugin is active.

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
- `imagick` **or** `gd` PHP extension (only needed for the optional Text & Logo Overlay)

## File Structure

```
ai-featured-image-generator/
├── ai-featured-image-generator.php  # Main plugin file
├── includes/
│   ├── class-admin-notices.php      # Admin notification system
│   ├── class-bulk-generator.php     # Batch processing
│   ├── class-image-generator.php    # Core image generation + variations + alt text
│   ├── class-image-overlay.php      # Text/logo overlay engine (Imagick/GD)
│   ├── class-social-variants.php    # Social / Open Graph sized variants
│   ├── class-styles.php             # Visual style presets
│   ├── class-whats-new.php          # "What's New" on-update notice
│   ├── class-post-meta-box.php      # Post editor integration
│   ├── class-security.php           # Security utilities
│   ├── class-settings.php           # Settings page
│   └── api/
│       ├── class-api-interface.php     # API provider interface (+ vision contract)
│       ├── class-openai-provider.php   # OpenAI DALL-E 3 / GPT Image / vision
│       ├── class-gemini-provider.php   # Google Gemini (Imagen) / vision
│       └── class-stability-provider.php # Stability AI (SD3)
├── assets/
│   ├── css/
│   │   └── admin.css                # Admin styles
│   ├── js/
│   │   └── admin.js                 # Admin JavaScript
│   └── fonts/
│       ├── Poppins-Bold.ttf         # Bundled overlay fonts (SIL OFL)
│       ├── Poppins-SemiBold.ttf
│       ├── Poppins-Regular.ttf
│       └── OFL.txt                  # Font license
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

// --- Added in 1.1.0 ---

// Add or change style presets (key => ['label' => ..., 'suffix' => ...])
add_filter('aifig_style_presets', function($presets) {
    $presets['my_brand'] = array(
        'label'  => 'My Brand Look',
        'suffix' => 'Bold brand colors, clean layout, on-brand illustration style.',
    );
    return $presets;
});

// Choose the vision models used for Auto Alt Text
add_filter('aifig_openai_vision_model', fn() => 'gpt-4o-mini');
add_filter('aifig_gemini_vision_model', fn() => 'gemini-2.0-flash');

// Add or change social / Open Graph sizes (key => ['label', 'width', 'height'])
add_filter('aifig_social_variants', function($specs) {
    $specs['story'] = array('label' => 'Story (1080×1920)', 'width' => 1080, 'height' => 1920);
    return $specs;
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

If you find this plugin helpful, consider [supporting on Ko-fi](https://ko-fi.com/gunjanjaswal) to back the development.

[![Support on Ko-fi](https://img.shields.io/badge/Ko--fi-Support-FF5E5B?style=for-the-badge&logo=ko-fi&logoColor=white)](https://ko-fi.com/gunjanjaswal)

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

### 1.0.6
- **WordPress 7.0 integration (real APIs, tested against `D:\wamp64\www\wordpress7`):**
  - Iframed editor: documented the meta-box-only integration. The meta box renders in the parent admin chrome (not the editor iframe), so no asset changes were required.
  - **AI Client API:** added `aifig_is_wp_ai_client_available()` (uses core `wp_supports_ai()` + `wp_ai_client_prompt()`) and `aifig_wp_ai_client_prompt()` helpers. Routing through the core client is opt-in via the `aifig_use_wp_ai_client` filter; bundled OpenAI / Gemini / Stability providers remain the default image-generation path.
  - **Connectors API:** registers an `ai_provider` connector on the `wp_connectors_init` action so the encrypted `aifig_api_key` option surfaces on the central Connections screen alongside core's auto-discovered providers. Connector ID: `aifig-image-generator`. Falls back silently on WP 6.x.

### 1.0.5
- Added `draft_to_publish` and `pending_to_publish` hooks for auto-generating featured images. Previously only `future_to_publish` was hooked, so images were only auto-generated for WordPress scheduled (future) posts. Now auto-generates featured images when posts transition from draft or pending to published (e.g., via custom auto-publish systems, bulk publishing, or manual publish).
- Replaced raw `error_log()` calls in the scheduled-publish auto-generate flow with a new `aifig_auto_generate_result` action hook so site owners can log results themselves without bundling debug code in production.
- Updated "Tested up to" to WordPress 7.0.
- Replaced Buy Me a Coffee donation link with Ko-fi (https://ko-fi.com/gunjanjaswal).
- Added "Contact Developer" link to plugin row meta on the Plugins screen.
- Corrected GitHub repository slug in the Documentation row-meta link.

### 1.0.4
- Fixed fatal error by requiring file.php before calling wp_tempnam() in OpenAI provider

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
