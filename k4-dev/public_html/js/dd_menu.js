/**
* k4 Bulletin Board, dd_menu.js
* Drop Down Menu Generator
*
* Copyright (c) 2004, Peter Goodman
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
* @version $Id: dd_menu.js,v 1.1 2005/04/05 02:32:33 necrotic Exp $
* @package k42
*/

/* A function to find the size of an array, instead of theArray.length */
Array.prototype.sizeOf = function() {
	
	/* Loop through the array */
	for (var i = 0; i < this.length; i++) {
		
		/* If the currently iterated key of this array is undefined, return it as the length */
		if ((this[i] == "undefined") || (this[i] == "") || (this[i] == null))
			
			/* Return the size of the array */
			return i;
	}

	/* Otherwise, just return the length of the array */
	return this.length;
}

/* A function to push a value onto the an array */
Array.prototype.push = function(Val) {
	
	/* Push the value onto the array */
	this[ this.sizeOf() ] = Val;
}

/* Get the right box from the stack that we are using */
Array.prototype.getMenuObject = function(Val) {
	
	/* Loop through the array */
	for(var i = 0; i < this.sizeOf(); i++) {
		
		/* If the array key value matches the Val (menuId), return the menu Object */
		if(this[i][0] == Val) {
			
			/* Return the box object */
			return this[i][1];
		}
	}
}

/* Get the only open menu object */
Array.prototype.getOpenMenuObject = function() {
	
	/* Loop through the array */
	for(var i = 0; i < this.sizeOf(); i++) {
		
		/* If the menu's display is set to block, return this object */
		if(this[i][1].menu.style.display == 'block') {
			
			/* Return the box object */
			return this[i][1];
		}
	}
}

/* Hide all open menus except for the one that we're on */
Array.prototype.hideMenus = function(Val) {
	
	/* Loop through the array */
	for(var i = 0; i < this.sizeOf(); i++) {
		
		/* If the array key value doesn't match the 'Val' (menu ID), then hide the menu */
		if(this[i][0] != Val && this[i][0] && this[i][0] != null) {
			
			/* Hide the other boxes */
			this[i][1].menu.style.display = 'none';
		}
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

var loadedMenus = new Array()

var tempX = null;
var tempY = null;

/* Track where the mouse is in all browsers */
document.onmousemove = function(event) {
	if(navigator.appName != "Netscape") {
		tempX = window.event.clientX + window.document.body.scrollLeft;
		tempY = window.event.clientY + window.document.body.scrollTop;
	} else {
		tempX = event.pageX;
		tempY = event.pageY;
	}

	if (tempX <= 0) { tempX = 0 }
	if (tempY <= 0) { tempY = 0 }

}

/* Function to create a drop down menu */
function RegisterMenu(linkId, menuId, leftOpen, leftClose, topOpen, topClose) {
	
	/* Set the Object variable, we laod this into the loadedMenus array */
	var Object = new Array(linkId, this)
	
	/* Push this object onto the loadedMenus array */
	loadedMenus.push(Object);
	
	/* Set up the main objects */
	this.link						= document.getElementById(linkId);
	this.menu						= document.getElementById(menuId);
	
	/* Find all of the offsets of the object */
	this.menu_table					= this.menu.getElementsByTagName("table");
	this.menu_width					= this.menu_table[0].offsetWidth;

	/* Set a variable with the temporary intial html of the menu */
	this.menu.initialHTML			= this.menu.innerHTML;
	
	/* get the menu's initial height and width */
	this.menu.initialHeight			= this.menu.offsetHeight;
	this.menu.initialWidth			= this.menu_width;

	/* Set some basic varibales for the drop down menu */
	this.menu.style.display			= 'none';
	this.menu.style.position		= 'absolute';
	this.menu.style.width			= this.menu_width;
	this.menu.style.left			= fetch_object_posleft(this.link) - (this.menu_width - this.link.offsetWidth);
	this.menu.style.top				= fetch_object_postop(this.link) + this.link.offsetHeight;


	if(navigator.appName != "Netscape") {
		this.menu.style.filter		= "progid:DXImageTransform.Microsoft.shadow(direction=135,color=#8E8E8E,strength=3)";
	}
	
	/* On mouse over of the menu */
	this.link.onmouseover = function() {
		
		/* Set what the cursor should be */
		try {
			this.style.cursor = 'pointer';
		} catch(e) {
			this.style.cursor = 'hand';
		}

		/* Get the current menu Object */
		menuObj						= loadedMenus.getMenuObject(linkId);
		
		/* Open the menu, but only if it is not already open */
		if(menuObj.menu.style.display == 'none') {
			menuObj.menu.openMenu();
		}
	}
	
	/* On mouse out of the function */
	this.link.onmouseout = function() {
		
		/* Get the current menu Object */
		menuObj						= loadedMenus.getMenuObject(linkId);

		/* Close the menu, but only if it's open */
		if(menuObj.menu.style.display == 'block') {
			
			menuObj.menu.close		= true;
			menuObj.menu.closeMenu();
		}
	}
	
	this.menu.onmouseover = function() {
		
		/* Get the current menu Object */
		menuObj						= loadedMenus.getMenuObject(linkId);

		menuObj.menu.close			= false;
	}

	/* On moust out function for the menu itself */
	this.menu.onmouseout = function() {
		
		/* Get the current menu Object */
		menuObj						= loadedMenus.getMenuObject(linkId);
		
		menuObj.menu.close			= true;
			
		/* Close the menu, but only if it's open */
		if(menuObj.menu.style.display == 'block') {
			menuObj.menu.closeMenu();
		}
	}
	
	/* Close the menu if someone clicks somewhere in the page where the menu isn't open */
	document.onclick = function() {
		
		try {
			/* Get the current menu Object */
			menuObj						= loadedMenus.getOpenMenuObject();

			/* Close the menu, but only if it's open */
			menuObj.menu.closeMenu();
		} catch(e) { }
	}
	
	/* Function to open a box */
	this.menu.openMenu = function() {

		/* Get the current menu object */
		menuObj						= loadedMenus.getMenuObject(linkId);
		
		/* Show this menu */
		menuObj.menu.style.display	= 'block';

		/* Change the height and width of the menu */
		menuObj.menu.style.height	= 0;
		menuObj.menu.style.width	= 0;
		
		/* Reset the Inner HTML of the menu */
		menuObj.menu.innerHTML		= '';

		/* Set the resizing */
		menuObj.resizeTop			= topOpen.floatVal();
		menuObj.resizeLeft			= leftOpen.floatVal();
		
		/* Constantly making sure our menu is in the right spot ;) */
		menuObj.menu.style.left			= fetch_object_posleft(menuObj.link) - (menuObj.menu_width - menuObj.link.offsetWidth) + "px";
		menuObj.menu.style.top			= fetch_object_postop(menuObj.link) + menuObj.link.offsetHeight + "px";
		
		/* Create the timer for the menu */
		menuObj.menu.timer			= setTimeout("menuObj.resizeMenu(" + menuObj.menu.timer + ");", 0);
	}

	/* Function to close the menu */
	this.menu.closeMenu = function() {
		
		/* Get the current menu object */
		menuObj							= loadedMenus.getMenuObject(linkId);
		
		/* Set the resizing */
		menuObj.resizeTop				= -1;
		menuObj.resizeLeft				= -1;

		/* Check if we are hovering over the menu */
		if( (tempY >= fetch_object_postop(menuObj.link) && tempY <= parseInt(fetch_object_postop(menuObj.menu) + menuObj.menu.initialHeight) ) &&
			(tempX >= fetch_object_posleft(menuObj.menu) && tempX <= parseInt(fetch_object_posleft(menuObj.menu) + menuObj.menu.initialWidth) ) ) {

			//alert('tempX: ' + tempX + '\r\ntempY: ' + tempY + '\r\nmenu Top: ' + fetch_object_postop(menuObj.menu) + '\r\nmenu Bottom: ' + parseInt(fetch_object_postop(menuObj.menu) + menuObj.menu.offsetHeight) + '\r\nmenu Left: ' + fetch_object_posleft(menuObj.menu) + '\r\nmenu Right: ' + parseInt(fetch_object_posleft(menuObj.menu) + menuObj.menu.offsetWidth) );

			menuObj.menu.style.display	= 'block';				
			
		} else {
			
			/* Advanced error checking */ 
			/*
			error		= "Mouse X: " + tempX + "\r\n";
			error		+="Mouse Y: " + tempY + "\r\n\r\n";
			error		+="(" + fetch_object_posleft(menuObj.menu) + ", " + fetch_object_postop(menuObj.link) + ") --- (" + parseInt(fetch_object_posleft(menuObj.menu) + menuObj.menu.initialWidth) + ", " + fetch_object_postop(menuObj.link) + ")\r\n\r\n";
			error		+="|                                   |\r\n";
			error		+="(" + fetch_object_posleft(menuObj.menu) + ", " + parseInt(fetch_object_postop(menuObj.menu) + menuObj.menu.initialHeight) + ") --- (" + parseInt(fetch_object_posleft(menuObj.menu) + menuObj.menu.initialWidth) + ", " + parseInt(fetch_object_postop(menuObj.menu) + menuObj.menu.initialHeight) + ")";
			
			alert(error);
			*/

			/* This gets set in both link.onmouseout and menu.onmouseover - check if we should close or not */
			if(menuObj.menu.close == true) {

				/* Create the timer to Hide the Menu */
				menuObj.menu.timer			= setTimeout("menuObj.menu.style.display = 'none';", 500);

				/* reset the menu.close */
				menuObj.menu.close = null;

			}
		}
	}

	/* Function to open/close a menu */
	this.resizeMenu = function(timerObj) {
		
		/* Get the current menu Object */
		menuObj							= loadedMenus.getMenuObject(linkId);

		/* Hide all other open menus */
		loadedMenus.hideMenus(linkId);
		
		/* Get the current height and width */
		menuX							= menuObj.menu.style.width.floatVal();
		menuY							= menuObj.menu.style.height.floatVal();

		/* Manage top-to-bottom resizing */
		if( ( (menuY < menuObj.menu.initialHeight) && menuObj.resizeTop > 0) || ( ( menuY > 0) && menuObj.resizeTop < 0) ) {
			
			/* Determine the next height for the menu */
			new_height = menuY + (menuObj.menu.initialHeight * menuObj.resizeTop);
			
		} else {
			
			/* Check if the menu is open or closed, this is for if the width is still resizing */
			if(menuY >= menuObj.menu.initialHeight) {
				
				/* Set the menu height to it's initial height */
				new_height = menuObj.menu.initialHeight;
			} else {
				
				/* Set the menu's height to zero */
				new_height = 0;
			}
		}

		/* Manage left-to-right resizing */
		if( ( (menuX < menuObj.menu.initialWidth) && menuObj.resizeLeft > 0) || ( ( menuX > 0) && menuObj.resizeLeft < 0) ) {
		
			/* Determine the next width for the menu */
			new_width = menuX + (menuObj.menu.initialWidth * menuObj.resizeLeft);
			
		} else {
			
			/* Check if the menu is open or closed, this is for if the width is still resizing */
			if(menuX >= menuObj.menu.initialWidth) {
				
				/* Set the menu width to it's initial height */
				new_width = menuObj.menu.initialWidth;
			} else {
				
				/* Set the menu's width to zero */
				new_width = 0;
			}
		}

		/* Set the boxes new height */
		menuObj.menu.style.height			= new_height > 0 ? new_height : 0;

		/* Set the boxes new width */
		menuObj.menu.style.width			= new_width > 0 ? new_width : 0;
		
		/* Check if we should continue to resize this box */
		if( ((new_width >= menuObj.menu.initialWidth) && (new_height < menuObj.menu.initialHeight))
			|| 
			((new_width < menuObj.menu.initialWidth) && (new_height >= menuObj.menu.initialHeight)) 
			|| 
			((new_width < menuObj.menu.initialWidth) && (new_height < menuObj.menu.initialHeight)) ) {
		
			/* Recurse through this function again */
			menuObj.menu.timer				= setTimeout("menuObj.resizeMenu(" + timerObj + ");", 0);

		} else {

			/* Just to be sure, reset the height and width of the menu */
			menuObj.menu.style.height	= menuObj.menu.initialHeight;
			menuObj.menu.style.width	= menuObj.menu.initialWidth;
			
			/* Set the inner HTML of the menu */
			menuObj.menu.innerHTML		= menuObj.menu.initialHTML;
			
			/* Clear out the timer */
			menuObj.clearTimer(timerObj);
		}
	}

	/* Function to clear the timeout */
	this.clearTimer = function(timerObj) {
		
		/* Stop our timer */
		clearTimeout(timerObj);
	}
}