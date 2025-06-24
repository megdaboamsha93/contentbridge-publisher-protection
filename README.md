# ContentBridge Publisher Protection

[![WordPress Plugin](https://img.shields.io/wordpress/plugin/v/contentbridge-publisher-protection.svg)](https://wordpress.org/plugins/contentbridge-publisher-protection/)
[![GitHub release](https://img.shields.io/github/v/release/your-org/contentbridge-publisher-protection.svg)](https://github.com/your-org/contentbridge-publisher-protection/releases)

## Description

**ContentBridge Publisher Protection** is a WordPress plugin that protects your content from unauthorized AI crawling and scraping, while allowing paid access via the ContentBridge marketplace. It integrates seamlessly with the ContentBridge platform and uses a secure Supabase endpoint for token validation.

## Features

- 🛡️ One-click content protection against unauthorized AI crawling
- 💰 Monetize your content through ContentBridge's marketplace
- 📊 Detailed analytics and revenue tracking
- ⚡ High-performance token-based access control
- 🎯 Customizable protection rules by post type, category, or tag
- 📱 Responsive admin interface
- 🔄 Real-time revenue reporting
- 📈 Export analytics data for further analysis

## Installation

1. Download the latest release from the [releases page](https://contentbridge.com/wordpress-plugin/releases)
2. Log in to your WordPress admin panel
3. Navigate to Plugins > Add New > Upload Plugin
4. Choose the downloaded zip file and click "Install Now"
5. After installation, click "Activate Plugin"
6. Go to ContentBridge settings to configure your API key and protection rules

## Configuration

### API Key Setup

1. Sign up for a ContentBridge account at [contentbridge.com](https://contentbridge.com)
2. Generate an API key from your ContentBridge dashboard
3. In WordPress, go to Settings > ContentBridge
4. Enter your API key and save changes

### Protection Rules

Configure content protection rules based on:
- Post types (posts, pages, custom post types)
- Categories
- Tags
- Individual URLs
- Custom rules using WordPress filters

### Cache Settings

Optimize performance by configuring:
- Token cache duration
- Analytics data aggregation
- API response caching

## Usage

### Protecting Content

1. Navigate to Posts or Pages
2. Edit any content piece
3. In the sidebar, find the "ContentBridge Protection" meta box
4. Enable protection and set pricing (optional)
5. Update the post

### Viewing Analytics

1. Go to ContentBridge > Dashboard
2. View real-time statistics:
   - Revenue generated
   - Content access patterns
   - Popular content
   - AI company usage
3. Export data in CSV format for detailed analysis

## FAQ

**Q: What happens if a user tries to access protected content without a valid token?**
A: They see a customizable "Access Denied" message and a link to the ContentBridge marketplace.

**Q: Can I customize which content is protected?**
A: Yes, via the settings page and custom rules.

**Q: Is analytics data sent to ContentBridge?**
A: Only token validation events are logged platform-side. Local analytics are also available in the dashboard.

## Development

### Requirements

- PHP 7.4 or higher
- WordPress 5.8 or higher
- MySQL 5.6 or higher

### Local Development Setup

1. Clone the repository:
```bash
git clone https://github.com/contentbridge/wordpress-plugin.git
cd wordpress-plugin
```

2. Install dependencies:
```bash
composer install
```

3. Set up WordPress development environment:
```bash
wp core download
wp config create
wp core install
```

### Running Tests

```bash
composer test
```

### Building Assets

```bash
npm install
npm run build
```

## API Documentation

### Filters

```php
// Modify protection rules
add_filter('contentbridge_protection_rules', function($rules, $post_id) {
    // Modify rules
    return $rules;
}, 10, 2);

// Customize access denied message
add_filter('contentbridge_access_denied_message', function($message) {
    return 'Custom message';
});
```

### Actions

```php
// Hook into successful content access
add_action('contentbridge_content_accessed', function($post_id, $token_data) {
    // Custom logic
}, 10, 2);

// Hook into revenue generation
add_action('contentbridge_revenue_generated', function($amount, $post_id) {
    // Custom logic
}, 10, 2);
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Support

- [Documentation](https://docs.contentbridge.com)
- [Support Forum](https://community.contentbridge.com)
- [Email Support](mailto:support@contentbridge.com)

## License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## Changelog

### 1.0.0 (2024-03-20)
- Initial release
- Core protection features
- Analytics dashboard
- Token-based access control
- Admin interface
- API integration

---

**Compatible with ContentBridge platform and Supabase-powered validation.** 