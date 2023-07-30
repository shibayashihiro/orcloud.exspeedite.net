//! test7.js - Various Screens Testing
// Run with 
// casperjs test test7.js
// Debug with
// casperjs test --log-level=debug --verbose test7.js

// Get Configuration, see config.json.
var fs = require('fs');
var exp = JSON.parse(fs.read('config.json'));

// Additional test data
var shipment_code;
var dropdown_value;

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
var setup_submenu = 'a#setupmenu';
var fuel_submenu = 'a#fuelmenu';
var rates_submenu = 'a#ratesmenu';
var profiles_menu = 'a#profilesmenu';
var operations_menu = 'a#operationsmenu';
var kpi_menu = 'a#kpimenu';

// Click Admin menu, followed by link (2 tests)
function menu_link( test, casper, menu, url ) {
	var link = 'ul.dropdown-menu li a[href="'+url+'"]';
	casper.then(function() {		
		casper.waitForSelector(menu, function() {
			//! Click menu
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

// Click Admin menu, followed by link (3 tests)
function menu_link2( test, casper, menu, submenu, url ) {
	var link = 'ul.dropdown-menu li a[href="'+url+'"]';
	casper.then(function() {		
		casper.waitForSelector(menu, function() {
			//! Click menu
			myclick( test, casper, menu );
		});
	});
	
	casper.then(function() {		
		casper.waitForSelector(submenu, function() {
			//! Click menu
			myclick( test, casper, submenu );
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

casper.fillAndUnload = function (form_selector, data, unload_selector, callback, timeout) {
    var classname = 'reload-' + (new Date().getTime());
    this.evaluate(function (unload_selector, classname) {
        $(unload_selector).addClass(classname);
    }, unload_selector, classname);
    this.fill(form_selector, data, true);
    this.waitWhileSelector(unload_selector + '.' + classname, callback, timeout);
};

casper.test.begin('Various Screens Testing', 79, function suite(test) {
	casper.start(exp.startUrl).then(function() {
		casper.viewport(1024, 768);		// Good for screen captures
		test.assert(this.getCurrentUrl().indexOf("exp_login.php") > 0, "Page is exp_login.php");
		test.assertTitle("Exspeedite - Sign In", "Login title is the one expected");
		test.assertExists('form[name="login"]', "login form is found");
		this.fill('form[name="login"]', {
			username: exp.userName,
			password: exp.userPw
			}, true);
	}).then(function() {		
		this.waitForSelector('li a#logout', function() {
			test.comment('Should be on home page now');
			test.assert(this.getCurrentUrl().indexOf("index.php") > 0, "Page is index.php");
			test.assertTitle("Exspeedite - Welcome to Exspeedite", "Home page title is ok");
			php_errors( test );

			
			//! Click Admin > Setup > Settings	
			menu_link2( test, casper, admin_menu, setup_submenu, 'exp_listsetting.php' );
		});
	}).then(function() {
		this.waitForUrl(/exp_listsetting\.php$/, function() {
			test.comment('Should be on List Settings page now');
			test.assert(this.getCurrentUrl().indexOf("exp_listsetting.php") > 0, "Page is exp_listsetting.php");
			test.assertTitle("Exspeedite - List Settings", "List Settings page title is ok");
			php_errors( test );

			//! Click Admin > Setup > Status Codes	
			menu_link2( test, casper, admin_menu, setup_submenu, 'exp_liststatus_codes.php' );
		});
	}).then(function() {
		this.waitForUrl(/exp_liststatus_codes\.php$/, function() {
			test.comment('Should be on List Status Codes page now');
			test.assert(this.getCurrentUrl().indexOf("exp_liststatus_codes.php") > 0, "Page is exp_liststatus_codes.php");
			test.assertTitle("Exspeedite - List Status Codes", "List Status Codes page title is ok");
			php_errors( test );

			//! Click Admin > Setup > Status Codes	
			menu_link2( test, casper, admin_menu, setup_submenu, 'exp_listitem_list.php' );
		});
	}).then(function() {
		this.waitForUrl(/exp_listitem_list\.php$/, function() {
			test.comment('Should be on List Items page now');
			test.assert(this.getCurrentUrl().indexOf("exp_listitem_list.php") > 0, "Page is exp_listitem_list.php");
			test.assertTitle("Exspeedite - List Items", "List Items page title is ok");
			php_errors( test );

			//! Click Admin > Fuel Management > Fuel Card FTP Configuration	
			menu_link2( test, casper, admin_menu, fuel_submenu, 'exp_list_card_ftp.php' );
		});
	}).then(function() {
		this.waitForUrl(/exp_list_card_ftp\.php$/, function() {
			test.comment('Should be on Fuel Card FTP Configuration page now');
			test.assert(this.getCurrentUrl().indexOf("exp_list_card_ftp.php") > 0, "Page is exp_list_card_ftp.php");
			test.assertTitle("Exspeedite - Fuel Card FTP Configuration", "Fuel Card FTP Configuration title is ok");
			php_errors( test );

			//! Click Admin > Fuel Management > FSC Schedule	
			menu_link2( test, casper, admin_menu, fuel_submenu, 'exp_view_fsc.php?TYPE=all' );
		});
	}).then(function() {
		this.waitForUrl(/exp_view_fsc\.php/, function() {
			test.comment('Should be on FSC Schedule page now');
			test.assert(this.getCurrentUrl().indexOf("exp_view_fsc.php") > 0, "Page is exp_view_fsc.php");
			test.assertTitle("Exspeedite - FSC Schedule", "FSC Schedule title is ok");
			php_errors( test );

			//! Click Admin > Rates > List Drivers Rate	
			menu_link2( test, casper, admin_menu, rates_submenu, 'exp_listdriverrates.php' );
		});
	}).then(function() {
		this.waitForUrl(/exp_listdriverrates\.php$/, function() {
			test.comment('Should be on List Drivers Rate page now');
			test.assert(this.getCurrentUrl().indexOf("exp_listdriverrates.php") > 0, "Page is exp_listdriverrates.php");
			test.assertTitle("Exspeedite - List Drivers Rate", "List Drivers Rate title is ok");
			php_errors( test );



			//! Back to home page
			test.assertExists('a#logo-home', "home button is found");
			this.evaluate(function() {
				document.querySelector('a#logo-home').click();
			});

		});
	}).then(function() {
		this.waitForUrl(/index\.php$/, function() {
			test.assert(this.getCurrentUrl().indexOf("index.php") > 0, "Page is index.php");
			test.assertTitle("Exspeedite - Welcome to Exspeedite", "Home page title is ok");
			php_errors( test );

			//! Log out
			myclick( test, this, 'a#logout' );		
		});
	}).run(function() {
		test.done();
	});
});

