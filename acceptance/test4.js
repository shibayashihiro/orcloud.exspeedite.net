//! test4.js - Clients Testing
// Run with 
// casperjs test test4.js
// Debug with
// casperjs test --log-level=debug --verbose test4.js

// Get Configuration, see config.json.
var fs = require('fs');
var exp = JSON.parse(fs.read('config.json'));

// Additional test data
var testClient = 'testclient' + Math.floor((Math.random() * 100) + 1);
var testContact = 'Bob';
var testAddr = '110 1st Avenue Northwest';
var testCity = 'Isanti';
var testState = 'MN';
var testZip = '55040';
var testCountry = 'USA'
var testCell = '888-999-0000';
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
	//test.assertTextDoesntExist('Warning:', 'No Warnings');
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

casper.test.begin('Clients Testing', 60, function suite(test) {
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

			
			//! Click Profiles > Clients	
			menu_link( test, casper, profiles_menu, 'exp_listclient.php?CLIENT_TYPE=client' );
		});
	}).then(function() {
		this.wait(2000, function() {
			//casper.capture('client_before_waitForUrl' +'.png');
			this.waitForUrl(/exp_listclient\.php/, function() {
				test.comment('1. Should be on List Clients page now');
				test.assert(this.getCurrentUrl().indexOf("exp_listclient.php") > 0, "Page is exp_listclient.php");
				test.assertTitle("Exspeedite - List Clients", "List Clients page title is ok");
				php_errors( test );
	
	
	
	 			test.assertExists('input[type="search"]', "search form is found");
				this.fillSelectors('form#RESULT_FILTERS_EXP_CLIENT', {
					'input[type="search"]':    testClient
	    			}, false);
			});
		});
	}).then(function() {

		test.comment('after fillSelectors, before waitForResource');

		this.waitForResource(/exp_listclientajax.php/, function() {
			test.comment('2. after waitForResource processing, before wait');
			this.wait(4000, function() {
				//casper.capture('client_1_' + testClient +'.png');
				test.assertTextExists('No matching records found', 'Confirmed that ' + testClient + ' does not exist');
			}).then(function() {
				//! Add Client
				test.comment('Add Client: ' + testClient);
				myclick( test, casper, 'a[href="exp_addclient.php"]' );
			});
		});

	}).then(function() {
		this.waitForUrl(/exp_addclient\.php$/, function() {
			test.comment('3. Should be on add client page now');
			test.assert(this.getCurrentUrl().indexOf("exp_addclient.php") > 0, "Page is exp_addclient.php");
			test.assertTitle("Exspeedite - Add Client", "Add Client page title is ok");
			test.assertExists('form[name="addclient"]', "addclient form is found");
			php_errors( test );
			
			//myclick( test, casper, 'a#addclient_cancel' );
			this.fill('form[name="addclient"]', {
				CLIENT_NAME: testClient,
				CLIENT_NOTES: 'Testing via CasperJS',
				SHIPPER: true,
				CONSIGNEE: true
				}, true);
		});
	}).then(function() {
		//test.comment('URL is now ' + this.getCurrentUrl() );
		this.waitForUrl(/exp_editclient\.php/, function() {
			test.comment('4. Should be on Edit Client page now');
			test.assert(this.getCurrentUrl().indexOf("exp_editclient.php") > 0, "Page is exp_editclient.php");
			test.assertTitle("Exspeedite - Edit Client", "Edit Client page title is ok");
			php_errors( test );

			test.assertSelectorHasText('#CLIENT_WARNINGS', 'Shipper selected, you need to add a contact info of type shipper');
			test.assertSelectorHasText('#CLIENT_WARNINGS', 'Consignee selected, you need to add a contact info of type consignee');
			
			
			myclick( test, this, 'a#EXP_CONTACT_INFO_add' );		
		});
	}).then(function() {
		this.waitForUrl(/exp_addcontact_info\.php/, function() {
			test.comment('5. Should be on Add Contact Info page now');
			test.assert(this.getCurrentUrl().indexOf("exp_addcontact_info.php") > 0, "Page is exp_addcontact_info.php");
			test.assertTitle("Exspeedite - Add Contact Info", "Add Contact Info page title is ok");
			php_errors( test );
			this.fill('form[name="add_contact_info"]', {
				CONTACT_TYPE: 'shipper',
				LABEL: testClient + 'S',
				CONTACT_NAME: testContact,
				ADDRESS: testAddr,
				CITY: testCity,
				ZIP_CODE: testZip,
				STATE: testState,
				COUNTRY: testCountry,
				PHONE_CELL: testCell,
				EMAIL: testEmail
				}, true);
		});
	}).then(function() {
		//test.comment('URL is now ' + this.getCurrentUrl() );
		this.waitForUrl(/exp_editclient\.php/, function() {

			test.assertSelectorDoesntHaveText('#CLIENT_WARNINGS', 'Shipper selected, you need to add a contact info of type shipper');
			test.assertSelectorHasText('#CLIENT_WARNINGS', 'Consignee selected, you need to add a contact info of type consignee');

			myclick( test, this, 'a#EXP_CONTACT_INFO_add' );		
		});
	}).then(function() {
		this.waitForUrl(/exp_addcontact_info\.php/, function() {
			test.comment('6. Should be on Add Contact Info page now');
			test.assert(this.getCurrentUrl().indexOf("exp_addcontact_info.php") > 0, "Page is exp_addcontact_info.php");
			test.assertTitle("Exspeedite - Add Contact Info", "Add Contact Info page title is ok");

			this.fill('form[name="add_contact_info"]', {
				CONTACT_TYPE: 'consignee',
				LABEL: testClient + 'C',
				CONTACT_NAME: testContact,
				ADDRESS: testAddr,
				CITY: testCity,
				ZIP_CODE: testZip,
				STATE: testState,
				COUNTRY: testCountry,
				PHONE_CELL: testCell,
				EMAIL: testEmail
				}, true);
		});
	}).then(function() {
		//test.comment('URL is now ' + this.getCurrentUrl() );
		this.waitForUrl(/exp_editclient\.php/, function() {

			test.assertSelectorDoesntHaveText('#CLIENT_WARNINGS', 'Shipper selected, you need to add a contact info of type shipper');
			test.assertSelectorDoesntHaveText('#CLIENT_WARNINGS', 'Consignee selected, you need to add a contact info of type consignee');

			//! Back to list client
			myclick( test, this, 'a#editclient_cancel' );		
		});
	}).then(function() {
		this.waitForUrl(/exp_listclient\.php$/, function() {
			test.comment('7. Should be on list clients page now');
			test.assert(this.getCurrentUrl().indexOf("exp_listclient.php") > 0, "Page is exp_listclient.php");
			test.assertTitle("Exspeedite - List Clients", "List Clients page title is ok");

 			test.assertExists('input[type="search"]', "search form is found");
			this.fillSelectors('form#RESULT_FILTERS_EXP_CLIENT', {
				'input[type="search"]':    testClient
    			}, false);
		});
	}).then(function() {

		test.comment('after fillSelectors, before waitForResource');

		//casper.capture('client_1_' + testClient +'.png');
		this.waitForResource(/exp_listclientajax.php/, function() {
			//casper.capture('client_2_' + testClient +'.png');
			test.comment('after waitForResource processing, before wait');
			this.wait(4000, function() {
				//casper.capture('client_3_' + testClient +'.png');
				test.assertTextDoesntExist('No matching records found', 'Confirmed that ' + testClient + ' does exist');
				test.assertElementCount('tbody tr', 1, "Should be one row in table, showing " + testClient);
			});
		});
	}).then(function() {


			//! Delete Client
			test.comment('Delete Client: ' + testClient);
			myclick( test, casper, 'tbody tr td div button' );
	}).then(function() {
		this.waitForSelector('ul.dropdown-menu li a#'+ testClient + '2', function() {
			myclick( test, casper, 'a#'+ testClient + '2' );
		});
	}).then(function() {
		this.wait(2000);
		this.waitForSelector('div.bootbox', function() {
			test.assertSelectorHasText('div.modal-body div.bootbox-body', 'Delete client ' + testClient + '?');
			myclick( test, casper, 'div.modal-footer button.btn-danger' );
		});
	}).then(function() {
		this.waitForUrl(/exp_listclient\.php$/, function() {
			test.comment('8. Should be on List Clients page now');
			test.assert(this.getCurrentUrl().indexOf("exp_listclient.php") > 0, "Page is exp_listclient.php");
			test.assertTitle("Exspeedite - List Clients", "List Clients page title is ok");


 			test.assertExists('input[type="search"]', "search form is found");
			this.fillSelectors('form#RESULT_FILTERS_EXP_CLIENT', {
				'input[type="search"]':    testClient
    			}, false);
		});
	}).then(function() {

		test.comment('after fillSelectors, before waitForResource');

		this.waitForResource(/exp_listclientajax.php/, function() {
			test.comment('after waitForResource processing, before wait');
			this.wait(4000, function() {
				test.assertTextExists('No matching records found', 'Confirmed that ' + testClient + ' does not exist');
			}).then(function() {
				//! Log out
				myclick( test, this, 'a#logout' );		
			});
		});
	}).run(function() {
		test.done();
	});
});
