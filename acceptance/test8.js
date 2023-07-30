//! test8.js - Checking settings Testing
// Run with 
// casperjs test test8.js
// Debug with
// casperjs test --log-level=debug --verbose test8.js

// Get Configuration, see config.json.
var fs = require('fs');
var exp = JSON.parse(fs.read('config.json'));

// Links for checking setting
var edi_enabled = exp.startUrl.replace('exp_login.php', 'exp_listsetting.php?category=api&setting=EDI_ENABLED');
var ifta_enabled = exp.startUrl.replace('exp_login.php', 'exp_listsetting.php?category=api&setting=IFTA_ENABLED');
var cms_enabled = exp.startUrl.replace('exp_login.php', 'exp_listsetting.php?category=option&setting=CMS_ENABLED');
var fleet_enabled = exp.startUrl.replace('exp_login.php', 'exp_listsetting.php?category=option&setting=FLEET_ENABLED');
var multi_company = exp.startUrl.replace('exp_login.php', 'exp_listsetting.php?category=option&setting=MULTI_COMPANY');
var multi_currency = exp.startUrl.replace('exp_login.php', 'exp_listsetting.php?category=option&setting=MULTI_CURRENCY');

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

function filler_tests( test, num ) {
	for (i = 1; i <= num; i++) {
		test.assert(1==1,'fake test '+ i + ' to balance number of tests - ignore');
	}
}

var admin_menu = 'a#adminmenu';
var setup_submenu = 'a#setupmenu';
var fuel_submenu = 'a#fuelmenu';
var rates_submenu = 'a#ratesmenu';
var sales_submenu = 'a#salesmenu';
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

// Click Admin menu, followed by link (2 tests)
function menu_link_missing( test, casper, menu, url ) {
	var link = 'ul.dropdown-menu li a[href="'+url+'"]';
	casper.then(function() {		
		casper.waitForSelector(menu, function() {
			//! Click menu
			myclick( test, casper, menu );
		});
	});
	
	casper.then(function() {
		test.assertDoesntExist(link);
	});
}

// Click Admin menu, followed by link (2 tests)
function menu_submenu_missing( test, casper, menu, submenu ) {
	casper.then(function() {		
		casper.waitForSelector(menu, function() {
			//! Click menu
			myclick( test, casper, menu );
		});
	});
	
	casper.then(function() {
		test.assertDoesntExist(submenu);
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

// Click Admin menu, followed by link (3 tests)
function menu_link2_missing( test, casper, menu, submenu, url ) {
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
		test.assertDoesntExist(link);
	});
}

// Look up value of a setting
function lookup_setting( test, category, setting ) {
	var link = exp.startUrl.replace('exp_login.php', 'exp_listsetting.php?category=' + category + '&setting=' + setting);
	var the_value;
	test.comment('link = ' + link);
	casper.open(link).then(function() {
		test.comment('before waitForSelector');
		this.waitForSelector('#THEVALUE', function() {
			the_value = this.getHTML('#THEVALUE');
			test.comment('value = ' + the_value);
		});
	}).run(function() {
		return the_value;
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

casper.test.begin('Conditional Testing Based On Settings', 87, function suite(test) {
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


			//----------------------------------------- Conditional EDI
			var the_value;
			//test.comment('link = ' + edi_enabled);
			this.open(edi_enabled).then(function() {
				this.waitForSelector('#THEVALUE', function() {
					the_value = this.getHTML('#THEVALUE');
					test.comment('EDI_ENABLED = ' + the_value);
					if( the_value == 'true') {
						//! Click Admin > EDI FTP Configuration	
						menu_link( test, casper, admin_menu, 'exp_listftp.php' );
						this.then(function() {
							this.waitForUrl(/exp_listftp\.php$/, function() {
								test.comment('Should be on EDI FTP Configuration page now');
								test.assert(this.getCurrentUrl().indexOf("exp_listftp.php") > 0, "Page is exp_listftp.php");
								test.assertTitle("Exspeedite - EDI FTP Configuration", "EDI FTP Configuration title is ok");
								php_errors( test );
							});
						});
					} else {
						test.comment('EDI is disabled');
						//! Click Admin > EDI FTP Configuration	
						menu_link_missing( test, casper, admin_menu, 'exp_listftp.php' );
						this.then(function() {
							filler_tests( test, 6 );
						});
					}
				});
			});

			//----------------------------------------- Conditional IFTA
			//test.comment('link = ' + ifta_enabled);
			this.thenOpen(ifta_enabled).then(function() {
				this.waitForSelector('#THEVALUE', function() {
					the_value = this.getHTML('#THEVALUE');
					test.comment('IFTA_ENABLED = ' + the_value);
					if( the_value == 'true') {
						//! Click Admin > Fuel Management > IFTA Log	
						menu_link2( test, casper, admin_menu, fuel_submenu, 'exp_listifta_log.php' );
						this.then(function() {
							this.waitForUrl(/exp_listifta_log\.php$/, function() {
								test.comment('Should be on IFTA Log page now');
								test.assert(this.getCurrentUrl().indexOf("exp_listifta_log.php") > 0, "Page is exp_listifta_log.php");
								test.assertTitle("Exspeedite - IFTA Log", "IFTA Log title is ok");
								php_errors( test );
							});
						});
					} else {
						test.comment('IFTA is disabled');
						//! Click Admin > Fuel Management > IFTA Log	
						menu_link2_missing( test, casper, admin_menu, fuel_submenu, 'exp_listifta_log.php' );
						this.then(function() {
							filler_tests( test, 6 );
						});
					}
				});
			});

			//----------------------------------------- Conditional CMS
			//test.comment('link = ' + cms_enabled);
			this.thenOpen(cms_enabled).then(function() {
				this.waitForSelector('#THEVALUE', function() {
					the_value = this.getHTML('#THEVALUE');
					test.comment('CMS_ENABLED = ' + the_value);
					if( the_value == 'true') {
						//! Click Admin > Sales Management > Sales Activity	
						menu_link2( test, casper, admin_menu, sales_submenu, 'exp_sales_activity.php' );
						this.then(function() {
							this.waitForUrl(/exp_sales_activity\.php$/, function() {
								test.comment('Should be on Sales Activity page now');
								test.assert(this.getCurrentUrl().indexOf("exp_sales_activity.php") > 0, "Page is exp_sales_activity.php");
								test.assertTitle("Exspeedite - Sales Activity", "Sales Activity title is ok");
								php_errors( test );
							});
						});
					} else {
						test.comment('CMS is disabled');
						//! Click Admin > Fuel Management > IFTA Log	
						menu_submenu_missing( test, casper, admin_menu, sales_submenu );
						this.then(function() {
							filler_tests( test, 7 );
						});
					}
				});
			});

			//----------------------------------------- Conditional FLEET
			//test.comment('link = ' + fleet_enabled);
			this.thenOpen(fleet_enabled).then(function() {
				this.waitForSelector('#THEVALUE', function() {
					the_value = this.getHTML('#THEVALUE');
					test.comment('FLEET_ENABLED = ' + the_value);
					if( the_value == 'true') {
						//! Click Profiles > Tractor Fleets	
						menu_link( test, casper, profiles_menu, 'exp_listfleet.php' );
						this.then(function() {
							this.waitForUrl(/exp_listfleet\.php$/, function() {
								test.comment('Should be on Tractor Fleets page now');
								test.assert(this.getCurrentUrl().indexOf("exp_listfleet.php") > 0, "Page is exp_listfleet.php");
								test.assertTitle("Exspeedite - List Tractor Fleets", "List Tractor Fleets title is ok");
								php_errors( test );
							});
						});
					} else {
						test.comment('FLEET is disabled');
						//! Click Profiles > Tractor Fleets	
						menu_link_missing( test, casper, profiles_menu, 'exp_listfleet.php' );
						this.then(function() {
							filler_tests( test, 6 );
						});
					}
				});
			});

			//----------------------------------------- Conditional MULTI_COMPANY
			//test.comment('link = ' + multi_company);
			this.thenOpen(multi_company).then(function() {
				this.waitForSelector('#THEVALUE', function() {
					the_value = this.getHTML('#THEVALUE');
					test.comment('MULTI_COMPANY = ' + the_value);
					if( the_value == 'true') {
						//! Click Admin > Companies	
						menu_link( test, casper, admin_menu, 'exp_listcompany.php' );
						this.then(function() {
							this.waitForUrl(/exp_listcompany\.php$/, function() {
								test.comment('Should be on List Companies page now');
								test.assert(this.getCurrentUrl().indexOf("exp_listcompany.php") > 0, "Page is exp_listcompany.php");
								test.assertTitle("Exspeedite - List Companies", "List Companies title is ok");
								php_errors( test );
								test.assertExists('table#EXP_COMPANY');
								var tl = this.evaluate(function() {
									return $('table#EXP_COMPANY tr').length;
								});
								test.comment('tl = ' + tl);
								casper.test.assert( tl > 1, 'EXP_COMPANY Should be more than one row' );
							});
						});
						this.then(function() {
							menu_link( test, casper, admin_menu, 'exp_listoffice.php' );
							this.then(function() {
								this.waitForUrl(/exp_listoffice\.php$/, function() {
									test.comment('Should be on List Offices page now');
									test.assert(this.getCurrentUrl().indexOf("exp_listoffice.php") > 0, "Page is exp_listoffice.php");
									test.assertTitle("Exspeedite - List Offices", "List Offices title is ok");
									php_errors( test );
									test.assertExists('table#EXP_OFFICE');
									var tl = this.evaluate(function() {
										return $('table#EXP_OFFICE tr').length;
									});
									test.comment('tl = ' + tl);
									casper.test.assert( tl > 1, 'EXP_OFFICE Should be more than one row' );
								});
							});
						});
					} else {
						test.comment('MULTI_COMPANY is disabled');
						//! Click Admin > Companies	
						menu_link_missing( test, casper, admin_menu, 'exp_listcompany.php' );
						menu_link_missing( test, casper, admin_menu, 'exp_listoffice.php' );
						this.then(function() {
							filler_tests( test, 16 );
						});
					}
				});
			});

			//----------------------------------------- Conditional MULTI_CURRENCY
			//test.comment('link = ' + multi_currency);
			this.thenOpen(multi_currency).then(function() {
				this.waitForSelector('#THEVALUE', function() {
					the_value = this.getHTML('#THEVALUE');
					test.comment('MULTI_CURRENCY = ' + the_value);
					if( the_value == 'true') {
						//! Click Admin > Exchange Rates	
						menu_link( test, casper, admin_menu, 'exp_listem.php' );
						this.then(function() {
							this.waitForUrl(/exp_listem\.php$/, function() {
								test.comment('Should be on Exchange Rates page now');
								test.assert(this.getCurrentUrl().indexOf("exp_listem.php") > 0, "Page is exp_listem.php");
								test.assertTitle("Exspeedite - List Exchange Rates", "List Exchange Rates title is ok");
								php_errors( test );
							});
						});
					} else {
						test.comment('MULTI_CURRENCY is disabled');
						//! Click Admin > Exchange Rates	
						menu_link_missing( test, casper, admin_menu, 'exp_listem.php' );
						this.then(function() {
							filler_tests( test, 6 );
						});
					}
				});
			});


			
	}).then(function() {
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

