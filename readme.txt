=== Carrot quest ===
Contributors: carrotquest
Donate link: http://example.com/
Tags: e-commerce, carrotquest, woocommerce, widgets, email campaigns, analytics
Requires at least: 4.2
Tested up to: 6.1.1
Stable tag: 2.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Carrot quest combines all instruments for marketing automation, sales and communications. Supports WooCommerce 5.x, 6.x, 7.x (tested up to 7.1.0).

== Description ==
[Carrot quest](https://www.carrotquest.io/?utm_source=wordpress&utm_medium=marketplace&utm_campaign=intagration) is a customer service, combining all instruments for marketing automation, sales and communications for your web app. Goal is to increase first and second sales.

[youtube https://youtu.be/RNOS-68LaSk]

1. Carrot quest tracks realtime customer information (names, emails, phone numbers, viewed products, shopping cart, orders).
2. All information on each customer is stored in an Online CRM
3. 5 marketing automation, customers return and sales tools work based on collected data:
	1. Pop-ups to collect new leads 
	2. Automated emails to recall customers and make additional sales (e.g. abandoned shopping cart email)
	3. Automated SMS to recall and keep customers and make additional sales
	4. Automated callback for instant sales and returning of profitable customers
	5. Automated chat messages for human support
4. All communications with customers are combined in one interface.

Service provides detailed analytics for all those communications.
As a result we make 30% more additional sales by automation scenarios. 


	**Case example:**
	The volume of visitors to the website in a month = 54495 unique visitors

	**Before:**
	Old conversion rate = 1.62%
	Old number of orders per month = 884
	Old revenue = $30 690

	**After:**
	New conversion rate = 1.83%
	New number of orders per month = 996
	New revenue = $36 153
	
	**Result:** Company revenue increased by 15.09%


You don’t lose contact with any of your customers, which help to increase second and additional sales.
1. Integrations with popular services (call tracking, CRM, callback);
2. Completing customers data with social networks information (not socfishing);
3. Online consultant and email are bonded with each other;
4. etc.

P.S.
Service has many cool features, which cannot be described in one paragraph. You should try them out.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/carrotquest` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Open Settings->Carrot quest and set API Key and API Secret values

== Frequently Asked Questions ==

= Where can I get API Key, API Secret and User Auth Key? =

You have to register at [Carrot quest](https://www.carrotquest.io/?utm_source=wordpress&utm_medium=marketplace&utm_campaign=intagration) then open "Settings" -> "API Keys" and copy needed values.

= What properties and events this plugin collects? =

Events:
1. Viewed product:
	* Product name;
	* Product description page link;
	* Product price. Integer;
	* Product image link.
2. Added product to cart:
	* Product name;
	* Product description page link;
	* Product price. Integer;
	* Product image link.
3. Viewed shopping cart:
	* Products names list;
	* Products description pages links list;
	* Products costs (integer) list;
	* Products image link.
5. Started checkout process
6. Completed checkout process:
	* Order ID;
	* Order total.

Events, occuring when user authorization is on and order was made by an authorized user:
1. Order paid (when status changed to Completed):
	* Order ID;
	* Items;
	* Order total.
2. Order refunded:
	* Order ID;
	* Items.
3. Order cancelled:
	* Order ID;
	* Items.


Properties:
1. Shopping cart total (integer) – updated when cart is viewed or product added to cart
2. Viewed products (list of products names) – updated when product is viewed
3. Shopping cart (list of product names) - updated when cart is viewed or product added to cart
4. Last payment (integer) – updated when order is made
5. Total revenue from user (integer, sum of all order totals) – updated when order is made
6. Customers name – updated when order is made
7. Customers email address – updated when order is made or email address were written in any input field
8. Customers phone number – updated when order is made
9. Last order status - updated when user authorization is on and order status was changed


== Screenshots ==

1. Keep detailed information about every visitor and his communications.
2. Collect all your communications with customers in one place.
3. Marketing and sales automation based on customer actions improves your conversion rates.
4. Find the best tools for your needs.
5. Automate your marketing and watch your metrics growing

== Changelog ==

= 1.0.0 =

* First release

= 1.1.0 =

* Authorize users (send User ID to Carrot quest)
* Track orders status changes
* Minor bug-fixes

= 1.1.1 =

* Updated some of WP API usage to current version
* Fixed some more bugs

= 1.1.2 =

* Set headers to requests (so that Carrot quest don't think this plugin is a bot)

= 1.1.3 =

* Minor bug fixes

= 1.1.4 =

* Updated service script to latest version
* Fix duplicate event for when adding item to the cart
* Fix compatibility issues up to WP 5.6, WooCommerce 4.x
* Made code prettier in general and abiding by the WordPress Coding Style
* Changed classes names and renamed class files to correlate with PHPCS/WPCS

= 2.0.0 =

* Well... Major ver. should've changed in previous release (1.1.4). Happens.
* Validate if we actually got something to work with
* Fix order total saved to CQ properties and event when order made

= 2.0.1 =

* Function call fix

= 2.1.0 =

* Action used to add our script to pages changed from wp_head to wp_enqueue_scripts
* Fixed Cart property values sent to Carrot quest. Instead of "None" value it will now actually have product names. As it was supposed to.

= 2.1.1 =

* Fixed code error on user authentication
