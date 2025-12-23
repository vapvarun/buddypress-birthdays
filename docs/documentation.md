# BuddyPress Birthday Widget - Documentation

**Version:** 2.4.0
**Requires WordPress:** 5.0+
**Requires PHP:** 7.4+
**Requires:** BuddyPress 5.0+

---

## Table of Contents

1. [Installation](#installation)
2. [Quick Start](#quick-start)
3. [Settings Configuration](#settings-configuration)
4. [Widget Setup](#widget-setup)
5. [Shortcode Usage](#shortcode-usage)
6. [Developer Reference](#developer-reference)
7. [Troubleshooting](#troubleshooting)
8. [FAQ](#faq)

---

## Installation

### Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- BuddyPress 5.0+ (required)
- BuddyPress Extended Profile component enabled

### Method 1: WordPress Dashboard

1. Go to **Plugins > Add New**
2. Click **Upload Plugin**
3. Choose the `buddypress-birthdays.zip` file
4. Click **Install Now**
5. Click **Activate**

### Method 2: FTP Upload

1. Extract the `buddypress-birthdays.zip` file
2. Upload the `buddypress-birthdays` folder to `/wp-content/plugins/`
3. Go to **Plugins** in WordPress admin
4. Find "Wbcom Designs - Birthday Widget for BuddyPress"
5. Click **Activate**

### Post-Installation Setup

After activation:

1. Ensure you have a date field in BuddyPress Extended Profile
2. Go to **Settings > Birthday Settings** (or **BuddyPress > Birthday Settings**)
3. Select your birthday field in the General tab
4. Configure notifications as desired

---

## Quick Start

### Step 1: Create Birthday Field

If you don't already have a birthday field:

1. Go to **Users > Profile Fields**
2. Click **Add New Field**
3. Set **Field Type** to "Date Selector" or "Birthdate"
4. Name it "Birthday" or "Date of Birth"
5. Save the field

### Step 2: Configure Plugin

1. Go to **Settings > Birthday Settings**
2. Select your birthday field from the dropdown
3. Enable any notifications you want
4. Save changes

### Step 3: Add Widget

1. Go to **Appearance > Widgets**
2. Find "BuddyPress Birthdays" widget
3. Drag it to your desired sidebar
4. Configure widget options
5. Save

---

## Settings Configuration

Access settings at **Settings > Birthday Settings** or **BuddyPress > Birthday Settings**

### General Tab

| Setting | Description | Default |
|---------|-------------|---------|
| **Default Birthday Field** | Select the xProfile field containing member birthdays | — |
| **Cache Duration** | How long to cache birthday data (1-1440 minutes) | 30 minutes |

> **Tip:** Lower cache values mean fresher data but more database queries. 30 minutes is recommended for most sites.

### Email Notifications Tab

Send automatic birthday greeting emails to members on their special day.

| Setting | Description | Default |
|---------|-------------|---------|
| **Enable Birthday Emails** | Turn on/off automatic birthday emails | Off |
| **Email Subject** | Subject line for birthday emails | "Happy Birthday, {name}!" |
| **Email Message** | HTML body of the birthday email | Default template |
| **Send Time** | Time to send emails (site timezone) | 09:00 |
| **Admin Summary** | Send daily summary to admin | Off |
| **Admin Email** | Email for summary (blank = site admin) | — |

**Available Email Placeholders:**

| Placeholder | Replacement |
|-------------|-------------|
| `{name}` | Member's display name |
| `{first_name}` | Member's first name |
| `{age}` | Member's new age |
| `{site_name}` | Site title |
| `{site_url}` | Site URL |
| `{profile_url}` | Member's profile URL |

**Example Subject:** `Happy {age}th Birthday, {first_name}!`

### Activity Feed Tab

Automatically post to the BuddyPress activity feed when a member has a birthday.

| Setting | Description | Default |
|---------|-------------|---------|
| **Enable Activity Posts** | Auto-post birthday announcements | Off |
| **Activity Message** | Message template for activity posts | "Today is {name}'s birthday! Send your wishes!" |

> **Note:** Requires BuddyPress Activity component to be active.

**Available Placeholders:** `{name}`, `{age}`, `{profile_url}`

### Notifications Tab

Send BuddyPress notifications to members about birthdays.

| Setting | Description | Default |
|---------|-------------|---------|
| **Enable Notifications** | Send BP notifications about birthdays | Off |
| **Only Notify Friends** | Only send to friends of birthday member | Off |
| **Notification Text** | Text shown in notification | "It's {name}'s birthday today!" |

> **Note:** Requires BuddyPress Notifications component to be active.

### Display Tab

Visual enhancements for birthday displays.

| Setting | Description | Default |
|---------|-------------|---------|
| **Confetti Animation** | Show confetti for today's birthdays | Off |
| **Zodiac Sign** | Display zodiac symbol next to birthday | Off |

---

## Widget Setup

### Adding the Widget

1. Go to **Appearance > Widgets**
2. Find "BuddyPress Birthdays" in available widgets
3. Drag to your sidebar or use the "Add Widget" button
4. Configure options (see below)
5. Click **Save**

### Widget Options

| Option | Description | Values |
|--------|-------------|--------|
| **Title** | Widget title | Text (e.g., "Upcoming Birthdays") |
| **Birthday Field** | xProfile field to use | Dropdown of date fields |
| **Date Format** | How to display dates | PHP date format (e.g., "F d" = January 15) |
| **Display Age** | Show member's age | Yes / No |
| **Birthday Range** | Time range to show | Weekly / Monthly / No Limit |
| **Show Birthdays Of** | Filter by relationship | All Members / Friends / Followers |
| **Number to Display** | Max birthdays to show | Number (e.g., 5) |
| **Emoji Style** | Decorative emoji | Cake / Balloon / Party / None |
| **Send Wishes Button** | Enable wish button | Yes / No |
| **Display Name Type** | Name format | Username / Nickname / First Name |

### Date Format Examples

| Format | Example Output |
|--------|---------------|
| `F d` | January 15 |
| `M d` | Jan 15 |
| `d F` | 15 January |
| `m/d` | 01/15 |
| `d/m` | 15/01 |

---

## Shortcode Usage

### Basic Shortcode

```
[bp_birthdays]
```

### Shortcode Attributes

| Attribute | Description | Default |
|-----------|-------------|---------|
| `limit` | Number of birthdays to show | 5 |
| `show_age` | Display age (yes/no) | yes |
| `date_format` | PHP date format | F d |
| `range_limit` | Time range (weekly/monthly/no_limit) | no_limit |
| `show_of` | Filter (all/friends/followers) | all |
| `field_id` | Specific xProfile field ID | — |

### Examples

**Show 10 birthdays with age:**
```
[bp_birthdays limit="10" show_age="yes"]
```

**Monthly birthdays only:**
```
[bp_birthdays range_limit="monthly" limit="20"]
```

**Custom date format:**
```
[bp_birthdays date_format="M j, Y"]
```

**Friends' birthdays only:**
```
[bp_birthdays show_of="friends" limit="5"]
```

**Specific birthday field:**
```
[bp_birthdays field_id="123"]
```

### Using in Templates

```php
<?php echo do_shortcode('[bp_birthdays limit="5"]'); ?>
```

---

## Developer Reference

### Hooks & Filters

#### Filters

**Modify Animation Speed**
```php
add_filter( 'bb_birthdays_animation_speed', function( $speed ) {
    return 500; // milliseconds
});
```

**Override Cache Duration**
```php
add_filter( 'bb_birthdays_cache_duration', function( $duration ) {
    return 3600; // 1 hour in seconds
});
```

**Force Asset Loading**
```php
add_filter( 'bb_core_load_assets', '__return_true' );
```

#### Actions

**After Birthday Email Sent**
```php
add_action( 'bp_birthdays_email_sent', function( $user_id, $birthday_data ) {
    // Custom logic after email sent
}, 10, 2 );
```

**After Activity Posted**
```php
add_action( 'bp_birthdays_activity_posted', function( $activity_id, $user_id ) {
    // Custom logic after activity posted
}, 10, 2 );
```

### Helper Functions

**Get Zodiac Sign**
```php
$zodiac = BP_Birthdays_Helpers::get_zodiac_sign( '1990-03-25' );
// Returns: ['name' => 'Aries', 'symbol' => '♈']
```

**Get Zodiac HTML**
```php
echo BP_Birthdays_Helpers::get_zodiac_html( '1990-03-25', true );
// Output: <span class="bp-birthday-zodiac" title="Aries">
//           <span class="zodiac-symbol">♈</span>
//           <span class="zodiac-name">Aries</span>
//         </span>
```

**Calculate Age**
```php
$age = BP_Birthdays_Helpers::calculate_age( '1990-03-25' );
// Returns: 34 (or current age)
```

**Check if Birthday is Today**
```php
$is_today = BP_Birthdays_Helpers::is_birthday_today( '1990-12-23' );
// Returns: true if today is December 23
```

**Days Until Birthday**
```php
$days = BP_Birthdays_Helpers::days_until_birthday( '1990-06-15' );
// Returns: number of days until June 15
```

### Accessing Settings Programmatically

```php
// Get all settings
$settings = BP_Birthdays_Admin::get_settings();

// Get specific setting
$email_enabled = BP_Birthdays_Admin::get_settings( 'email_enabled' );
$cache_duration = BP_Birthdays_Admin::get_settings( 'cache_duration' );
```

### Triggering Birthday Check Manually

```php
// For testing - trigger birthday processing immediately
BP_Birthdays_Notifications::trigger_now();
```

### Constants

| Constant | Description |
|----------|-------------|
| `BIRTHDAY_WIDGET_PLUGIN_URL` | Plugin URL path |
| `BIRTHDAY_WIDGET_PLUGIN_PATH` | Plugin file path |

---

## Troubleshooting

### No Birthdays Showing

1. **Check Birthday Field**
   - Go to **Users > Profile Fields**
   - Ensure you have a date field (datebox or birthdate type)
   - Verify members have filled in their birthday

2. **Check Widget Settings**
   - Ensure correct birthday field is selected
   - Try "No Limit" for range initially
   - Set "Show Birthdays Of" to "All Members"

3. **Clear Cache**
   - Birthdays are cached for 30 minutes by default
   - Wait for cache to refresh or use object cache flush

### Emails Not Sending

1. **Check Email Configuration**
   - Verify "Enable Birthday Emails" is checked
   - Test your WordPress email sending (install WP Mail SMTP)
   - Check spam folders

2. **Check Cron**
   - Emails are sent via WP Cron at configured time
   - Use WP Crontrol plugin to verify cron is running
   - Check if cron is disabled in `wp-config.php`

3. **Check Time Settings**
   - Verify site timezone in **Settings > General**
   - Email send time uses site timezone

### Activity Posts Not Appearing

1. **Check BuddyPress**
   - Ensure Activity component is active
   - Go to **Settings > BuddyPress > Components**

2. **Check Settings**
   - Verify "Enable Activity Posts" is checked
   - Check activity message is not empty

### Today's Birthdays Not Highlighted

1. **Check Timezone**
   - Plugin uses WordPress timezone
   - Verify at **Settings > General > Timezone**

2. **Check Birthday Format**
   - Birthday field should store full date (Y-m-d)
   - Some themes may alter date storage

### Widget Not Appearing

1. **Check Theme Support**
   - Ensure theme has widget areas
   - Check if widget area is displayed on current page

2. **Check BuddyPress**
   - Plugin requires BuddyPress to be active
   - Check for BuddyPress errors

### Performance Issues

For sites with many users:

1. Increase cache duration in settings
2. Consider using object caching (Redis/Memcached)
3. Limit birthdays displayed to 5-10

---

## FAQ

### Does this work with BuddyBoss?

Yes! The plugin is fully compatible with BuddyBoss platform and BuddyBoss theme.

### Can I use multiple birthday widgets?

Yes, you can add the widget to multiple sidebars with different settings.

### How do I style the widget?

The widget uses minimal, theme-compatible CSS. Add custom CSS to your theme:

```css
/* Widget container */
.bp-birthdays-widget { }

/* Individual birthday item */
.bp-birthday-item { }

/* Today's birthday highlight */
.bp-birthday-today { }

/* Member name */
.bp-birthday-name { }

/* Birthday date */
.bp-birthday-date { }

/* Wish button */
.bp-birthday-wish-btn { }
```

### Can members opt-out of birthday notifications?

Currently, the plugin uses BuddyPress field visibility settings. Members can set their birthday field to private to hide it from the widget.

### What zodiac signs are supported?

All 12 zodiac signs with Unicode symbols:

| Sign | Symbol | Dates |
|------|--------|-------|
| Capricorn | ♑ | Dec 22 - Jan 19 |
| Aquarius | ♒ | Jan 20 - Feb 18 |
| Pisces | ♓ | Feb 19 - Mar 20 |
| Aries | ♈ | Mar 21 - Apr 19 |
| Taurus | ♉ | Apr 20 - May 20 |
| Gemini | ♊ | May 21 - Jun 20 |
| Cancer | ♋ | Jun 21 - Jul 22 |
| Leo | ♌ | Jul 23 - Aug 22 |
| Virgo | ♍ | Aug 23 - Sep 22 |
| Libra | ♎ | Sep 23 - Oct 22 |
| Scorpio | ♏ | Oct 23 - Nov 21 |
| Sagittarius | ♐ | Nov 22 - Dec 21 |

### Is it translation ready?

Yes! The plugin is fully internationalized. Translation files are in the `/languages` folder. Use Loco Translate or similar plugins to create translations.

### How do I test email notifications?

Use the following code in your theme's `functions.php` temporarily:

```php
// Trigger birthday check manually (for testing)
add_action( 'init', function() {
    if ( isset( $_GET['test_birthday_emails'] ) && current_user_can( 'manage_options' ) ) {
        BP_Birthdays_Notifications::trigger_now();
        wp_die( 'Birthday check triggered!' );
    }
});
```

Then visit: `yoursite.com/?test_birthday_emails=1`

---

## Support

- **Documentation:** This file
- **Plugin Page:** [wbcomdesigns.com](https://wbcomdesigns.com/downloads/buddypress-birthdays/)
- **Support:** [Contact Wbcom Designs](https://wbcomdesigns.com/contact/)

---

*Last Updated: December 2025 | Version 2.4.0*
