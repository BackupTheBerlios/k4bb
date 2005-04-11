/**
* k4 Bulletin Board, dd_menu.js
*
* Copyright (c) 2005, Peter Goodman
*
* Permission is hereby granted, free of charge, to any person obtaining
* a copy of this software and associated documentation files (the
* "Software"), to deal in the Software without restriction, including
* without limitation the rights to use, copy, modify, merge, publish,
* distribute, sublicense, and/or sell copies of the Software, and to
* permit persons to whom the Software is furnished to do so, subject to
* the following conditions:
*
* The above copyright notice and this permission notice shall be
* included in all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
* EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
* MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
* NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
* BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
* ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
* CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
* SOFTWARE.
*
* @author Peter Goodman
* @version $Id: dd_menu.js,v 1.2 2005/04/11 02:15:13 k4st Exp $
* @package k42
*/

var active_menus			= new Array()
var open_menu				= false;
var do_slide				= true;
var slidetimer				= false;
var slide_steps				= 10;

var tempX					= 0;
var tempY					= 0;

/* Track where the mouse is in all browsers */
document.onmousemove = function(event) {
	try {
		if(navigator.appName != "Netscape") {
			tempX = window.event.clientX + window.document.body.scrollLeft;
			tempY = window.event.clientY + window.document.body.scrollTop;
		} else {
			tempX = event.pageX;
			tempY = event.pageY;
		}

		if (tempX <= 0) { tempX = 0 }
		if (tempY <= 0) { tempY = 0 }
	} catch(e) {
		//alert(e.message);
	}
}

/* Turn a floating point string into a floating point number */
String.prototype.floatVal = function() { 
	if(this) {
		var match = '';
		
		/* Check if the string ends with 'px' or 'pt' */
		match = this.match(/px/) ? 'px' : match; // for IE
		match = this.match(/pt/) ? 'pt' : match; // for MOZ

		/* Trim and return the string if there were any matches */
		return (this.substr(0, (this.length - match.length)) * 1);
	}

	/* Just a placeholder if nothing happened */
	return this;
}

/* Array push method */
function array_push(array, value) {
	array[array.length] = value;
}

/* Get the left position of an object */
function fetch_object_posleft(obj) {
	
	var left = obj.offsetLeft;
	while((obj = obj.offsetParent) != null)
	{
		left += obj.offsetLeft;
	}
	return left;
}

/* Get the top position of an object */
function fetch_object_postop(obj) {
	
	var top = obj.offsetTop;
	while((obj = obj.offsetParent) != null)
	{
		top += obj.offsetTop;
	}
	return top;
}

/* Initialize a menu object to the active menu's array */
function menu_init(link_id, menu_id) {
	
	/* Get the link and the menu */
	var link				= document.getElementById(link_id);
	var menu				= document.getElementById(menu_id);
	
	/* Get where the menu should end up being */
	var left				= fetch_object_posleft(link);
	var top					= fetch_object_postop(link) + link.offsetHeight;

	/* Set the link id to the menu */
	menu.link_id			= link.id;

	/* Move the menu */
	menu.style.position		= 'absolute';
	menu.style.top			= top + 'px';

	/* Set some object menu positions */
	menu.posLeft			= left;
	menu.posRight			= left + menu.offsetWidth;
	menu.posTop				= top;
	menu.posBottom			= top + menu.offsetHeight;

	/* Hide the menu */
	menu.style.display		= 'none';
	
	/* Make the link item have the link cursor */
	try {
		link.style.cursor = 'pointer';
	} catch(e) {
		link.style.cursor = 'hand';
	}

	/* Do we force this menu to slide right-to-left? */
	menu.force_slide_right	= menu.posRight >= document.body.clientWidth ? true : false;
	
	/* Get the left position of the menu, and compensate for 10px in mozilla */
	if(menu.force_slide_right) {
		left_style			= (navigator.appName == "Netscape") ? ((left - (menu.posRight - menu.posLeft)) + link.offsetWidth) + 10 : ((left - (menu.posRight - menu.posLeft)) + link.offsetWidth);
	} else {
		left_style			= (navigator.appName == "Netscape") ? left + 10 : left;
	}

	/* Give the menu a nice shadow if this is IE */
	if(navigator.appName != "Netscape") {
		try {
			menu.style.filter += "progid:DXImageTransform.Microsoft.shadow(direction=135,color=#8E8E8E,strength=3)";
		} catch(e) { }
	}
	
	/* Set the left position of the menu */
	menu.style.left			= left_style + 'px';

	var menu_method			= {
							"menu_id"	: menu.id,
							"menu"		: menu,
							"L"			: menu.posLeft,
							"R"			: menu.posRight,
							"B"			: menu.posBottom,
							"T"			: menu.posTop
							}

	/* Put this menu into the active menu's array */
	//array_push(active_menus, menu_method);
	
	/* Onclick function for the current link */
	link.onclick = function() {
		
		/* Open or close the menu */
		if(menu.style.display == 'none') {
			openmenu(menu);
		} else {
			closemenu(menu);
		}
	}

	/* Onmouseover for a link object */
	link.onmouseover = function() {
		if(open_menu) {
			if(open_menu.link_id != this.id) {
				
				/* Close the current open menu */
				closemenu(open_menu);

				/* Open this new menu */
				openmenu(menu);
			}
		}
	}

	/* Whenever you click in the document, close the menu */
	document.onclick = function() {
		if(open_menu) {
			
			open_menulink	= document.getElementById(open_menu.link_id);

			if( (tempY >= fetch_object_postop(open_menulink) && tempY <= (fetch_object_postop(open_menu) + open_menu.offsetHeight) ) &&
			(tempX >= fetch_object_posleft(open_menu) && tempX <= (fetch_object_posleft(open_menu) + open_menu.offsetWidth) ) ) {

			} else {
				closemenu(open_menu);
			}
		}
	}

	/* Look for tr's with the class 'alt1' */
	var link_rows		= menu.getElementsByTagName("tr");
	
	for(var i = 0; i < link_rows.length; i++) {
		if(link_rows[i]) {
			
			if(link_rows[i].className == 'alt1') {
				
				try {
					link_rows[i].style.cursor = 'pointer';
				} catch(e) {
					link_rows[i].style.cursor = 'hand';
				}

				link_rows_link		= link_rows[i].getElementsByTagName("a");
				
				/* Deal with onclick of the tr's */
				if(link_rows_link && link_rows_link[0]) {
					link_rows[i].onclick = function() {
						document.location = link_rows_link[0].href;
					}
				}
				
				/* Deal with onmouseover and onmouseout of the tr's */
				link_rows[i].onmouseover = function() {
					this.className	= 'alt2';
				}
				link_rows[i].onmouseout = function() {
					this.className	= 'alt1';
				}
			}
		}
	}
}

/* Find out if object 'm' (menu) overlaps object 'obj' (generally a <select>) */
function is_in_grid(m, obj) {
	var s = {
			"L"		: fetch_object_posleft(obj),
			"R"		: fetch_object_posleft(obj) + obj.offsetWidth,
			"T"		: fetch_object_postop(obj),
			"B"		: fetch_object_postop(obj) + obj.offsetHeight
			}
	
	if (s['L'] >= m['L'] && s['L'] <= m['R'] && ((s['T'] >= m['T'] && s['T'] <= m['B']) || (s['B'] >= m['T'] && s['B'] <= m['B']))) { return true; }
	else if (s['R'] >= m['L'] && s['R'] <= m['R'] && ((s['T'] >= m['T'] && s['T'] <= m['B']) || (s['B'] >= m['T'] && s['B'] <= m['B']))) { return true; }
	else if (s['B'] >= m['T'] && s['T'] <= m['B'] && ((s['L'] >= m['L'] && s['L'] <= m['R']) || (s['R'] >= m['R'] && s['R'] <= m['R']))) { return true; }
	else if (m['B'] >= s['T'] && m['T'] <= s['B'] && ((m['L'] >= s['L'] && m['L'] <= s['R']) || (m['R'] >= s['R'] && m['R'] <= s['R']))) { return true; }
	else { return false; }

}

function openmenu(menu) {

	/* If the menu is currently closed */
	if(menu.style.display == 'none') {
		
		menu.style.display = 'block';

		/* Loop through all of the <select>'s on a page */
		selects = document.getElementsByTagName("select");
		for (var s = 0; s < selects.length; s++) {
			
			/* If the menu overlaps the select menu, hide the select */
			if (is_in_grid(menu, selects[s])) {
				selects[i].style.display = 'none';
			}
		}

		/* Found how much we need to increment each menu clip by */
		var intervalX = Math.ceil(menu.offsetWidth / slide_steps);
		var intervalY = Math.ceil(menu.offsetHeight / slide_steps);
		
		/*  clip: rect(top, right, bottom, left) */
		
		/* If the menu will slide open left-to-right */
		if(menu.force_slide_right == false) {
			
			menu.style.clip		= 'rect(auto, auto, 0px, 0px)';
			open_menu_left(menu.id, 0, 0, intervalX, intervalY);
			
		/* If the menu will slide open right-to-left */
		} else {

			menu.style.clip		= 'rect(auto, auto, 0px, ' + menu.offsetWidth + 'px)';
			open_menu_right(menu.id, 0, 0, intervalX, intervalY);
		}

	/* Assume that the menu is open */
	} else {
		close_menu(menu);
	}

	return true;
}

function closemenu(menu) {
	menu.style.display	= 'none';
	open_menu			= null;
}

function open_menu_left(menu_id, clipX, clipY, intervalX, intervalY) {
	
	var menu	= document.getElementById(menu_id);

	menu.style.display = 'block';

	/* Get the new clipX and clipY */
	clipX	= clipX < menu.offsetWidth ? clipX + intervalX : menu.offsetWidth;
	clipY	= clipY < menu.offsetHeight ? clipY + intervalY : menu.offsetHeight;

	/* Check to see if we should continue to resize or not */
	if( ((clipX >= menu.offsetWidth) && (clipY < menu.offsetHeight))
		|| 
		((clipX < menu.offsetWidth) && (clipY >= menu.offsetHeight)) 
		|| 
		((clipX < menu.offsetWidth) && (clipY < menu.offsetHeight)) ) {
		
		menu.style.clip		= 'rect(auto, ' + clipX + 'px, ' + clipY + 'px, auto)';

		slidetimer = setTimeout("open_menu_left('" + menu_id + "', " + clipX + ", " + clipY + ", " + intervalX + ", " + intervalY + ");", 0);
		
	} else {
		
		menu.style.clip		= 'rect(auto, ' + menu.offsetWidth + 'px, ' +  menu.offsetHeight + 'px, auto)';

		/* Stop our timer */
		clearTimeout(slidetimer);
		slidetimer	= false;
		open_menu	= menu;
	}
}

function open_menu_right(menu_id, clipX, clipY, intervalX, intervalY) {
	
	var menu	= document.getElementById(menu_id);

	/* Get the new clipX and clipY */
	clipX	= clipX < menu.offsetWidth ? clipX + intervalX : menu.offsetWidth;
	clipY	= clipY < menu.offsetHeight ? clipY + intervalY : menu.offsetHeight;
	
	/* Check to see if we should continue to resize or not */
	if( ((clipX >= menu.offsetWidth) && (clipY < menu.offsetHeight))
		|| 
		((clipX < menu.offsetWidth) && (clipY >= menu.offsetHeight)) 
		|| 
		((clipX < menu.offsetWidth) && (clipY < menu.offsetHeight)) ) {
		
		menu.style.clip		= 'rect(auto, ' + clipX + 'px, ' + clipY + 'px, auto)';
		
		slidetimer = setTimeout("open_menu_right('" + menu_id + "', " + clipX + ", " + clipY + ", " + intervalX + ", " + intervalY + ");", 0);
	} else {
		
		menu.style.clip		= 'rect(auto, ' + menu.offsetWidth + 'px, ' + menu.offsetHeight + 'px, auto)';

		/* Stop our timer */
		clearTimeout(slidetimer);
		slidetimer	= false;
		open_menu	= menu;
	}
}