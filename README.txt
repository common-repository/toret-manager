
=== Toret Manager ===
Requires at least: 6.2
Tested up to: 6.6
WC requires at least: 6.7
WC tested up to: 9.3.3
Requires PHP: 7.4
Version: 1.1.1
Stable tag: 1.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connects WordPress and WooCommerce website with Toret.net.

== Description ==

**Connect your WordPress sites and WooCommerce stores with a single plugin.**

Synchronize products, posts, inventory, user accounts, and other items, and manage everything from one place – the Toret Manager app.

### TORET MANAGER: SYNCHRONIZATION AND UNIFIED MANAGEMENT OF WORDPRESS SITES AND WOOCOMMERCE STORES ###

Lack of time and too much work – this is the daily reality of anyone managing multiple websites or stores. Routine tasks like copying posts, updating products, or managing inventory across multiple sites consume your capacity, which could be better invested elsewhere.

Saving time and effort is the main vision behind Toret Manager. It can help you too.

#### LESS WORK, MORE TIME – THANKS TO AUTOMATIC SYNCHRONIZATION ####

Do you operate multiple WordPress sites or WooCommerce stores and need to share data between them in real-time? There’s no need to resort to lengthy manual copying when you can connect them through Toret Manager. It enables complete synchronization of the following items:

- **Products**
- **Inventory Status**
- **Orders**
- **Posts and Pages**
- **Categories, Tags, and Taxonomies**
- **User Accounts**
- **Comments, Reviews, and Ratings**

Explore the detailed synchronization options on [our website](https://www.toret.net/en/functions/#synchronization).

#### SYNCHRONIZE THE WAY YOU NEED ####

You can configure synchronization settings for each item and each site individually, precisely as you need. You can set:

- One-way or two-way synchronization
- Synchronization rules separately for each site and item type
- Synchronization of new and existing items, changes made, or deletions

You can, for example, have a master site from which items are copied to others, but not the other way around. Or, you can share items only with the Toret Manager app, allowing unified access to all content without synchronizing between sites.

There are many possibilities; it’s up to you how you decide to use them. Once set, synchronization runs automatically.

Find more practical examples of usage at [toret.net](https://www.toret.net/en/guides/).

#### SUITABLE FOR YOUR WEBSITE OR STORE ####

Toret Manager is compatible with popular page builders like Divi, Spectra, Gutenberg, and Elementor, and with the WooCommerce plugin. Synchronization won’t be an issue between your sites and stores.

###EASY AND FAST MANAGEMENT OF ALL SITES IN ONE APP###

No more switching between different admin panels. With Toret Manager, you can easily access all the content of your sites or stores, and with search and filtering, you’ll quickly find what you need. View all important information in the item details, and if you wish to edit it, you can click directly into its admin detail.

### SPECIAL FEATURES FOR E-COMMERCE ###

Store owners with multiple WooCommerce stores will appreciate managing all orders in one place. The app provides an overview and detailed order information from all stores, helping you and your team efficiently prepare all shipments.

**Order Overview** in Toret Manager allows bulk actions, searching, filtering, and changing order statuses.

Create **user accounts with restricted permissions** for your employees, designed specifically for their needs – for example, access to orders only for fulfillment workers.

**Packing Function** helps minimize errors when preparing shipments. It tracks packing times and the responsible worker’s name. You can check off items packed directly in the order detail and confirm its completion.

### KEEP YOUR SITES SECURELY UNDER CONTROL ###

Toret Manager uses user accounts with individually configurable access rights. You can provide your team access only to the sections they need for their work, reducing errors and security risks.

### SIMPLE IMPLEMENTATION AND COMPATIBILITY ###

Toret Manager is designed to simplify your work, not complicate it. Therefore, its implementation is straightforward.

Install the Toret Manager plugin on all connected sites, set synchronization rules, and connect them via access credentials obtained from [toret.net](https://www.toret.net/en/beta-registration/).

For a smooth setup, we have prepared [detailed documentation](https://www.toret.net/en/documentation/) that guides you through the process step by step. If anything goes wrong, our customer support is available.

Toret Manager is currently in the testing phase. You can use it for free now. Try it today.

== Creating Users in the WordPress Installation ==

Our plugin creates users during synchronization if they do not already exist. This ensures data consistency between different websites and storage locations. This process is transparent and occurs with the user’s consent, as detailed in our [documentation](https://www.toret.net/cs/dokumentace/). Creating users is essential for the proper functioning of the plugin and data synchronization.

== Use of 3rd Party or External Services ==

Our plugin relies on the following third-party services for data synchronization and processing:

1. **Toret Endpoint Services:**
   - Used for data synchronization between websites.
   - Service URL: [app.toret.net](https://app.toret.net)
   - [Terms of Use and Privacy Policies](https://www.toret.net/privacy-policy/)

2. **wp-background-processing Library:**
   - Used for gradual processing of lengthy tasks, specifically for initial synchronization.
   - Library URL: [wp-background-processing](https://github.com/deliciousbrains/wp-background-processing)

These services are integral to the plugin’s operation, and their use is fully documented and transparent.

== Screenshots ==

1. Plugin Settings
2. Plugin Post Synchronization Settings
3. Plugin Tools
4. Plugin Product Settings

== Changelog ==
<a href="https://app.toret.net" target="_blank">Changelog</a>

= 1.1.1 =
* Now products can also be paired by SKU number

= 1.1.0 =
* Added support for WooCommerce 9.3.3
* Fixed support for PHP8
* Optimized synchronization performance

= 1.0.1 =
* Bug fixes

= 1.0.0 =
* Plugin release

