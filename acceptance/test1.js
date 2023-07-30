//! test1.js - Initial login & logout testing
// Run with 
// casperjs test test1.js
// Debug with
// casperjs test --log-level=debug --verbose test1.js

// Get Configuration, see config.json.
var fs = require('fs');
var exp = JSON.parse(fs.read('config.json'));

// Additional test data
var badPw = 'BADPW';

// http://docs.casperjs.org/en/latest/events-filters.html#resource-error
casper.on("resource.error", function(resourceError) {
	if( resourceError.errorCode != 5 && resourceError.errorCode != 6 )
	    this.echo("ResourceError: " + JSON.stringify(resourceError, undefined, 4));
});

casper.test.begin('Initial login & logout testing', 15, function suite(test) {
	casper.start(exp.startUrl).then(function() {
		test.assert(this.getCurrentUrl().indexOf("exp_login.php") > 0, "Page is exp_login.php");
		test.assertTitle("Exspeedite - Sign In", "Login title is the one expected");
		test.assertExists('form[name="login"]', "login form is found");
		test.assertSelectorHasText('h3.form-signin-heading', 'Please sign in', "Please sign in");

		//! Try bad PW	
		test.comment('Try bad PW');
		this.fill('form[name="login"]', {
			username: exp.userName,
			password: badPw
			}, true);
	}).then(function() {
		this.waitForUrl(/exp_login\.php$/, function() {
			this.wait(1000);
			test.assert(this.getCurrentUrl().indexOf("exp_login.php") > 0, "Page is exp_login.php");
			test.assertTitle("Exspeedite - Sign In", "Login title is the one expected");
			test.assertExists('form[name="login"]', "login form is found");
			test.assertSelectorHasText('h3.form-signin-heading', 'Please Try again', "Please Try again");
			casper.open(exp.startUrl);
		});
	}).then(function() {
		this.wait(1000);
		test.assert(this.getCurrentUrl().indexOf("exp_login.php") > 0, "Page is exp_login.php");
		test.assertTitle("Exspeedite - Sign In", "Login title is the one expected");
		test.assertExists('form[name="login"]', "login form is found");


		//! Try valid PW	
		test.comment('Try valid PW');
		this.fill('form[name="login"]', {
			username: exp.userName,
			password: exp.userPw
			}, true);
	}).then(function() {		
		casper.waitForSelector('li a#logout', function() {
			this.wait(1000);
			test.assert(this.getCurrentUrl().indexOf("index.php") > 0, "Page is index.php");
			test.assertTitle("Exspeedite - Welcome to Exspeedite", "Home page title is ok");
			test.assertExists('a#logout', "logout button is found");


			//! Logout	
			this.evaluate(function() {
				document.querySelector('a#logout').click();
			});
			//casper.click('a#logout');
		});
	}).then(function() {
		this.waitForSelector('form[name="login"]', function() {
			test.assertTitle("Exspeedite - Sign In", "Login title is the one expected");
		});
	}).run(function() {
		test.done();
	});
});

