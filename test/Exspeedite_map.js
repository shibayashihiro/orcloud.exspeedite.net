var myMap = new UIMap();

myMap.addPageset({
    name: 'allPages'
    , description: 'all pages'
    , pathRegexp: '.*'
});

myMap.addPageset({
    name: 'login_page'
    , description: 'all alistapart.com pages'
    , pathRegexp: '.*/exp_login.php'
});

myMap.addElement('allPages', {
    name: 'masthead'
    , description: 'top level image link to site homepage'
    , locator: "xpath=//*[@id='masthead']/a/img"
    , testcase1: {
        xhtml: '<h1 id="masthead"><a><img expected-result="1" /></a></h1>'
    }
});

//******************************************************************************

var myRollupManager = new RollupManager();

