<?php 

// $Id: exp_about.php 5168 2023-07-23 01:37:35Z dev $
// About information, including release notes.

// Set flag that this is a parent file
define( '_STS_INCLUDE', 1 );

// Setup Session
require_once( "include/sts_session_setup.php" );

require_once( "include/sts_config.php" );
$sts_debug = isset($_GET['debug']) && in_group( EXT_GROUP_DEBUG );
require_once( "include/sts_session_class.php" );

$my_session = sts_session::getInstance( $exspeedite_db, $sts_debug );
$my_session->access_check( EXT_GROUP_USER );	// Make sure we should be here

$sts_subtitle = "About Exspeedite";
require_once( "include/header_inc.php" );
require_once( "include/navbar_inc.php" );
require_once( "include/sts_setting_class.php" );

$release_notes = array(
	'9.0.6 RC1' => 'SCRs:
- none

Other:
- Fix for video directory
- Fix for edit IFTA
- Added PC*Miler maps
- Added non-working button to find carrier screen
- Fix for attachments, support for ipads	
',
	'9.0.5 RC1' => 'SCRs:
SCR# 915, office approval stage [SETTINGS CHANGED]
SCR# 916, Assign one area
SCR# 918, Load Optimization Feature	

Other:
- Fix edit tractor screen to report when called with no CODE=xx parameter.
- Adding debug for Check Distance 2
',	'9.0.4 RC1' => 'SCRs:
SCR# 906, send notifications [SETTINGS CHANGED] [SCHEMA CHANGED]

Other:
- Tweak for delete_attachment.
- Fix for EXP_RELATIVE_PATH issue.
- Changes to checkrel.
- Fix for Array to string conversion error in view load screen.
',
	'9.0.3 RC1' => 'SCRs:
SCR# 885, Lane report csv problem [PATCHED ON CLIENT SYSTEM]
SCR# 906, send notifications [SETTINGS CHANGED] [SCHEMA CHANGED]
SCR# 910, Reuse office numbers from cancelled shipment on duplicate [SETTINGS CHANGED]

Other:
- List shipment don’t show cancelled and approved (operations) shipments.
- Tweak layout for forms with empty, read-only, table field. Specifically, add shipment screen, business code.
- Tidy up diagnostic information for background processing.
- Pass subdomain to child process for quickbooks online requests.
- Fix for driver pay, fixed rate, conditional on client rate.
- Fix attachments class to be fault tolerant when login fails.
- Fixes to PHP depreciated messages.
- Update acceptance tests to look for Depreciated.
- Fix for team driver pay.
- Fix for quickbooks, set BILLING_STATUS = billed when QB reports already exists.
- Fix for non-sales changing bill-to and updating sales person.
- More debug in DB class.
- Optimize lookup user.
- Optimize add shipment for speed.
- Optimize attachment class for speed.
- Cache EXP_STATES data for performance.
- Performance update to the home screen, get rid of the message.
- Motive/KT webhooks work.
- Checkpoint BVD work in progress.
- Update automated test suite.
- Update Exspeedite to check for gd library.
- Update checkrel.
- Fix for form buttons.
- Enhance PC*MILER validate_address() to add new zip/postal to the DB if possible.
- Fix for blank field in list result.
- Performance fix for margin report.
- Fix to add missing entries to database found by PC*Miler.
- Missing timezone for zip, now try to fix.
- Attempt to resolve timeout issue.
- Raise error if PC*Miler returns different city/state/zip.
- Tweak error handling for FilesAnywhere login.
- Attachments - reconnect to filesanywhere when viewing attachment if possible.
- Attachments - fix issues in migration.
- Fix for Check Distance screen.
- Fix for PDF emails.
',
	'9.0.2 RC1' => 'Other:
- Show related shipments for a load, with links to each
- Don’t allow completing a load if it hasn’t been dispatched
- Don’t re-use the SS number of a cancelled shipment
',
	'9.0.1 RC1' => 'SCRs:
SCR# 794, Keep Trucking Motive
SCR# 864, run rates
SCR# 873, Removing margin code from all reports

Other:
- new graphic
- Fix for depreciated error on quickbooks
- Tweak error message when timezone not found
- Clear carrier info when driver selected
',
	'8.0.16 RC1' => 'SCRs:
SCR# 887, new features / Reports Requested [SCHEMA CHANGED]
SCR# 895, driver pay screen [PATCHED ON CLIENT SYSTEM]
SCR# 898, Fatal errorUncaught TypeError
SCR# 899, Column TIMEZONE cannot be null
SCR# 900, listcarrier screen [SETTINGS CHANGED]
SCR# 901, upstream request timeout

Other:
- Adjust changed date column to show year on settings and SCR screens
- Show list of shipments on add driver pay screen
- Update schema for Husky card import
',
	'8.0.15 RC1' => 'SCRs:
SCR# 896, Client Rates - flat rate if selected shipper or consignee is set [SCHEMA CHANGED]

Other:
- Fix for CHECK_SCHEMA (generated columns, _utf8mb4)
',
	'8.0.14 RC1' => 'SCRs:
SCR# 894, Driver rates - flat rate based on shipper or consignee [SCHEMA CHANGED]

Other:
- Work on IFTA import (IFTA API changes as of 2023)
',
	'8.0.13 RC1' => 'SCRs:
SCR# 881, upgrade 8.0.12 inconsistency in the database [PATCHED ON CLIENT SYSTEM]
SCR# 883, Quickbooks Online
SCR# 892, Driver pay - make flat rate driver rate conditional on client rate specified.
SCR# 893, Container issue [PATCHED ON CLIENT SYSTEM]

Other:
- update schema
- Test code for BVD import
- Update instructions on driver rate screen
- Performance upgrade for the load screen
- Fix for timezone on load screen
- Added email/EMAIL_INVOICE_OVERRIDE to override to address for invoices
- Fix for Intermodal containers
- Update SALES_PERSON if it’s NULL. Set to the client’s SALES_PERSON
- Tweak email layout on approval screen
- Fix for margin report, duplicate lines
- Fix for Stop ETA issue
',
	'8.0.12 RC2' => 'Other:
- Fix for PHP 8
- DB optimization load screen back end
',
	'8.0.12 RC1' => 'SCRs:
SCR# 866, Sage50 push request [PATCHED ON CLIENT SYSTEM]
SCR# 867, Attachment location
SCR# 875, Intermodal container [SETTINGS CHANGED]

Other:
- Hide container column for non-intermodal
',
	'8.0.11 RC1' => 'SCRs:
SCR# 872, PICKUP_DATE DELIVER_DATE

Other:
- Various syntax fixes for linux / PHP 8
- Work on intermodal containers
- Work on tracking MySQL timeout issue
- Fix backup load feature to clear the completed date
- Template change for SCR# 872
',	
	'8.0.10 RC1' => 'Other:
- Updates to intermodal code.
- List carrier report, remove restriction to admin group.
- List lane screen, include link to carrier.
- Decrypt data - return empty string rather than raise an exception.
',
	'8.0.9 RC1' => 'SCRs:
SCR# 865, Dock Name missing in crossdock.

Other:
- Offer to repair LOAD_REVENUE and LOAD_EXPENSE in the view load screen.
- Fix the TOTAL_CHARGES column if needed when you view a shipment.
- Fix for IM empty drop, when you change it in the shipment screen, it changes the stop.
- Remove Syn flag on list shipment screen.
',
	'8.0.8 RC1' => 'SCRs:
SCR# 863, carrier list

Other:
- Fix for driver pay report
',
	'8.0.7 RC1' => 'SCRs:
SCR# 856, yard_container

Other:
- audit attachments feature
- Fixes for EDI
- Fixes for container without trailer
- Update schema to include new videos
',
	'8.0.6 RC2' => 'SCRs:
SCR# 855, QuickBooks Utilities.php / Fix for PHP 8.0
SCR# 853, yard Status
',
	'8.0.6 RC1' => 'SCRs:
SCR# 852, Intermodal/Container support, ETA for stops. [SETTINGS CHANGED] [SCHEMA CHANGED]

Other:
- Fix scrolling issues in my articles page
- Fixes to dispatch3 (add resources to load)
- Fix to Due column in list load screen
- Changes to CHECK_SCHEMA
',
	'8.0.5 RC1' => 'SCRs:
SCR# 845, Invoice needs Customer # and Reference # number
SCR# 846, Changes to Standard build
SCR# 850, Move local Attachment
SCR# 851, Hide Passwords in Settings [SETTINGS CHANGED] [SCHEMA CHANGED]

Other:
- Fix driver pay issues
- Fix nav bar for test server
- Tweak driver pay report for Tolls
- Fix KT invisible characters in code
- Fix for Margin report
- Fix for Driver pay report
',
	'8.0.4 RC4' => 'SCRs:
SCR# 836, resend an attachment
SCR# 837, Nav bar color change needed
SCR# 839, Driver pay fixes [PATCHED ON CLIENT SYSTEM]
SCR# 840, Undefined index [PATCHED ON CLIENT SYSTEM]
SCR# 843, Driver pay report

Other:
- Add debug to Driver Pay
- Tweaks to Driver Pay Report
- Added Flat Rate for driver pay
- If PDF conversion, Driver Pay report is output in HTML
- Fix for list client, empty trash
- Checkpoint - resend attachments
',
	'8.0.4 RC3' => 'SCRs:
SCR# 838, Change trace names [SETTINGS CHANGED]

Other:
- Hide tax info when multiple currency disabled.
- Explain error when EMAIL_SEND_INVOICES is false.
- Fix for sending email in the background, find the correct DB cache file.
- Added settings email/REQUEST_DELIVERY_RECEIPT and email/REQUEST_READ_RECEIPT
- More work on background send email.
',

	'8.0.4 RC2' => 'SCRs:
SCR# 834, WAITING TIME OwnerOperators -2
SCR# 835, Xcharges Driver pay % of client charges Company drivers -3

Other:
- Fixes for assigning resources to load with no shipments
- Added logging for attachments, to help us diagnose issues in the future.
',

	'8.0.4 RC1' => 'Other:
- Fix to alerts
- Migrate log_financial and check_financial to new file, to reduce duplication.
- Update total to match sum of charges.
- Make CMDTY column unsortable.
- Tweaks for desktop QB
',	
	'8.0.3 RC3' => 'Other:
- Added rollup for superadmin
- Revised SS_SERIAL to check for unique SS_NUMBER
- Make INVOICE_PREFIX UNIQUE, to avoid issues with duplicate SS_NUMBERS
- Force the alert refresh button to show
- Tweaks to alert caching
',
	'8.0.3 RC2' => 'SCRs:
SCR# 825, resend all Emails button [PATCHED ON CLIENT SYSTEM]
SCR# 818, Client additional requests related to margin report

Other:
- Improve home page alerts caching
- Update home page via AJAX instead of caching
- PHP 8 fixes for PC*Miler API
- PHP 8 fixes
- margin report tweaks
- margin report, remove timeout
- Tweak driver pay report
- Tweak schema for SCRs
',
	'8.0.3 RC1' => 'SCRs:
SCR# 810, Add shipment fixes [PATCHED ON CLIENT SYSTEM]
SCR# 813, Import Info not reporting correctly [PATCHED ON CLIENT SYSTEM]
SCR# 817, Import info tweeks [PATCHED ON CLIENT SYSTEM]

Other:
- Fix for change in Quickbooks API, CustomerCommunicationEmailAddr.php
- Fix for driver pay report
- Remove triggers that caused issue, and update shipment and load state transition tables columns: LOAD_REVENUE, LOAD_EXPENSE
- Add Reply-to: email header. email/EMAIL_REPLY_TO
- Fix for add load screen
- Fix for duplicate customs broker on add shipment screen
- Add message id for send attachment.
- Fix for zero shipments on separate loads
- Add Message-ID field to outgoing emails.
- Fix for billed shipment, no broker showing.
- Improve insurance comparisons.
- Add column sorting for list lane 1 screen
- Item list fix
- Added more logging for email, especially manifest
',
	'8.0.2 RC4' => 'SCRs:
SCR# 809, Batch invoices - select specific invoices

Other:
- Security update, restrict users to view shipments/loads they have access to.
- Updates to margin report, PDF output.
- Updates to batch invoice.
- Updates to margin report.
- Fix for get_expired when inspection reports disabled.
- Fix for add shipment screen, make duplicate popup draggable.
- Fix for find carrier, make load office the same as the shipment.
- Fix for check distances II screen.
- Fix for linux/PHP 8
',
	'8.0.2 RC3' => 'SCRs:

SCR# 800, Margin (resticted) report not working per user [PATCHED ON CLIENT SYSTEM]
SCR# 803, Batch invoicing date [SETTINGS CHANGED]
SCR# 805, Margin Report - hide Billed column for non-admin
SCR# 806, Margin Report - Allow users to choose to convert to whichever currency
SCR# 807, Margin Report - change CSV output layout

Other:
- Fix for debug DB log file, format SQL API required a proper SSL certificate
- Change cache timeout to 8 hours
- Optimize add driver pay screen
- Fix for batch invoice
- Fix for schema function IS_HAIRY
- Batch email, only spawn one process to loop through all messages
',
	'8.0.2 RC2' => 'SCRs:

SCR# 798, Shag service / tax exemption for lumper/shag [SCHEMA CHANGED]

Other:

Further tweak to margin report, handle NO SALESPERSON.
Further tweak related to batch invoices. Allow users to edit the INVOICE_EMAIL_STATUS column.
Fix list clients/prospects/leads screen to show correct counts
',
	'8.0.2 RC1' => 'SCRs:
	
SCR# 788, Reuse codebase
SCR# 789, Builk e-mail of invoices to certain clients [SETTINGS CHANGED] [SCHEMA CHANGED]
SCR# 790, Invoice and margin report [SETTINGS CHANGED] [SCHEMA CHANGED]
SCR# 792, additional tracking for attachments and inspection reports [SCHEMA CHANGED] [PATCHED ON CLIENT SYSTEM]

Other:

- Fix bug that occurs when when attachment directory is un-writeable
- Fix bug that occurs when when PO_FIELDS is disabled
- Tweak for CHECK_SCHEMA with older MySQL versions
- Tweak for add load screen
- Fix for driver images
- Fix NF => NL in tax table
- Fix to CHECK_SCHEMA / triggers / DEFINER (no longer needs root account)
- Updates to checkrel
- Added more helpful explanations for Scott when installing new system
- Tweaks to driver pay report
',
	'8.0.1 RC1' => 'SCRs:
	
SCR# 778, Slingshot Upgrade issues 7.0.15 R2 [PATCHED ON CLIENT SYSTEM]
- make sure in sage, it updates the status to billed for shipment when you export CSV
SCR# 779, Duplicate Shipment - changes [SETTINGS CHANGED] [PATCHED ON CLIENT SYSTEM]
- Added option/STOPOFF_COPY_PICKUP
- Added option/STOPOFF_COPY_PO
SCR# 780, Revisions to Billed & email sent filters [SETTINGS CHANGED]
- Added option/SHIPMENT_FBILLING_ATTACHMENT
SCR# 785, Driver pay statement 2 [PATCHED ON CLIENT SYSTEM]

Other:
- Updates to Driver Pay report
- Fix for date + time in the hour when the clock moves ahead.
- Add demo.png to pass tests
- Fix for select carrier
- Adjustment to driver pay report, include extra stops in grand total
- Remove duplicate listings in list client screen
- Update to 8.0
',	
	'7.0.17 RC2' => 'Other:
- Fixes for form values ending in a \
- Fixes for filesanywhere attachments on linux
- Don’t copy reference numbers on dup shipment
- Fix for linux PHP
- Fix for Driver pay report on MySQL 5.7
- Remove extra debug
	',
	'7.0.17 RC1' => 'SCR# 774, Carrier terms lockdown

Other:
- Rename Duplicate buttons on Add Shipment screen
	',
	'7.0.16 RC1' => 'Other:
- Work on Find Carrier feature
	Respect option/SHIPMENT_CUTOFF for expired shipments
	Check shipment business code. Use option/FIND_CARRIER_BC for list of business codes
	Fixed repeat issue
- Work on Linux (commented out)
	',
	'7.0.15 RC1' => 'SCR# 717, Lock down billing terms for carriers
SCR# 719, Shipments AND Loads - log both financial changes and sending of emails in history [SCHEMA CHANGED]
SCR# 721, Regarding salespeople [SETTINGS CHANGED]
   Added setting option/SHIPMENT_DUP_UPDT_SALES
   You need to turn it on. Then it will take the sales person from the bill-to client .

SCR# 722, Miscellaneous lives easier Filters + [SETTINGS CHANGED]
   Added email/NOTIFY_NEW_SHIPCONS

SCR# 757, Driver pay report
SCR# 760, Customer that we bill in CAD, but has USD [SCHEMA CHANGED]
SCR# 761, Hazmat info upgrade request [SCHEMA CHANGED]
SCR# 763, Check insurance for lumper companies [PATCHED ON CLIENT SYSTEM]
SCR# 766, UN Codes [SCHEMA CHANGED]

Other:
- Updates for Linux
- Make duplicate detection more strict, catch more possible duplicates
- Find Carrier, convert all currency to home currency
- Allow scrolling in debug on list client
- Fix for non-pipedrive users
- Move Archive out of superadmin
- new setting option/FIND_CARRIER_YEARS
',	
	'7.0.14 RC1' => 'SCR# 769, Alerts - show probable duplicate leads [SCHEMA CHANGED]

Other:
- More work on SCR# 715, add a label to sucessfully imported prospects.
- Added setting api/PIPEDRIVE_LABEL to specify label to use.
- Will not overwrite existing labels.
',	
	'7.0.13 RC1' => 'SCR# 715, requests concerning Pipedrive [SETTINGS CHANGED] [SCHEMA CHANGED]
New settings
   option/PIPEDRIVE_ENABLED
   api/PIPEDRIVE_URL
   api/PIPEDRIVE_LOG_FILE
   api/PIPEDRIVE_DIAG_LEVEL
	
Other:
- Added empty trash feature for clients/leads/prospects
',
	'7.0.12 RC3' => 'Other:
- Clean up exp_un_number table.
',
	'7.0.12 RC2' => 'Other:
- Reverse fix to list summary, that broke it.
',

	'7.0.12 RC1' => 'SCR# 766, UN Codes [SCHEMA CHANGED]
	
Other:
- Lock down email related filters from list shipment screen
',

	'7.0.11 RC2' => 'Other:
- Updates to formmail code
- Add update cache button to reports and videos screens
- Updates to Find Carrier feature
',

	'7.0.11 RC1' => 'Other:
- Added articles feature
- Added Mailcheck library
- Add ‘all’ option to group for articles feature
- Work on Form Mail Templates
- Show home currency for client insurance requirements
- Fix MySQL 8.x naming issue
',

	'7.0.10' => 'SCR# 755, Update to interliner lanes screen - working carrier list
SCR# 763, Check insurance for lumper companies [PATCHED ON CLIENT SYSTEM]
SCR# 764, New Weekly KPI - Fleet Revenue

Other:
- Turn off debug in the header, so you can see more.
- Fixes to email re-sending.
- Fixes to include paths in certain modules.
- Fixes to tabs on view load.
- Added new filters on list shipment screen, Billed (Email sent), Billed (Email NOT sent) & Billed (Email sent ERROR)
- Updates to email queue system. Can view PDF messages, compare with HTML. Also, got the codes/office number fixed.
- Fixes to record when an invoice for a shipment was emailed, if at all.
- html2pdfrocket changed to https
- Fix for timeout during IFTA import from KeepTruckin
- Add a warning to import drivers from keep truckin. May create duplicate records.
- Fix for numeric fields, trim them first. Might help is_numeric() test.
- Fix for email PDF, added a setting api/HTML2PDFROCKET_URL
- Work on performance upgrades
- Fix for SCR# 763 - Filter lumpers
- Find Carrier feature
- Optimize add shipment screen
- Add MySQL configuration information into the installer
- Updated handling of filesanywhere.com, for reliability of attachments 
',
	'7.0.9' => 'SCR# 760, Customer that we bill in CAD, but has USD [SCHEMA CHANGED]
SCR# 761, Hazmat info upgrade request [SCHEMA CHANGED]

Other:
- Fix for lanes report. Not all shipments have a matching billing entry	
- Minor tweaks for MySQL 8.0 & MySQL 5.x compatibility

',
	'7.0.8' => 'SCR# 748, R&M - Add vendor column to parts table [SCHEMA CHANGED]
SCR# 750, Lead will not change status

Other:
- Move column on SCR report
- Fix carrier stops template functioning
- Fix for advance load state to a stop
- Fix - send email manifest shouldn’t depend on setting for invoices.
- Corrections to schema, fix NF to NL, add ‘tractor’, ‘trailer’ to exp_email_queue.SOURCE_TYPE
- Updates to KeepTruckin API required changes to our code.
- Expire email queue, using setting option/EMAIL_QUEUE_EXPIRE default 7 days
- Fix for undefined variable, client_table
- Fix to attachment class - re-try filesanywhere if there is a soap error.
- Tweak current_driver_code() function, URL path.
- Add diagnostics for ftp_Connect for card FTP
- Fix for re-sending email,  for manifest, use the office address for From.
- Updates for Mysql 8.0 [SCHEMA CHANGED]
- Updates for PHP 7.4
- Performance upgrade to Zip code lookup
- Fix to client billing
- Fixed KeepTruckin API for PHP 7.4
- Work on Quickbooks API for PHP 7.4
- Fixes to client pay.
- Fixes to driver pay.
- Added holidays until 2025.
',
	'7.0.7' => 'Other:
- SCR# 722, Update notification to send email to sales person where possible.
	DIDN’T WORK. ENTERED SHIPPER “ON A ROLL BAKERY”, AND USED IT ON A SHIPMENT FOR A CUSTOMER OF MICHELLE’S – 3 STAR LETTUCE. MICHELLE/ACCOUNTING DID NOT GET AN EMAIL ABOUT A NEW SHIPPER.
- Updates to currency logging for loads and shipments WHEN I EDITED CURRENCY & PAY ON THE LOAD SIDE (LOAD 74498), IT WAS LOGGED. WHEN USER EDITED A LOAD’S CURRENCY & PAY, IT WAS NOT LOGGED (LOAD 74499).
- Extended zips are not in our DB, trim it for the search.
- Updated archiving to include a checkbox to select only cancelled shipments and loads.
- Display requirements on the Assign Resources screen.
',	
	'7.0.6' => 'Other:
- SCR# 722, Update notification to send email to sales person where possible.
- Updates to currency logging for loads and shipments
- Make sure to observe AND ISDELETED column for contacts, ignoring deleted records.
- Performance enhancements to zip/postal code lookup
- default SERVER_TZ to Central
',
	
	'7.0.5' => 'SCR# 721, Regarding salespeople [SETTINGS CHANGED]
SCR# 722, Miscellaneous lives easier Filters + [SETTINGS CHANGED]
SCR# 723, Adding Time Zone Support [SETTINGS CHANGED] [SCHEMA CHANGED]
SCR# 726, Install on exp_repair_db.php
SCR# 727, resizing issue

Other:
- Work done to build Exspeedite DB from scratch.
',
	
	'7.0.4' => 'SCR# 719, Shipments AND Loads - log both financial changes and sending of emails in history [SCHEMA CHANGED]
SCR# 720, Add Shipment - triggers "Unable To Save Changes"

Other:
- Update logging to show changes to CURRENCY, TERMS
- More work on SCR# 719 - include email attachments into email queue, and log events.
- Fix for add detail - remove previous event handlers. It caused duplicate commodities added to shipments.
',
	'7.0.3' => 'SCR# 712, Email queue implementation [SETTINGS CHANGED] [SCHEMA CHANGED]
SCR# 713, User stays and after deleted [PATCHED ON CLIENT SYSTEM]
SCR# 714, List Shipment screen - should order by pickup date [PATCHED ON CLIENT SYSTEM]
SCR# 717, Lock down billing terms for carriers

Other:
- Tweak to add/edit detail. Add more javascript logging and a warning text.
- Tried adding YAML support.
- Update edit SCR screen, allow click on titles to copy text.
- Update list SCR screen, use datatables fixedcolumns package.
- Fix to stop a customs broker being added to a shipment by mistake.
',
	
	'7.0.2' => 'SCR# 710, PC*Miler fails - can\'t import schema [PATCHED ON CLIENT SYSTEM]
SCR# 696, List Load Screen - Color code pick up and delivery times [SETTINGS CHANGED]
- Add a setting option/COLOUR_LOAD_TIMES
- Turned off for now

Other:
- Work on SCR# 712 - Email queueing  (turn off for now)
- Tweak Zip/Postal code lookup (eliminate duplicates)
- Remove dump of HTML to email log when PDF conversion fails.
- Tweak to login dialog size/position
',
	
	'7.0.1' => 'Update to new release level.
- SCR# 702, Equipment type in a checklist [SCHEMA CHANGED]
	
Other:
- SCR# 695 - check permissions for shortcuts
- List SCR - Don’t show Awaiting Customer Acceptance in My Current SCRs
- Tweaks to the login screen
- Fix for City, State searches in typeahead for zipcodes
- Check Distance II - allow users to specify cost and speed parameters

RC2:
- Fixes to billing state change, and Sage50 CSV export
- Avoid Warehouse in PC*Miler addresses
- Fix for SCR# 710 - PC*Miler fails - can\'t import schema
- Fix CHECK_SCHEMA to put parens around GENERATED columns
	',
	'6.0.22' => 'SCR# 695, Add "Add Load" button in Load screen [SETTINGS CHANGED]
- Added shortcut buttons via settings
SCR# 706, Checkcall in Loads GUI

Other:
- Add print button to check distance screen.
- Revert Add Load labels back
- Fix duplicate PCM setting
- Added jQuery UI Touch Punch for iPad compatibility.
',
	
	'6.0.21' => 'SCR# 661, E-mail failing for slingshot
SCR# 662, Invoice tons of errors
SCR# 663, Issues with assigning carrier to load [SCHEMA CHANGED]
SCR# 676, lock down the billing terms
SCR# 683, Building more in "Check Distance" screen [SETTINGS CHANGED]
SCR# 687, View load screen - add recalc distance button
SCR# 694, Update to Check Call modal dialog - make draggable
SCR# 695, Add "Add Load" button in Load screen
SCR# 696, List Load Screen - Color code pick up and delivery times
SCR# 698, Support for multiple email addresses [SCHEMA CHANGED]
SCR# 699, R&M Score
SCR# 700, trouble with R&M reports updating [SCHEMA CHANGED] [PATCHED ON CLIENT SYSTEM]
SCR# 701, e-mail does not display picture

Other:
- Fix to sts_cache->get_videos()
- Added Database Cache inspector (superadmin only)
- Fix for KT updates. Need to set the $session_path, so we read the correct cache
- Fix column name
- Fix for delete fuel card mapping.
- Fix for state transition table caching issue.
- Fix for Brian, timeout error
- Fix for strange situation, Sage 50 GL codes wouldn’t work.
- Fix for SCR 662 - error messages from previous fix
- Tweak to CONVERT_RATE to allow dates up to 90 days ahead
- Session logging for Ohio office/slingshot
- Fix for newer PHP compatibility
- When schema version mismatch, show version numbers
- Minor fix to add shipment screen
- Reliability enhancement for convert html email to PDF
- SCR screen force reverse order on SCR#
- Fix error for list load, non-multi-company searching
- Add extra logging for KT import (fetch code)
- Tweaks to create_address() to avoid certain address2 lines
- Debug for failed to add attachment
',	
	'6.0.20' => 'Other:
- Fix CONVERT_RATE() SQL function, to look back up to 30 days. Returns 0 if fails
- Tweak to show count of logged in users.
- Add ‘none’ as an option to ADDR_VALID, Same in shipment table
- Add ‘none’ as an option to ADDR_VALID in exp_pcm_cache
  Also add pretty printing to JSON file for schema
- Added indexes for optimization (make it work faster)
- Work on possible limiting number of logged in users
- Added logging all DB queries (to the DB) with new setting option/DB_LOGGING
- Optimization, avoid checking main/LAST_CHECKED every time. Once a session is enough.
- Make list SCR screen sortable
- Optimize checking ISACTIVE for users, only check once every 10 screens.
- Optimize cache_following_states, use cache instead of query.
- Optimize setting get, where value is 0.
- Optimize videos table,  cache data rather than query each time.
- List SCRs, add filter on product
- Keeptruckin sync driver, fix column name
- Tweak column width for most used SQL
- Add more DB log views
- Optimize check carrier expired for main page.
- Add index to EXP_ALERT_CACHE, EXP_PCM_DISTANCE_CACHE, EXP_ATTACHMENT, EXP_UNIT
- Fix for updating boolean columns
- Added holidays for 2021 and 2022
- Tweak installer, add more helpful diagnostics
- Tweak settings class to remove PHP count() warning
- Tweak admin menus
- Fix for selecting carriers when no client has insurance requirements.
- Tweak for adding large attachments.
- Attachment stats - restrict location to superadmins
- Tweak, add longer timeout for session_setup
- Added REVERSE keyword for CHECK_SCHEMA
- Tweak to the Processing… popup for data tables.
',
	'6.0.19' => 'Other:
- Fix for bool fields, such as tax exempt.
- Optimize the searching carriers based on insurance requirements.
- Fix STATUS error on overdue CMS feature
- Tweak to send_email() mime encoding html body
',
	'6.0.18' => 'Other:
- Fix for logging changes to client/carrier where the field it long.
  The user log comments column is 256 chars max.
- Increase timeout for AJAX functions to avoid potential timeout issue.
- Another fix for insurance - updated SQL function CHECK_CARRIER_INS
',
	'6.0.17' => 'SCR# 658, Driver load screen - refnum issue
	
Other:
- Allow edit user to have driver + debug
- Fix caching for PHP extension errors
- Fixes to insurance: currency conversion and checking the insurance is current if required.
- Fix state graph code to new column labels
- If option/Skip freight agreement is true, and option/CARRIER_INSUFFCIENT_INS is true
check carrier insurance.
',
	
	'6.0.16' => 'SCR# 652, Send email to accounting when a user changes insurance $ and/or dates? (client and carrier sides) [SETTINGS CHANGED]
SCR# 654, Consolidating issue [PATCHED ON CLIENT SYSTEM]
SCR# 655, Incorrect carrier insurance alert
',
	
	'6.0.15' => 'SCR# 649, Additional change for C-TPAT, SCR# 639 [SCHEMA CHANGED]
SCR# 650, Restrict "Complete Load" to admin if not enough insurance [SETTINGS CHANGED]
SCR# 651, Additional work for Random - SCR# 646

Other:
- Fix for edit user. Log changes to user event log under admin category.
- Don’t show password changed when it wasn’t.
- Update session copy of groups if you changed them.
- Fix for text fields in forms with double quotes.
- Update sts_setting::set_defaults() to refresh the cache if the LAST_CHECKED != current release.
',
	'6.0.14' => 'SCR# 647, R&M Reports - Additional Work [SETTINGS CHANGED]
SCR# 648, Random alert - additional change to SCR# 646 [SCHEMA CHANGED]

Other:
- Add guard for NULL numeric columns which are nullable
- Another attempt to ensure session gc disabled
- Solution for sorting date columns. See edit tractor/trailer
- Another attempt to cure session timeouts
- Fix SCR# 646 - random feature, hide shading based on random group, not admin
- Fix form class for groups field
',
	'6.0.13' => 'SCR# 637, More audit trails [SCHEMA CHANGED]

Other:
- More work on SCR# 646 - hide shading from non-admin, change timeline
- Fix for edit carrier amount, allow selection of currency for lumper
- Fix for CONVERT_AMT() SQL function
',

	'6.0.12' => 'SCR# 638, Carrier insurance - make General Liability insurance optional [SCHEMA CHANGED]
SCR# 639, New requests - clients & carriers [SETTINGS CHANGED] [SCHEMA CHANGED]

Added settings:
- option/RESTRICT_INSURANCE
- option/RESTRICT_CREDIT_LIMIT
- option/RESTRICT_CURRENCY
',
	'6.0.11' => 'SCR# 645, New lumper tidy-up
SCR# 646, Drivers - new request - Random alert [SCHEMA CHANGED]

Other:
- Added setting option/ATTACHMENT_STATS to control access to attachment stats
- Prune local attachments directory tool
',	
	'6.0.10' => 'SCR# 644, Attachment storage - FilesAnywhere support [SETTINGS CHANGED] [SCHEMA CHANGED]

Other:
- Fix for https
',
	'6.0.9' => 'SCR# 643, Assign Resources screen - Add Carrier Trailer,

Other:
- Fix for IFTA map page
- Work on KT updates
- Fix for logging for IFTA/KT
- Fix log out link for timeout during QBOE
- View fuel card now shows raw file contents
- Fixes from macOS install experience
',	
	'6.0.8' => 'SCR# 642, Additional work to SCR# 617 - R&M Reports
	',
	'6.0.7' => 'SCR# 641, Screens keep refreshing on mobile devices

Other:
- Lumper tax, round to 2 decimal places.
- List load, add new filter - Separately paid lumper
- Fix for required fields in company tax table
- Fix for edit driver list loads tab
- Fix to listlane/listlane2 SQL
- More fixes for PHP 7
- Add setting option/ALERT_TAX_ISSUE to disable email alert
- Display larger warning on client billing screen if there is a tax issue.
',

	'6.0.6' => 'SCR# 635, Pay tax for Canadian lumper companies [SCHEMA CHANGED]
SCR# 636, Shipment - Disable send invoices via e-mail during Finance (approve) [SETTINGS CHANGED]

RC2: allow zero carrier if lumper
RC2: work on edit carrier amount
RC2: fix uninitialized reports property for driver login
RC2: fix assert for PHP 7

Other:
- Fix for expired tabs/windows not visible
- Fuel card FTP reliability enhancements
- Fix for add driver pay for PHP7
- Extend session timeout to 4 hours
- Fix for delete company tax.
',
	'6.0.5' => 'SCR# 634, Reuse empty loads [SETTINGS CHANGED]
	
Other:
Added new filters to list shipment screen
	',
	'6.0.4' => 'Major revision of the session code.
- Garbage collection disabled.
- Session lifetime 48 hrs.
- Cookie lifetime 24 hrs.
- Inactivity timeout 2 hrs.
- Warning at 1 hr. (Added modal popup for warning)
- I use _STS_SESSION_AJAX to disable session expiry in AJAX modules
- $_SESSION["LAST_ACTIVITY"] is used to track last activity
- When logged out for activity timeout, you get a screen explaining this.
- User events log shows when you were logged out for activity timeout
- Added setting option/BACKUP_SESSION_FILES
- Debug logging to debug log is set at debug level. Change option/DEBUG_DIAG_LEVEL to view/hide.

Fix for non-numeric shipment code in add shipment screen.
Added setting option/LOGIN_BLOCK_AUTOCOMPLETE
Various fixes for PHP 7, MySQL 8 compatibility.
',
	'6.0.3 RC3' => 'More attempts to fix session issues
Optimizations in caching static data.
Fixes for PHP 7 compatibility
',	
	'6.0.3 RC2' => 'Misc Fixes to Reserved Words and Keywords for MySQL 8.x compatibility
Updates for 6.0 release. 
	',
	'6.0.3' => 'Fix issue with caching R&M Report button on home page
Change line break to \n for csv export
	',
	
	'6.0.2' => 'SCR# 617, R&M PHP Reports
- updated access control via reports
- added next hours/odo in next due columns 
	
Other:
- Fixed excessive javascript Google geocode calls
',

	'6.0.1' => 'SCR# 623, Allow clients to add missing ZIP/Postal codes
SCR# 625, 6.0
SCR# 626, Add lead screen - CLIENT_NAME field issue

Other:
Issue with excessive calls to Google geocoding API
Issues with SCR# 614, add lead form
Add tool to check SQL script for reserved words
Fix column names for trailer class, see SCR#614

',
	'5.0.20' => 'SCR# 615, Driver pay - add NEXT week [PATCHED ON CLIENT SYSTEM]
SCR# 616, Alert Expired - hover over unit number [SETTINGS CHANGED]
SCR# 617, R&M PHP Reports [SETTINGS CHANGED] [SCHEMA CHANGED]
SCR# 620, Carrier audit
SCR# 621, Repair and Maintenance reports [PATCHED ON CLIENT SYSTEM]

Other:
Fix for QB issues due to SCR# 614
Add more driver automated tests
Avoid “Unable to complete load” messages
Add alerts to exp_addattachment.php
Update year
Fix error message in exp_addattachment.php
Session expire, show age
Fix column names, related to SCR# 614 (expect more of these)
',
	'5.0.19' => 'SCR# 257, Quickbooks Online API - support Canadian companies [SETTINGS CHANGED] [SCHEMA CHANGED]
SCR# 606, Archiving Records [SETTINGS CHANGED]
SCR# 610, Shipment report CSV issue [PATCHED ON CLIENT SYSTEM]
SCR# 611, data integrity carrier cost [SCHEMA CHANGED]
SCR# 612, Edit setting - option/SHIPMENT_CUTOFF [PATCHED ON CLIENT SYSTEM]
SCR# 613, Edit client screen - Weird error
SCR# 614, Remove Reserved Words and Keywords for MySQL 8.x compatibility [SCHEMA CHANGED]

Other:
Fix for negative temperature
Work on SCR# 257 - currencies
Add a limit to LOAD_EXPENSE SQL function
More automated testing - add/delete driver
Fix javascript log entry
',
	'5.0.18' => 'SCR# 607, Add loads - shipment gets added to more than one load

Other:
Work on SCR# 604 - add expanded view, enhance icon.
',
	'5.0.17' => 'SCR# 604, Cache Alerts on home page [SCHEMA CHANGED]
SCR# 605, SQL Enhancement to List Loads screen

Other:
Fix for attachment class, $letters error
Fixed an issue with attachments
Fix to zip class SQL
',
	'5.0.16' => 'SCR# 601, Zoominfo - Importing leads - fatal error
SCR# 602, R&M - view report - include attached images
SCR# 603, Duplicate shipment - current month copy over dates [SETTINGS CHANGED]

Other:
Add table check for #fields = #values in get_insert_id()
Adjust DB to support UTF-8 - throw exception if it fails.
List attachment view, show CREATED_BY rather than CHANGED_BY
Tweak the email alerts for tax issues',
	'5.0.15' => 'SCR# 599, Distribute attachment files into subdirectories
	
Other:
Tweak sts_company_tax::tax_info()
Send an alert email if there is a problem, likely a setup issue.',
	'5.0.14' => 'SCR# 596, R&M - Repair notes - title does not appear
SCR# 597, Approve Shipment - check for consolidated shipments without pickup/delivery dates

Other:
Cache Info Page - show top 10 entries
Tweak to List R&M Classes screen - show # Forms and # Instances columns
QuickBooks Online Patch from Github
In IPP.php:
Fixed sign (+ to -) bug for token renewal/expiry calculation. With + , token would need to be expired for 61 seconds before it will be renewed, not 60 seconds left, as intended.',
	
	'5.0.13' => 'SCR# 584, New colum Client_ID in EXP_CLIENT table [SETTINGS CHANGED] [SCHEMA CHANGED]
SCR# 593, Inspection reports - Mechanic user kicked out of edit tractor screen
SCR# 594, Apply distance caching to Google MAPs API
	(Google now requires API keys connected to billing accounts)
	Also includes Google maps on Add shipment to load screen and add stops screen.
	Also allows checking distances between zipcodes,
	Improves address validation.

Other:
Addition to SCR# 588 - Stopoffs want to have dates carried over
Update to ZIP/Postal code typeahead - display error message if no matching entry
Add stopoff note similar to extra charges note in Sage50 export.
Add stopoff note similar to extra charges note in the invoice template.
Tweak to edit setting layout.
',
	'5.0.11' => 'SCR# 571, Quickbooks OAuth 2.0 Sync [SCHEMA CHANGED]
SCR# 581, Duplicate shipment - update Bill-to information [SETTINGS CHANGED]
SCR# 582, stars on required columns
SCR# 587, Date checking too strict
SCR# 588, Add setting to NOT carry over dates when duplicating shipments [SETTINGS CHANGED]
SCR# 591, Invoice add remit on report [SETTINGS CHANGED]',
	'5.0.10' => 'SCR# 567, Add Shipment screen - need save button on shipper and Consignee
SCR# 569, KeepTrucking OAuth 2.0 Sync [SETTINGS CHANGED]
SCR# 573, Add lead screen - possible duplicate fails when name contains quote
SCR# 574, EXP_LOAD Table, Add CARRIER_TOTAL Column [SCHEMA CHANGED]
SCR# 575, Add Shipment screen - lockdown Business Code, after it’s pushed to Sage
SCR# 576, View load, List Load screen - should not allow complete load when already completed
SCR# 577, Canceled loads showing up on report
SCR# 579, Edit Trailer screen - make inspection reports sortable
SCR# 580, FSC Schedule screen - allow sorting columns',
	'5.0.9' => 'SCR# 563, KPI Shipment report
SCR# 572, Add leads screen - mandatory fields',
	'5.0.8' => 'SCR# 520, Accounts Payable
SCR# 555, Cancelled LOAD shows money
SCR# 556, Inspection reports - failed: Data too long for column \'SERIAL\' [SCHEMA CHANGED]
SCR# 557, Edit Driver - drivers’ loads are not listed any more
SCR# 559, Add report fails, due to missing required fields
SCR# 561, Shipment Billing screen - penny rounding issue with percent FSC [PATCHED ON CLIENT SYSTEM]
SCR# 562, Decimal point needs to be moved from scr 561 fix [PATCHED ON CLIENT SYSTEM]
SCR# 564, KPI Completed and not billed 2',
	'5.0.7' => 'SCR# 554, Fuel Card import - additional work to SCR 551 - Add more visibility to import process',
	'5.0.6' => 'SCR# 553, Fuel Card import - additional work to SCR 551',
	'5.0.5' => 'Additional to SCR# 551, added Fuel Card Mapping screen. (Admin->Fuel Management->Fuel Card Mapping)',
	'5.0.4' => 'SCR# 551, BVD fuel card [SCHEMA CHANGED]
Separated out FTP vs MANUAL transport
Added BVD (a form of CSV) formats',
	'5.0.3' => 'SCR# 544, Home page - count of drivers/tractors/trailers does not match [SETTINGS CHANGED]
SCR# 545, Remove remote licensing feature
SCR# 546, Make messages available to managers group, move to Operations menu [SETTINGS CHANGED]
SCR# 547, Messages - clean out expired messages [SETTINGS CHANGED]
SCR# 548, Quickbooks Online Issue [PATCHED ON CLIENT SYSTEM]
SCR# 550, TRYING TO APPROVE OFFICE',
	'5.0.2' => 'SCR# 542, Message Feature [SCHEMA CHANGED]',
	'5.0.1' => 'SCR# 541, Lane Report II - further tweaks [PATCHED ON CLIENT SYSTEM]
Start of 5.0 branch
Updates to login screen, revised logos, updated width of home screen.',
	'4.0.51' => 'SCR# 539, Lane Report II - add multiple selections for states
SCR# 540, RE: Lane Report logic ISSUE',
	'4.0.50' => 'SCR# 537, Additional R&M Work to SCR# 522 [SCHEMA CHANGED]
SCR# 538, Lane Report logic ISSUE [PATCHED ON CLIENT SYSTEM]',
	'4.0.49' => 'SCR# 522, repair and maintenance aka.. inspection report [SETTINGS CHANGED] [SCHEMA CHANGED]
SCR# 523, Updates to Tractor Profiles
SCR# 524, Updates to Trailer profile 2',
	'4.0.48' => 'SCR# 535, Lane Report II',
	'4.0.47' => 'SCR# 531, Exspeedite saving problem [SETTINGS CHANGED]
SCR# 532, drivers exit date
SCR# 533, Home page - warning only active drivers if option/DEFAULT_ACTIVE = true [SETTINGS CHANGED]
',
	'4.0.46' => 'SCR# 514, KPI Lane Report [SETTINGS CHANGED] [SCHEMA CHANGED]
SCR# 528, load screen does not list all loads [SCHEMA CHANGED] [PATCHED ON CLIENT SYSTEM]
SCR# 529, profit settings not working properly
SCR# 530, when I change permission kills password please fix ASAP',
	'4.0.45' => 'SCR# 525, List driver - default to active drivers [SETTINGS CHANGED]
SCR# 526, Add shipment screen - shows Sage50 when using QBOE',
	'4.0.43' => 'SCR# 519, Issues pushing to Sage [SETTINGS CHANGED]
SCR# 521, Manifest - @ symbol prints as # on load sheet.
',
	'4.0.42' => 'SCR# 508, Driver pay - missing detail information for load 0 [PATCHED ON CLIENT SYSTEM]
SCR# 511, mysql 8 [SCHEMA CHANGED]
SCR# 515, Shipment Billing - SQL warning Subquery returns more than 1 row [SCHEMA CHANGED] [PATCHED ON CLIENT SYSTEM]
SCR# 517, failed import',
	);

?>
<div class="container theme-showcase" role="main">
	<div class="well">
		<h1 class="text-center">About Exspeedite</h1>
<!-- Nav tabs -->
<ul class="nav nav-tabs">
    <li role="presentation" class="active"><a href="#EUSLA" aria-controls="EUSLA" role="tab" data-toggle="tab">End User Software License Agreement</a></li>
    <li role="presentation"><a href="#included" aria-controls="settings" role="tab" data-toggle="tab">Included Software</a></li>
    <li role="presentation"><a href="#release" aria-controls="settings" role="tab" data-toggle="tab">Release Notes</a></li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
	<div class="tab-pane active" id="EUSLA">

<pre style="white-space: pre-wrap; word-wrap: break-word;">
END USER SOFTWARE LICENSE AGREEMENT

NOTICE TO USER: PLEASE READ THIS CONTRACT CAREFULLY. BY USING ALL OR ANY PORTION OF THE SOFTWARE YOU ACCEPT ALL THE TERMS AND CONDITIONS OF THIS AGREEMENT, INCLUDING, IN PARTICULAR THE LIMITATIONS ON: USE CONTAINED IN SECTION 2; TRANSFERABILITY IN SECTION 4; WARRANTY IN SECTION 6 AND 7; AND LIABILITY IN SECTION 8. YOU AGREE THAT THIS AGREEMENT IS ENFORCEABLE LIKE ANY WRITTEN NEGOTIATED AGREEMENT SIGNED BY YOU. IF YOU DO NOT AGREE, DO NOT USE THIS SOFTWARE. IF YOU ACQUIRED THE SOFTWARE ON TANGIBLE MEDIA (e.g. CD) WITHOUT AN OPPORTUNITY TO REVIEW THIS LICENSE AND YOU DO NOT ACCEPT THIS AGREEMENT, YOU MAY OBTAIN A REFUND OF THE AMOUNT YOU ORIGINALLY PAID IF YOU: (A) DO NOT USE THE SOFTWARE AND (B) RETURN IT, WITH PROOF OF PAYMENT, TO THE LOCATION FROM WHICH IT WAS OBTAINED WITHIN THIRTY (30) DAYS OF THE PURCHASE DATE.

1. DEFINITIONS

When used in this Agreement, the following terms shall have the respective meanings indicated, such meanings to be applicable to both the singular and plural forms of the terms defined:

“Licensor” means Exspeedite LLC, with its main address located at 1004 Hillock Street North West, Isanti, Minnesota, Mn., United States.

“Software” means (a) all of the contents of the files, disk(s), CD-ROM(s) or other media with which this Agreement is provided, including but not limited to (i) Exspeedite LLC or third party computer information or software; (ii) digital images, stock photographs, clip art, sounds or other artistic works (“Stock Files”); (iii) related explanatory written materials or files (“Documentation”); and (iv) fonts; and (b) upgrades, modified versions, updates, additions, and copies of the Software, if any, licensed to you by Exspeedite LLC (collectively, “Updates”).

“Use” or “Using” means to access, install, download, copy or otherwise benefit from using the functionality of the Software in accordance with the Documentation.

“Licensee” means You or Your Company, unless otherwise indicated.

“Permitted Number” means one (1) unless otherwise indicated under a valid license (e.g. volume license) granted by Exspeedite LLC.

“Computer” means an electronic device that accepts information in digital or similar form and manipulates it for a specific result based on a sequence of instructions.

2. SOFTWARE LICENSE

As long as you comply with the terms of this End User License Agreement (the “Agreement”), Exspeedite LLC grants to you a non-exclusive license to Use the Software for the purposes described in the Documentation. Some third party materials included in the Software may be subject to other terms and conditions, which are typically found in a “Read Me” file located near such materials.

2.1 General Use

You may install and Use a copy of the Software on your compatible computer, up to the Permitted Number of computers; or
2.2 Server Use

You may install one copy of the Software on your computer file server for the purpose of downloading and installing the Software onto other computers within your internal network up to the Permitted Number or you may install one copy of the Software on a computer file server within your internal network for the sole and exclusive purpose of using the Software through commands, data or instructions (e.g. scripts) from an unlimited number of computers on your internal network. No other network use is permitted, including but not limited to, using the Software either directly or through commands, data or instructions from or to a computer not part of your internal network, for internet or web hosting services or by any user not licensed to use this copy of the Software through a valid license from Exspeedite LLC; and

2.3 Backup Copy

You may make one backup copy of the Software, provided your backup copy is not installed or used on any computer. You may not transfer the rights to a backup copy unless you transfer all rights in the Software as provided under Section 6.

2.4 Home Use

You, as the primary user of the computer on which the Software is installed, may also install the Software on one of your home computers. However, the Software may not be used on your home computer at the same time the Software on the primary computer is being used.

2.5 Stock Files

Unless stated otherwise in the “Read-Me” files associated with the Stock Files, which may include specific rights and restrictions with respect to such materials, you may display, modify, reproduce and distribute any of the Stock Files included with the Software. However, you may not distribute the Stock Files on a stand-alone basis, i.e., in circumstances in which the Stock Files constitute the primary value of the product being distributed. Stock Files may not be used in the production of libelous, defamatory, fraudulent, lewd, obscene or pornographic material or any material that infringes upon any third party intellectual property rights or in any otherwise illegal manner. You may not claim any trademark rights in the Stock Files or derivative works thereof.

2.6 Limitations

To the extent that the Software includes Exspeedite LLC Exspeedite TMS software and integration pieces to Exspeedite software, (i) you may customize the installer for such software in accordance with the restrictions found at www.exspeedite.com(e.g., installation of additional plug-in and help files); however, you may not otherwise alter or modify the installer program or create a new installer for any of such software, (ii) such software is licensed and distributed by Exspeedite LLC, and (iii) you are not authorized to use any plug-in or enhancement that permits you to save modifications to a file with such software; however, such use is authorized with Exspeedite LLC, Exspeedite LLC Exspeedite TMS, and other current and future Exspeedite LLC products. For information on how to distribute Exspeedite TMS and integration pieces to Exspeedite software please refer to the sections entitled “How to Distribute Exspeedite TMS” at www.exspeedite.com.

3. INTELLECTUAL PROPERTY RIGHTS

The Software and any copies that you are authorized by Exspeedite LLC to make are the intellectual property of and are owned by Exspeedite LLC and its suppliers. The structure, organization and code of the Software are the valuable trade secrets and confidential information of Exspeedite LLC and its suppliers. The Software is protected by copyright, including without limitation by America Copyright Law, international treaty provisions and applicable laws in the country in which it is being used. You may not copy the Software, except as set forth in Section 2 (“Software License”).

Any copies that you are permitted to make pursuant to this Agreement must contain the same copyright and other proprietary notices that appear on or in the Software. You also agree not to reverse engineer, decompile, disassemble or otherwise attempt to discover the source code of the Software except to the extent you may be expressly permitted to decompile under applicable law, it is essential to do so in order to achieve operability of the Software with another software program, and you have first requested Exspeedite LLC to provide the information necessary to achieve such operability and Exspeedite LLC has not made such information available. Using this API to Quickbooks Online or other outside Vendors, users agree to Exspeedite importing data including clients, vendors, invoices and bills.

Exspeedite LLC has the right to impose reasonable conditions and to request a reasonable fee before providing such information. Any information supplied by Exspeedite LLC or obtained by you, as permitted hereunder, may only be used by you for the purpose described herein and may not be disclosed to any third party or used to create any software which is substantially similar to the expression of the Software. Requests for information should be directed to the Exspeedite LLC Customer Support Department. Trademarks shall be used in accordance with accepted trademark practice, including identification of trademarks owners’ names. Trademarks can only be used to identify printed output produced by the Software and such use of any trademark does not give you any rights of ownership in that trademark. Except as expressly stated above, this Agreement does not grant you any intellectual property rights in the Software.

4. TRANSFER

You may not, rent, lease, sublicense or authorize all or any portion of the Software to be copied onto another user’s computer except as may be expressly permitted herein. You may, however, transfer all your rights to Use the Software to another person or legal entity provided that: (a) you also transfer each this Agreement, the Software and all other software or hardware bundled or pre-installed with the Software, including all copies, Updates and prior versions, and all copies of font software converted into other formats, to such person or entity; (b) you retain no copies, including backups and copies stored on a computer; and (c) the receiving party accepts the terms and conditions of this Agreement and any other terms and conditions upon which you legally purchased a license to the Software. Notwithstanding the foregoing, you may not transfer education, pre-release, or not for resale copies of the Software.

5. MULTIPLE ENVIRONMENT SOFTWARE / MULTIPLE LANGUAGE SOFTWARE / DUAL MEDIA SOFTWARE / MULTIPLE COPIES/ BUNDLES / UPDATES

If the Software supports multiple platforms or languages, if you receive the Software on multiple media, if you otherwise receive multiple copies of the Software, or if you received the Software bundled with other software, the total number of your computers on which all versions of the Software are installed may not exceed the Permitted Number. You may not, rent, lease, sublicense, lend or transfer any versions or copies of such Software you do not Use. If the Software is an Update to a previous version of the Software, you must possess a valid license to such previous version in order to Use the Update. You may continue to Use the previous version of the Software on your computer after you receive the Update to assist you in the transition to the Update, provided that: the Update and the previous version are installed on the same computer; the previous version or copies thereof are not transferred to another party or computer unless all copies of the Update are also transferred to such party or computer; and you acknowledge that any obligation Exspeedite LLC may have to support the previous version of the Software may be ended upon availability of the Update.

6. NO WARRANTY

The Software is being delivered to you “AS IS” and Exspeedite LLC makes no warranty as to its use or performance. Exspeedite LLC AND ITS SUPPLIERS DO NOT AND CANNOT WARRANT THE PERFORMANCE OR RESULTS YOU MAY OBTAIN BY USING THE SOFTWARE. EXCEPT FOR ANY WARRANTY, CONDITION, REPRESENTATION OR TERM TO THE EXTENT TO WHICH THE SAME CANNOT OR MAY NOT BE EXCLUDED OR LIMITED BY LAW APPLICABLE TO YOU IN YOUR JURISDICTION, Exspeedite LLC AND ITS SUPPLIERS MAKE NO WARRANTIES CONDITIONS, REPRESENTATIONS, OR TERMS (EXPRESS OR IMPLIED WHETHER BY STATUTE, COMMON LAW, CUSTOM, USAGE OR OTHERWISE) AS TO ANY MATTER INCLUDING WITHOUT LIMITATION NONINFRINGEMENT OF THIRD PARTY RIGHTS, MERCHANTABILITY, INTEGRATION, SATISFACTORY QUALITY, OR FITNESS FOR ANY PARTICULAR PURPOSE.

7. PRE-RELEASE PRODUCT ADDITIONAL TERMS

If the product you have received with this license is pre-commercial release or beta Software (“Pre-release Software”), then the following Section applies. To the extent that any provision in this Section is in conflict with any other term or condition in this Agreement, this Section shall supercede such other term(s) and condition(s) with respect to the Pre-release Software, but only to the extent necessary to resolve the conflict. You acknowledge that the Software is a pre-release version, does not represent final product from Exspeedite LLC, and may contain bugs, errors and other problems that could cause system or other failures and data loss. Consequently, the Pre-release Software is provided to you “AS-IS”, and Exspeedite LLC disclaims any warranty or liability obligations to you of any kind. WHERE LEGALLY LIABILITY CANNOT BE EXCLUDED FOR PRE-RELEASE SOFTWARE, BUT IT MAY BE LIMITED, Exspeedite LLC’S LIABILITY AND THAT OF ITS SUPPLIERS SHALL BE LIMITED TO THE SUM OF FIFTY DOLLARS (U.S. $50) IN TOTAL. You acknowledge that Exspeedite LLC has not promised or guaranteed to you that Pre-release Software will be announced or made available to anyone in the future, that Exspeedite LLC has no express or implied obligation to you to announce or introduce the Pre-release Software and that Exspeedite LLC may not introduce a product similar to or compatible with the Pre-release Software. Accordingly, you acknowledge that any research or development that you perform regarding the Pre-release Software or any product associated with the Pre-release Software is done entirely at your own risk. During the term of this Agreement, if requested by Exspeedite LLC , you will provide feedback to Exspeedite LLC regarding testing and use of the Pre-release Software, including error or bug reports. If you have been provided the Pre-release Software pursuant to a separate written agreement, such as the Exspeedite LLC Serial Agreement for Unreleased Products, your use of the Software is also governed by such agreement. You agree that you may not and certify that you will not sublicense, lease, loan, rent, or transfer the Pre-release Software. Upon receipt of a later unreleased version of the Pre-release Software or release by Exspeedite LLC of a publicly released commercial version of the Software, whether as a stand-alone product or as part of a larger product, you agree to return or destroy all earlier Pre-release Software received from Exspeedite LLC and to abide by the terms of the End User License Agreement for any such later versions of the Pre-release Software. Notwithstanding anything in this Section to the contrary, if you are located outside the United States of America or Canada, you agree that you will return or destroy all unreleased versions of the Pre-release Software within thirty (30) days of the completion of your testing of the Software when such date is earlier than the date for Exspeedite LLC’s first commercial shipment of the publicly released (commercial) Software.

8. LIMITATION OF LIABILITY

IN NO EVENT WILL Exspeedite LLC OR ITS SUPPLIERS BE LIABLE TO YOU FOR ANY DAMAGES, CLAIMS OR COSTS WHATSOEVER OR ANY CONSEQUENTIAL, INDIRECT, INCIDENTAL DAMAGES, OR ANY LOST PROFITS OR LOST SAVINGS, EVEN IF AN Exspeedite LLC REPRESENTATIVE HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH LOSS, DAMAGES, CLAIMS OR COSTS OR FOR ANY CLAIM BY ANY THIRD PARTY. THE FOREGOING LIMITATIONS AND EXCLUSIONS APPLY TO THE EXTENT PERMITTED BY APPLICABLE LAW IN YOUR JURISDICTION. Exspeedite LLC’S AGGREGATE LIABILITY AND THAT OF ITS SUPPLIERS UNDER OR IN CONNECTION WITH THIS AGREEMENT SHALL BE LIMITED TO THE AMOUNT PAID FOR THE SOFTWARE, IF ANY. Nothing contained in this Agreement limits Exspeedite LLC’s liability to you in the event of death or personal injury resulting from Exspeedite LLC’s negligence or for the tort of deceit (fraud). Exspeedite LLC is acting on behalf of its suppliers for the purpose of disclaiming, excluding and/or limiting obligations, warranties and liability as provided in this Agreement, but in no other respects and for no other purpose. For further information, please see the jurisdiction specific information at the end of this Agreement, if any, or contact Exspeedite LLC’s Customer Support Department.

9. EXPORT RULES (OPTIONAL – FOR AMERICAN COMPANIES)

You agree that the Software will not be shipped, transferred or exported into any country or used in any manner prohibited by the United States Export Administration Act or any other export laws, restrictions or regulations (collectively the “Export Laws”). In addition, if the Software is identified as export controlled items under the Export Laws, you represent and warrant that you are not a citizen, or otherwise located within, an embargoed nation (including without limitation Iran, Iraq, Syria, Sudan, Libya, Cuba, North Korea, and Serbia) and that you are not otherwise prohibited under the Export Laws from receiving the Software. All rights to Use the Software are granted on condition that such rights are forfeited if you fail to comply with the terms of this Agreement.

10. GOVERNING LAW

This Agreement shall be governed by and interpreted in accordance with the laws of the MN.

11. GENERAL PROVISIONS

If any part of this Agreement is found void and unenforceable, it will not affect the validity of the balance of the Agreement, which shall remain valid and enforceable according to its terms. This Agreement shall not prejudice the statutory rights of any party dealing as a consumer. This Agreement may only be modified by a writing signed by an authorized officer of Exspeedite LLC. Updates may be licensed to you by Exspeedite LLC with additional or different terms. This is the entire agreement between Exspeedite LLC and you relating to the Software and it supersedes any prior representations, discussions, undertakings, communications or advertising relating to the Software.

12. NOTICE TO U.S. GOVERNMENT END USERS

The Software and Documentation are “Commercial Items,” as that term is defined at 48 C.F.R. §2.101, consisting of “Commercial Computer Software” and “Commercial Computer Software Documentation,” as such terms are used in 48 C.F.R. §12.212 or 48 C.F.R. §227.7202, as applicable. Consistent with 48 C.F.R. §12.212 or 48 C.F.R. §§227.7202-1 through 227.7202-4, as applicable, the Commercial Computer Software and Commercial Computer Software Documentation are being licensed to U.S. Government end users (a) only as Commercial Items and (b) with only those rights as are granted to all other end users pursuant to the terms and conditions herein. Unpublished-rights reserved under the copyright laws of the United States. For U.S. Government End Users, Exspeedite LLC agrees to comply with all applicable equal opportunity laws including, if appropriate, the provisions of Executive Order 11246, as amended, Section 402 of the Vietnam Era Veterans Readjustment Assistance Act of 1974 (38 USC 4212), and Section 503 of the Rehabilitation Act of 1973, as amended, and the regulations at 41 CFR Parts 60-1 through 60-60, 60-250, and 60-741. The affirmative action clause and regulations contained in the preceding sentence shall be incorporated by reference in this Agreement.

13. COMPLIANCE WITH LICENSES

If you are a business or organization, you agree that upon request from Exspeedite LLC or Exspeedite LLC ‘s authorized representative, you will within thirty (30) days fully document and certify that use of any and all Exspeedite LLC Software at the time of the request is in conformity with your valid licenses from Exspeedite LLC.

If you have any questions regarding this Agreement or if you wish to request any information from Exspeedite LLC please use the address and contact information included with this product to contact the Exspeedite LLC office serving your jurisdiction.

Exspeedite LLC, Exspeedite TMS, and Exspeedite are either registered trademarks or trademarks of Exspeedite LLC in the United States and/or other countries.
</pre>
	</div>
		<div class="tab-pane" id="included">
 <div class="well well-sm">

			<h2>Bootstrap</h2>

<pre style="white-space: pre-wrap; word-wrap: break-word;">
MIT license

Copyright (c) 2011-2016 Twitter, Inc.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
</pre>

			<h2>Datatables</h2>

<pre style="white-space: pre-wrap; word-wrap: break-word;">
MIT license

Copyright (C) 2008-2016, SpryMedia Ltd.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
</pre>

			<h2>QuickBooks PHP DevKit</h2>

<pre style="white-space: pre-wrap; word-wrap: break-word;">
Eclipse Public License -v 1.0

THE ACCOMPANYING PROGRAM IS PROVIDED UNDER THE TERMS OF THIS ECLIPSE PUBLIC 
LICENSE ("AGREEMENT"). ANY USE, REPRODUCTION OR DISTRIBUTION OF THE PROGRAM 
CONSTITUTES RECIPIENT'S ACCEPTANCE OF THIS AGREEMENT.

1. DEFINITIONS

"Contribution" means:

a) in the case of the initial Contributor, the initial code and documentation 
distributed under this Agreement, and

b) in the case of each subsequent Contributor:

i) changes to the Program, and

ii) additions to the Program;

where such changes and/or additions to the Program originate from and are 
distributed by that particular Contributor. A Contribution 'originates' from a 
Contributor if it was added to the Program by such Contributor itself or anyone 
acting on such Contributor's behalf. Contributions do not include additions to 
the Program which: (i) are separate modules of software distributed in 
conjunction with the Program under their own license agreement, and (ii) are 
not derivative works of the Program.

"Contributor" means any person or entity that distributes the Program.

"Licensed Patents " mean patent claims licensable by a Contributor which are 
necessarily infringed by the use or sale of its Contribution alone or when 
combined with the Program.

"Program" means the Contributions distributed in accordance with this Agreement.

"Recipient" means anyone who receives the Program under this Agreement, 
including all Contributors.

2. GRANT OF RIGHTS

a) Subject to the terms of this Agreement, each Contributor hereby grants 
Recipient a non-exclusive, worldwide, royalty-free copyright license to reproduce, 
prepare derivative works of, publicly display, publicly perform, distribute 
and sublicense the Contribution of such Contributor, if any, and such derivative 
works, in source code and object code form.

b) Subject to the terms of this Agreement, each Contributor hereby grants 
Recipient a non-exclusive, worldwide, royalty-free patent license under Licensed 
Patents to make, use, sell, offer to sell, import and otherwise transfer the 
Contribution of such Contributor, if any, in source code and object code form. 
This patent license shall apply to the combination of the Contribution and the 
Program if, at the time the Contribution is added by the Contributor, such 
addition of the Contribution causes such combination to be covered by the 
Licensed Patents. The patent license shall not apply to any other combinations 
which include the Contribution. No hardware per se is licensed hereunder.

c) Recipient understands that although each Contributor grants the licenses to 
its Contributions set forth herein, no assurances are provided by any Contributor 
that the Program does not infringe the patent or other intellectual property 
rights of any other entity. Each Contributor disclaims any liability to Recipient 
for claims brought by any other entity based on infringement of intellectual 
property rights or otherwise. As a condition to exercising the rights and 
licenses granted hereunder, each Recipient hereby assumes sole responsibility 
to secure any other intellectual property rights needed, if any. For example, 
if a third party patent license is required to allow Recipient to distribute 
the Program, it is Recipient's responsibility to acquire that license before 
distributing the Program.

d) Each Contributor represents that to its knowledge it has sufficient copyright 
rights in its Contribution, if any, to grant the copyright license set forth in 
this Agreement.

3. REQUIREMENTS

A Contributor may choose to distribute the Program in object code form under its 
own license agreement, provided that:

a) it complies with the terms and conditions of this Agreement; and

b) its license agreement:

i) effectively disclaims on behalf of all Contributors all warranties and 
conditions, express and implied, including warranties or conditions of title and 
non-infringement, and implied warranties or conditions of merchantability and 
fitness for a particular purpose;

ii) effectively excludes on behalf of all Contributors all liability for 
damages, including direct, indirect, special, incidental and consequential 
damages, such as lost profits;

iii) states that any provisions which differ from this Agreement are offered 
by that Contributor alone and not by any other party; and

iv) states that source code for the Program is available from such 
Contributor, and informs licensees how to obtain it in a reasonable manner on 
or through a medium customarily used for software exchange.

When the Program is made available in source code form:

a) it must be made available under this Agreement; and

b) a copy of this Agreement must be included with each copy of the Program.

Contributors may not remove or alter any copyright notices contained within 
the Program.

Each Contributor must identify itself as the originator of its Contribution, 
if any, in a manner that reasonably allows subsequent Recipients to identify 
the originator of the Contribution.

4. COMMERCIAL DISTRIBUTION

Commercial distributors of software may accept certain responsibilities with 
respect to end users, business partners and the like. While this license is 
intended to facilitate the commercial use of the Program, the Contributor who 
includes the Program in a commercial product offering should do so in a manner 
which does not create potential liability for other Contributors. Therefore, 
if a Contributor includes the Program in a commercial product offering, such 
Contributor ("Commercial Contributor") hereby agrees to defend and indemnify 
every other Contributor ("Indemnified Contributor") against any losses, 
damages and costs (collectively "Losses") arising from claims, lawsuits and 
other legal actions brought by a third party against the Indemnified 
Contributor to the extent caused by the acts or omissions of such Commercial 
Contributor in connection with its distribution of the Program in a commercial 
product offering. The obligations in this section do not apply to any claims 
or Losses relating to any actual or alleged intellectual property infringement. 
In order to qualify, an Indemnified Contributor must: a) promptly notify the 
Commercial Contributor in writing of such claim, and b) allow the Commercial 
Contributor to control, and cooperate with the Commercial Contributor in, the 
defense and any related settlement negotiations. The Indemnified Contributor 
may participate in any such claim at its own expense.

For example, a Contributor might include the Program in a commercial product 
offering, Product X. That Contributor is then a Commercial Contributor. If that 
Commercial Contributor then makes performance claims, or offers warranties 
related to Product X, those performance claims and warranties are such 
Commercial Contributor's responsibility alone. Under this section, the 
Commercial Contributor would have to defend claims against the other 
Contributors related to those performance claims and warranties, and if a court 
requires any other Contributor to pay any damages as a result, the Commercial 
Contributor must pay those damages.

5. NO WARRANTY

EXCEPT AS EXPRESSLY SET FORTH IN THIS AGREEMENT, THE PROGRAM IS PROVIDED ON AN 
"AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, EITHER EXPRESS OR 
IMPLIED INCLUDING, WITHOUT LIMITATION, ANY WARRANTIES OR CONDITIONS OF TITLE, 
NON-INFRINGEMENT, MERCHANTABILITY OR FITNESS FOR A PARTICULAR PURPOSE. Each 
Recipient is solely responsible for determining the appropriateness of using 
and distributing the Program and assumes all risks associated with its exercise 
of rights under this Agreement , including but not limited to the risks and 
costs of program errors, compliance with applicable laws, damage to or loss of 
data, programs or equipment, and unavailability or interruption of operations.

6. DISCLAIMER OF LIABILITY

EXCEPT AS EXPRESSLY SET FORTH IN THIS AGREEMENT, NEITHER RECIPIENT NOR ANY 
CONTRIBUTORS SHALL HAVE ANY LIABILITY FOR ANY DIRECT, INDIRECT, INCIDENTAL, 
SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING WITHOUT LIMITATION 
LOST PROFITS), HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN 
CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING 
IN ANY WAY OUT OF THE USE OR DISTRIBUTION OF THE PROGRAM OR THE EXERCISE OF ANY 
RIGHTS GRANTED HEREUNDER, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.

7. GENERAL

If any provision of this Agreement is invalid or unenforceable under applicable 
law, it shall not affect the validity or enforceability of the remainder of the 
terms of this Agreement, and without further action by the parties hereto, such 
provision shall be reformed to the minimum extent necessary to make such 
provision valid and enforceable.

If Recipient institutes patent litigation against any entity (including a 
cross-claim or counterclaim in a lawsuit) alleging that the Program itself 
(excluding combinations of the Program with other software or hardware) 
infringes such Recipient's patent(s), then such Recipient's rights granted 
under Section 2(b) shall terminate as of the date such litigation is filed.

All Recipient's rights under this Agreement shall terminate if it fails to 
comply with any of the material terms or conditions of this Agreement and does 
not cure such failure in a reasonable period of time after becoming aware of 
such noncompliance. If all Recipient's rights under this Agreement terminate, 
Recipient agrees to cease use and distribution of the Program as soon as 
reasonably practicable. However, Recipient's obligations under this Agreement 
and any licenses granted by Recipient relating to the Program shall continue 
and survive.

Everyone is permitted to copy and distribute copies of this Agreement, but in 
order to avoid inconsistency the Agreement is copyrighted and may only be 
modified in the following manner. The Agreement Steward reserves the right to 
publish new versions (including revisions) of this Agreement from time to time. 
No one other than the Agreement Steward has the right to modify this Agreement. 
The Eclipse Foundation is the initial Agreement Steward. The Eclipse Foundation 
may assign the responsibility to serve as the Agreement Steward to a suitable 
separate entity. Each new version of the Agreement will be given a distinguishing 
version number. The Program (including Contributions) may always be distributed 
subject to the version of the Agreement under which it was received. In addition, 
after a new version of the Agreement is published, Contributor may elect to 
distribute the Program (including its Contributions) under the new version. 
Except as expressly stated in Sections 2(a) and 2(b) above, Recipient receives 
no rights or licenses to the intellectual property of any Contributor under 
this Agreement, whether expressly, by implication, estoppel or otherwise. All 
rights in the Program not expressly granted under this Agreement are reserved.

This Agreement is governed by the laws of the State of New York and the 
intellectual property laws of the United States of America. No party to this 
Agreement will bring a legal action under this Agreement more than one year 
after the cause of action arose. Each party waives its rights to a jury trial 
in any resulting litigation.
</pre>

			<h2>Twitter Bootstrap Responsive Navbar with Multiple Dropdowns (Childrens)</h2>

<pre style="white-space: pre-wrap; word-wrap: break-word;">
                    GNU GENERAL PUBLIC LICENSE
                       Version 3, 29 June 2007

 Copyright (C) 2007 Free Software Foundation, Inc. <http://fsf.org/>
 Everyone is permitted to copy and distribute verbatim copies
 of this license document, but changing it is not allowed.

                            Preamble

  The GNU General Public License is a free, copyleft license for
software and other kinds of works.

  The licenses for most software and other practical works are designed
to take away your freedom to share and change the works.  By contrast,
the GNU General Public License is intended to guarantee your freedom to
share and change all versions of a program--to make sure it remains free
software for all its users.  We, the Free Software Foundation, use the
GNU General Public License for most of our software; it applies also to
any other work released this way by its authors.  You can apply it to
your programs, too.

  When we speak of free software, we are referring to freedom, not
price.  Our General Public Licenses are designed to make sure that you
have the freedom to distribute copies of free software (and charge for
them if you wish), that you receive source code or can get it if you
want it, that you can change the software or use pieces of it in new
free programs, and that you know you can do these things.

  To protect your rights, we need to prevent others from denying you
these rights or asking you to surrender the rights.  Therefore, you have
certain responsibilities if you distribute copies of the software, or if
you modify it: responsibilities to respect the freedom of others.

  For example, if you distribute copies of such a program, whether
gratis or for a fee, you must pass on to the recipients the same
freedoms that you received.  You must make sure that they, too, receive
or can get the source code.  And you must show them these terms so they
know their rights.

  Developers that use the GNU GPL protect your rights with two steps:
(1) assert copyright on the software, and (2) offer you this License
giving you legal permission to copy, distribute and/or modify it.

  For the developers' and authors' protection, the GPL clearly explains
that there is no warranty for this free software.  For both users' and
authors' sake, the GPL requires that modified versions be marked as
changed, so that their problems will not be attributed erroneously to
authors of previous versions.

  Some devices are designed to deny users access to install or run
modified versions of the software inside them, although the manufacturer
can do so.  This is fundamentally incompatible with the aim of
protecting users' freedom to change the software.  The systematic
pattern of such abuse occurs in the area of products for individuals to
use, which is precisely where it is most unacceptable.  Therefore, we
have designed this version of the GPL to prohibit the practice for those
products.  If such problems arise substantially in other domains, we
stand ready to extend this provision to those domains in future versions
of the GPL, as needed to protect the freedom of users.

  Finally, every program is threatened constantly by software patents.
States should not allow patents to restrict development and use of
software on general-purpose computers, but in those that do, we wish to
avoid the special danger that patents applied to a free program could
make it effectively proprietary.  To prevent this, the GPL assures that
patents cannot be used to render the program non-free.

  The precise terms and conditions for copying, distribution and
modification follow.

                       TERMS AND CONDITIONS

  0. Definitions.

  "This License" refers to version 3 of the GNU General Public License.

  "Copyright" also means copyright-like laws that apply to other kinds of
works, such as semiconductor masks.

  "The Program" refers to any copyrightable work licensed under this
License.  Each licensee is addressed as "you".  "Licensees" and
"recipients" may be individuals or organizations.

  To "modify" a work means to copy from or adapt all or part of the work
in a fashion requiring copyright permission, other than the making of an
exact copy.  The resulting work is called a "modified version" of the
earlier work or a work "based on" the earlier work.

  A "covered work" means either the unmodified Program or a work based
on the Program.

  To "propagate" a work means to do anything with it that, without
permission, would make you directly or secondarily liable for
infringement under applicable copyright law, except executing it on a
computer or modifying a private copy.  Propagation includes copying,
distribution (with or without modification), making available to the
public, and in some countries other activities as well.

  To "convey" a work means any kind of propagation that enables other
parties to make or receive copies.  Mere interaction with a user through
a computer network, with no transfer of a copy, is not conveying.

  An interactive user interface displays "Appropriate Legal Notices"
to the extent that it includes a convenient and prominently visible
feature that (1) displays an appropriate copyright notice, and (2)
tells the user that there is no warranty for the work (except to the
extent that warranties are provided), that licensees may convey the
work under this License, and how to view a copy of this License.  If
the interface presents a list of user commands or options, such as a
menu, a prominent item in the list meets this criterion.

  1. Source Code.

  The "source code" for a work means the preferred form of the work
for making modifications to it.  "Object code" means any non-source
form of a work.

  A "Standard Interface" means an interface that either is an official
standard defined by a recognized standards body, or, in the case of
interfaces specified for a particular programming language, one that
is widely used among developers working in that language.

  The "System Libraries" of an executable work include anything, other
than the work as a whole, that (a) is included in the normal form of
packaging a Major Component, but which is not part of that Major
Component, and (b) serves only to enable use of the work with that
Major Component, or to implement a Standard Interface for which an
implementation is available to the public in source code form.  A
"Major Component", in this context, means a major essential component
(kernel, window system, and so on) of the specific operating system
(if any) on which the executable work runs, or a compiler used to
produce the work, or an object code interpreter used to run it.

  The "Corresponding Source" for a work in object code form means all
the source code needed to generate, install, and (for an executable
work) run the object code and to modify the work, including scripts to
control those activities.  However, it does not include the work's
System Libraries, or general-purpose tools or generally available free
programs which are used unmodified in performing those activities but
which are not part of the work.  For example, Corresponding Source
includes interface definition files associated with source files for
the work, and the source code for shared libraries and dynamically
linked subprograms that the work is specifically designed to require,
such as by intimate data communication or control flow between those
subprograms and other parts of the work.

  The Corresponding Source need not include anything that users
can regenerate automatically from other parts of the Corresponding
Source.

  The Corresponding Source for a work in source code form is that
same work.

  2. Basic Permissions.

  All rights granted under this License are granted for the term of
copyright on the Program, and are irrevocable provided the stated
conditions are met.  This License explicitly affirms your unlimited
permission to run the unmodified Program.  The output from running a
covered work is covered by this License only if the output, given its
content, constitutes a covered work.  This License acknowledges your
rights of fair use or other equivalent, as provided by copyright law.

  You may make, run and propagate covered works that you do not
convey, without conditions so long as your license otherwise remains
in force.  You may convey covered works to others for the sole purpose
of having them make modifications exclusively for you, or provide you
with facilities for running those works, provided that you comply with
the terms of this License in conveying all material for which you do
not control copyright.  Those thus making or running the covered works
for you must do so exclusively on your behalf, under your direction
and control, on terms that prohibit them from making any copies of
your copyrighted material outside their relationship with you.

  Conveying under any other circumstances is permitted solely under
the conditions stated below.  Sublicensing is not allowed; section 10
makes it unnecessary.

  3. Protecting Users' Legal Rights From Anti-Circumvention Law.

  No covered work shall be deemed part of an effective technological
measure under any applicable law fulfilling obligations under article
11 of the WIPO copyright treaty adopted on 20 December 1996, or
similar laws prohibiting or restricting circumvention of such
measures.

  When you convey a covered work, you waive any legal power to forbid
circumvention of technological measures to the extent such circumvention
is effected by exercising rights under this License with respect to
the covered work, and you disclaim any intention to limit operation or
modification of the work as a means of enforcing, against the work's
users, your or third parties' legal rights to forbid circumvention of
technological measures.

  4. Conveying Verbatim Copies.

  You may convey verbatim copies of the Program's source code as you
receive it, in any medium, provided that you conspicuously and
appropriately publish on each copy an appropriate copyright notice;
keep intact all notices stating that this License and any
non-permissive terms added in accord with section 7 apply to the code;
keep intact all notices of the absence of any warranty; and give all
recipients a copy of this License along with the Program.

  You may charge any price or no price for each copy that you convey,
and you may offer support or warranty protection for a fee.

  5. Conveying Modified Source Versions.

  You may convey a work based on the Program, or the modifications to
produce it from the Program, in the form of source code under the
terms of section 4, provided that you also meet all of these conditions:

    a) The work must carry prominent notices stating that you modified
    it, and giving a relevant date.

    b) The work must carry prominent notices stating that it is
    released under this License and any conditions added under section
    7.  This requirement modifies the requirement in section 4 to
    "keep intact all notices".

    c) You must license the entire work, as a whole, under this
    License to anyone who comes into possession of a copy.  This
    License will therefore apply, along with any applicable section 7
    additional terms, to the whole of the work, and all its parts,
    regardless of how they are packaged.  This License gives no
    permission to license the work in any other way, but it does not
    invalidate such permission if you have separately received it.

    d) If the work has interactive user interfaces, each must display
    Appropriate Legal Notices; however, if the Program has interactive
    interfaces that do not display Appropriate Legal Notices, your
    work need not make them do so.

  A compilation of a covered work with other separate and independent
works, which are not by their nature extensions of the covered work,
and which are not combined with it such as to form a larger program,
in or on a volume of a storage or distribution medium, is called an
"aggregate" if the compilation and its resulting copyright are not
used to limit the access or legal rights of the compilation's users
beyond what the individual works permit.  Inclusion of a covered work
in an aggregate does not cause this License to apply to the other
parts of the aggregate.

  6. Conveying Non-Source Forms.

  You may convey a covered work in object code form under the terms
of sections 4 and 5, provided that you also convey the
machine-readable Corresponding Source under the terms of this License,
in one of these ways:

    a) Convey the object code in, or embodied in, a physical product
    (including a physical distribution medium), accompanied by the
    Corresponding Source fixed on a durable physical medium
    customarily used for software interchange.

    b) Convey the object code in, or embodied in, a physical product
    (including a physical distribution medium), accompanied by a
    written offer, valid for at least three years and valid for as
    long as you offer spare parts or customer support for that product
    model, to give anyone who possesses the object code either (1) a
    copy of the Corresponding Source for all the software in the
    product that is covered by this License, on a durable physical
    medium customarily used for software interchange, for a price no
    more than your reasonable cost of physically performing this
    conveying of source, or (2) access to copy the
    Corresponding Source from a network server at no charge.

    c) Convey individual copies of the object code with a copy of the
    written offer to provide the Corresponding Source.  This
    alternative is allowed only occasionally and noncommercially, and
    only if you received the object code with such an offer, in accord
    with subsection 6b.

    d) Convey the object code by offering access from a designated
    place (gratis or for a charge), and offer equivalent access to the
    Corresponding Source in the same way through the same place at no
    further charge.  You need not require recipients to copy the
    Corresponding Source along with the object code.  If the place to
    copy the object code is a network server, the Corresponding Source
    may be on a different server (operated by you or a third party)
    that supports equivalent copying facilities, provided you maintain
    clear directions next to the object code saying where to find the
    Corresponding Source.  Regardless of what server hosts the
    Corresponding Source, you remain obligated to ensure that it is
    available for as long as needed to satisfy these requirements.

    e) Convey the object code using peer-to-peer transmission, provided
    you inform other peers where the object code and Corresponding
    Source of the work are being offered to the general public at no
    charge under subsection 6d.

  A separable portion of the object code, whose source code is excluded
from the Corresponding Source as a System Library, need not be
included in conveying the object code work.

  A "User Product" is either (1) a "consumer product", which means any
tangible personal property which is normally used for personal, family,
or household purposes, or (2) anything designed or sold for incorporation
into a dwelling.  In determining whether a product is a consumer product,
doubtful cases shall be resolved in favor of coverage.  For a particular
product received by a particular user, "normally used" refers to a
typical or common use of that class of product, regardless of the status
of the particular user or of the way in which the particular user
actually uses, or expects or is expected to use, the product.  A product
is a consumer product regardless of whether the product has substantial
commercial, industrial or non-consumer uses, unless such uses represent
the only significant mode of use of the product.

  "Installation Information" for a User Product means any methods,
procedures, authorization keys, or other information required to install
and execute modified versions of a covered work in that User Product from
a modified version of its Corresponding Source.  The information must
suffice to ensure that the continued functioning of the modified object
code is in no case prevented or interfered with solely because
modification has been made.

  If you convey an object code work under this section in, or with, or
specifically for use in, a User Product, and the conveying occurs as
part of a transaction in which the right of possession and use of the
User Product is transferred to the recipient in perpetuity or for a
fixed term (regardless of how the transaction is characterized), the
Corresponding Source conveyed under this section must be accompanied
by the Installation Information.  But this requirement does not apply
if neither you nor any third party retains the ability to install
modified object code on the User Product (for example, the work has
been installed in ROM).

  The requirement to provide Installation Information does not include a
requirement to continue to provide support service, warranty, or updates
for a work that has been modified or installed by the recipient, or for
the User Product in which it has been modified or installed.  Access to a
network may be denied when the modification itself materially and
adversely affects the operation of the network or violates the rules and
protocols for communication across the network.

  Corresponding Source conveyed, and Installation Information provided,
in accord with this section must be in a format that is publicly
documented (and with an implementation available to the public in
source code form), and must require no special password or key for
unpacking, reading or copying.

  7. Additional Terms.

  "Additional permissions" are terms that supplement the terms of this
License by making exceptions from one or more of its conditions.
Additional permissions that are applicable to the entire Program shall
be treated as though they were included in this License, to the extent
that they are valid under applicable law.  If additional permissions
apply only to part of the Program, that part may be used separately
under those permissions, but the entire Program remains governed by
this License without regard to the additional permissions.

  When you convey a copy of a covered work, you may at your option
remove any additional permissions from that copy, or from any part of
it.  (Additional permissions may be written to require their own
removal in certain cases when you modify the work.)  You may place
additional permissions on material, added by you to a covered work,
for which you have or can give appropriate copyright permission.

  Notwithstanding any other provision of this License, for material you
add to a covered work, you may (if authorized by the copyright holders of
that material) supplement the terms of this License with terms:

    a) Disclaiming warranty or limiting liability differently from the
    terms of sections 15 and 16 of this License; or

    b) Requiring preservation of specified reasonable legal notices or
    author attributions in that material or in the Appropriate Legal
    Notices displayed by works containing it; or

    c) Prohibiting misrepresentation of the origin of that material, or
    requiring that modified versions of such material be marked in
    reasonable ways as different from the original version; or

    d) Limiting the use for publicity purposes of names of licensors or
    authors of the material; or

    e) Declining to grant rights under trademark law for use of some
    trade names, trademarks, or service marks; or

    f) Requiring indemnification of licensors and authors of that
    material by anyone who conveys the material (or modified versions of
    it) with contractual assumptions of liability to the recipient, for
    any liability that these contractual assumptions directly impose on
    those licensors and authors.

  All other non-permissive additional terms are considered "further
restrictions" within the meaning of section 10.  If the Program as you
received it, or any part of it, contains a notice stating that it is
governed by this License along with a term that is a further
restriction, you may remove that term.  If a license document contains
a further restriction but permits relicensing or conveying under this
License, you may add to a covered work material governed by the terms
of that license document, provided that the further restriction does
not survive such relicensing or conveying.

  If you add terms to a covered work in accord with this section, you
must place, in the relevant source files, a statement of the
additional terms that apply to those files, or a notice indicating
where to find the applicable terms.

  Additional terms, permissive or non-permissive, may be stated in the
form of a separately written license, or stated as exceptions;
the above requirements apply either way.

  8. Termination.

  You may not propagate or modify a covered work except as expressly
provided under this License.  Any attempt otherwise to propagate or
modify it is void, and will automatically terminate your rights under
this License (including any patent licenses granted under the third
paragraph of section 11).

  However, if you cease all violation of this License, then your
license from a particular copyright holder is reinstated (a)
provisionally, unless and until the copyright holder explicitly and
finally terminates your license, and (b) permanently, if the copyright
holder fails to notify you of the violation by some reasonable means
prior to 60 days after the cessation.

  Moreover, your license from a particular copyright holder is
reinstated permanently if the copyright holder notifies you of the
violation by some reasonable means, this is the first time you have
received notice of violation of this License (for any work) from that
copyright holder, and you cure the violation prior to 30 days after
your receipt of the notice.

  Termination of your rights under this section does not terminate the
licenses of parties who have received copies or rights from you under
this License.  If your rights have been terminated and not permanently
reinstated, you do not qualify to receive new licenses for the same
material under section 10.

  9. Acceptance Not Required for Having Copies.

  You are not required to accept this License in order to receive or
run a copy of the Program.  Ancillary propagation of a covered work
occurring solely as a consequence of using peer-to-peer transmission
to receive a copy likewise does not require acceptance.  However,
nothing other than this License grants you permission to propagate or
modify any covered work.  These actions infringe copyright if you do
not accept this License.  Therefore, by modifying or propagating a
covered work, you indicate your acceptance of this License to do so.

  10. Automatic Licensing of Downstream Recipients.

  Each time you convey a covered work, the recipient automatically
receives a license from the original licensors, to run, modify and
propagate that work, subject to this License.  You are not responsible
for enforcing compliance by third parties with this License.

  An "entity transaction" is a transaction transferring control of an
organization, or substantially all assets of one, or subdividing an
organization, or merging organizations.  If propagation of a covered
work results from an entity transaction, each party to that
transaction who receives a copy of the work also receives whatever
licenses to the work the party's predecessor in interest had or could
give under the previous paragraph, plus a right to possession of the
Corresponding Source of the work from the predecessor in interest, if
the predecessor has it or can get it with reasonable efforts.

  You may not impose any further restrictions on the exercise of the
rights granted or affirmed under this License.  For example, you may
not impose a license fee, royalty, or other charge for exercise of
rights granted under this License, and you may not initiate litigation
(including a cross-claim or counterclaim in a lawsuit) alleging that
any patent claim is infringed by making, using, selling, offering for
sale, or importing the Program or any portion of it.

  11. Patents.

  A "contributor" is a copyright holder who authorizes use under this
License of the Program or a work on which the Program is based.  The
work thus licensed is called the contributor's "contributor version".

  A contributor's "essential patent claims" are all patent claims
owned or controlled by the contributor, whether already acquired or
hereafter acquired, that would be infringed by some manner, permitted
by this License, of making, using, or selling its contributor version,
but do not include claims that would be infringed only as a
consequence of further modification of the contributor version.  For
purposes of this definition, "control" includes the right to grant
patent sublicenses in a manner consistent with the requirements of
this License.

  Each contributor grants you a non-exclusive, worldwide, royalty-free
patent license under the contributor's essential patent claims, to
make, use, sell, offer for sale, import and otherwise run, modify and
propagate the contents of its contributor version.

  In the following three paragraphs, a "patent license" is any express
agreement or commitment, however denominated, not to enforce a patent
(such as an express permission to practice a patent or covenant not to
sue for patent infringement).  To "grant" such a patent license to a
party means to make such an agreement or commitment not to enforce a
patent against the party.

  If you convey a covered work, knowingly relying on a patent license,
and the Corresponding Source of the work is not available for anyone
to copy, free of charge and under the terms of this License, through a
publicly available network server or other readily accessible means,
then you must either (1) cause the Corresponding Source to be so
available, or (2) arrange to deprive yourself of the benefit of the
patent license for this particular work, or (3) arrange, in a manner
consistent with the requirements of this License, to extend the patent
license to downstream recipients.  "Knowingly relying" means you have
actual knowledge that, but for the patent license, your conveying the
covered work in a country, or your recipient's use of the covered work
in a country, would infringe one or more identifiable patents in that
country that you have reason to believe are valid.

  If, pursuant to or in connection with a single transaction or
arrangement, you convey, or propagate by procuring conveyance of, a
covered work, and grant a patent license to some of the parties
receiving the covered work authorizing them to use, propagate, modify
or convey a specific copy of the covered work, then the patent license
you grant is automatically extended to all recipients of the covered
work and works based on it.

  A patent license is "discriminatory" if it does not include within
the scope of its coverage, prohibits the exercise of, or is
conditioned on the non-exercise of one or more of the rights that are
specifically granted under this License.  You may not convey a covered
work if you are a party to an arrangement with a third party that is
in the business of distributing software, under which you make payment
to the third party based on the extent of your activity of conveying
the work, and under which the third party grants, to any of the
parties who would receive the covered work from you, a discriminatory
patent license (a) in connection with copies of the covered work
conveyed by you (or copies made from those copies), or (b) primarily
for and in connection with specific products or compilations that
contain the covered work, unless you entered into that arrangement,
or that patent license was granted, prior to 28 March 2007.

  Nothing in this License shall be construed as excluding or limiting
any implied license or other defenses to infringement that may
otherwise be available to you under applicable patent law.

  12. No Surrender of Others' Freedom.

  If conditions are imposed on you (whether by court order, agreement or
otherwise) that contradict the conditions of this License, they do not
excuse you from the conditions of this License.  If you cannot convey a
covered work so as to satisfy simultaneously your obligations under this
License and any other pertinent obligations, then as a consequence you may
not convey it at all.  For example, if you agree to terms that obligate you
to collect a royalty for further conveying from those to whom you convey
the Program, the only way you could satisfy both those terms and this
License would be to refrain entirely from conveying the Program.

  13. Use with the GNU Affero General Public License.

  Notwithstanding any other provision of this License, you have
permission to link or combine any covered work with a work licensed
under version 3 of the GNU Affero General Public License into a single
combined work, and to convey the resulting work.  The terms of this
License will continue to apply to the part which is the covered work,
but the special requirements of the GNU Affero General Public License,
section 13, concerning interaction through a network will apply to the
combination as such.

  14. Revised Versions of this License.

  The Free Software Foundation may publish revised and/or new versions of
the GNU General Public License from time to time.  Such new versions will
be similar in spirit to the present version, but may differ in detail to
address new problems or concerns.

  Each version is given a distinguishing version number.  If the
Program specifies that a certain numbered version of the GNU General
Public License "or any later version" applies to it, you have the
option of following the terms and conditions either of that numbered
version or of any later version published by the Free Software
Foundation.  If the Program does not specify a version number of the
GNU General Public License, you may choose any version ever published
by the Free Software Foundation.

  If the Program specifies that a proxy can decide which future
versions of the GNU General Public License can be used, that proxy's
public statement of acceptance of a version permanently authorizes you
to choose that version for the Program.

  Later license versions may give you additional or different
permissions.  However, no additional obligations are imposed on any
author or copyright holder as a result of your choosing to follow a
later version.

  15. Disclaimer of Warranty.

  THERE IS NO WARRANTY FOR THE PROGRAM, TO THE EXTENT PERMITTED BY
APPLICABLE LAW.  EXCEPT WHEN OTHERWISE STATED IN WRITING THE COPYRIGHT
HOLDERS AND/OR OTHER PARTIES PROVIDE THE PROGRAM "AS IS" WITHOUT WARRANTY
OF ANY KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING, BUT NOT LIMITED TO,
THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
PURPOSE.  THE ENTIRE RISK AS TO THE QUALITY AND PERFORMANCE OF THE PROGRAM
IS WITH YOU.  SHOULD THE PROGRAM PROVE DEFECTIVE, YOU ASSUME THE COST OF
ALL NECESSARY SERVICING, REPAIR OR CORRECTION.

  16. Limitation of Liability.

  IN NO EVENT UNLESS REQUIRED BY APPLICABLE LAW OR AGREED TO IN WRITING
WILL ANY COPYRIGHT HOLDER, OR ANY OTHER PARTY WHO MODIFIES AND/OR CONVEYS
THE PROGRAM AS PERMITTED ABOVE, BE LIABLE TO YOU FOR DAMAGES, INCLUDING ANY
GENERAL, SPECIAL, INCIDENTAL OR CONSEQUENTIAL DAMAGES ARISING OUT OF THE
USE OR INABILITY TO USE THE PROGRAM (INCLUDING BUT NOT LIMITED TO LOSS OF
DATA OR DATA BEING RENDERED INACCURATE OR LOSSES SUSTAINED BY YOU OR THIRD
PARTIES OR A FAILURE OF THE PROGRAM TO OPERATE WITH ANY OTHER PROGRAMS),
EVEN IF SUCH HOLDER OR OTHER PARTY HAS BEEN ADVISED OF THE POSSIBILITY OF
SUCH DAMAGES.

  17. Interpretation of Sections 15 and 16.

  If the disclaimer of warranty and limitation of liability provided
above cannot be given local legal effect according to their terms,
reviewing courts shall apply local law that most closely approximates
an absolute waiver of all civil liability in connection with the
Program, unless a warranty or assumption of liability accompanies a
copy of the Program in return for a fee.

                     END OF TERMS AND CONDITIONS

            How to Apply These Terms to Your New Programs

  If you develop a new program, and you want it to be of the greatest
possible use to the public, the best way to achieve this is to make it
free software which everyone can redistribute and change under these terms.

  To do so, attach the following notices to the program.  It is safest
to attach them to the start of each source file to most effectively
state the exclusion of warranty; and each file should have at least
the "copyright" line and a pointer to where the full notice is found.

    <one line to give the program's name and a brief idea of what it does.>
    Copyright (C) <year>  <name of author>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

Also add information on how to contact you by electronic and paper mail.

  If the program does terminal interaction, make it output a short
notice like this when it starts in an interactive mode:

    <program>  Copyright (C) <year>  <name of author>
    This program comes with ABSOLUTELY NO WARRANTY; for details type `show w'.
    This is free software, and you are welcome to redistribute it
    under certain conditions; type `show c' for details.

The hypothetical commands `show w' and `show c' should show the appropriate
parts of the General Public License.  Of course, your program's commands
might be different; for a GUI interface, you would use an "about box".

  You should also get your employer (if you work as a programmer) or school,
if any, to sign a "copyright disclaimer" for the program, if necessary.
For more information on this, and how to apply and follow the GNU GPL, see
<http://www.gnu.org/licenses/>.

  The GNU General Public License does not permit incorporating your program
into proprietary programs.  If your program is a subroutine library, you
may consider it more useful to permit linking proprietary applications with
the library.  If this is what you want to do, use the GNU Lesser General
Public License instead of this License.  But first, please read
<http://www.gnu.org/philosophy/why-not-lgpl.html>.
</pre>

			<h2>Bootstrap Submenu</h2>

<pre style="white-space: pre-wrap; word-wrap: break-word;">
The MIT License (MIT)

Copyright Vasily A., 2014-2016

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
</pre>

			<h2>Bootstrap Datetimepicker</h2>

<pre style="white-space: pre-wrap; word-wrap: break-word;">
 * bootstrap-datetimepicker.js
 * =========================================================
 * Copyright 2012 Stefan Petre
 * Improvements by Andrew Rowls
 * Improvements by Sébastien Malot
 * Improvements by Yun Lai
 * Project URL : http://www.malot.fr/bootstrap-datetimepicker
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
</pre>

			<h2>A simple PHP CAPTCHA script</h2>

<pre style="white-space: pre-wrap; word-wrap: break-word;">
_Copyright 2011 Cory LaViska for A Beautiful Site, LLC. (http://abeautifulsite.net/)_

_Licensed under the MIT license: http://opensource.org/licenses/MIT_

## Demo and Usage

http://labs.abeautifulsite.net/simple-php-captcha/

## Attribution

 - Special thanks to Subtle Patterns for the patterns used for default backgrounds: http://subtlepatterns.com/
 - Special thanks to dafont.com for providing Times New Yorker: http://www.dafont.com/
</pre>

			<h2>Typeahead</h2>

<pre style="white-space: pre-wrap; word-wrap: break-word;">
Copyright (c) 2013-2014 Twitter, Inc

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
</pre>

			<h2>jQuery.UI.iPad plugin</h2>

<pre style="white-space: pre-wrap; word-wrap: break-word;">
* Copyright (c) 2010 Stephen von Takach
* licensed under MIT.
* Date: 27/8/2010
*
* Project Home: 
* http://code.google.com/p/jquery-ui-for-ipad-and-iphone/
</pre>


			<h2>JQuery Location Picker plugin</h2>

<pre style="white-space: pre-wrap; word-wrap: break-word;">
The MIT License (MIT)

Copyright (c) 2013 Logicify

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
</pre>




			<h2>Bootstrap Multiselect</h2>

<pre style="white-space: pre-wrap; word-wrap: break-word;">

Apache License, Version 2.0

    Copyright 2012 - 2018 David Stutz

    Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0.

    Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
BSD 3-Clause License

    Copyright (c) 2012 - 2018 David Stutz

    All rights reserved.

    Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

        Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
        Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
        Neither the name of David Stutz nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission. 

    THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
</pre>
		</div>
	</div>
		<div class="tab-pane" id="release">
 <div class="well well-sm">
<?php
	foreach( $release_notes as $release => $details ) {
		echo '<h3>Release '.$release.'</h3>
		<pre>'.$details.'</pre>
		<hr>';
	}	
?>
		</div>
</div>


	</div>
</div>
<?php
require_once( "include/footer_inc.php" );
?>

