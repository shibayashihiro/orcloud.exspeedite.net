// $Id$
//! test0.js - Installer Testing
// This checks the PHP version and that the DB is setup correctly
// Run with 
// casperjs test test0.js
// Debug with
// casperjs test --log-level=debug --verbose test0.js

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
var profiles_menu = 'a#profilesmenu';
var operations_menu = 'a#operationsmenu';
var kpi_menu = 'a#kpimenu';

// Click Admin menu, followed by link (2 tests)
function menu_link( test, casper, menu, url ) {
	var link = 'ul.dropdown-menu li a[href="'+url+'"]';
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

casper.test.begin('Installer', 40, function suite(test) {
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
			
			var installer = exp.startUrl.replace('exp_login.php', "exp_install.php?code=875406");
			casper.open(installer);
		});
	}).then(function() {
		this.waitForUrl(/exp_install\.php\?code=875406$/, function() {
			test.comment('Should be on Installer page now');
			test.assert(this.getCurrentUrl().indexOf("exp_install.php") > 0, "Page is exp_install.php");
			test.assertTitle("Exspeedite - Install Exspeedite", "Install page title is ok");
			php_errors( test );
			test.assertSelectorHasText('h2', 'Exspeedite Settings', "Exspeedite Settings");

			// Check PHP Info
			myclick( test, casper, 'a#PHP_INFO' );
		});
	}).then(function() {
		this.waitForUrl(/exp_install\.php\?code=875406&info$/, function() {
			test.comment('Should be on PHP Info page now');
			test.assert(this.getCurrentUrl().indexOf("exp_install.php") > 0, "Page is exp_install.php");
			php_errors( test );
			test.assertSelectorHasText('h1', 'PHP Version', "PHP Version");
			version = this.getHTML('h1');
			test.comment(version);
			test.assertSelectorHasText('h2 a[name="module_ftp"]', 'ftp', "ftp module present");
			test.assertSelectorHasText('h2 a[name="module_json"]', 'json', "json module present");
			test.assertSelectorHasText('h2 a[name="module_libxml"]', 'libxml', "libxml module present");
			test.assertSelectorHasText('h2 a[name="module_mysqli"]', 'mysqli', "mysqli module present");
			test.assertSelectorHasText('h2 a', 'SimpleXML', "SimpleXML module present");
			
			casper.back();
		});
	}).then(function() {
		this.waitForUrl(/exp_install\.php\?code=875406$/, function() {
			test.comment('Should be on Installer page now');
			test.assert(this.getCurrentUrl().indexOf("exp_install.php") > 0, "Page is exp_install.php");
			test.assertTitle("Exspeedite - Install Exspeedite", "Install page title is ok");
			php_errors( test );

			// Check DB
			myclick( test, casper, 'a#CHECK_DB' );
		});
	}).then(function() {
		this.wait(1000);
		this.waitForSelector('div#db_check_modal[aria-hidden="false"]', function() {
			test.assertSelectorHasText('div.modal-body h3', 'Successful DB Connection...');
			test.assertSelectorHasText('div.modal-body h3', 'DB OK');
			test.assertSelectorHasText('div.modal-body h3', 'Found root@localhost');
			test.assertSelectorHasText('div.modal-body h3', 'Priviliges OK');
			
			//! Close modal
			myclick( test, casper, 'div.modal-body h3 button.btn-default' );

			//! Log out
			var logout = exp.startUrl.replace('exp_login.php', "exp_logout.php");
			casper.open(logout);
		});
	}).run(function() {
		test.done();
	});
});

