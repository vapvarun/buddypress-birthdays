# QA Checklist - BuddyPress Birthdays

**Version:** 2.4.1
**Date:** April 2026

---

## Pre-Release QA

### 1. Installation & Activation
- [ ] Plugin installs without errors
- [ ] Plugin activates without errors
- [ ] No PHP warnings or notices on activation
- [ ] BuddyPress dependency check works correctly

### 2. Widget Functionality
- [ ] Widget appears in Appearance > Widgets
- [ ] Widget saves settings correctly
- [ ] Widget displays on front-end
- [ ] Birthday field dropdown populated correctly
- [ ] Date format options work (F d, M d, d F, etc.)
- [ ] Display age option works
- [ ] Birthday range (weekly/monthly/no_limit) filters work
- [ ] Show birthdays of (all/friends/followers) filters work
- [ ] Emoji options (cake/balloon/party/none) display correctly
- [ ] Send wishes button works (when BP messaging enabled)
- [ ] Display name types (user_name/nickname/first_name) work
- [ ] Pagination works when birthdays_per_page is set
- [ ] Today's birthdays highlighted correctly

### 3. Shortcode Functionality
- [ ] `[bp_birthdays]` basic shortcode works
- [ ] `limit` parameter works
- [ ] `show_age` parameter works
- [ ] `date_format` parameter works
- [ ] `range_limit` parameter works
- [ ] `show_birthdays_of` parameter works
- [ ] `display_name_type` parameter works
- [ ] `emoji` parameter works
- [ ] `field_name` parameter works
- [ ] `birthdays_per_page` parameter works
- [ ] `title` parameter works
- [ ] `show_message_button` parameter works
- [ ] `confetti` parameter works (overrides global setting)
- [ ] `zodiac` parameter works (overrides global setting)

### 4. Admin Settings (Settings > BuddyPress Birthdays)
- [ ] Settings page accessible under BuddyPress menu (or Settings menu)
- [ ] Setup wizard runs without errors
- [ ] General tab saves correctly
  - [ ] Default birthday field selection works
  - [ ] Cache duration setting works
- [ ] Email notifications tab saves correctly
  - [ ] Enable birthday emails toggle works
  - [ ] Email send time configuration works
- [ ] Activity feed tab saves correctly
  - [ ] Enable activity posts toggle works
  - [ ] Activity message saves correctly
- [ ] Notifications tab saves correctly
  - [ ] Enable notifications toggle works
  - [ ] Friends only option works
  - [ ] Notification text saves correctly
- [ ] Display tab saves correctly
  - [ ] Confetti animation toggle works
  - [ ] Zodiac sign display toggle works

### 5. Notifications
- [ ] Birthday email sends correctly
- [ ] Email placeholders replaced correctly ({{{recipient.name}}}, {{{birthday.age}}}, {{{site.name}}})
- [ ] Activity feed post works
- [ ] BuddyPress notification works
- [ ] Email content customization works via BuddyPress Emails
- [ ] Send time configuration works

### 6. Privacy & Visibility
- [ ] xProfile visibility settings respected
- [ ] Private birthday fields hidden from widget
- [ ] Onlyme visibility works correctly

### 7. Performance
- [ ] Object caching works
- [ ] Cache clears on settings update
- [ ] Cache clears on profile update
- [ ] No N+1 query issues
- [ ] Date filter works at database level (not PHP only)

### 8. Accessibility
- [ ] aria-label on pagination buttons
- [ ] role="alert" on error containers
- [ ] aria-live on dynamic content
- [ ] Keyboard navigation works
- [ ] Screen reader compatible

### 9. Compatibility
- [ ] Works with BuddyPress 12+
- [ ] Works with BuddyBoss Platform
- [ ] Works with BuddyBoss Theme
- [ ] Works with Youzify
- [ ] Works with Reign Theme

### 10. Code Quality
- [ ] No WPCS errors
- [ ] No PHPStan errors
- [ ] No security issues (XSS, SQL injection)
- [ ] All functions have proper escaping
- [ ] Nonce verification in place
- [ ] All hooks documented

---

## Bug-Specific Checks

### Original Bug: Username A-D Filter Issue
- [ ] Users with usernames starting with e-z display correctly
- [ ] No artificial limit on user query
- [ ] All birthday users show regardless of username

### Pagination Feature
- [ ] Pagination renders when birthdays_per_page set
- [ ] Previous/Next buttons work
- [ ] Page URL parameter updates correctly
- [ ] Works with all filter combinations

### New Features: Confetti Animation
- [ ] Confetti animation displays for today's birthdays
- [ ] Confetti only shows when enabled in settings
- [ ] Confetti works with shortcode `confetti="yes"`
- [ ] Confetti animation triggers correctly in browser
- [ ] No JavaScript errors when confetti loads
- [ ] Confetti respects global setting when not overridden

### New Features: Zodiac Signs
- [ ] Zodiac signs display next to birthdays
- [ ] All 12 zodiac symbols show correctly (♈ ♉ ♊ ♋ ♌ ♍ ♎ ♏ ♐ ♑ ♒ ♓)
- [ ] Zodiac calculation works for leap years
- [ ] Zodiac works with shortcode `zodiac="yes"`
- [ ] Zodiac signs respect global setting when not overridden
- [ ] Zodiac tooltips show correct sign names

### Email System Integration
- [ ] Birthday emails sent via BuddyPress Emails system
- [ ] Email template "Birthday Greeting" exists in BP Emails
- [ ] Email customization works in BP Emails admin
- [ ] Email tokens work correctly in templates
- [ ] Email send time respects timezone settings

### Cache Performance
- [ ] Cache duration set to 60 minutes by default
- [ ] Cache invalidation works on profile updates
- [ ] Cache invalidation works on friendship changes
- [ ] Cache invalidation works on settings changes
- [ ] Object cache works with Redis/Memcached

---

## Post-Release Verification
- [ ] readme.txt matches plugin version
- [ ] documentation.md matches plugin version
- [ ] Changelog entries accurate
- [ ] Translation files up to date
- [ ] Plugin works on fresh WordPress install
