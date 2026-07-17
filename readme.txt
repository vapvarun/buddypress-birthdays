=== Wbcom Designs - Birthday Widget for BuddyPress ===
Contributors: wbcomdesigns, vapvarun
Donate link: https://wbcomdesigns.com/donate/
Tags: buddypress, birthdays, widget, community, members
Requires at least: 5.3
Tested up to: 7.0
Requires PHP: 7.4
Requires Plugins: buddypress
Stable tag: 2.5.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Text Domain: buddypress-birthdays
Domain Path: /languages

Show the upcoming birthdays of your BuddyPress members in a widget, block-widget area or shortcode, so your community never misses a celebration.

== Description ==

**Birthday Widget for BuddyPress** lists your members' upcoming birthdays wherever you want them: a widget area, a page, or a post via the [bp_birthdays] shortcode. Members see who is celebrating, today's birthdays get highlighted, and one click opens a private message to send wishes.

The plugin reads birthdays from a BuddyPress Extended Profile date field (datebox or birthdate), so it needs BuddyPress active with at least one date profile field. It respects BuddyPress field visibility, and every member can hide their own birthday from every surface of the plugin.

= What you get =

**Smart birthday display**
* Show birthdays for all members, friends, or followings (people the member follows).
* Friends needs the BuddyPress Friends component active. Followings needs the third-party BP Follow plugin; without it the option does not appear.
* Time ranges that match the widget: No Limit, Next 7 Days, or Next 30 Days (a rolling 30-day window, not a calendar month).
* Today's birthdays get special highlighting.
* Optional age display (Turning X).
* Pagination: set "Birthdays per page" and members page through the list with prev and next controls. The shortcode takes birthdays_per_page (default 10).

**Privacy and GDPR**
* Per-member opt-out: every member can hide their birthday from the widget, shortcode, activity feed, notifications and emails, from their BuddyPress Settings > General screen.
* The opt-out is enforced centrally on every surface, and can be driven programmatically with the bb_birthday_user_opted_out filter.
* Respects BuddyPress profile field visibility, so private birthday fields stay private.

**Flexible configuration**
* Multiple name display options (username, nickname, first name).
* Customizable date formats.
* Optional emoji.
* Send birthday wishes via private message. This needs the BuddyPress Messages component active and a logged-in viewer; logged-out visitors do not see the button.

**Automatic notifications**
* Automatic birthday email greetings to members.
* Activity feed auto-posts for birthdays.
* BuddyPress notification support.
* Admin daily birthday summary email.
* Configurable send time and templates.

**Display enhancements**
* Zodiac sign display option.
* Confetti animation for celebrations.
* Centralized admin settings page under the WB Plugins menu.

**Developer friendly**
* Theme-compatible styling using CSS inheritance.
* Smart asset loading (assets load only where the widget or shortcode renders).
* Shortcode support: [bp_birthdays] with 11 attributes.
* Filter and action hooks throughout.
* WPCS compliant code.

**Performance**
* Object-cache backed with a configurable cache duration.
* Conditional asset loading.
* Bounded queries on large communities, tunable with the bb_birthdays_widget_candidate_multiplier and bb_birthdays_widget_candidate_cap filters.
* Zero Cumulative Layout Shift (CLS).

**Accessibility ready**
* Honours prefers-reduced-motion, so the confetti and animations stand down for members who ask for less motion.
* Keyboard navigation support.
* ARIA labels on the pagination controls and screen-reader friendly markup.

= Perfect For =

* BuddyPress and BuddyBoss communities that want members to feel recognized
* Membership sites looking for a low-effort engagement win
* Intranets and team sites that celebrate their people
* Any community where a birthday greeting keeps members coming back

= Premium Support =

Our dedicated support team is ready to help you get the most out of this plugin. Whether you need help with setup, customization, or troubleshooting, we're here to assist.

= Documentation =

* **[Documentation and Support](https://docs.wbcomdesigns.com/)** - Setup walkthrough, settings reference, and usage guides

= Translations =

* English (default)
* Ready for translation in your language with included POT file
* RTL language support included

= Links =

* [Plugin Homepage](https://wbcomdesigns.com/downloads/buddypress-birthdays/)
* [Documentation](https://docs.wbcomdesigns.com/)
* [Support](https://wbcomdesigns.com/support/)
* [Request Features](https://wbcomdesigns.com/contact/)

= Compatibility =

* WordPress 5.3 and higher
* PHP 7.4 and higher (8.0+ recommended)
* BuddyPress required, with at least one Extended Profile date field (datebox or birthdate)
* Works with BuddyBoss Platform and the BuddyBoss theme
* Works with Youzify
* Optional: BuddyPress Messages component for the send-wishes button, Friends component for the friends mode, and the BP Follow plugin for the followings mode
* Tested with popular themes including BuddyX and Reign

= What's New in 2.5.0 =

Version 2.5 adds a per-member birthday privacy opt-out so members can hide their birthday from every surface of the plugin, redesigns the admin settings into a card-panel interface under the WB Plugins menu, and bounds every widget query so large communities stay fast. It also fixes the birthday range filters, the cache duration setting, and birthday email template installation.

== More Free Tools from Wbcom Designs ==

Remembering birthdays is a small gesture that keeps members feeling seen and coming back. These other free tools from Wbcom Designs give your community more reasons to show up all year round, from the theme and social network itself to forums, media, events, gamification, directories, jobs, and courses.

* **[BuddyX](https://wbcomdesigns.com/downloads/buddyx-theme/)** - A free, fast community theme for BuddyPress, BuddyBoss and PeepSo with a modern layout and dark mode.
* **[BuddyNext](https://wbcomdesigns.com/downloads/buddynext/)** - Stand up a complete WordPress community with activity streams, member spaces, profiles, direct messaging, and built-in moderation.
* **[Jetonomy](https://wbcomdesigns.com/downloads/jetonomy/)** - Add forums, question-and-answer boards, and idea spaces that stay tidy through trust-based auto-moderation even past 100,000 topics.
* **[Mediaverse](https://wbcomdesigns.com/downloads/mediaverse/)** - Let members build photo and video albums, react, follow each other, and message privately while AI moderation keeps things clean.
* **[Eventonomy](https://wbcomdesigns.com/downloads/eventonomy/)** - Run community events with RSVPs, calendars, and front-end submissions.
* **[WB Gamification](https://wbcomdesigns.com/downloads/wordpress-gamification-plugin/)** - Reward members with points, badges, and leaderboards to keep engagement high.
* **[Listora](https://wbcomdesigns.com/downloads/listora/)** - Publish searchable directories across ten listing types with reviews, maps, and member-submitted entries from the front end.
* **[WP Career Board](https://wbcomdesigns.com/downloads/wp-career-board/)** - Add a job board with front-end listings, applications, and employer profiles.
* **[Learnomy](https://wbcomdesigns.com/downloads/learnomy/)** - Build and sell online courses, auto-grade quizzes, collect payments, and award certificates when learners finish.

== Installation ==

= Before you start =

BuddyPress must be active, and you need at least one Extended Profile field of type "datebox" or "birthdate" for members to enter their birthday. Create one at Users > Profile Fields if you do not have one yet.

= Install the plugin =

1. Upload the plugin files to the `/wp-content/plugins/buddypress-birthdays/` directory, or install the ZIP through Plugins > Add New > Upload Plugin
2. Activate the plugin through the Plugins menu in WordPress
3. Go to **WB Plugins > Birthdays** to configure notifications, display options and cache duration

= Show the birthday list =

Pick whichever fits your theme:

* **Block themes (WordPress 5.9 and later, no Appearance > Widgets screen):** edit your template or a page in the Site Editor or block editor and add the **Legacy Widget** block, then choose "BuddyPress Birthdays". Or just use the shortcode below.
* **Classic themes:** go to **Appearance > Widgets** and drag the "BuddyPress Birthdays" widget into a sidebar or footer area.
* **Any theme, any page or post:** add the `[bp_birthdays]` shortcode. This is the simplest route and works everywhere.

Then set the widget or shortcode options: which members to show (all, friends, followings), the range, the birthday field, how many to show, and how many per page.

= Post-installation setup =

1. Visit **WB Plugins > Birthdays > Overview** to confirm the plugin found your birthday profile field
2. Configure email greetings, activity posts and notifications under the settings tabs
3. Ask members to fill in their birthday field, and let them know they can hide it from Settings > General if they prefer
4. See the [documentation](https://docs.wbcomdesigns.com/) for detailed instructions

= Requirements =

* WordPress 5.3 or higher
* PHP 7.4 or higher
* BuddyPress, with at least one Extended Profile date field

== Frequently Asked Questions ==

= Where do birthdays come from? =

From a BuddyPress Extended Profile field of type "datebox" or "birthdate". Create a date field at Users > Profile Fields and select it in the widget settings. If you do not pick one in the shortcode, the plugin uses the first date field it finds.

= Can I show birthdays in a post or page? =

Yes. Use the `[bp_birthdays]` shortcode anywhere in your content. See the full attribute list below.

= What shortcode attributes are available? =

All eleven, with their defaults:

* `title` - heading above the list. Default: Upcoming Birthdays
* `limit` - how many birthdays to fetch in total. Default: 5
* `show_age` - show "Turning X". `yes` or `no`. Default: yes
* `show_message_button` - show the send-wishes button. `yes` or `no`. Default: yes. Needs the Messages component and a logged-in viewer
* `date_format` - PHP date format for the displayed date. Default: `F d`
* `range_limit` - `no_limit`, `weekly` (next 7 days) or `monthly` (next 30 days). Default: no_limit
* `show_birthdays_of` - `all`, `friends` or `followers` (followers means the people the viewer follows, and needs BP Follow). Default: all
* `display_name_type` - `user_name`, `nick_name` or `first_name`. Default: user_name
* `emoji` - the emoji shown next to each name, for example `balloon`, `cake` or `none`. Default: balloon
* `field_name` - **the numeric ID of the xProfile date field, not its name.** Despite the attribute name, this expects a number, for example `field_name="12"`. Leave it out and the plugin auto-discovers the first datebox or birthdate field
* `birthdays_per_page` - how many birthdays per page in the paginated list. Default: 10

Example: `[bp_birthdays limit="20" birthdays_per_page="5" range_limit="monthly" show_age="no"]`

= Can members hide their birthday? =

Yes. Every member can tick "Hide my birthday" on their BuddyPress **Settings > General** screen. When they do, they are removed from the widget, the shortcode, activity posts, notifications and emails. Developers can force the same result with the `bb_birthday_user_opted_out` filter.

= Can members send birthday wishes? =

Yes, when the BuddyPress Messages component is active and the viewer is logged in. The wish button opens the private message compose screen with the birthday member preselected. Logged-out visitors do not see the button.

= What is the difference between friends and followings? =

Friends shows the viewer's BuddyPress friends and needs the Friends component active. Followings shows the people the viewer follows and needs the third-party BP Follow plugin. If a component or plugin is not active, its option does not appear in the widget.

= Does this work with BuddyBoss? =

Yes, the plugin works with BuddyBoss Platform and the BuddyBoss theme.

= Is it compatible with Youzify? =

Yes, the plugin works with Youzify and other popular BuddyPress extensions.

= Does it work with custom themes? =

Yes. The plugin uses theme-compatible styling that inherits your theme's colors and fonts.

= Is it mobile responsive? =

Yes. The widget is built mobile-first and works on all screen sizes.

= Does it cache birthday data? =

Yes. The plugin uses the WordPress object cache. The refresh interval is configurable in the admin settings (default 30 minutes).

= Can I customize the date format? =

Yes. Set a PHP date format like "F d" for "January 15", "M d" for "Jan 15", or "d M" for "15 Jan", in the widget settings or the shortcode's date_format attribute.

= Does it respect privacy settings? =

Yes. The plugin respects BuddyPress field visibility, so private birthday fields are not shown to unauthorized users, and it honours each member's own opt-out.

= Will it slow down a large community? =

No. Every widget mode fetches a bounded, batch-primed candidate pool instead of walking every member. Tune the bounds with the `bb_birthdays_widget_candidate_multiplier` and `bb_birthdays_widget_candidate_cap` filters.

== Screenshots ==

1. Upcoming Birthdays list on the front end, with member avatars, ages and "send wishes" buttons.
2. Member Settings > General, showing the per-member "Hide my birthday" opt-out checkbox.
3. Admin overview dashboard with birthday-field stats and current channel configuration.
4. Discover tab linking to other free Wbcom Designs community plugins.

== Changelog ==

= 2.5.0 - July 2026 =

Redesigned admin settings, big-community performance bounds, and a batch of widget and notification fixes.

* New      - Added German, Spanish, French, Italian and Portuguese (Brazil) translations.
* New      - Redesigned admin settings: card-panel interface under the WB Plugins menu with an overview dashboard and grouped settings tabs. The settings page URL and stored option are unchanged.
* New      - Birthday wish tracking: clicking the send-wishes button now records the wish before opening the compose screen, and records older than 7 days are cleaned up daily.
* New      - Per-member birthday privacy opt-out (GDPR): members can hide their birthday from every plugin surface (widget, shortcode, activity, notifications, emails) from their BuddyPress Settings > General screen. Override programmatically with the bb_birthday_user_opted_out filter.
* Improve  - Large-community performance: every widget mode (all members, friends, followers) now fetches a bounded, batch-primed candidate pool instead of iterating every member. Tune via the bb_birthdays_widget_candidate_multiplier and bb_birthdays_widget_candidate_cap filters.
* Improve  - Plugin lifecycle: cron events are scheduled on activation, cleared on deactivation, and all plugin options and wish-tracking user meta are removed on uninstall.
* Improve  - Translations now load from the bundled languages folder for self-hosted installs.
* Improve  - Keyboard focus visibility on admin form controls.
* Fix      - Bundled translations could never load because the Domain Path header was missing. WordPress 6.7+ needs it to locate the translation files.
* Fix      - The twelve zodiac sign names, shown in the birthday tooltip, had no translatable source and always rendered in English.
* Fix      - The birthday activity and notification defaults differed between the settings screen and the scheduled task, so the same site could post two different messages. Both now use the same text, without emoji.
* Fix      - Screen-reader labels on the wish button and the widget had no translatable source.
* Fix      - Frontend styles and scripts are now versioned with the plugin version, so updating no longer leaves you on a stale cached copy. Previously the confetti option and wish recording could silently do nothing after an update until the browser cache was cleared.
* Fix      - Widget birthday query: the field date format is now converted to MySQL specifiers, so weekly and monthly ranges and upcoming-birthday ordering work again.
* Fix      - Database placeholder notice raised by the widget birthday query.
* Fix      - Cache Duration setting is now honoured on frontend renders instead of a fixed 30 minutes.
* Fix      - Birthday email templates now install correctly instead of being skipped by a dead guard.
* Fix      - Documentation link now points to the correct docs page.
* Compat   - Tested with WordPress 7.0.
* Compat   - The minimum supported WordPress version is now stated correctly as 5.3, which is what the plugin has actually required since it began using wp_date() and wp_timezone().

= 2.4.2 =
* Fix: Birthday celebration activities were showing empty content in the activity feed.
* Fix: Removed leftover console logging from production code.
* Update: Refreshed POT file for translations.

= 2.4.1 =
* Code Quality: Fixed all WordPress Coding Standards (WPCS) violations across all PHP files
* Code Quality: Applied inline comment punctuation, Yoda conditions, and proper spacing
* Code Quality: Added missing translators comments for i18n functions with placeholders
* Code Quality: Added phpcs:ignore for legacy file naming and widget registration
* Code Quality: Fixed all Plugin Check errors (0 errors)

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

= 2.5.0 =
Members can now hide their own birthday, the admin settings are redesigned, and large communities render faster. This release also fixes stale cached styles and scripts after an update, so the confetti and wish-recording options work right away.

= 2.2.0 =
Major update with complete UI redesign, improved performance, and enhanced theme compatibility. Recommended for all users.

= 2.1.0 =
Important bug fixes and performance improvements. Update recommended for better functionality.

= 2.0.3 =
Compatibility update for BuddyPress v12 and various bug fixes. Update recommended.
