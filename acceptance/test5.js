//! test5.js - Shipments Testing
// Run with 
// casperjs test test5.js
// Debug with
// casperjs test --log-level=debug --verbose test5.js

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

casper.test.on('fail', function doSomething() {
    casper.echo('fail event fired!');
	casper.capture('shipment_1.png');
});

casper.fillAndUnload = function (form_selector, data, unload_selector, callback, timeout) {
    var classname = 'reload-' + (new Date().getTime());
    this.evaluate(function (unload_selector, classname) {
        $(unload_selector).addClass(classname);
    }, unload_selector, classname);
    this.fill(form_selector, data, true);
    this.waitWhileSelector(unload_selector + '.' + classname, callback, timeout);
};

casper.test.begin('Shipments Testing', 93, function suite(test) {
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

			
			//! Click Operations > Shipments	
			menu_link( test, casper, operations_menu, 'exp_listshipment.php' );
		});
	}).then(function() {
		this.waitForUrl(/exp_listshipment\.php$/, function() {
			test.comment('Should be on List Shipments page now');
			test.assert(this.getCurrentUrl().indexOf("exp_listshipment.php") > 0, "Page is exp_listshipment.php");
			test.assertTitle("Exspeedite - List Shipments", "List Shipments page title is ok");
			php_errors( test );

			//! Add Shipment
			test.comment('Add Shipment: ');
			myclick( test, casper, 'a#EXP_SHIPMENT_add' );
		});
	}).then(function() {
		this.waitForUrl(/exp_addshipment\.php/, function() {
			test.comment('Should be on Add Shipment page now');
			test.assert(this.getCurrentUrl().indexOf("exp_addshipment.php") > 0, "Page is exp_addshipment.php");
			test.assertTitle("Exspeedite - Add Shipment", "Add Shipment page title is ok");
			php_errors( test );
			
			this.wait(1000);
			
			// The static field with the shipment code in it
			test.assertExists('p#SHIPMENT_CODE_STATIC');
			shipment_code = this.getHTML('p#SHIPMENT_CODE_STATIC');
			test.comment('Shipment code: ' + shipment_code);
			


			//! Cancel Shipment
			test.comment('Cancel Shipment: ');
			myclick( test, casper, 'a#Cancel_Shipment' );
		});
	}).then(function() {
		this.wait(1000);
		this.waitForSelector('div.bootbox', function() {
			test.assertSelectorHasText('div.modal-body div.bootbox-body', 'Confirm: Cancel Shipment');
			myclick( test, casper, 'div.modal-footer button.btn-danger' );
		});
	}).then(function() {
		this.waitForUrl(/exp_listshipment\.php$/, function() {
			test.comment('Should be on List Shipments page now');
			test.assert(this.getCurrentUrl().indexOf("exp_listshipment.php") > 0, "Page is exp_listshipment.php");
			test.assertTitle("Exspeedite - List Shipments", "List Shipments page title is ok");
			php_errors( test );

 			//! New code here
 			test.comment('Set filter to entry, which should limit search clashes looking for ' + shipment_code);
 			test.assertExists('select#SHIPMENT_STATUS', "Dropdown filter is found 1");
			this.fillSelectors('form#RESULT_FILTERS_EXP_SHIPMENT', {
				'select#SHIPMENT_STATUS':    'entry'
    			}, false);
		});
	}).then(function() {
 		this.waitForUrl(/exp_listshipment\.php$/, function() {
			test.comment('Should be on List Shipments page now');
			test.assert(this.getCurrentUrl().indexOf("exp_listshipment.php") > 0, "Page is exp_listshipment.php");
			test.assertTitle("Exspeedite - List Shipments", "List Shipments page title is ok");
			php_errors( test );

 			test.assertExists('input[type="search"]', "search form is found");
			this.fillSelectors('form#RESULT_FILTERS_EXP_SHIPMENT', {
				'input[type="search"]':    shipment_code
    			}, false);
		});
	}).then(function() {

		test.comment('after fillSelectors, before waitForResource');

		this.waitForResource(/exp_listshipmentajax.php/, function() {
			test.comment('after waitForResource processing, before wait');
			this.waitForText('No matching records found', function() {
				test.assertTextExists('No matching records found', 'Confirmed that ' + shipment_code + ' does not exist');
			}).then(function() {
	 			test.assertExists('select#SHIPMENT_STATUS', "Dropdown filter is found 2");
	 			dropdown_value = this.getFormValues('form#RESULT_FILTERS_EXP_SHIPMENT').SHIPMENT_STATUS;
				test.comment('Dropdown: ' + dropdown_value);
				this.fillSelectors('form#RESULT_FILTERS_EXP_SHIPMENT', {
					'select#SHIPMENT_STATUS':    'dandb'
	    			}, false);

			});
		});
	}).then(function() {
 		this.waitForUrl(/exp_listshipment\.php$/, function() {
			test.comment('Should be on List Shipments page now');
			test.assert(this.getCurrentUrl().indexOf("exp_listshipment.php") > 0, "Page is exp_listshipment.php");
			test.assertTitle("Exspeedite - List Shipments", "List Shipments page title is ok");
			php_errors( test );

 			test.assertExists('select#SHIPMENT_STATUS', "Dropdown filter is found 3");
			test.assertEquals(this.getFormValues('form#RESULT_FILTERS_EXP_SHIPMENT').SHIPMENT_STATUS, 'dandb', 'Dropdown menu set to Delivered and Billed');
			this.fillSelectors('form#RESULT_FILTERS_EXP_SHIPMENT', {
				'select#SHIPMENT_STATUS':    'assign'
    			}, false);

		});
	}).then(function() {
 		this.waitForUrl(/exp_listshipment\.php$/, function() {
			test.comment('Should be on List Shipments page now');
			test.assert(this.getCurrentUrl().indexOf("exp_listshipment.php") > 0, "Page is exp_listshipment.php");
			test.assertTitle("Exspeedite - List Shipments", "List Shipments page title is ok");
			php_errors( test );

 			test.assertExists('select#SHIPMENT_STATUS', "Dropdown filter is found 4");
			test.assertEquals(this.getFormValues('form#RESULT_FILTERS_EXP_SHIPMENT').SHIPMENT_STATUS, 'assign', 'Dropdown menu set to assign');
			this.fillSelectors('form#RESULT_FILTERS_EXP_SHIPMENT', {
				'select#SHIPMENT_STATUS':    'dropped'
    			}, false);

		});
	}).then(function() {
 		this.waitForUrl(/exp_listshipment\.php$/, function() {
			test.comment('Should be on List Shipments page now');
			test.assert(this.getCurrentUrl().indexOf("exp_listshipment.php") > 0, "Page is exp_listshipment.php");
			test.assertTitle("Exspeedite - List Shipments", "List Shipments page title is ok");
			php_errors( test );

			test.assertExists('select#SHIPMENT_STATUS', "Dropdown filter is found 5");
			test.assertEquals(this.getFormValues('form#RESULT_FILTERS_EXP_SHIPMENT').SHIPMENT_STATUS, 'dropped', 'Dropdown menu set to dropped');
			this.fillSelectors('form#RESULT_FILTERS_EXP_SHIPMENT', {
				'select#SHIPMENT_STATUS':    dropdown_value
    			}, false);
		});
	}).then(function() {
		this.waitForUrl(/exp_listshipment\.php$/, function() {
			test.comment('Should be on List Shipments page now');
			test.assert(this.getCurrentUrl().indexOf("exp_listshipment.php") > 0, "Page is exp_listshipment.php");
			test.assertTitle("Exspeedite - List Shipments", "List Shipments page title is ok");
			php_errors( test );

			test.assertEquals(this.getFormValues('form#RESULT_FILTERS_EXP_SHIPMENT').SHIPMENT_STATUS, dropdown_value, 'Dropdown menu set to ' + dropdown_value);


			//! Click back to go to home sceeen
			myclick( test, casper, 'a#EXP_SHIPMENT_cancel' );
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

