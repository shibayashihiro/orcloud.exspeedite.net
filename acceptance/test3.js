//! test3.js - User Admin Testing
// Run with
// casperjs test test3.js
// Debug with
// casperjs test --log-level=debug --verbose test3.js

// Get Configuration, see config.json.
var fs = require("fs");
var exp = JSON.parse(fs.read("config.json"));

// Additional test data
var testUser = 'testuser' + Math.floor((Math.random() * 1000) + 1);
var testPw = 'testing1234';
var testFullname = 'Test User';
var testEmail = 'testuser@strongtco.com';

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
	test.assertTextDoesntExist('Deprecated:', 'Deprecated');
}

// http://docs.casperjs.org/en/latest/events-filters.html#remote-message
casper.on("remote.message", function(msg) {
    this.echo("Console: " + msg);
});

// http://docs.casperjs.org/en/latest/events-filters.html#page-error
casper.on("page.error", function(msg, trace) {
    this.echo("Page Error: " + msg);
    casper.capture('deluser_' + testUser +'.png');
    // maybe make it a little fancier with the code from the PhantomJS equivalent
});

casper.on("waitFor.timeout", function(timeout, details) {
    this.echo("waitFor.timeout: " + timeout);
    casper.capture('waitFor-timeout.png');
    //this.echo(casper.fetchText("body"));
    //casper.capture('deluser_' + testUser +'.png');
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

casper.test.begin('User Admin Testing', 81, function suite(test) {
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

			
 			test.assertExists('input[type="search"]', "search form is found");
			this.fillSelectors('form#RESULT_FILTERS_EXP_USER', {
				'input[type="search"]':    testUser
    			}, false);
		});
	}).then(function() {

		test.comment('after fillSelectors, before waitForResource 1');
		//casper.capture('check1_' + testUser +'.png');
		this.waitForResource(/exp_listuserajax.php/, function() {
			test.comment('after waitForResource processing, before wait');
			this.wait(500, function() {
				//casper.capture('check2_' + testUser +'.png');
				test.assertTextExists('No matching records found', 'Confirmed that ' + testUser + ' does not exist');
			}).then(function() {
				//! Add User
				test.comment('Add User: ' + testUser);
				myclick( test, casper, 'a[href="exp_adduser.php"]' );
			});
		});

	}).then(function() {
		this.waitForUrl(/exp_adduser\.php$/, function() {
			test.comment('Should be on add user page now');
			test.assert(this.getCurrentUrl().indexOf("exp_adduser.php") > 0, "Page is exp_adduser.php");
			test.assertTitle("Exspeedite - Add User", "Add User page title is ok");
			test.assertExists('form[name="adduser"]', "adduser form is found");
			php_errors( test );

				
			if (this.exists('#OFFICE_1')) {
				test.comment('Multi-company is enabled - will check first office');
				this.fill('form[name="adduser"]', {
					OFFICE_1: true,
				}, false);
			}

			this.fill('form[name="adduser"]', {
				USERNAME: testUser,
				USER_PASSWORD: testPw,
				FULLNAME:testFullname,
				EMAIL: testEmail,
				USER_GROUPS_user: true,
				USER_GROUPS_dispatch: true,
				ISACTIVE: 'Active'
				}, true);
		});
	}).then(function() {
		this.waitForUrl(/exp_listuser\.php$/, function() {
			test.comment('Should be on list users page now');
			test.assert(this.getCurrentUrl().indexOf("exp_listuser.php") > 0, "Page is exp_listuser.php");
			test.assertTitle("Exspeedite - List Users", "List Users page title is ok");

			//! Logout exp.userName
			test.comment('Logout User: ' + exp.userName);
			myclick( test, casper, 'a#logout' );
		});
	}).then(function() {
		this.waitForSelector('form[name="login"]', function() {
			test.assertTitle("Exspeedite - Sign In", "Login title is the one expected");

			//! Login testUser
			test.comment('Login User: ' + testUser);
			test.assert(this.getCurrentUrl().indexOf("exp_login.php") > 0, "Page is exp_login.php");
			test.assertTitle("Exspeedite - Sign In", "Login title is the one expected");
			test.assertExists('form[name="login"]', "login form is found");
			this.fill('form[name="login"]', {
				username: testUser,
				password: testPw
				}, true);
		});
	}).then(function() {		
		casper.waitForSelector('li a#logout', function() {
			test.comment('Should be on home page now');
			test.assert(this.getCurrentUrl().indexOf("index.php") > 0, "Page is index.php");
			test.assertTitle("Exspeedite - Welcome to Exspeedite", "Home page title is ok");


			//! Logout testUser
			test.comment('Logout User: ' + testUser);
			myclick( test, casper, 'a#logout' );
		});
	}).then(function() {
		this.waitForSelector('form[name="login"]', function() {
			test.assertTitle("Exspeedite - Sign In", "Login title is the one expected");

			//! Login exp.userName
			test.comment('Login User: ' + exp.userName);
			test.assert(this.getCurrentUrl().indexOf("exp_login.php") > 0, "Page is exp_login.php");
			test.assertTitle("Exspeedite - Sign In", "Login title is the one expected");
			test.assertExists('form[name="login"]', "login form is found");
			this.fill('form[name="login"]', {
				username: exp.userName,
				password: exp.userPw
				}, true);
		});
	}).then(function() {		
		casper.waitForSelector('li a#logout', function() {
			test.comment('Should be on home page now');
			test.assert(this.getCurrentUrl().indexOf("index.php") > 0, "Page is index.php");
			test.assertTitle("Exspeedite - Welcome to Exspeedite", "Home page title is ok");

			//! Click Admin > Users
			menu_link( test, casper, admin_menu, 'exp_listuser.php' );
		});
	}).then(function() {
		this.waitForUrl(/exp_listuser\.php$/, function() {
			test.comment('Should be on list users page now');
			test.assert(this.getCurrentUrl().indexOf("exp_listuser.php") > 0, "Page is exp_listuser.php");
			test.assertTitle("Exspeedite - List Users", "List Users page title is ok");
			php_errors( test );


			//! Edit user
			test.comment('Edit User: ' + testUser);
			test.assertExists('input[type="search"]', "search form is found");
			this.fillSelectors('form#RESULT_FILTERS_EXP_USER', {
				'input[type="search"]':    testUser
    			}, false);
		});
	}).then(function() {

		test.comment('after fillSelectors, before waitForResource 2');

		this.waitForResource(/exp_listuserajax.php/, function() {
			test.comment('after waitForResource processing, before wait');
			this.wait(500, function() {
				test.assertElementCount('tbody tr', 1, "Should be one row in table, showing " + testUser);
				myclick( test, casper, 'tbody tr td div button' );
			});
		});
	}).then(function() {
		this.waitForSelector('ul.dropdown-menu li a#'+ testUser + '1', function() {
			myclick( test, casper, 'a#'+ testUser + '1' );
		});
	}).then(function() {
		this.waitForUrl(/exp_edituser\.php/, function() {
			test.comment('Should be on Edit User page now');
			test.assert(this.getCurrentUrl().indexOf("exp_edituser.php") > 0, "Page is exp_edituser.php");
			test.assertTitle("Exspeedite - Edit User", "Edit User page title is ok");
			php_errors( test );

			test.assertExists('form[name="edituser"]', "edituser form is found");
			this.fill('form[name="edituser"]', {
				USER_GROUPS_sales: true,
				USER_GROUPS_dispatch: false
				}, true);
		});
	}).then(function() {
		this.waitForUrl(/exp_listuser\.php$/, function() {
			test.comment('Should be on list users page now');
			test.assert(this.getCurrentUrl().indexOf("exp_listuser.php") > 0, "Page is exp_listuser.php");
			test.assertTitle("Exspeedite - List Users", "List Users page title is ok");




			//! Delete user
			test.comment('Delete User: ' + testUser);
			test.assertExists('input[type="search"]', "search form is found");
			this.fillSelectors('form#RESULT_FILTERS_EXP_USER', {
				'input[type="search"]':    testUser
    			}, false);
		});
	}).then(function() {

		test.comment('after fillSelectors, before waitForResource 3');

		this.waitForResource(/exp_listuserajax.php/, function() {
			test.comment('after waitForResource processing, before wait');
			this.wait(500, function() {

				test.assertElementCount('tbody tr', 1, "Should be one row in table, showing " + testUser);
				myclick( test, casper, 'tbody tr td div button' );
			});
		});
	}).then(function() {
		this.waitForSelector('ul.dropdown-menu li a#'+ testUser + '2', function() {
			myclick( test, casper, 'a#'+ testUser + '2' );
		});
	}).then(function() {
		this.wait(1000);
		test.comment('after wait, before waitForSelector div.bootbox');
		//casper.capture('deluser_' + testUser +'.png');
		this.waitForSelector('div.bootbox', function() {
			//casper.capture('deluser_' + testUser +'.png');
			test.assertSelectorHasText('div.modal-body div.bootbox-body', 'Delete user ' + testUser + '?');
			myclick( test, casper, 'div.modal-footer button.btn-danger' );
		});
	}).then(function() {
		this.waitForUrl(/exp_listuser\.php$/, function() {
			test.comment('Should be on list users page now');
			test.assert(this.getCurrentUrl().indexOf("exp_listuser.php") > 0, "Page is exp_listuser.php");
			test.assertTitle("Exspeedite - List Users", "List Users page title is ok");

    		//casper.capture('deluser_' + testUser +'.png');
	
 			test.assertExists('input[type="search"]', "search form is found");
			this.fillSelectors('form#RESULT_FILTERS_EXP_USER', {
				'input[type="search"]':    testUser
    			}, false);
		});
	}).then(function() {

		test.comment('after fillSelectors, before waitForResource 4');

		this.waitForResource(/exp_listuserajax.php/, function() {
			test.comment('after waitForResource processing, before wait');
			this.wait(500, function() {
				test.assertTextExists('No matching records found', 'Confirmed that ' + testUser + ' has been removed');
   			
    			
				//! Log out
				myclick( test, casper, 'a#logout' );
			});
		});
	}).then(function() {
		this.waitForSelector('form[name="login"]', function() {
			test.assertTitle("Exspeedite - Sign In", "Login title is the one expected");
		});
	}).run(function() {
		test.done();
	});
});

