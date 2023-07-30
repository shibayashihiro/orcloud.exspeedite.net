//! test9.js - Add & remove Driver test data
// Run with 
// casperjs test test9.js
// Debug with
// casperjs test --log-level=debug --verbose test9.js

// Get Configuration, see config.json.
var fs = require('fs');
var exp = JSON.parse(fs.read('config.json'));

// Links for adding driver
var add_driver = exp.startUrl.replace('exp_login.php', 'exp_create_td.php?PW=Geocaching&TYPE=driver');
var edit_driver_page = exp.startUrl.replace('exp_login.php', 'exp_editdriver.php?CODE=');
var home_page = exp.startUrl.replace('exp_login.php', 'index.php');
var multi_company = exp.startUrl.replace('exp_login.php', 'exp_listsetting.php?category=option&setting=MULTI_COMPANY');

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
    casper.capture('add_driver.png');
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

var has_multi_company;

casper.test.begin('Add a Driver', 75, function suite(test) {
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

			//----------------------------------------- Create Driver
			var the_value;
			var the_result;
			var first_name = 'NONAME';
			test.comment('1. link = ' + add_driver);
			this.open(add_driver).then(function() {
				//casper.capture('add_driver1.png');
				this.waitForSelector('#RESULT', function() {
					the_value = this.getHTML('#RESULT');
					test.comment('DRIVER_CODE = ' + the_value);
					test.assert(the_value > 0, 'Add of driver ' + the_value + ' was successful');
				});

			}).then(function() {
				//----------------------------------------- Edit Driver
				test.comment('2. link = ' + edit_driver_page + the_value);
				this.open(edit_driver_page + the_value).then(function() {
					test.comment('Should be on Edit Drivers page now');
					test.assert(this.getCurrentUrl().indexOf("exp_editdriver.php") > 0, "Page is exp_editdriver.php");
					test.assertTitle("Exspeedite - Edit Driver", "Edit Drivers page title is ok");
					php_errors( test );
					test.assertDoesntExist('#NOTFOUND');
					test.assertExists('#DRIVER_CODE');
					var edit_value = this.evaluate(function() {
						return $('#DRIVER_CODE').val();
					});

					test.comment('DRIVER_CODE = ' + edit_value);
					test.assert(the_value == edit_value, 'Edit driver ' + the_value + ' was successful');
					first_name = this.evaluate(function() {
						return $('#FIRST_NAME').val();
					});
					test.comment('FIRST_NAME = ' + first_name);
				});
				
			}).then(function() {
				//----------------------------------------- Conditional MULTI_COMPANY
				test.comment('2a link = ' + multi_company);
				this.thenOpen(multi_company).then(function() {
					this.waitForSelector('#THEVALUE', function() {
						has_multi_company = this.getHTML('#THEVALUE');
						test.comment('MULTI_COMPANY = ' + has_multi_company);
					});
				});
			}).then(function() {
				//----------------------------------------- List Driver
				this.open(home_page).then(function() {
					this.waitForSelector('li a#logout', function() {
						test.comment('3. Should be back on home page now');
						test.assert(this.getCurrentUrl().indexOf("index.php") > 0, "Page is index.php");
						test.assertTitle("Exspeedite - Welcome to Exspeedite", "Home page title is ok");
						php_errors( test );

						//! Click Profiles > Drivers	
						menu_link( test, casper, profiles_menu, 'exp_listdriver.php' );

					});
				});
			}).then(function() {
				this.waitForUrl(/exp_listdriver\.php$/, function() {
					test.comment('4. Should be on List Drivers page now');
					test.comment('MULTI_COMPANY = ' + has_multi_company);
					test.assert(this.getCurrentUrl().indexOf("exp_listdriver.php") > 0, "Page is exp_listdriver.php");
					test.assertTitle("Exspeedite - List Drivers", "List Drivers page title is ok");
					php_errors( test );

					if( has_multi_company == 'true') {
						test.comment('4a. Set office to All');
						casper.then(function(){
						    this.evaluate(function() {
						        var form = document.querySelector('#DRIVER_OFFICE');
						        form.selectedIndex = 'all';
						        $(form).change();
						    });
						}).then(function() {
							this.wait(2000, function() {
								this.waitForUrl(/exp_listdriver\.php$/, function() {
									test.comment('4b. Should be on List Drivers page now');
    
    //casper.capture('list_driver_all.png');

									test.assertExists('input[type="search"]', "search form is found");
									this.fillSelectors('form#RESULT_FILTERS_EXP_DRIVER', {
										'input[type="search"]':    first_name
						    			}, false);
						    	});
						    });
						});
					} else {
						test.assertExists('input[type="search"]', "search form is found");
						this.fillSelectors('form#RESULT_FILTERS_EXP_DRIVER', {
							'input[type="search"]':    first_name
			    			}, false);
					}
					
				});
				
			}).then(function() {
				this.waitForResource(/exp_listdriverajax.php/, function() {
					this.wait(4000, function() {
    //casper.capture('list_driver_all2.png');
						test.assertTextDoesntExist('No matching records found', 'Confirmed that driver ' + first_name + ' does exist');
			    		test.assertElementCount('tbody tr', 1, 'Should find 1 driver');
			    	});
				});
				
				
			}).then(function() {
				//----------------------------------------- Delete Driver
				var del_driver = add_driver + '&DEL=' + the_value;
				test.comment('5. link = ' + del_driver);
				this.open(del_driver).then(function() {
					this.waitForSelector('#RESULT', function() {
						the_result = this.getHTML('#RESULT');
						test.comment('RESULT = ' + the_result);
						test.assert(the_result == 'true', 'Delete of driver ' + the_value + ' was successful');
					});
				});

			}).then(function() {
				//----------------------------------------- Edit Driver
				test.comment('Make sure driver ' + the_value + ' is deleted.');
				test.comment('6. link = ' + edit_driver_page + the_value);
				this.open(edit_driver_page + the_value).then(function() {
					test.comment('Should be on Edit Drivers page now');
					test.assert(this.getCurrentUrl().indexOf("exp_editdriver.php") > 0, "Page is exp_editdriver.php");
					test.assertTitle("Exspeedite - Edit Driver", "Edit Drivers page title is ok");
					php_errors( test );
					test.assertExists('#NOTFOUND');

					//! Click Profiles > Drivers	
					menu_link( test, casper, profiles_menu, 'exp_listdriver.php' );

				});
			}).then(function() {
				this.waitForUrl(/exp_listdriver\.php$/, function() {
					test.comment('7. Should be on List Drivers page now');
					test.assert(this.getCurrentUrl().indexOf("exp_listdriver.php") > 0, "Page is exp_listdriver.php");
					test.assertTitle("Exspeedite - List Drivers", "List Drivers page title is ok");
					php_errors( test );
					
					test.assertExists('input[type="search"]', "search form is found");
					this.fillSelectors('form#RESULT_FILTERS_EXP_DRIVER', {
						'input[type="search"]':    first_name
		    			}, false);
				});
				
			}).then(function() {
				this.waitForResource(/exp_listdriverajax.php/, function() {
					this.wait(2000, function() {
						test.assertTextExists('No matching records found', 'Confirmed that driver ' + first_name + ' does NOT exist');
			    	});
				});
				
			//----------------------------------------- Back to home page
			}).then(function() {
				this.open(home_page).then(function() {
					this.waitForSelector('li a#logout', function() {
						test.comment('8. Should be back on home page now');
						test.assert(this.getCurrentUrl().indexOf("index.php") > 0, "Page is index.php");
						test.assertTitle("Exspeedite - Welcome to Exspeedite", "Home page title is ok");
						php_errors( test );
					});
				});
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

