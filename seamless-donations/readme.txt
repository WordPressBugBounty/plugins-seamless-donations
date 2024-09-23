=== Seamless Donations is Sunset ===
Contributors: givewp
Donate link: https://zatzlabs.com/seamless-donations-must-read/
Tags: givewp
Requires at least: 4.0
Tested up to: 6.4
Stable tag: 5.3
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

= Sunset Notice =
**Seamless Donations is sunsetting and will no longer be updated.**

Learn more on [how to migrate off of Seamless Donations](https://zatzlabs.com/seamless-donations-must-read/).

== Frequently Asked Questions ==

= Seamless Donations is being Sunset. What do I do now? =

**IMPORTANT: This plugin is no longer actively maintained or supported. [We recommend migrating to GiveWP instead.](https://zatzlabs.com/seamless-donations-must-read/)**


== Screenshots ==

1. The default, mobile-responsive donation form
2. Forms in the Colorful Donation Forms add-on pack
3. Forms in the Beautiful Donation Forms add-on pack
4. Payment gateway settings tab
5. Donation list screen
6. Form options screen from core plugin

== Changelog ==

= 5.3.0 =
* **IMPORTANT: This plugin is no longer actively maintained or supported. [We recommend migrating to GiveWP instead.](https://zatzlabs.com/seamless-donations-must-read/)**

= 5.2.7 =
* Added a new log status message for when an email was successful.

= 5.2.6 =
* Increased the header label size for all admin area sections
* Added repeating field indicator to Donations list view
* Bug fixes

= 5.2.5 =
* Added reconcilliation audit debug mode

= 5.2.4 =
* Added new form type textarea to forms engine.

= 5.2.3 =
* Minor fixes to hooks and filters.

= 5.2.2 =
* Mandatory update if you're using PayPal Checkout. Fixes a critical bug.

= 5.2.1 =
* Fixed minor bug where the PayPal live mode secret key was saved, but wasn't shown in the dashboard

= 5.2.0 =
* Added new PayPal Checkout system as new way to process donations with PayPal.
* Removed IPN processing from new PayPal interface.
* Added ability for old PayPal IPN requests to still be recognized for existing users.
* New users will be required to use PayPal Checkout rather than the old legacy PayPal processing system.
* Fixed presentation bugs in Donors and Donation lists

= 5.1.20 and 5.1.21 =
* Internal bug fix upgrades

= 5.1.19 =
* Fixed bug that caused some new Stripe users to get an invalid parameters error.

= 5.1.18 =
* Fixed bug with setting Stripe keys

= 5.1.17 =
* Added access codes to help determine where an Access Denied message is sent from

= 5.1.16 =
* Fixed minor bug in expanded currency beta

= 5.1.15 =
* Fixed minor bug causing some PHP complaints

= 5.1.14 =
* Fixed security feature that recorded extra zeroes in some donations.
* Fixed bug saving Stripe key data

= 5.1.13 =
* Fixed yet more security bugs. Be sure to update to this version.

= 5.1.8 to 5.1.12 =
* Fixed security bugs. Released internally only.

= 5.1.7 =
* Added Live and Sandbox email fields for PayPal payments.
* Added much more granular log reporting for Stripe key errors.
* Added debug option to turn off Stripe key validation.
* Added internal test code for future rolling 31 day transaction audit debug mode (requires PayPal id and secret key)
* Improved clarity in some UI element descriptions.

= 5.1.6 =
* Added compatibility check that identifies caching errors for transaction IDs.

= 5.1.5 =
* Added option to extend currencies for Stripe, offering 134 currencies instead of the 26 previously offered.
* Added a whole bunch of debug modes that help when supporting users with challenging-to-diagnose issues.
* Fixed validation bug when "Other" was selected and no value was entered. Thanks to Daniel Oizumi for the fix.

= 5.1.4 =
* Fixed responsiveness error in Modern form style

= 5.1.3 =
* Fixed bug introduced in 5.1.2 that caused recurring donations to misplace decimal point

= 5.1.2 =
* Modified test mailing fields to be less amusing and more useful (no more Internet Cat Fund, sorry!)
* Added Stripe support for zero-decimal currencies (like the Japanese Yen)
* Added two filters to email.php: seamless_donations_email_subject and seamless_donations_email_complete_body (thanks to user Robert for the suggestion)

= 5.1.1 =
* Added link to view IPN transaction history in PayPal
* Added HTTP 200 response to IPN chatback response, which should eliminate retries
* Replaced PayPal IPN chatback code with streamlined version
* Fixed bug where multiple zero-dollar emails are sent after a successful PayPal transaction
* Fixed bug with PayPal SSL security compatibility indicator on Settings tab
* Fixed issue users described as "extra blobs" after the submit button (in forms engine)

= 5.1.0 =
* Added Total Donated column to Funds list
* Added Options Explorer to Debug Mode
* Added warning to pre-4.0 users to read blog post on upgrading
* Added a more secure form initiation process that should be compatible with more hosting providers
* Added more secure PayPal IPN processing that should also be compatible with more hosting providers
* Changed the IPN URL *PLEASE CHANGE THIS WITH PAYPAL (see Settings tab)*
* Added validation and prompting to make sure Stripe keys are correct
* Substantially optimized code base, reducing overall size of plugin considerably
* Removed "Process form data via initiating page or post" option in settings since this option is deprecated
* Removed old Seamless Donations legacy code written before adoption of the plugin
* Removed unnecessary private version of jQuery that had been tagging along with the plugin for a while
* Disabled SSL check on Logs tab when in debug mode to avoid the check itself from interfering with debugging
* Fixed bug in Funds detail where donor names were always listed as Anonymous
* Fixed bug where Indian Rupee and Israeli New Sheqel were mixed up, due to an abbreviation snafu
* Fixed validation and donor form bugs (thanks to user Jaka Kranjc for the code)

= 5.0.23 =
* Added security code that fixes some vulnerabilities
* Added new Stripe Event History debug mode
* Added additional logging data in Stripe transaction check
* Added debug line to determine where session IDs are derived from
* Fixed bug in Stripe payment section where donation ID was sometimes missing
* Fixed bug that set default currency to USD even if another default currency was set

= 5.0.22 =
* Added street address field to notification email
* Added repeating confirmation log entry for Stripe payment
* Fixed minor undefined variable bug
* Fixed issue where employer name wasn't showing up on the donor list, even if specified
* Fixed JavaScript bug where country element would sometimes return as undefined

= 5.0.21 =
* Added option to require billing address collection in Stripe checkout form
* Added SSL security status indicators and checks for Stripe
* Added code to poll Stripe for repeating donation transaction data
* Added an Seamless Donations cron subsystem, mostly for Stripe polling
* Added one-time-run function that fixes transaction IDs for Stripe recurring donations
* Added a whole series of internal utility functions for getting transaction data from Stripe
* Added debug mode option to run a block of test code on Seamless Donations initialization
* Added diagnostic message for when Seamless Donations redirects to Stripe during donation
* Increased visible field size for Stripe keys
* Fixed Stripe field entry validation for Stripe keys so already-entered keys aren't deleted on error

= 5.0.20 =
* Added additional diagnostic log data for Stripe payments
* Fixed bug in form styles that would sometimes cause a crash

= 5.0.19 =
* Added selective HTML tag support inside Thank You message
* Preliminary testing with WordPress 5.5

= 5.0.18 =
* Added more debugging telemetry to Stripe gateway functionality
* Added a please post a review item to plugin's menu

= 5.0.17 =
* Fixed bug where non-required telephone number was required
* Fixed bug where an error would be triggered if form style set to None
* Fixed some undefined variable warnings
* Fixed another bug in legacy licensing code

= 5.0.16 =
* Fixed bug in legacy licensing code

= 5.0.15 =
* Fixed bug in Stripe production/live server processing
* Fixed bug in Funds where the fund visibility wouldn't save
* Fixed initiation issue with PayPal IPN.php processing

= 5.0.14 =
* Fixed a conflict in browser identification code when a WordPress component wasn't loaded in time
* Fixed a minor bug in donor, donation, and fund details where an undefined variable was sometimes referenced

= 5.0.13 =
* Added long-overdue default state option to form options
* Added settings debug option to no longer check for pre-5.0 add-ons
* Fixed newly-introduced bug that prevented donor, donation, and funds detail from being shown
* Fixed a bug where browser identification in debug log failed for WordPress.com Business Plan users

= 5.0.12 =
* Fixed duplicate header bug found on some systems

= 5.0.11 =
* Added uninstall reason dialog so I can get feedback and make Seamless Donations better
* Fixed bug where ipn.php was invoked every few seconds.
* Eliminated a bunch (but possibly not all) warning messages that showed when WP_DEBUG turned on.

= 5.0.10 =
* Added helpful up-to-the-minute server status info link to Logs page for PayPal servers
* Added fully-responsive default Modern-style donation form

= 5.0.9 =
* Fixed serious bug where gateway would default to Stripe even if PayPal was live

= 5.0.8 =
* Soft launch of new form styling code... much more to come

= 5.0.7 =
* Fixed Stripe production processing bug

= 5.0.6 =
* Add Stylesheet Priority option to Form Options to help prevent themes from corrupting form styles

= 5.0.5 =
* Added support for Stripe Checkout with recurring donations
* Added link to comprehensive SSL report for domain, located on Log screen
* Added security features to prevent most Seamless Donations' php files from being executed directly
* Added additional debug options to debug mode
* Updated Add-ons screen with data export plugin and link to tutorial

= 5.0.4 =
* Very minor tweak to make updating add-ons a bit easier

= 5.0.3 =
* First public repository release of major 5.0 admin UI rebuild (5.0.0, 5.0.1, and 5.0.2 were beta releases only)

= 5.0.2 =
* Added new action seamless_donations_tab_settings_before_payments which runs just before seamless_donations_tab_settings_before_paypal.
  These two are separated out as unique elements because the PayPal engine may not always be there.
* Added a new Payments Processor Compatibility table on the Logs tab
* Fixed a problem introduced in 5.0.0 where the WordPress Media tab would hang

= 5.0.1 =
* Added two settings action callbacks: seamless_donations_tab_settings_before_paypal and seamless_donations_tab_settings_before_host
* Added two css action callbacks: seamless_donations_add_styles_first and seamless_donations_add_styles_after
* Fixed bug that caused an error because it was attempting to include a file no longer needed
* Fixed bug in sd4 add-on retirement code so it now retires better

= 5.0.0 =
* Enabled the ability for donors, donations, and funds to be listed as Public, Private, or Password Protected. Also allowed donor, donation, and funds pages to be unpublished, if desired.
* Enabled the permalink on the donor, donation, and fund detail pages for easy preview of the donor, donation, and fund pages.
* Added helpful link to the IPN entry location in PayPal (on Settings tab).
* Rebuilt entire plugin on CMB2 admin UI library.
* Fixed issue where couldn't create a new fund when Compact Menus enabled and Funds tucked under main Seamless Donations menu.
* Fixed issue where the log obscurify feature didn't properly initialize/
* Deprecated the following admin filters: seamless_donations_donor_header_style and seamless_donations_donation_header_style. This is now formatted in adminstyles.css
* Changed how admin validation filters are called. Before, the filter took three parameters. Now, it's only passed one, the $_POST. You can process admin form validations for any button pressed in the Seamless Donations admin UI. The filter is validate_page_slug_[page_slug_name]. For example, if a page’s slug is seamless_donations_tab_thanks, it calls the filter validate_page_slug_seamless_donations_tab_thanks.
* Changed the naming convention for all admin callbacks. Prior to 5.0, admin pages were previously of the form seamless_donations_admin_[name] and, as of 5.0, are now of the form seamless_donations_tab_[name]. Therefore any callback that would have used the slug seamless_donations_admin_[name] must now use the slug seamless_donations_tab_[name].
* Fixed issue in Basic Widget Pack where a crash was caused when deactivating Seamless Donations itself. Other Seamless Donations add-ons did not have this problem.

= 4.0.23 =
* Fixed a typo (an extra slash) introduced in 4.0.22. Thanks again, Jacob!

= 4.0.22 =
* Incorporated fixes from user Rachel3004 (Jacob) for PayPal's apparent deprecation of fsockopen.

= 4.0.21 =
* Added PayPal TLS test results to Settings panel
* Removed option to see obsolete IPN

= 4.0.20 =
* Tweak that might help some very out-of-date users update more successfully.
* Tweak to PayPal arguments to provide better plugin support

= 4.0.19 =
* Minor update to add new support information and notice

= 4.0.18 =
* Minor update to improve TLS compatibility with PayPal
* Added an option (that should probably never be used) to turn on legacy SSL for PayPal transactions

= 4.0.17 =
* Added processing mode entry to log
* Fixed spurious blank IPN log entries
* Fixed bug that wrote incorrect IPN to log entries
* Fixed nasty little bug introduced in 4.0.16 that substantially slowed processing by calling both IPN handlers on every page

= 4.0.16 =
* Required update for PayPal https IPN compatibility
* Added https verification and notification code to PayPal section in preparation for PayPal security update
* Added https-compliant IPN URL to PayPal settings section in preparation for PayPal security update
* Added helpful note to Host Compatibility Options section

= 4.0.15 =
* Added Donations This Month widget
* Added failover PayPal security option with cURL TLS support
* Added new Host Compatibility Options section in settings
* Added host compatibility option and mechanism to process form data via initiating page or post rather than external PHP file
* Added host compatibility option and mechanism to bypass nonce validation for those hosts who break nonces on form submission
* Added host compatibility option and mechanism to generate unique transaction IDs in JavaScript rather than at the host
* Added nicer styled beta labels
* Added versioning to transaction IDs
* Added option to obscurify donor names shown in logs
* Donor names in logs now default to obscurified names unless otherwise turned off
* Modified YouTube tutorial video in main admin panel to resize responsively
* Changed name of main Debug Mode section to Debug Options
* Fixed a bug where UK Gift Aid selection was not being recorded. Unfortunately, the data wasn't actually written to any transaction logging due to a code typo, so there's no pre-existing gift aid data available for recovery.

= 4.0.14 =
* Update fixing PayPal chatback bug.

= 4.0.13 =
* On the donor detail page, each donation now shows as Yes or No depending on whether the donation was made anonymously
* On the donor detail page, each donor's overall anonymity flag is displayed under the address
* Donor records now have an internal overall anonymity flag. If a donor ever specified anonymity in any donation, that donor's record is flagged as anonymous (even if the donor doesn't ask for anonymity in other donations)
* The cross-reference rebuild option in the Settings Debug Mode now also rebuilds the anonymity indexes

= 4.0.12 =
* Clicking into a fund now displays a list of donations for each fund
* Added a new Settings option to Debug Mode that allows users to rebuild the cross-reference index
* New update to Spanish translation, courtesy David Chavez
* Added a helpful prompt guiding users to the PayPal video tutorial and another to remind users to switch email addresses when moving from Sandbox to Live mode
* Implemented important architectural change in funds, so donations and funds now cross-index
* Implemented cross-reference rebuild function for funds and donors, so databases can be reindexed if necessary
* Implemented an internal stored running total for funds and donors for performance
* Modified license check code to provide details to error log
* Refactored the donations.php file to make it easier to maintain
* Renamed some donations functions from 'transient' to 'transaction' for accuracy
* Added a new 'seamless_donations_admin_settings_before_tweaks' action that allows placing items on the Settings tabs before the lower-priority tweaks and debugging elements
* Fixed bug where funds showed unsupported post-related options that could cause breakage

= 4.0.11 =
* Added helpful upgrade notes
* Added dgx_donate_thank_you_email_body hook. This works with the legacy text-only mailer, takes in body text of email and returns back a possibly modified body text
* Minor bug fix

= 4.0.10 =
* Fixed another bug in repeating donations

= 4.0.9 =
* Added Add-ons and Licensing tabs, along with full licensing and premium extensions support
* Added a new beta tweak to Settings tab that tucks Donations, Funds, and Donor custom post types under the Seamless Donations menu
* Added Portuguese translation (thanks to Daniel Sousa)
* Added Singapore as a country requiring a postal code
* Fixed initial display of postal code for countries other than US, CA, UK
* Fixed bug on Donations and Donors list page where "Add New" was an available option. You can only add donations through the shortcode

= 4.0.8 =
* Added support for GoodBye Captcha spam-blocking plugin
* Fixed bug limiting notification emails to one email address
* Fixed other minor and potential bugs in the code

= 4.0.7 =
* Fixed bug in repeating donations

= 4.0.6 =
* Added a transaction audit database table that replaced the unreliable transient data system.
* Rewrote payment initiation system. Payments no longer are initiated by JavaScript running on visitors' browsers, but by a PHP script running inside the plugin on the server.
* Added new shortcode extensibility system.
* Added a debug mode checkbox to the Settings panel.

= 4.0.5 =
* Public beta release only

= 4.0.4 =
* Beta release only

= 4.0.3 =
* Fixed fatal bug introduced in 4.0.2

= 4.0.2 =
* Added Spanish translation (thanks to David Chávez) and French translation (thanks to Etienne Lombard).
* Added new Form Tweaks section to Form Options, with an option to enable Label Tags. This may improve form layout for some themes, particularly those where vertical form field alignment needs improvement.
* Added an indicator comment in the form code to allow inspection to determine the version of the plugin that's currently running.
* Fixed bug in legacy export code introduced in 4.0. Unnecessary mode check caused the routine to fail.
* Fixed bug where getting the plugin version number failed internally in some instances.

= 4.0.1 =
* Added German translation
* Fixed problem with Windows servers and long path names
* Fixed multiple currency-related bugs: be sure to re-save your settings for this fix to take effect
* Fixed the giving level filter
* Fixed "undefined index" error
* Fixed bug where default fields didn't default properly
* Fixed overly oppressive field sanitization

= 4.0.0 =
* Major update
* Added updated, modern UI
* Funds and donors have now been implemented as custom post types.
* Designed for extensibility with support for wide range of hooks
* Array-driven forms engine
* Translation-ready

= 3.3.5 =
* Added update notice warning and splash so current site operators can have some warning before the new 4.0 version lands. Also added MailChimp subscribe form to main plugin page.

= 3.3.4 =
* Officially adopting the plugin and beginning support by David Gewirtz as new developer

= 3.3.3 =
* Officially marking this plugin as unsupported and putting it up for adoption

= 3.3.2 =
* Updated: Seamless Donation news feed updated to point to designgeneers.com
* Fixed: Corrected variable name to resolve PHP Warning for formatted amount that would be displayed on sending a test email
* Fixed: Corrected variable name to resolve PHP error for new donation created from PayPal data

= 3.3.1 =
* Tested with WordPress 4.1

= 3.3.0 =
* Changed PayPal IPN reply to use TLS instead of SSL because of the POODLE vulnerability
* Changed PayPal IPN reply to better handle unexpected characters and avoid IPN verification failure - props smarques

= 3.2.4 =
* Fixed: Don't start a PHP session if one has already been started - props nikdow and gingrichdk

= 3.2.3 =
* Fixed: Unwanted extra space in front of Add me to your mailing list prompt

= 3.2.2 =
* Added Currency Support: Brazilian Real, Czech Krona, Danish Krone, Hong Kong Dollar, Hungarian Forint, Israeli New Sheqel
* Added Currency Support: Malaysian Ringit, Mexican Peso, Norwegian Krone, New Zealand Dollar, Philippine Peso, Polish Zloty
* Added Currency Support: Russian Ruble, Singapore Dollar, Swedish Krona, Swiss Franc, Taiwan New Dollar, Thai Bhat, Turkish Lira

= 3.2.1 =
* Added: Occupation field to donation form and to donation detail in admin
* Added: Employer name to donation detail in admin
* Added: Employer and occupation fields to report

= 3.2.0 =
* Added: More control over which parts of the donation form appear

= 3.1.0 =
* Added: Filter for donation item name
* Added IDs for form sections to allow for more styling of the donation form

= 3.0.3 =
* Fixed: A few strings were not properly marked for translation.

= 3.0.2 =
* Fixed: Bug: Removed unused variable that was causing PHP warning

= 3.0.1 =
* Fixed: Bug: Was using admin_print_styles to enqueue admin CSS. Switched to correct hook - admin_enqueue_scripts

= 3.0.0 =
* Added: Gift Aid checkbox for UK donors
* Fixed: Bug that would cause IPN notifications to not be received

= 2.9.0 =
* Added: Optional employer match section to donation form - props Jamie Summerlin
* Fixed: Javascript error in admin on settings page

= 2.8.2 =
* Fixed: Don't require postal code for countries that don't require postal codes
* Fixed: International tribute gift addresses were not displaying country information in donation details

= 2.8.1 =
* Added: Support for non US currencies: Australian Dollar, Canadian Dollar, Euro, Pound Sterling, and Japanese Yen

= 2.8.0 =
* Added: Support for specifying name for emails to donors (instead of WordPress)
* Added: Automatic textarea height increase for email templates and thank you page
* Fixed: Bug that would allow invalid email address to cause email to donor to not go out (defaults to admin email now)

= 2.7.0 =
* Added: Support for donors located outside the United States

= 2.6.0 =
* Added: Support for repeating donations
* Added: Support for loading scripts in footer
* Added: Greyed out donate button on click
* Added: Prompt to confirm before deleting a donation in admin
* Added: Seamless Donations news feed to main plugin admin page
* Added: Help/FAQ submenu
* Added: Replaced main admin page buttons with Quick Links
* Added: Display of PayPal IPN URL in Settings
* Added: More logging to PayPal IPN for troubleshooting hosts that don't support fsockopen to PayPal on 443
* Fixed: Bug in displaying thank you after completing donation
* Fixed: Changed admin log formatting to make reading, cutting and pasting easier
* Fixed: Major update to admin pages code in support of localization

= 2.5.0 =
* Added support for designated funds
* Fixed: A couple warnings when saving changes to thank you email templates.

= 2.4.4 =
* Fixed: Cleaned up warnings when run with WP_DEBUG

= 2.4.3 =
* Fixed: Changed form submit target to _top most window (in case theme places content in iframes)
* Fixed: Updated plugin URI to point to allendav.com

= 2.4.2 =
* Automatically trim whitespace from PayPal email address to avoid common validation error and improve usability.

= 2.4.1 =
* Changed mail function to use WordPress wp_mail instead of PHP mail - this should help avoid dropped emails

= 2.4.0 =
* Added the ability to export donation information to spreadsheet (CSV - comma separated values)

= 2.3.0 =
* Added a setting to allow you to turn the Tribute Gift section of the form off if you'd like

= 2.2.0 =
* Added the ability to delete a donation (e.g. if you create a test donation)

= 2.1.7 =
* Rolled back change in 2.1.6 for ajax display due to unanticipated problem with search

= 2.1.6 =
* Added ajax error display to aid in debugging certain users not being able to complete donations on their sites

= 2.1.5 =
* Changed plugin name to simply Seamless Donations

= 2.1.4 =
* Added logging, log menu item and log display to help troubleshoot IPN problems

= 2.1.3 =
* Changed PayPal cmd from _cart to _donations to avoid donations getting delayed

= 2.1.2 =
* Removed tax deductible from donation form, since not everyone using the plugin is a charity

= 2.1.1 =
* Added missing states - AK and AL - to donation form
* Added more checks for invalid donation amounts (minimum donation is set to 1.00)
* Added support for WordPress installations using old-style (not pretty) permalinks
* Fix bug that caused memorial gift checkbox to be ignored

= 2.1.0 =
* Added new suggested giving amounts
* Now allows you to choose which suggested giving amounts are displayed on the donation form
* Added ability to change the default state for the donation form

= 2.0.2 =
* Initial release to WordPress.Org repository
