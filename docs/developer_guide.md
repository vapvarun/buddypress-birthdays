# BuddyPress Birthdays - Developer Guide

## Architecture Overview

The BuddyPress Birthdays plugin is built with performance, security, and extensibility in mind. It follows WordPress coding standards and integrates seamlessly with BuddyPress, BuddyBoss, and Youzify platforms.

## File Structure

```
buddypress-birthdays/
├── buddypress-birthdays.php          # Main plugin file
├── core-init.php                     # Core initialization & asset management
├── admin/
│   ├── class-bp-birthdays-admin.php  # Admin settings page
│   ├── css/                          # Admin stylesheets
│   └── js/                           # Admin JavaScript
├── includes/
│   ├── class-bp-birthdays-notifications.php  # Email & notifications
│   └── class-bp-birthdays-helpers.php        # Helper functions
├── assets/
│   ├── css/bb-core.css              # Theme-compatible styles
│   ├── js/bb-core.js                # Enhanced JavaScript
│   └── inc/
│       └── buddypress-birthdays-widget.php  # Main widget class
├── languages/                       # Translation files
├── docs/                            # Documentation
├── readme.txt                       # WordPress.org readme
├── package.json                     # NPM dependencies
└── gruntfile.js                     # Build configuration
```

## Core Components

### 1. Main Plugin File (`buddypress-birthdays.php`)

**Purpose**: Entry point, dependency checks, plugin meta

```php
// Plugin header with all required meta
// BuddyPress dependency check
// Plugin deactivation if requirements not met
```

**Key Functions**:
- `bb_check_bp_active()` - Validates BuddyPress is active
- `bb_dependent_plugin_notice()` - Shows admin notice if dependencies missing

### 2. Core Initialization (`core-init.php`)

**Purpose**: Smart asset loading, shortcode support, AJAX handlers

**Key Features**:
- **Conditional Asset Loading**: Only loads CSS/JS when widget is active
- **Smart Caching**: 60-minute cache with automatic invalidation  
- **Shortcode Support**: `[bp_birthdays]` with full attribute support
- **AJAX Handlers**: Birthday actions and cache management
- **Performance Monitoring**: Debug mode for development
- **Visual Effects**: Confetti animation and zodiac sign display
- **Email Integration**: BuddyPress Emails system integration

**Critical Functions**:

```php
// Smart asset loading
function bb_should_load_assets() {
    // Checks widget active, customizer, BuddyPress pages, etc.
}

// Asset loading with cache busting
function bb_register_core_css() {
    if ( bb_should_load_assets() ) {
        wp_enqueue_style( 'bb-core', BB_CORE_CSS . 'bb-core.css', array(), BB_CORE_VERSION );
    }
}

// Shortcode implementation
function bb_birthdays_shortcode( $atts ) {
    // Converts shortcode attributes to widget instance format
    // Supports all widget options as shortcode attributes
}
```

### 3. Widget Class (`buddypress-birthdays-widget.php`)

**Purpose**: Main widget functionality, birthday calculations, data processing

## Technical Implementation

### Birthday Calculation Logic

**Core Algorithm**:
```php
public function bbirthday_get_upcoming_birthday( $birthdate ) {
    // 1. Parse birthdate with site timezone
    $birth_date = DateTime::createFromFormat( 'Y-m-d', $birthdate, wp_timezone() );
    
    // 2. Handle leap year edge cases (Feb 29)
    if ( '02' === $birth_month && '29' === $birth_day ) {
        if ( ! $this->is_leap_year( $current_year ) ) {
            $birth_day = '28'; // Use Feb 28 in non-leap years
        }
    }
    
    // 3. Calculate this year's birthday
    $this_year_birthday = DateTime::createFromFormat( /* ... */ );
    
    // 4. If passed, use next year
    if ( $this_year_birthday < $today ) {
        // Move to next year with leap year handling
    }
    
    return $this_year_birthday->format( 'Y-m-d' );
}
```

**Key Features**:
- **Timezone Aware**: Uses `current_datetime()` and `wp_timezone()`
- **Leap Year Handling**: Properly handles Feb 29 birthdays
- **Edge Case Management**: Handles invalid dates, timezone differences
- **Performance Optimized**: Minimal calculations, efficient sorting

### Data Flow Architecture

```
User Request
    ↓
Widget Display (widget())
    ↓
Cache Check (get_transient)
    ↓
[Cache Miss] → Data Fetch (bbirthdays_get_array())
    ↓
Member Filtering (friends/followers/all)
    ↓
Birthday Calculation (bbirthday_get_upcoming_birthday())
    ↓
Range Filtering (weekly/monthly/unlimited)
    ↓
Visibility Check (xprofile_get_field_visibility_level)
    ↓
Age Calculation & Data Structure
    ↓
Sorting (by celebration date)
    ↓
Cache Storage (wp_cache_set - 60 min)
    ↓
Template Rendering
    ↓
HTML Output
```

### Caching Strategy

**Implementation**:
```php
// Cache group and key
$cache_group = 'bp_birthdays';
$cache_key   = md5( wp_json_encode( $instance ) );

// Add user ID to cache key for user-specific filters (friends/followers)
if ( is_user_logged_in() && in_array( $instance['show_birthdays_of'], array( 'friends', 'followers' ), true ) ) {
    $cache_key .= '_user_' . get_current_user_id();
}

// Cache check using object cache
$birthdays = wp_cache_get( $cache_key, $cache_group );

if ( false === $birthdays ) {
    // Fetch and process data
    $birthdays = $this->bbirthdays_get_array( $instance );
    
    // Store for 60 minutes using object cache
    wp_cache_set( $cache_key, $birthdays, $cache_group, 60 * MINUTE_IN_SECONDS );
}

// Cache invalidation on settings change
function bb_clear_birthday_caches() {
    wp_cache_flush_group( 'bp_birthdays' );
}
```

**Cache Benefits**:
- **Performance**: Uses WordPress object cache API
- **Scalability**: Works with Redis/Memcached for persistent caching
- **Smart Invalidation**: Auto-clears on profile updates, friendships, user changes
- **Object Cache**: More efficient than transients for large sites

## Security Implementation

### Input Sanitization

```php
// Widget settings sanitization
$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
$instance['birthdays_to_display'] = (int) $new_instance['birthdays_to_display'];
$instance['birthday_field_name'] = sanitize_text_field( $new_instance['birthday_field_name'] );

// User input validation
$user_id = (int) $user_id;
$field_id = sanitize_text_field( $field_id );
```

### Output Escaping

```php
// HTML output escaping
echo esc_html( $display_name );
echo esc_url( $user_url );
echo esc_attr( $item_class );

// Safe HTML output
echo wp_kses_post( $args['after_widget'] );
```

### AJAX Security

```php
// Nonce verification
if ( ! wp_verify_nonce( $_POST['nonce'], 'bb_birthdays_nonce' ) ) {
    wp_die( 'Security check failed' );
}

// Input sanitization
$action = sanitize_text_field( $_POST['birthday_action'] ?? '' );
$user_id = (int) ( $_POST['user_id'] ?? 0 );
```

### Privacy Compliance

```php
// Visibility level checking
$visibility = xprofile_get_field_visibility_level( $field_id, $user_id );

if ( 'onlyme' === $visibility ) {
    continue; // Skip private data
}

// Permission-based visibility
if ( 'friends' === $visibility ) {
    return friends_check_friendship( get_current_user_id(), $user_id );
}
```

## Performance Optimizations

### Database Optimization

**Efficient Queries**:
```php
// Query all users with birthday data directly from xProfile table
global $wpdb;
$users_with_birthday = $wpdb->get_col(
    $wpdb->prepare(
        "SELECT DISTINCT user_id FROM {$wpdb->prefix}bp_xprofile_data WHERE field_id = %d AND value != ''",
        $field_id
    )
);

$members = array_filter( array_map( 'absint', $users_with_birthday ) );
```

**Query Reduction**:
- Direct xProfile data query instead of get_users()
- No artificial limit - fetches all users with birthday data
- Filter by date range in PHP after fetching
- Skip processing for inactive users

### Asset Loading Strategy

**Smart Loading Logic**:
```php
function bb_should_load_assets() {
    // Widget active check
    if ( is_active_widget( false, false, 'widget_buddypress_birthdays' ) ) {
        return true;
    }
    
    // Shortcode usage check
    if ( has_shortcode( $post->post_content, 'bp_birthdays' ) ) {
        return true;
    }
    
    // BuddyPress context check
    if ( function_exists( 'bp_is_directory' ) && bp_is_directory() ) {
        return true;
    }
    
    return false;
}
```

**Benefits**:
- **Reduces HTTP Requests**: Only loads when needed
- **Improves Page Speed**: No unnecessary assets
- **Better User Experience**: Faster page loads
- **Server Resource Savings**: Less CPU/memory usage

### JavaScript Optimizations

**Performance Features**:
```javascript
// Event delegation for better performance
document.on('click.bpBirthdays', '.bp-send-wishes', handler);

// Debounced events
window.on('resize.bpBirthdays', debounce(handleResize, 250));

// Intersection Observer for animations
if ('IntersectionObserver' in window) {
    // Only animate visible elements
}

// Memory cleanup
function destroy() {
    // Remove event listeners
    // Clear timeouts
    // Clean up DOM references
}
```

## Extensibility & Hooks

### Available Filters

```php
// Customize animation speed (default: 300ms)
add_filter( 'bb_birthdays_animation_speed', function( $speed ) {
    return 500;
} );

// Customize tooltip delay (default: 300ms)
add_filter( 'bb_birthdays_tooltip_delay', function( $delay ) {
    return 500;
} );

// Customize cache duration (default: 3600 seconds = 60 minutes)
add_filter( 'bb_birthdays_cache_duration', function( $duration ) {
    return 7200; // 2 hours
} );

// Force asset loading
add_filter( 'bb_core_load_assets', '__return_true' );

// Customize name display
add_filter( 'bbirthdays_get_name_to_display', function( $display, $user_info ) {
    return $display;
}, 10, 2 );
```

### Action Hooks

```php
// Note: Currently no custom action hooks are implemented
// The plugin uses BuddyPress hooks internally for cache invalidation:
// - xprofile_data_after_save
// - friends_friendship_accepted
// - friends_friendship_deleted
// - delete_user
// - user_register
// - bp_follow_start_following
// - bp_follow_stop_following
```

### Custom Extensions

**Example: Custom Name Display**:
```php
// Modify birthday display name
add_filter( 'bbirthdays_get_name_to_display', 'my_custom_name_format', 10, 2 );

function my_custom_name_format( $display, $user_info ) {
    $user = get_userdata( $user_info->user_id );
    return $user->display_name . ' 🎂';
}
```

---

## Compatibility Matrix

### Platform Compatibility

| Platform | Version | Status | Notes |
|----------|---------|---------|-------|
| WordPress | 5.0+ | ✅ Full | Requires WordPress 5.0+ |
| BuddyPress | 8.0+ | ✅ Full | All versions supported |
| BuddyPress 12.0+ | Latest | ✅ Full | Uses new BP functions |
| BuddyBoss Platform | All | ✅ Full | Special BP compatibility |
| Youzify | All | ✅ Full | Profile extension support |

### Theme Compatibility

**CSS Inheritance Strategy**:
```css
.widget_bp_birthdays {
    background: inherit;    /* Uses theme background */
    color: inherit;        /* Uses theme text color */
    font-family: inherit;  /* Uses theme fonts */
}

.bp-send-wishes {
    border: 1px solid rgba(0,0,0,0.08);  /* Subtle, theme-neutral */
    background: rgba(0,0,0,0.03);        /* Adapts to any background */
}
```

**Responsive Design**:
```css
@media (max-width: 768px) {
    .bp-birthday-item {
        flex-direction: column;  /* Stack on mobile */
    }
}
```

### Plugin Compatibility

| Plugin | Compatibility | Integration |
|--------|---------------|-------------|
| BP Follow | ✅ Full | Followers filter support |
| BuddyBoss Follow | ✅ Full | Auto-detection & integration |
| WP Caching Plugins | ✅ Full | Cache-aware design |
| Translation Plugins | ✅ Full | i18n ready |

## Development Workflow

### Local Development Setup

```bash
# Clone repository
git clone https://github.com/your-repo/buddypress-birthdays.git

# Install dependencies (if using build tools)
npm install

# Start local WordPress environment
wp server --host=localhost --port=8080
```

### Testing Framework

**Unit Testing Structure**:
```php
// Test birthday calculations
class Test_Birthday_Calculations extends WP_UnitTestCase {
    
    public function test_leap_year_handling() {
        // Test Feb 29 birthdays in non-leap years
    }
    
    public function test_timezone_consistency() {
        // Test different timezone scenarios
    }
    
    public function test_today_detection() {
        // Test today's birthday detection
    }
}
```

### Code Quality Standards

**WordPress Coding Standards (WPCS)**:
```php
// Strict comparisons
if ( 0 === $age ) { }

// Proper spacing
DateTime::createFromFormat( 'Y-m-d', $date, $timezone );

// Yoda conditions
if ( 'yes' === $setting ) { }

// Type casting
$user_id = (int) $user_id;
```

**Security Standards**:
- All input sanitized
- All output escaped
- Nonce verification for AJAX
- Capability checks for admin functions

### Debugging Tools

**Debug Mode**:
```php
// Enable debug mode
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );

// View debug info (admin only)
add_query_var( 'debug_birthdays', '1' );
// Shows widget status, asset loading, member data
```

**Performance Monitoring**:
```javascript
// JavaScript performance tracking
console.time('BP Birthdays Init');
// ... widget initialization
console.timeEnd('BP Birthdays Init');
```

## Deployment Guidelines

### Pre-Deployment Checklist

- [ ] **Code Quality**: WPCS compliance check
- [ ] **Security Review**: Input sanitization audit  
- [ ] **Performance Testing**: Large dataset testing
- [ ] **Compatibility Testing**: Multiple themes/platforms
- [ ] **Cache Testing**: Cache invalidation verification
- [ ] **Mobile Testing**: Responsive design verification
- [ ] **Accessibility Testing**: WCAG 2.1 compliance
- [ ] **Translation Ready**: i18n string verification

### Version Management

**Semantic Versioning**:
- **Major (X.0.0)**: Breaking changes, major features
- **Minor (2.X.0)**: New features, backward compatible
- **Patch (2.2.X)**: Bug fixes, security updates

**Release Process**:
1. Update version numbers in all files
2. Update changelog in readme.txt
3. Tag release in version control
4. Deploy to WordPress.org (if applicable)
5. Update documentation

### Performance Benchmarks

**Target Performance Metrics**:
- **Database Queries**: < 5 queries per widget display
- **Memory Usage**: < 2MB additional memory
- **Load Time**: < 100ms widget rendering
- **Cache Hit Rate**: > 90% for repeated requests

---

**Built with ❤️ for the BuddyPress community**