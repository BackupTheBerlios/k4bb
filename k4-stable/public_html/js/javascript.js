/**********************************************************************************
 *     k4 Bulletin Board
 *     Copyright (c) 2004, Peter Goodman

 *     Permission is hereby granted, free of charge, to any person obtaining 
 *     a copy of this software and associated documentation files (the 
 *     "Software"), to deal in the Software without restriction, including 
 *     without limitation the rights to use, copy, modify, merge, publish, 
 *     distribute, sublicense, and/or sell copies of the Software, and to 
 *     permit persons to whom the Software is furnished to do so, subject to 
 *     the following conditions:

 *     The above copyright notice and this permission notice shall be 
 *     included in all copies or substantial portions of the Software.

 *     THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, 
 *     EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF 
 *     MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND 
 *     NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS 
 *     BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN 
 *     ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN 
 *     CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE 
 *     SOFTWARE.
 *********************************************************************************/

/* Startlist function from A List Apart */
function startList() { 
    if (document.all && document.getElementById) { 
        navMenus = 100; 
        for (x=0; x<navMenus; x++) { 
            navRoot = document.getElementById("nav"+x); 
			if(navRoot) {
				for (i=0; i<navRoot.childNodes.length; i++) { 
					node = navRoot.childNodes[i]; 
					if (node.nodeName=="LI") { 
						node.onmouseover=function() { 
							//alert('hi');
							this.className+=" over"; 
							//this.style.display = 'block';
						} 
						node.onmouseout=function() { 
							//alert('hey');
							this.className=this.className.replace(" over", "");
							//this.style.display = 'none';
						} 
					} 
				}
			}
        }		
    } 
}
AttachEvent(window,'load',startList,false);

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

/* Draws out the rows on the threaded & hybrid views */
function drawRow(name, post_id, poster_name, poster_id, created, indent, img_dir) {
	var length = indent.length;
	var row = '';
	var url = document.location.href.split("?");
	var request = url[1].split("&");
	var id = null;
	var display = 'hybrid';
	for(var i=0; i < request.length; i++ ) {
		var temp = request[i].split("=");
		if(temp[0] == 'id') {
			id = temp[1];
		} else if(temp[0] == 'display') {
			display = temp[1];
		}
	}
	
	if(id) {
		row += '<div class="threaded_off"><table cellpadding="0" cellspacing="0" border="0" style="padding: 0px; margin: 0px;">';
		row += '<tr>';
		
		if(length > 2) {
			for(var i = 0; i < length-1; i++ ) {
				if((length-2) != i) {
					row += '<td><img src="Images/' + img_dir + '/Icons/tree_i.gif" alt="" title="" border="" /></td>';
				} else {
					row += '<td><img src="Images/' + img_dir + '/Icons/tree_l.gif" alt="" title="" border="" /></td><td><img src="Images/' + img_dir + '/Icons/post_old.gif" border="0" /></td>';
				}
			}
		} else if(indent.length == 2) {
			row += '<td><img src="Images/' + img_dir + '/Icons/tree_t.gif" alt="" title="" border="" /></td><td><img src="Images/' + img_dir + '/Icons/post_old.gif" border="0" /></td>';
		}
		
		row += '<td>&nbsp;<a href="member.php?id=' + poster_id + '"><strong>' + poster_name + '</strong></a>&nbsp;<a href="viewthread.php?id=' + id + '&display=' + display + '#' + post_id + '"><u>' + name + '</u></a>&nbsp;&nbsp;' + created + '</td>';
		row += '</tr>';
		row += '</table></div>'; 

		return document.write(row);
	}
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
