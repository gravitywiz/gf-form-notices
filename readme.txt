=== GF Form Notices ===
Contributors: gravitywiz
Tags: gravity forms, notices, announcements, scheduled messages, banners
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Display scheduled messages above Gravity Forms based on date ranges. Perfect for office closings, promotions, announcements, and more.

== Description ==

GF Form Notices is a Gravity Forms add-on that allows you to display custom messages above your forms during specified date ranges. Each notice is managed as a feed, making it easy to create, edit, and organize multiple announcements.

= Features =

* **Feed-Based Management** - Each notice is a separate feed that can be independently configured
* **Flexible Date Formats** - Support for specific dates (YYYY-MM-DD) and recurring dates with wildcards (*-12-25)
* **Rich Text Messages** - Use WordPress editor for formatting notice messages
* **Date Merge Tags** - Dynamic date formatting in messages with {start_date:FORMAT}, {end_date:FORMAT}, and {next_date:FORMAT}
* **Feed Ordering** - Sort and prioritize multiple notices
* **Advance Notice** - Messages display one weekday early to give users advance warning
* **Multiple Active Notices** - Display multiple messages when date ranges overlap

= Date Format Examples =

* Specific date: `2024-12-25`
* Recurring date: `*-12-25` (Christmas every year)
* Recurring date: `*-07-04` (July 4th every year)

= Message Merge Tags =

Use these merge tags in your notice messages:

* `{start_date:l, F jS}` - Display start date as "Monday, December 25th"
* `{end_date:l, F jS}` - Display end date as "Tuesday, December 26th"
* `{next_date:l, F jS}` - Display next business day after end date

The format portion uses PHP date format strings. Common formats:
* `l` - Full day name (Monday)
* `F` - Full month name (December)
* `jS` - Day with ordinal suffix (25th)
* `Y` - 4-digit year (2024)
* `m/d/Y` - Date as 12/25/2024

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/gf-form-notices/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to any Gravity Form → Settings → Form Notices
4. Create your first notice feed

== Frequently Asked Questions ==

= How do I create a recurring notice? =

Use wildcards in your date fields. For example, `*-12-25` will match December 25th of any year.

= Can I have multiple notices active at once? =

Yes! If multiple date ranges overlap, all applicable messages will be displayed.

= How early does the message appear? =

Messages appear one weekday before the start date, giving users advance notice.

= What are some use cases for this plugin? =

* Office closings and holiday hours
* Limited-time promotions or sales
* Event announcements and deadlines
* Maintenance windows
* Seasonal messages
* Support availability updates

== Changelog ==

= 1.0.0 =
* Initial release
* Feed-based architecture
* Support for specific and recurring dates
* Rich text message editor
* Date merge tags
* Feed ordering
