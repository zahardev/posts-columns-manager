=== Plugin Name ===
Contributors: zahardoc
Tags: admin, editor, manage, columns,
Requires at least: 4.7
Tested up to: 5.6
Stable tag: 1.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

This plugin provides possibility to add custom columns in WordPress posts editor.
It supports ACF fields. You can either add ACF fields columns, or custom taxonomies columns.
Additionally, there is an option to filter and sort by custom columns (if that's possible).

== Frequently Asked Questions ==

= I want to show ACF field in the posts editor. Is it possible? =

Yes, this is one of the main plugin features.

= I don't use ACF. Will this plugin work in this case? =

Yes, you can still use it to manage custom taxonomies.

= Does the plugin supports custom post types? =

Yes, it does.

= Why is the "Sort by"|"Is numeric" checkbox not active? =

You can sort and filter only columns for custom fields with type "text" and "number".
"Is numeric" checkbox is used for switching the sorting type (by alphabet or by number).
For example, there are 3 field values: 5, 2, 12.
If the "Numeric" checkbox is not checked, then sorting in ascending order is performed as follows:
12, 2, 5. If checked, the sorting treats values as numbers - 2, 5, 12.


== Screenshots ==

1. Plugin settings - check which columns do you want to add in the editor and additional settings for them.
2. Custom columns are added to the posts editor.

== Changelog ==

= 1.0 =
* Plugin release, all the main features are added
