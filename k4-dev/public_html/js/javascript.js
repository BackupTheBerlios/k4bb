/**********************************************************************************
 *     File Arts
 *     Copyright (c) 2005, Peter Goodman, BestWebEver.com
 *********************************************************************************/

// Check for Browser & Platform for PC & IE specific bits
// More details from: http://www.mozilla.org/docs/web-developer/sniffer/browser_type.html
var clientPC = navigator.userAgent.toLowerCase(); // Get client info
var clientVer = parseInt(navigator.appVersion); // Get browser version

var is_ie = ((clientPC.indexOf("msie") != -1) && (clientPC.indexOf("opera") == -1));
var is_nav = ((clientPC.indexOf('mozilla')!=-1) && (clientPC.indexOf('spoofer')==-1)
                && (clientPC.indexOf('compatible') == -1) && (clientPC.indexOf('opera')==-1)
                && (clientPC.indexOf('webtv')==-1) && (clientPC.indexOf('hotjava')==-1));


/* Show or Hide an html element */
function ShowHide(Id) {
	var id = document.getElementById(Id);
	if(id.style.display == 'none') {
		id.style.display = 'block';
	} else {
		id.style.display = 'none';
	}
}

/* Set the index on a select form field */
function setIndex(element, array) {
	var temp = document.getElementById(array);
	temp.selectedIndex = getSelectedIndex(element, temp);
}

/* Get the positiong of an element in an array */
function getSelectedIndex(element, array) {
	var pos = '';
	for(var i=0;i<array.length;i++) {
		if(array[i].value == element) {
			pos = i;
		}
	}
	return pos;
}

/* Popup the a file, in this case, the files.php (set elsewhere) */
function popup_upload(file) {
	day = new Date();
	id = day.getTime();
	eval("page" + id + " = window.open('" + file + "', '" + id + "', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=300,height=100,left = 462,top = 334');");
}

function popup_file(file, width, height) {
	day = new Date();
	id = day.getTime();
	eval("page" + id + " = window.open('" + file + "', '" + id + "', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=" + width + ",height=" + height + ",left = 462,top = 334');");
}

/* Set a cookie */

function set_cookie(name, value, expires, path, domain, secure)
{
    document.cookie= name + "=" + escape(value) +
        ((expires) ? "; expires=" + expires.toGMTString() : "") +
        ((path) ? "; path=" + path : "") +
        ((domain) ? "; domain=" + domain : "") +
        ((secure) ? "; secure" : "");
}

/* Fetch a cookie */
// Get the value of a cookie based on its name, this get_cookie function is from Phrogz.net, same guy who made the AttachEvent function, thatnks man!
function fetch_cookie(cookieName){
	var cookies=document.cookie+"";
	if (!cookies) return null;
	cookies=cookies.split(/; */);
	for (var i=0,len=cookies.length;i<len;i++){
		var keyVal = cookies[i].split("=");
		if (unescape(keyVal[0])==cookieName) return unescape(keyVal[1]);
	}
	return null;
}
/* Delete a cookie */
function delete_cookie(name)
{
	var expireNow = new Date();
	document.cookie = name + "=" + "; expires=Thu, 01-Jan-70 00:00:01 GMT" +  "; path=/";
}

/* Checks if 'needle' is in the array 'haystack' */
function in_array(needle, haystack) {
	var bool = false;
	for (var i=0; i<haystack.length; i++) {
		if (haystack[i] == needle) {
			bool = true;
		}
	}
	return bool;
}
function array_push(thearray, value) {
	thearray[ getarraysize(thearray) ] = value;
}
function QuickJump(Id) {
	if(Id.options[Id.selectedIndex].value != -1)
		document.location = '#' + Id.options[Id.selectedIndex].value;
	else
		return false;
}

//*** The following is copyright 2003 by Gavin Kistner, gavin@refinery.com
//*** It is covered under the license viewable at http://phrogz.net/JS/_ReuseLicense.txt
//*** Reuse or modification is free provided you abide by the terms of that license.
//*** (Including the first two lines above in your source code satisfies the conditions.)


//***Cross browser attach event function. For 'evt' pass a string value with the leading "on" omitted
//***e.g. AttachEvent(window,'load',MyFunctionNameWithoutParenthesis,false);

function AttachEvent(obj,evt,fnc,useCapture){
	if (!useCapture) useCapture=false;
	if (obj.addEventListener){
		obj.addEventListener(evt,fnc,useCapture);
		return true;
	} else if (obj.attachEvent) return obj.attachEvent("on"+evt,fnc);
	else{
		MyAttachEvent(obj,evt,fnc);
		obj['on'+evt]=function(){ MyFireEvent(obj,evt) };
	}
} 

//The following are for browsers like NS4 or IE5Mac which don't support either
//attachEvent or addEventListener
function MyAttachEvent(obj,evt,fnc){
	if (!obj.myEvents) obj.myEvents={};
	if (!obj.myEvents[evt]) obj.myEvents[evt]=[];
	var evts = obj.myEvents[evt];
	evts[evts.length]=fnc;
}
function MyFireEvent(obj,evt){
	if (!obj || !obj.myEvents || !obj.myEvents[evt]) return;
	var evts = obj.myEvents[evt];
	for (var i=0,len=evts.length;i<len;i++) evts[i]();
}
/* End of the code that Gavin Kistner made */
