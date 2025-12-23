=== Wbcom Designs - Birthday Widget for BuddyPress ===
Contributors: vapvarun,wbcomdesigns
Tags: buddypress, birthdays, widget, community, members
Donate link: https://www.paypal.me/wbcomdesigns
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 2.4.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Display upcoming birthdays of BuddyPress members with a beautiful, responsive widget that integrates seamlessly with any WordPress theme.

== Description ==

Transform your BuddyPress community with the Birthday Widget! Know the upcoming birthdays of your site's members and help them celebrate their special day. A little effort to greet community members keeps the saying alive: "community that celebrates together stays together".

**Key Features:**

**Smart Birthday Display**
* Show birthdays for all members, friends, or followers
* Flexible time ranges: weekly, monthly, or unlimited
* Today's birthdays get special highlighting
* Age display with customizable "Turning X" format

**Modern & Responsive Design**
* Clean, minimal design that works with any theme
* Mobile-first responsive layout
* Smooth animations and hover effects
* Optimized for performance with smart caching

**Flexible Configuration**
* Multiple name display options (username, nickname, first name)
* Customizable date formats
* Optional emoji support
* Send birthday wishes via private messages

**NEW: Automatic Notifications**
* Automatic birthday email greetings to members
* Activity feed auto-posts for birthdays
* BuddyPress notification support
* Admin daily birthday summary email
* Configurable send time and templates

**NEW: Display Enhancements**
* Zodiac sign display option
* Confetti animation for celebrations
* Centralized admin settings page

**Developer Friendly**
* Theme-compatible styling using CSS inheritance
* Smart asset loading (only loads when widget is active)
* Shortcode support: `[bp_birthdays]`
* Extensive filter and action hooks
* WPCS compliant code

**Performance Optimized**
* 30-minute smart caching system
* Conditional asset loading
* Minimal database queries
* Zero Cumulative Layout Shift (CLS)

**Accessibility Ready**
* WCAG 2.1 compliant
* Keyboard navigation support
* Screen reader friendly
* Reduced motion support

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/buddypress-birthdays/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Appearance > Widgets and add the "BuddyPress Birthdays" widget to your sidebar
4. Configure the widget settings according to your preferences

== Frequently Asked Questions ==

= Does this work with BuddyBoss? =
Yes! The plugin is fully compatible with BuddyBoss platform and BuddyBoss theme.

= Can I show birthdays in a post or page? =
Yes! Use the shortcode `[bp_birthdays]` anywhere in your content. You can customize it with attributes like limit, show_age, date_format, etc.

= How do I set up birthday fields? =
The plugin works with BuddyPress Extended Profile datebox or birthdate field types. Create a date field in your BuddyPress profile fields and select it in the widget settings.

= Does it work with custom themes? =
Absolutely! The plugin uses theme-compatible styling that inherits your theme's colors and fonts, ensuring seamless integration.

= Can members send birthday wishes? =
Yes! If BuddyPress private messaging is enabled, members can click the wish button to send birthday messages directly.

= Is it mobile responsive? =
Yes! The widget is built with a mobile-first approach and works perfectly on all devices and screen sizes.

= Does it cache birthday data? =
Yes! The plugin includes smart caching that refreshes every 30 minutes for optimal performance while keeping data current.

= Can I customize the date format? =
Yes! You can set custom date formats like "January 15", "Jan 15", "15 Jan", etc. in the widget settings.

= Does it respect privacy settings? =
Absolutely! The plugin respects BuddyPress field visibility settings. Private birthday fields won't be displayed to unauthorized users.

= Is it compatible with Youzify? =
Yes! The plugin works seamlessly with Youzify and other popular BuddyPress extensions.

== Screenshots ==

1. Birthday widget display showing upcoming birthdays with user avatars
2. Widget configuration options in WordPress admin
3. Mobile responsive layout on smaller screens
4. Today's birthday special highlighting
5. Integration with BuddyBoss platform

== Changelog ==

= 2.4.0 =
* New: Admin settings page under BuddyPress menu for centralized configuration.
* New: Automatic birthday email notifications with customizable templates.
* New: Activity feed auto-post when members have birthdays.
* New: BuddyPress notifications for birthdays (notify all members or friends only).
* New: Admin daily summary email of today's birthdays.
* New: Zodiac sign display option with Unicode symbols.
* New: Confetti animation option for birthday celebrations.
* New: Helper functions for zodiac signs and age calculation.
* Improved: WP Cron integration for scheduled birthday checks.
* Improved: Modular code architecture with separate classes.

= 2.3.0 =
* Fixed: Widget no longer shows empty container when no birthdays to display.
* Fixed: Widget visibility now works correctly for logged-out users.
* Fixed: "All Members" filter now visible to logged-out users for public birthdays.
* Fixed: Friends/Followers filter properly hidden for logged-out users.
* Fixed: Widget cache now clears properly when settings are updated.
* Fixed: Replaced transient caching with object cache for better performance on large sites.
* Fixed: Non-activated users are now properly excluded from birthday listings.
* Fixed: JavaScript error messages now properly localized.
* Improved: WordPress.org Plugin Check compatibility.
* Improved: Added grunt build process for distribution.
* Updated: Regenerated .pot file with all translation strings.

= 2.2.0 =
* Enhancement: Complete UI/UX redesign with modern, clean interface.
* Enhancement: Improved theme compatibility with CSS inheritance.
* Enhancement: Zero Cumulative Layout Shift (CLS) implementation.
* Enhancement: Smart asset loading - only loads when widget is active.
* Enhancement: Enhanced mobile responsiveness with touch-friendly buttons.
* Enhancement: Improved performance with optimized caching system.
* Enhancement: Better accessibility with WCAG 2.1 compliance.
* Enhancement: Smooth animations and hover effects.

= 2.1.0 =
* Fixed issue where logged-in users could view their own birthdays.
* Resolved "No Limit" filter not working as expected.
* Updated strings for better localization and readability.
* Enhanced visibility logic and sorting for birthday displays.
* Added a filter for customizing BuddyPress Birthday query arguments.
* Optimized BuddyPress Birthday queries for large user datasets.
* Resolved conflict issue with the "Who Viewed My Profile" plugin.
* Checked and fixed DOB field visibility for accurate birthday display.
* Removed unnecessary shortcode code for improved efficiency.
* Resolved PHPCS errors and renamed files for consistency and functionality checks.

= 2.0.3 =
* Fix: (#56) Compatibility with BuddyPress v12
* Managed: Extra space in birthday widget
* Fix: Fixed monthly date range did not display upcoming bdays
* Managed: Widget code improvement
* Fix: Fixed Wordpress Coding Standards
* Update: Date function update in monthly limit
* Fix: Compatibility with BuddyPress v12
* Update: Monthly limit code update in widget
* Fix: Fixed birthday display issue from the january, not from the current month 

= 2.0.2 =
* Fix: (#45) Fixed missing string

= 2.0.1 =
* Fix: (#45) Fixed send my wishes add tooltip for message icon

= 1.8.2 =
* Fix: (#39)Fixed birthday is not showing on its day
* Fix: (#39)Fixed birthday is not shown with weekly and monthly filter

= 1.8.1 =
* Fix: (#39) Fixed age/anniversary display issue

= 1.8.0 =
* Fix: (#34) Fixed mail box icon issue for logout user
* Fix: (#38) Added message if not any single user has update their birthday
* Fix: (#35) Added wp timezone to display members birthday

= 1.7.0 =
* Fix: Fixed php fatal error
* Fix: Added widget option show brithdays of followings or friends 

= 1.6.0 =
* Fix: (#21)Fixed show birthdays to only connections
* Fix: Fixed text domain error
* Fix: Fixed escaping function error

= 1.5.0 =
* Fix: #18 - birthday date translation
* Fix: (#16) Update php code structure and css file

= 1.4.0 =
* New Feature: (#13) Option added for display username,nicename and first name
* Fix: (#14) Fixced PHPCS issue

= 1.3.0 =
* Fix: PHPCS fixes
* Fix:  (#12) Added buddypress xprofile custom field types support
* Fix:  (#11) Fixed php notices with one community theme
* Fix:  (#1) Fixed not compatible with varuna theme
* Fix:  (#4) Fixed PHP notices and warnings on widget form
* Fix:  (#8) Display widget if birthday available

= 1.2.0 =
* Fix: Fixed language issue.

= 1.1.0 =
* Fix: Default option and widget Title fixed

= 1.0.0 =
* Initial Release

== Upgrade Notice ==

= 2.2.0 =
Major update with complete UI redesign, improved performance, and enhanced theme compatibility. Recommended for all users.

= 2.1.0 =
Important bug fixes and performance improvements. Update recommended for better functionality.

= 2.0.3 =
Compatibility update for BuddyPress v12 and various bug fixes. Update recommended.