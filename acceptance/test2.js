//! test2.js - Menu Navigation Testing
// Run with 
// casperjs test test2.js
// Debug with
// casperjs test --log-level=debug --verbose test2.js

// Get Configuration, see config.json.
var fs = require('fs');
var exp = JSON.parse(fs.read('config.json'));

// This is a workaround alternative to this.click(link)
// It does not trigger a resize event. (1 test)
function myclick( test, casper, link ) {
	test.assertExists(link);
	test.comment('myclick: send click event to ' + link);
	casper.evaluate(function(link) {
		var evt = document.createEvent('MouseEvents');
		evt.initEvent('click', true, false);
		document.querySelector(link).dispatchEvent(evt); 
	}, link);
}

var admin_menu = 'a#adminmenu';
var profiles_menu = 'a#profilesmenu';
var operations_menu = 'a#operationsmenu';
var kpi_menu = 'a#reportsmenu';

// Click Admin menu, followed by link (2 tests)
function menu_link( test, casper, menu, url ) {
	var link = 'ul.dropdown-menu li a[href="'+url+'"]';
	casper.evaluate(function () {
	    [].forEach.call(__utils__.findAll('a'), function(link) {
	        link.removeAttribute('target');
	    });
	});

	casper.then(function() {		
		casper.waitForSelector(menu, function() {
			//! Click Admin
			myclick( test, casper, menu );
		});
	});
	
	casper.then(function() {
		this.waitForSelector(link, function() {
			//! Click Link
			myclick( test, casper, link );
		});
	});
}

// Look for PHP errors visible
function php_errors( test ) {
	test.assertTextDoesntExist('Notice:', 'No Notices');
	test.assertTextDoesntExist('Warning:', 'No Warnings');
	test.assertTextDoesntExist('Error:', 'No Errors');
	test.assertTextDoesntExist('Fatal error:', 'No Fatal errors');
	test.assertTextDoesntExist('Deprecated:', 'Deprecated');
}

// http://docs.casperjs.org/en/latest/events-filters.html#remote-message
casper.on("remote.message", function(msg) {
    this.echo("Console: " + msg);
});

// http://docs.casperjs.org/en/latest/events-filters.html#page-error
casper.on("page.error", function(msg, trace) {
    this.echo("Error: " + msg);
    // maybe make it a little fancier with the code from the PhantomJS equivalent
});

// http://docs.casperjs.org/en/latest/events-filters.html#resource-error
casper.on("resource.error", function(resourceError) {
	if( resourceError.errorCode != 5 && resourceError.errorCode != 6 )
	    this.echo("ResourceError: " + JSON.stringify(resourceError, undefined, 4));
});

// http://docs.casperjs.org/en/latest/events-filters.html#page-initialized
casper.on("page.initialized", function(page) {
    // CasperJS doesn't provide `onResourceTimeout`, so it must be set through 
    // the PhantomJS means. This is only possible when the page is initialized
    page.onResourceTimeout = function(request) {
        console.log('Response Timeout (#' + request.id + '): ' + JSON.stringify(request));
    };
});

// If you get dubious - adjust the number of tests to match
casper.test.begin('Menu Navigation Testing', 132, function suite(test) {
	casper.start(exp.startUrl).then(function() {
		test.assert(this.getCurrentUrl().indexOf("exp_login.php") > 0, "Page is exp_login.php");
		test.assertTitle("Exspeedite - Sign In", "Login title is the one expected");
		test.assertExists('form[name="login"]', "login form is found");
		this.fill('form[name="login"]', {
			username: exp.userName,
			password: exp.userPw
			}, true);
	}).then(function() {		
		casper.waitForSelector('li a#logout', function() {
			test.comment('Should be on home page now');
			test.assert(this.getCurrentUrl().indexOf("index.php") > 0, "Page is index.php");
			test.assertTitle("Exspeedite - Welcome to Exspeedite", "Home page title is ok");
			php_errors( test );

			
			//! Click Admin > Users
			menu_link( test, casper, admin_menu, 'exp_listuser.php' );
		});
	}).then(function() {
		this.waitForUrl(/exp_listuser\.php$/, function() {
			test.comment('Should be on list users page now');
			test.assert(this.getCurrentUrl().indexOf("exp_listuser.php") > 0, "Page is exp_listuser.php");
			test.assertTitle("Exspeedite - List Users", "List Users page title is ok");
			php_errors( test );


			//! Click Admin > Status Codes	
			menu_link( test, casper, admin_menu, 'exp_liststatus_codes.php' );
		});
	}).then(function() {
		this.waitForUrl(/exp_liststatus_codes\.php$/, function() {
			test.comment('Should be on Status Codes page now');
			test.assert(this.getCurrentUrl().indexOf("exp_liststatus_codes.php") > 0, "Page is exp_liststatus_codes.php");
			test.assertTitle("Exspeedite - List Status Codes", "List Status Codes page title is ok");
			php_errors( test );



			//! Click Admin > Units	
			menu_link( test, casper, admin_menu, 'exp_listunit.php' );
		});
	}).then(function() {
		this.waitForUrl(/exp_listunit\.php$/, function() {
			test.comment('Should be on Units page now');
			test.assert(this.getCurrentUrl().indexOf("exp_listunit.php") > 0, "Page is exp_listunit.php");
			test.assertTitle("Exspeedite - List Units", "List Units page title is ok");
			php_errors( test );



			//! Click Admin > Settings	
			menu_link( test, casper, admin_menu, 'exp_listsetting.php' );
		});
	}).then(function() {
		this.waitForUrl(/exp_listsetting\.php$/, function() {
			test.comment('Should be on Settings page now');
			test.assert(this.getCurrentUrl().indexOf("exp_listsetting.php") > 0, "Page is exp_listsetting.php");
			test.assertTitle("Exspeedite - List Settings", "List Settings page title is ok");
			php_errors( test );



			//! Click Profiles > Drivers	
			menu_link( test, casper, profiles_menu, 'exp_listdriver.php' );
		});
	}).then(function() {
		this.waitForUrl(/exp_listdriver\.php$/, function() {
			test.comment('Should be on List Drivers page now');
			test.assert(this.getCurrentUrl().indexOf("exp_listdriver.php") > 0, "Page is exp_listdriver.php");
			test.assertTitle("Exspeedite - List Drivers", "List Drivers page title is ok");
			php_errors( test );



			//! Click Profiles > Tractors	
			menu_link( test, casper, profiles_menu, 'exp_listtractor.php' );
		});
	}).then(function() {
		this.waitForUrl(/exp_listtractor\.php$/, function() {
			test.comment('Should be on List Tractors page now');
			test.assert(this.getCurrentUrl().indexOf("exp_listtractor.php") > 0, "Page is exp_listtractor.php");
			test.assertTitle("Exspeedite - List Tractors", "List Tractors page title is ok");
			php_errors( test );



			//! Click Profiles > Trailers	
			menu_link( test, casper, profiles_menu, 'exp_listtrailer.php' );
		});
	}).then(function() {
		this.waitForUrl(/exp_listtrailer\.php$/, function() {
			test.comment('Should be on List Trailers page now');
			test.assert(this.getCurrentUrl().indexOf("exp_listtrailer.php") > 0, "Page is exp_listtrailer.php");
			test.assertTitle("Exspeedite - List Trailers", "List Trailers page title is ok");
			php_errors( test );



			//! Click Operations > Shipments	
			menu_link( test, casper, operations_menu, 'exp_listshipment.php' );
		});
	}).then(function() {
		this.waitForUrl(/exp_listshipment\.php$/, function() {
			test.comment('Should be on List Shipments page now');
			test.assert(this.getCurrentUrl().indexOf("exp_listshipment.php") > 0, "Page is exp_listshipment.php");
			test.assertTitle("Exspeedite - List Shipments", "List Shipments page title is ok");
			php_errors( test );



			//! Click Operations > Summary View	
			menu_link( test, casper, operations_menu, 'exp_listsummary.php' );
		});
	}).then(function() {
		this.waitForUrl(/exp_listsummary\.php$/, function() {
			test.comment('Should be on Summary View page now');
			test.assert(this.getCurrentUrl().indexOf("exp_listsummary.php") > 0, "Page is exp_listsummary.php");
			test.assertTitle("Exspeedite - Summary View", "Summary View page title is ok");
			php_errors( test );



			//! Click Operations > Trips/Loads	
			menu_link( test, casper, operations_menu, 'exp_listload.php' );
		});
	}).then(function() {
		this.waitForUrl(/exp_listload\.php$/, function() {
			test.comment('Should be on Loads page now');
			test.assert(this.getCurrentUrl().indexOf("exp_listload.php") > 0, "Page is exp_listload.php");
			test.assertTitle("Exspeedite - Loads", "Loads page title is ok");
			php_errors( test );



			//! Click KPI > Mileage Report	
			menu_link( test, casper, kpi_menu, 'exp_listmileage.php' );
		});
	}).then(function() {
		this.waitForUrl(/exp_listmileage\.php$/, function() {
			test.comment('Should be on Mileage Report page now');
			test.assert(this.getCurrentUrl().indexOf("exp_listmileage.php") > 0, "Page is exp_listmileage.php");
			test.assertTitle("Exspeedite - Mileage Report", "Mileage Report page title is ok");
			php_errors( test );



			//! Click KPI > Top 20 Clients	
			menu_link( test, casper, kpi_menu, 'exp_list_top20.php' );
		});
	}).then(function() {
		this.waitForUrl(/exp_list_top20\.php$/, function() {
			test.comment('Should be on Top 20 Clients page now');
			test.assert(this.getCurrentUrl().indexOf("exp_list_top20.php") > 0, "Page is exp_list_top20.php");
			test.assertTitle("Exspeedite - Top 20 Clients", "Top 20 Clients page title is ok");
			php_errors( test );



			//! Click KPI > Key Accounts	
			menu_link( test, casper, kpi_menu, 'exp_list_key_acct.php' );
		});
	}).then(function() {
		this.waitForUrl(/exp_list_key_acct\.php$/, function() {
			test.comment('Should be on Key Accounts page now');
			test.assert(this.getCurrentUrl().indexOf("exp_list_key_acct.php") > 0, "Page is exp_list_key_acct.php");
			test.assertTitle("Exspeedite - Key Accounts", "Key Accounts page title is ok");
			php_errors( test );



			//! Back to home page
			test.assertExists('a#logo-home', "home button is found");
			this.evaluate(function() {
				document.querySelector('a#logo-home').click();
			});
		});
	}).then(function() {
		this.waitForUrl(/index\.php$/, function() {
			test.comment('Should be on Home page now');
			test.assert(this.getCurrentUrl().indexOf("index.php") > 0, "Page is index.php");
			test.assertTitle("Exspeedite - Welcome to Exspeedite", "Home page title is ok");



			//! Log out
			myclick( test, casper, 'a#logout' );

		});
	}).then(function() {
		this.waitForSelector('form[name="login"]', function() {
			test.assertTitle("Exspeedite - Sign In", "Login title is the one expected");
		});
	}).run(function() {
		test.done();
	});
});
