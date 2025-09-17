# Easy Google Reviews WordPress Plugin

**Author:** Alan Blair <alan@wpeasy.au>
**Version:** 1.0.0
**Min PHP:** 7.4
**Tested PHP:** 8.2
**Min WordPress:** 6.0
**Tested WordPress:** 6.4

## Purpose

This WordPress plugin is for displaying Google Reviews and statistics. We will have to use the Google Business Profile API because we want to count 5 Star reviews. 

It will need to handle the OAuth 2.0 authentication, and manage Referesh token. If the token expires, it will need to renew it before it expires. Results will be cached using transients.

I need to be able to fetch all reviews for the business.

It will need a Settings page where we can:
 - do the Authentication to Google
 - Set the default rows_per_page for pagination
 - Det the default fields to include in the shortcodes output
 - Set the default transient timeout, defaulting to 6 hours

 For businesses with a large number of reviews which may not get all in a single fetch, it will need a CRON to keep fetching until all have been received.

## Architecture

**Namespace**: `WP_Easy\EasyGoogleReviews`
**ConstantPrefix**: EGR_
**textDomain**: egr
**JavascriptVariablPrefix**: --wpe-


### Core Classes

## Code Style Guidelines

### PHP Conventions

1. **Namespace**: All classes use `WP_Easy\EasyGoogleReviews` namespace
2. **Class Structure**: Final classes with static methods for WordPress hooks
3. **Security**: Always use `defined('ABSPATH') || exit;` at top of files
4. **Sanitization**: Extensive use of WordPress sanitization functions
6. **Constants**: Plugin paths defined as constants (`EGR_PLUGIN_PATH`, `EGR_PLUGIN_URL`)

### Method Patterns

- `init()`: Static method to register WordPress hooks
- `render()`, `handle_*()`: Methods that output HTML or handle requests
- Private helper methods prefixed with underscore when appropriate
- Extensive parameter validation and type checking

### JavaScript
- Never use jQuery, always use ES6 and native Browser APIs wherever possible
- Use AlpineJS for and reactive JS and state management. Serve Alpine locally, not from a CDN.

### Database

### Frontend Assets

1. **JavaScript**: Vanilla JS with AlpineJS integration, no jQuery dependency.
2. **CSS**: BEM methodology with `egr__` prefix.
3. **Icons**: Inline SVG icons with `currentColor` for theme compatibility
4. **Responsive**: Mobile-first design approach

### Security Practices

- Same-origin enforcement in REST API
- Nonce validation on all endpoints
- Sanitization of all user inputs

### WordPress Integration

- Follows WordPress coding standards
- Uses WordPress APIs extensively (Settings API, REST API, Custom Post Types)
- Translation ready with `egr` text domain
- Hooks into WordPress media library for file management
- Compatible with WordPress multisite

### Development Features

- CodeMirror 6 integration for CSS editing in admin
- Composer autoloading (PSR-4)
- Graceful fallbacks (Alpine.js optional, CSS editor fallback to textarea)
- Extensive error handling and validation

## Configuration

### Key Settings

### Shortcode Usage

[egr_reviews count="5"] // reviews grid
[egr_5star_count] // A simple number of the count of 5 star reviews

// Pagination examples
[egr_reviews show_all="true" rows_per_page="20"] // reviews grid showing all with 20 per page

### Pagination Parameters

### REST Endpoints

**Base URL**: `/wp-json/egr/v1/`

**Security**: Same-origin enforcement (domain/origin must match site URL)

#### GET `/wp-json/egr/v1/5star-count`
Returns the count of 5-star reviews.

**Response**:
```json
{
  "success": true,
  "data": {
    "five_star_count": 42
  }
}
```

#### GET `/wp-json/egr/v1/reviews`
Returns paginated reviews data.

**Parameters**:
- `page_number` (int, default: 1) - Page number (1-based)
- `row_count` (int, default: 10, max: 100) - Reviews per page

**Response**:
```json
{
  "success": true,
  "data": {
    "reviews": [
      {
        "name": "John Doe",
        "rating": 5,
        "text": "Great service!",
        "date": {
          "timestamp": 1234567890,
          "formatted": "January 1, 2024",
          "relative": "2 days ago"
        },
        "reply": {
          "text": "Thank you!",
          "date": {
            "timestamp": 1234567890,
            "formatted": "January 2, 2024",
            "relative": "1 day ago"
          }
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 10,
      "total_items": 50,
      "total_pages": 5,
      "has_next_page": true,
      "has_prev_page": false
    }
  }
}
```

#### GET `/wp-json/egr/v1/stats`
Returns connection status and sync information.

**Response**:
```json
{
  "success": true,
  "data": {
    "connection_status": "connected",
    "last_sync": {
      "timestamp": 1234567890,
      "formatted": "January 1, 2024 10:30 AM",
      "relative": "2 hours ago"
    },
    "totals": {
      "total_reviews": 150,
      "five_star_count": 42
    },
    "has_error": false,
    "error_message": null
  }
}
```

## Development Notes

- Plugin follows WordPress plugin standards
- Uses modern PHP features while maintaining 7.4 compatibility
- Frontend uses modern JavaScript (ES6+) with graceful degradation
- CSS uses modern features (Grid, Flexbox, CSS Custom Properties)
- Extensive use of WordPress core functions and APIs
- Alpine.js served locally from `assets/vendor/alpine.min.js`, CodeMirror uses CDN (esm.sh)
