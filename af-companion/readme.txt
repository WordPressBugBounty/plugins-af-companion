=== AF Companion – WordPress Performance, Maintenance & Site Health ===
Contributors: afthemes
Tags: performance, database, accessibility, broken-links, seo
Requires at least: 6.0
Tested up to: 7.1
Stable tag: 3.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Optimize WordPress performance, clean your database, audit image alt text, monitor content and links, and check site health.

== Description ==

= WordPress Performance, Maintenance, Content Audits & Site Health =

**AF Companion** brings essential WordPress performance, maintenance, and website monitoring tools together in one dashboard. Optimize common WordPress settings, maintain your database, review website content, find missing image alternative text, monitor links, and check important server and WordPress health indicators.

Whether you manage a blog, business website, online magazine, or publishing platform, AF Companion helps you take care of routine website maintenance without relying on a collection of separate utility plugins.

**Key features include:**

* **Performance Optimization:** Control selected WordPress features and frontend optimizations, including Emojis, oEmbeds, XML-RPC, HTML minification, asynchronous JavaScript loading, Google Fonts display, and other asset settings.
* **Database Maintenance:** Review database overhead and clean up selected revisions, expired transients, spam comments, and orphaned post metadata with dry-run scanning and batched deletion.
* **Content Monitor:** Monitor website content and identify content that may need attention as part of your regular maintenance workflow.
* **Alt Text Manager:** Audit your media library for images with missing alternative text and update image alt text.
* **Link Monitor:** Check website link health and identify links that may need review.
* **Website Diagnostics:** Check important WordPress and server components, including REST API availability, loopback requests, PHP configuration, scheduled tasks, and database information.
* **SEO and Analytics Integrations:** Configure supported analytics, tracking, social metadata, schema, and webmaster verification settings.
* **Legacy Demo Import:** Existing demo and manual import tools remain available for supported AF themes setup workflows.

Manage these tools from the **AF Companion** dashboard in your WordPress administration area.

**Documentation:** https://docs.afthemes.com/

**Support:** https://afthemes.com/support/

**AF themes:** https://afthemes.com/

== Performance Optimization ==

Improve your WordPress site's configuration with controls for selected core features, frontend assets, and loading behavior.

Available optimization settings include:

* Disable WordPress Emojis when they are not needed.
* Disable oEmbeds.
* Disable XML-RPC when your website and integrations do not require it.
* Remove selected header metadata, including RSD, WLW, and WordPress version information.
* Minify HTML output.
* Load JavaScript asynchronously.
* Configure Google Fonts display using `font-display: swap`.
* Remove query strings from selected static assets.
* Disable Dashicons for visitors who are not logged in.
* Control WordPress Heartbeat API frequency.
* Adjust the editor autosave interval.
* Limit the number of stored post revisions.
* Remove selected block library CSS when it is not needed.
* Enable lazy loading for video iframes.

These settings provide control over selected WordPress behaviors and frontend resources. Results depend on your theme, plugins, hosting environment, and site configuration. Test optimization settings with your active theme and plugins before enabling them on a production website.

== Database Maintenance ==

Keep your WordPress database manageable with tools for reviewing and cleaning selected data.

Database maintenance features include:

* Scan database overhead before performing cleanup.
* Preview eligible cleanup operations using dry-run scanning.
* Remove selected post revisions.
* Clean expired transients.
* Remove spam comments.
* Remove orphaned post metadata.
* Process deletion tasks in batches to help reduce the risk of long-running database operations.
* Inspect database information and autoloaded options.
* Convert supported MyISAM database tables to InnoDB.

**Important:** Database cleanup and table conversion can affect stored website data. Review the available operations and create a database backup before performing maintenance. Test database changes on a staging site when possible.

== Content Monitor ==

Keep track of website content as part of your regular publishing and maintenance workflow.

The Content Monitor module provides content monitoring tools to help you review existing content and identify items that may need attention.

Use it as part of a regular editorial maintenance routine to help keep published pages and posts up to date.

== Alt Text Manager ==

Review image alternative text across your WordPress media library.

The Alt Text Manager helps you:

* Audit media library images for missing alternative text.
* Identify images that may need descriptive alt text.
* Update image alternative text from the management interface.

Alternative text helps communicate the purpose or content of meaningful images to people using assistive technologies. Write descriptions that fit the image's context rather than adding keywords for search engines.

An attachment's alt text and an image's rendered markup are not always the same. Images inserted through custom HTML, page builders, or other plugins may require separate review.

== Link Monitor ==

Keep an eye on links across your website with the Link Monitor module.

Use link monitoring to review website link health and identify links that may need attention. Checking links regularly can help you find outdated or unavailable destinations and maintain a better experience for visitors.

Review reported link issues before changing or removing URLs. A destination that temporarily blocks automated requests or limits traffic may not necessarily be unavailable to visitors.

== SEO, Analytics & Social Integrations ==

Configure supported tracking, metadata, and publishing integrations from the AF Companion dashboard.

**Analytics and tracking**

* Google Analytics 4 (GA4).
* Google Tag Manager.
* Meta Pixel.
* Microsoft Clarity.

**SEO and structured data**

* Article Schema (JSON-LD) settings.
* SEO plugin compatibility checks to help prevent duplicate schema output.
* Supported integrations with Yoast SEO, Rank Math, All in One SEO, and SEOPress.

**Social sharing**

* Open Graph settings.
* Fallback social sharing images.
* Live social share card preview for supported social platforms, including Facebook, X, and WhatsApp.

**Webmaster verification**

* Google Search Console.
* Bing Webmaster Tools.
* Yandex.
* Pinterest.

**Publisher tools**

* Google News keywords and related publisher metadata.
* Copyright attribution URL settings.
* RSS feed content attribution footer.
* AdSense Auto Ads integration.
* Mailchimp API settings.

Tracking and third-party integrations may require configuration or account credentials from the relevant service. Check your site's privacy and consent requirements before enabling tracking scripts.

If another SEO plugin already outputs structured data, review your schema settings to avoid duplicate markup.

== Website Health & Diagnostics ==

Review important parts of your WordPress installation and hosting environment.

Available diagnostic checks include:

* WordPress REST API accessibility.
* Loopback request availability.
* PHP memory and execution limits.
* PHP extension availability.
* WP-Cron monitoring.
* Database information and autoloaded options.
* Imagick and GD image library availability.
* MyISAM and InnoDB database table information.

These checks help identify configuration issues that may require attention from your hosting provider or website administrator. AF Companion does not replace server monitoring or a complete security audit.

== Legacy Demo Import Tools ==

AF Companion retains its legacy demo and manual import functionality for supported AF themes workflows.

The manual import interface supports the following files:

* **XML:** Import WordPress demo content.
* **WIE or JSON:** Import widget settings exported using a compatible widget import/export tool.
* **DAT:** Import Customizer settings exported from the same theme.

The XML file is required for a content import. Widget and Customizer files are optional.

Customizer settings should be imported only when the export file was created using the same theme. Always review imported content and settings before using them on a live website.

== Installation ==

1. In your WordPress dashboard, go to **Plugins > Add New**.
2. Search for **AF Companion**.
3. Install and activate the plugin.
4. Open **AF Companion** from your WordPress administration menu.
5. Select the module you want to configure and review its available settings.

Before enabling performance optimizations or running database maintenance, create a backup of your website.

== Frequently Asked Questions ==

= What is AF Companion? =

AF Companion is a WordPress maintenance and optimization plugin. It includes performance controls, database maintenance, content monitoring, image alt text auditing, link monitoring, website diagnostics, and supported SEO and analytics integrations.

= Where can I find the AF Companion dashboard? =

After activating the plugin, open **AF Companion** from the WordPress administration menu. The dashboard provides access to its available modules and settings.

= Can AF Companion help improve WordPress performance? =

Yes. AF Companion includes settings for selected WordPress core features, frontend assets, JavaScript loading, HTML output, Heartbeat API frequency, editor autosave, post revisions, and video iframe lazy loading. The impact of each setting depends on your theme, plugins, hosting, and website configuration.

= Does AF Companion include database cleanup tools? =

Yes. You can review database overhead and clean selected post revisions, expired transients, spam comments, and orphaned post metadata. The maintenance tools include dry-run scanning and batched deletion.

Always back up your database before performing cleanup operations.

= What does the Content Monitor do? =

Content Monitor provides tools for monitoring website content and identifying content that may need attention as part of your regular publishing and maintenance workflow.

= How does the Alt Text Manager help with accessibility? =

The Alt Text Manager audits your media library to identify images with missing alternative text and provides a way to update image alt text. Meaningful images should have descriptions appropriate to their context, while decorative images generally do not need descriptive alternative text.

= Can AF Companion check broken links? =

AF Companion includes a Link Monitor module for reviewing website link health and identifying links that may need attention. Review reported issues before changing URLs, since temporary connection problems or destination-side restrictions can affect automated checks.

= Does AF Companion include SEO features? =

Yes. AF Companion includes supported Article Schema settings, SEO plugin compatibility checks, Open Graph settings, webmaster verification fields, and publisher-specific metadata. It is intended to complement your SEO workflow, not replace a dedicated SEO plugin.

= Which analytics services are supported? =

The available integrations include Google Analytics 4, Google Tag Manager, Meta Pixel, and Microsoft Clarity. You may need credentials or configuration details from the relevant service.

= Can I use AF Companion with an SEO plugin? =

Yes. AF Companion includes schema compatibility checks for supported SEO plugins, including Yoast SEO, Rank Math, All in One SEO, and SEOPress. Review your configuration to avoid duplicate structured data.

= Can I use AF Companion with a caching plugin? =

AF Companion provides WordPress optimization settings that may be used alongside a caching plugin. However, some optimization features can overlap or conflict with caching, minification, and JavaScript optimization tools. Test settings individually and disable duplicate functionality where necessary.

= Does AF Companion still include demo import functionality? =

Yes. Legacy demo and manual import tools remain available for supported AF themes workflows. The plugin's primary focus is now WordPress performance, maintenance, monitoring, and site health.

= Is AF Companion suitable for websites that do not use AF themes? =

Many of AF Companion's performance, maintenance, monitoring, and diagnostic tools are intended for general WordPress websites. Legacy demo import functionality is intended for compatible AF themes workflows.

= Where can I get help with AF Companion? =

For documentation and support, visit:

* Documentation: https://docs.afthemes.com/
* Support: https://afthemes.com/support/

== Screenshots ==

1. **AF Companion Dashboard:** Overview of the plugin's modules and administration navigation.
2. **Performance Optimization:** Controls for selected WordPress core features and frontend optimization settings.
3. **Database Maintenance:** Database overhead scanning and available cleanup operations.
4. **Content Monitor:** Content monitoring interface for reviewing website content.
5. **Alt Text Manager:** Media library audit showing images with missing alternative text.
6. **Link Monitor:** Link monitoring interface showing website link health information.
7. **SEO and Analytics Integrations:** Settings for supported analytics, structured data, social metadata, and webmaster verification.
8. **Website Health & Diagnostics:** WordPress and server diagnostic information.

## AF themes
>The most flexible WordPress themes and plugins crafted for the future web.

##  Access a diverse collection of ready-to-use websites with AF Companion 🌟

**💁 Pre-made full website starter demo sites**
This plugin provides you with access to hundreds of starter demo websites that are featured on a single dashboard.

**💁 One Click Demo Import**
All you have to do is choose the demo that best meets your needs, import it, and you're ready to start!

**💁 Your website in Minutes**
You'll get access to the sites in a matter of minutes.

🚀 Enhance your website further by unlocking premium demos through our All Themes Plan Bundles or selecting individual Premium Themes.

## Newspaper Website using Gutenberg Blocks, Patterns, Templates - Page-builder like Features
[youtube https://www.youtube.com/watch?v=niy3RhamIOE]

## Facilitating Seamless Guest Author Collaboration for WordPress Posts | WP Post Author Plugin
[youtube https://www.youtube.com/watch?v=8qHrfDw0zCE]

## Get the outstanding themes from AF themes
__ Check all of our [Free](https://afthemes.com/products/category/free/) themes __

👉 [CoverNews](https://afthemes.com/products/covernews/)
👉 [ChromeNews](https://afthemes.com/products/chromenews/)
👉 [MoreNews](https://afthemes.com/products/morenews/)
👉 [Newsphere](https://afthemes.com/products/newsphere/)
👉 [DarkNews](https://afthemes.com/products/darknews/)
👉 [Newspare](https://afthemes.com/products/newspare/)
👉 [BroadNews](https://afthemes.com/products/broadnews/)
👉 [EnterNews](https://afthemes.com/products/enternews/)
👉 [Newsium](https://afthemes.com/products/newsium/)
👉 [Newsever](https://afthemes.com/products/newsever/)
👉 [Shopical](https://afthemes.com/products/shopical/)
👉 [StoreCommerce](https://afthemes.com/products/storecommerce/)
👉 [Storeship](https://afthemes.com/products/storeship/)
👉 [Elegant Magazine](https://afthemes.com/products/elegant-magazine/)
👉 [And More..](https://afthemes.com/products/category/free/)

__ Check all of our [Premium](https://afthemes.com/products/category/pro/) themes__

👉 [CoverNews Pro](https://afthemes.com/products/covernews-pro/)
👉 [ChromeNews Pro](https://afthemes.com/products/chromenews-pro/)
👉 [MoreNews Pro](https://afthemes.com/products/morenews-pro/)
👉 [Newsphere Pro](https://afthemes.com/products/newsphere-pro/)
👉 [ReviewNews Pro](https://afthemes.com/products/reviewnews-pro/)
👉 [DarkNews Pro](https://afthemes.com/products/darknews-pro/)
👉 [BroadNews Pro](https://afthemes.com/products/broadnews-pro/)
👉 [EnterNews Pro](https://afthemes.com/products/enternews-pro/)
👉 [Newsium Pro](https://afthemes.com/products/newsium-pro/)
👉 [Newsever Pro](https://afthemes.com/products/newsever-pro/)
👉 [Shopical Pro](https://afthemes.com/products/shopical-pro/)
👉 [StoreCommerce Pro](https://afthemes.com/products/storecommerce-pro/)
👉 [Storeship Pro](https://afthemes.com/products/storeship-pro/)
👉 [Elegant Magazine Pro](https://afthemes.com/products/elegant-magazine-pro/)
👉 [And More..](https://afthemes.com/products/category/pro/)

## Welcome in for other amazing plugins from AF themes

__ Check all of our [Free](https://profiles.wordpress.org/afthemes/#content-plugins) plugins __

👉 [Templatespare](https://templatespare.com)
👉 [WP Post Author](https://afthemes.com/plugins/wp-post-author/)
👉 [Blockspare](https://blockspare.com)
👉 [Elespare](https://afthemes.com/plugins/elespare/)
👉 [AF Translate](https://wordpress.org/plugins/af-translate/)
👉 [Latest Posts Block Lite](https://wordpress.org/plugins/latest-posts-block-lite/)
👉 [Magic Content Box Lite](https://wordpress.org/plugins/magic-content-box-lite/)


__ Check all of our [Premium](https://afthemes.com/plugins) plugins __

👉 [WP Post Author Pro](https://afthemes.com/plugins/wp-post-author/)
👉 [Blockspare  Pro](https://blockspare.com)
👉 [Elespare Pro](https://afthemes.com/plugins/elespare/)
👉 [Latest Posts Block](https://1.envato.market/2xxbD)
👉 [Magic Content Box](https://1.envato.market/NOO1O)
👉 [Pricing Table Block](http://1.envato.market/XxAZYg)
👉 [Team and Testimonial Block](https://1.envato.market/Jr0QzE)



== License ==

AF Companion uses 'One Click Demo Import' plugin script
https://github.com/proteusthemes/one-click-demo-import
(C) 2016 ProteusThemes.com
Licensed under the GNU General Public License v2.0,
https://www.gnu.org/licenses/gpl-2.0.html

AF Companion uses 'Wordpress Importer' plugin script
https://github.com/humanmade/WordPress-Importer
(C) 2016 @humanmade
Licensed under the GNU General Public License v2.0,
https://www.gnu.org/licenses/gpl-2.0.html


== Copyright ==

AF Companion, Copyright 2026 AF themes

AF Companion is distributed under the terms of the GNU GPL

This program is free software; you can raftcstribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.


== Changelog ==

## 3.0.0 - 05/11/2026

* Introduced a unified administration header with AF themes branding, plugin version information, and responsive module navigation.
* Added the Speed Booster control center with database write throttling, post revision limits, editor autosave interval controls, Heartbeat API frequency settings, selective block library CSS removal, and video iframe lazy loading.
* Added the Alt Text Manager for auditing media library images with missing alternative text.
* Added Content Monitor and Link Monitor modules.
* Improved database maintenance with dry-run scanning and batched cleanup operations for selected revisions, expired transients, spam comments, and orphaned post metadata.
* Improved system diagnostics for loopback requests, REST API accessibility, PHP extensions, and scheduled tasks.
* Added database table conversion support from MyISAM to InnoDB.
* Added a live social sharing preview for supported social platforms.
* Added webmaster verification settings for Google Search Console, Bing, Yandex, and Pinterest.
* Improved SEO schema compatibility checks for supported SEO plugins.
* Added publisher metadata settings, including Google News keywords, copyright attribution URLs, and RSS content attribution footers.
* Added a recommended free themes showcase for AF themes.
* Improved administration layout, input sanitization, capability checks, and modular code organization.


## 2.2.0 - 15/09/2026

* Fixed a security vulnerability in the demo import file upload process that could allow unauthorized file uploads and privilege escalation.
* Improved file upload security by restricting uploads to allowed MIME types.
* Improved demo import security and validation for content, widget, and customizer files.
* Fixed an undefined array key warning in the demo import process.
* Added support for displaying demos based on the selected demo import conditions.
* Improved compatibility with all AF themes plans.
* Improved plugin code structure and maintainability.
* Improved admin menu organization.
* Fixed issues with manually uploaded files during demo import.
* Added additional security checks for file uploads.
* Security fix reported by Farhan Fawwaz Saputra and coordinated with WPScan/Jetpack.


## 2.0.0 - 01/05/2026

Changes:
*New – Performance & Speed Booster engine integration*
*New – Growth Tools suite for GA4, Adsense, and Meta Pixel integration*
*New – System Status & Engine Diagnostics dashboard*
*New – Automated Article Schema (JSON-LD) for SEO*
*New – Core Cleanup features (Disable Emojis, oEmbeds, XML-RPC)*
*New – Advanced Header Cleanup (RSD, WLW, WP Version removal)*
*New – Frontend Optimization (HTML Minification, Async JS, Font-display swap)*
*New – Asset Management (Disable jQuery Migrate, Remove Query Strings)*
*New – Image Optimization (Native Lazy Loading support)*
*New – Gutenberg Bloat Removal (Optional Block Library CSS cleanup)*
*New – Server Health Monitoring (Memory, Execution Time, OPcache)*
*New – Database Health Monitoring (Autoloaded options, Size, Overhead)*
*New – Analytics Integration for Hotjar and Microsoft Clarity*
*Update – Major UI/UX overhaul of the AF Companion Dashboard*
*Update – Refined One-Click Import logic for modern block themes*
*Update – Improved error handling and diagnostic reporting*
*Update – Compatibility with WordPress 6.9+ and PHP 8.2+*


### 1.2.6 - 25/08/2023

Changes:
*Update – Compatibility with PHP 8.2*

### 1.2.5 - 25/08/2023

Changes:
*Update – Compatibility with WP 6.3*

### 1.2.4 - 27/04/2023

Changes:
*Update – Compatibility with WP 6.2*

### 1.2.3 - 06/01/2023

Changes:
*Update – Compatibility with WP 6.1.1*


### 1.2.2 - 20/01/2022

Changes:
*New – Language file added for translation*
*Update – Functional updates*

### 1.2.1 - 22/12/2021

Changes:
*New – Language file added for translation*
*Update – Security updates*


### 1.2.0 - 22/12/2021

Changes:
*Fixed – check_ajax_referer issue resolved*

### 1.1.2 - 16/12/2021

Changes:
*Fixed – Unused functions removed*

### 1.1.1 - 12/11/2021

Changes:
*Fixed – Unused functions removed*

### 1.1.0 - 12/11/2021

Changes:
*Fixed - Required plugins setup functionality added*

### 1.0.6 - 15/09/2021

Changes:
*Fixed - Demo Files not Accessed Issues*


### 1.0.5 - 13/08/2021

Changes:
*Fixed - WP Importer Redeclaration Issues*
*Update - Added Some Essentials Links*


### 1.0.4 - 19/04/2021

Changes:
*Update - WP 5.8 Compatibility Check*


### 1.0.3 - 19/04/2021

Changes:
*Update - WP 5.7 Compatibility Check*

### 1.0.2 - 19/04/2021

Changes:
*Fixed - Import Success Message*

### 1.0.1 - 18/04/2021

Changes:
*Fixed - Undefined Pro Link*

### 1.0.0 - 16/02/2021

Changes:
*Initial Release*
