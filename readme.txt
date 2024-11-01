=== WP Scheduled Read-Only ===
Contributors: bastho,
Donate link: http://eelv.fr/adherer/
Tags: readonly, comments, disallow, block, multisite, network
Requires at least: 3.8
Tested up to: 5.8
Stable tag: /trunk
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Schedule readonly mode for your WordPress site

== Description ==
Schedule readonly mode for WordPress on a multisite network, this is useful when you need that nobody change content on a blog or on the whole network.

Read only mode:

* Disallows access to the admin panel for non-admin users
* Temporary deactivate comments on front

= Languages =
* fr_FR : 100%
* en	: 100%

== Installation ==

1. Upload `wp-scheduled-readonly` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress admin


== Changelog ==

= 1.3.1 =
* Fix PHP warning

= 1.3.0 =
* Allow write permission to specific roles

= 1.2.0 =
* Make available for single site AND network
* Security fix: use nonce in setting page
* Update textdomain to match plugin slug
* Improved UI in setting page: added notices and place-holders

= 1.1.0 =
* WP 4.3 compliant
* Code cleanup

= 1.0.0 =
* Initial release

== Upgrade notice ==

No particular informations
