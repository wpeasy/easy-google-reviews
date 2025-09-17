# Changelog

All notable changes to the Easy Google Reviews plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1-beta] - 2025-01-17

### Added
- Comprehensive REST API endpoints for frontend integration
  - GET `/wp-json/egr/v1/5star-count` - Returns 5-star review count
  - GET `/wp-json/egr/v1/reviews` - Returns paginated reviews with metadata
  - GET `/wp-json/egr/v1/stats` - Returns connection status and sync information
- Complete REST endpoints documentation in admin instructions
- JavaScript integration examples for REST API usage
- Same-origin security enforcement for all REST endpoints

### Improved
- Enhanced Business Location ID instructions with clear format requirements
- Added explicit clarification that entire location ID string must be entered
- Improved Step 6 instructions with detailed response location guidance
- Better error handling and troubleshooting documentation

### Fixed
- CSS variable naming consistency across all framework files
- Updated all variable references to use correct `--wpe-category-property--variation` pattern
- Fixed border and font variable naming in framework files
- Restored styling to instructions page after framework conversion

### Technical
- Maintained custom Google API implementation (evaluated vs official library)
- Ensured PHP 7.4+ compatibility over official library's PHP 8.0+ requirement
- Optimized for WordPress plugin standards and minimal dependencies

## [1.0.0-beta] - 2025-01-17

### Added
- Initial release of Easy Google Reviews plugin
- Google Business Profile API integration with OAuth 2.0 authentication
- WordPress admin interface with settings and instructions
- Frontend shortcodes `[egr_reviews]` and `[egr_5star_count]`
- CSS framework with design tokens and responsive components
- CRON-based background review syncing
- Comprehensive error handling and security measures
- Translation-ready implementation
- Multisite compatibility