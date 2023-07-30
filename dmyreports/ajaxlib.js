// JavaScript Document
// Configuration Data

var progressIMGURL = '<img src="images/anim.gif">';

//---------------------------------------------------------------------------

function createREQ() {
try {
     req = new XMLHttpRequest();
     } catch(err1) {
       try {
       req = new ActiveXObject('Msxml2.XMLHTTP');
       } catch (err2) {
         try {
         req = new ActiveXObject("Microsoft.XMLHTTP");
         } catch (err3) {
          req = false;
         }
       }
     }
     return req;
}

function requestGET(url, query, req) {
myRand=parseInt(Math.random()*99999999);
req.open("GET",url+'?'+query+'&rand='+myRand,true);
req.send(null);
}

function requestPOST(url, query, req) {
req.open("POST", url,true);
req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
req.send(query);
}

function doCallback(callback,item) {
eval(callback + '(item)');
}

function doAjax(url,query,callback,reqtype,getxml,imgElement) {
// create the XMLHTTPRequest object instance
var myreq = createREQ();

myreq.onreadystatechange = function() {
  if(myreq.readyState == 4) {
   if(myreq.status == 200) {
      var item = myreq.responseText;
      if(getxml==1) {
         item = myreq.responseXML;
      }
      doCallback(callback, item);
  	  try {
		document.getElementById(imgElement).innerHTML = '';
	  } catch (err3) {
	  }
    }
  } else {
	try {
 		document.getElementById(imgElement).innerHTML = progressIMGURL;
	} catch (err3) {
	}
  }
}

if(reqtype=='post') {
requestPOST(url,query,myreq);
} else {
requestGET(url,query,myreq);
}
}

