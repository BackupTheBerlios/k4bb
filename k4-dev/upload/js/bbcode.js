/**
* k4 Bulletin Board, bbcode.js
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
* @version $Id: bbcode.js,v 1.2 2005/04/05 20:23:54 k4st Exp $
* @package k42
*/

var bbcode_editors			= new Array()
var bbcode_buttons			= new Array('b', 'i', 'u', 'quote', 'code', 'php')
var bbcode_adv				= new Array('color', 'font', 'size')
var bbcode_opentags			= new Array()
var bbcode_button_objects	= new Array()

/**
 * Array Push function 
 */
Array.prototype.push = function(value) {
	this[this.length] = value;
	//alert('pushed: ' +bbcode_opentags[bbcode_opentags.length - 1]);
}

/**
 * Array pop function, but for a given value 
 */
Array.prototype.unset = function(value) {
	for(var i = 0; i < this.length; i++) {
		if(this[i] == value) {
			//alert('popped: ' +bbcode_opentags[i]);
			delete this[i];
		}
	}

	return true;
}

/**
 * In array type function
 */
Array.prototype.in_array = function(value) {
	for(var i = 0; i < this.length; i++) {
		if(this[i] == value) {
			return true;
		}
	}
	return false;
}

/**
 * Get the position of an object in an array depending on its opening tag
 */
function get_obj_pos(tag) {
	var tmp = 0;

	//alert(bbcode_opentags[1]);
	for(var i = 0; i < bbcode_opentags.length; i++) {
		if(bbcode_opentags[i]) {
			if(bbcode_opentags[i][0] == tag) {
				tmp = i;
			}
		}
	}
	return tmp;
}

/**
 * Function to get a selection 
 */
function get_selection(editor) {
	var selection = '';

	/* Focus the textarea */
	editor.focus();
	
	if (window.getSelection) {
		if(editor.selectionEnd && (editor.selectionEnd - editor.selectionStart > 0)) {
			selection = true;
		} else {
			//selection	= window.getSelection;
			selection	= false;
		}
	} else if(document.getSelection) {
		selection	= document.getSelection();
	} else if(document.selection) {
		selection	= document.selection.createRange().text;
	} else {
		selection	= false;
	}

	return selection;
}

/** 
 * Function to replace a selection 
 */
function replace_selection(editor, open, close, selection) {
	
	/* Focus the textarea */
	editor.focus();

	/* Several methods of checking and replacing the selection */
	if (window.getSelection) {

		/* Mozilla */
		if(editor.selectionEnd && (editor.selectionEnd - editor.selectionStart > 0)) {
			
			var selection_length					= editor.textLength;
			var selection_start						= editor.selectionStart;
			var selection_end						= editor.selectionEnd;
			if (selection_end == 1 || selection_end == 2)
				selection_end						= selection_length;

			var before_tag							= (editor.value).substring(0, selection_start);
			var selected_text						= (editor.value).substring(selection_start, selection_end)
			var after_tag							= (editor.value).substring(selection_end, selection_length);
			
			matches									= selected_text.match(/(\[.+\])+(.+)+(\[\/.+\])/);

			/* Set the new selection data */
			if(matches && matches != null && matches.length > 0 && matches[1] == open) {
				editor.value						= before_tag + matches[2] + after_tag;
			} else {
				editor.value						= before_tag + open + selected_text + close + after_tag;
			}
		} else {
			window.getSelection()					= open + window.getSelection() + close;
		}
	} else if(document.getSelection) {
		
		matches										= selection.match(/(\[.+\])+(.+)+(\[\/.+\])/);
		
		if(matches && matches != null && matches.length > 0 && matches[1] == open) {
			document.getSelection()					= matches[2];					
		} else {	
			document.getSelection()					= open + document.getSelection() + close;
		}
	} else if(document.selection) {

		/* Internet Explorer */		
		matches										= selection.match(/(\[.+\])+(.+)+(\[\/.+\])/);
		if(matches && matches != null && matches.length > 0 && matches[1] == open) {
			document.selection.createRange().text	= matches[2];					
		} else {
			document.selection.createRange().text	= open + document.selection.createRange().text + close;
		}
	}
}

/** 
 * Function to manage all of the bbcode buttons 
 */
function bbcodex_button_click(i) {
	
	var obj			= document.getElementById(bbcode_button_objects[i]);

	/* Get this context's editor */
	var editor		= bbcode_button_objects[i].split('_');
	editor			= document.getElementById(editor[editor.length-1]);

	/* Get selected text, if any */
	var selection	= get_selection(editor);
	
	/* Get the value of a select option if it is a select */
	var value		= obj.length ? obj[obj.selectedIndex].value : null;
	
	/* If we are using a select box, and the value of the selected index is null */
	if(obj.length) {
		if(obj[obj.selectedIndex] && obj[obj.selectedIndex].value == '') {
			
			/* Return nothing, but focus the editor */
			return editor.focus();
		}
	}
	
	/* Has this person highlighted something? */
	if(selection) {
		
		replace_selection(editor, tag_open(obj.name, value), tag_close(obj.name), selection);
	
	/* If there is no selection */
	} else {
		
		var open_tag	= tag_open(obj.name, value);
		var closed_tag	= tag_close(obj.name);
		
		/* Add the opening tag to the object */
		obj.tag			= open_tag;
		obj.closed_tag	= closed_tag;
		
		/* Check to see if this tag is open or not */
		tag_is_open		= get_open_tag(obj, value);
		
		/* If the tag is already open, close it */
		if(tag_is_open) {
			
			/* Close the current tag, and any others before it that haven't been closed */
			close_tag(obj, editor);

		/* If we are opening this tag */	
		} else {
			
			/* Add the opening value to the editor */
			editor.value	+= open_tag;
			
			/* Change the look of the button itself */
			if(value == null || value == '')
				obj.value	+= '*';

			var new_obj		= new Array(obj.tag, obj.closed_tag, obj)
			
			/* Add this tag to the open tags array */
			bbcode_opentags.push(new_obj);
		}
	}

	/* Focus the textarea */
	editor.focus();
}

/** 
 * Close a tag, and any tags that haven't been closed that come before it 
 */
function close_tag(obj, editor) {

	/* Reset the value of the button */
	obj.value		= obj.name.toUpperCase();

	var obj_i		= get_obj_pos(obj.tag);

	//alert((bbcode_opentags.length - 1) + ' ' + obj_i);
	var new_obj		= bbcode_opentags[obj_i];

	/* Loop through the open tags array */
	for(var i = (bbcode_opentags.length - 1); i > obj_i; i--) {
		
		/* If the open tag is not the current button object, and that we arn't passed this tag */
		//if(bbcode_opentags[i][0] != obj.tag && new_obj != null) {
			
			if(bbcode_opentags[i]) { 
				/* Update the editor value */
				editor.value				+= bbcode_opentags[i][1];

				/* Change the look of this button */
				bbcode_opentags[i][2].value	= bbcode_opentags[i][2].name.toUpperCase();

				/* Remove this tag from the open tags array */
				bbcode_opentags.unset(bbcode_opentags[i]);
			}
		//} else {
		//	new_obj							= bbcode_opentags[i];
		//}
	}
	
	if(new_obj) { }
	else {
		new_obj = new Array(obj.tag, obj.closed_tag, obj)
	}

	/* Add the opening value to the editor */
	editor.value	+= new_obj[1];

	/* Remove this tag from the open tags array */
	bbcode_opentags.unset(new_obj);
}

/** 
 * Tag open wrapping 
 */
function tag_open(key, val) {
	if(bbcode_adv.in_array(key)) {
		return '[' + key + '=' + val + ']';
	} else {
		return '[' + key + ']';
	}
}

/**
 * Tag close wrapping 
 */
function tag_close(val) {
	return '[/' + val + ']';
}

/** 
 * get an open tag depending on the object 
 */
function get_open_tag(obj, val) {
	
	/* Make a virtual opening tag using the info given */
	tag = tag_open(obj.name, val);

	/* Loop through all open tags */
	for(var i = 0; i < bbcode_opentags.length; i++) {
		
		if(bbcode_opentags[i]) {
			
			/* The bbcode_opentags[i].tag can be flimsy, so we create a temporary tag to check against */
			//temp_value = null;
			//if(bbcode_opentags[i].length) {
			//	temp_value = bbcode_opentags[i][bbcode_opentags[i].selectedIndex].value;
			//}

			/* Create the temporary open tag of this element */
			//temp_tag = tag_open(bbcode_opentags[i].name, temp_value);
			//alert(temp_tag);
			//if(bbcode_opentags[i] == obj)
			//	alert('arg');

			/* If the open tag is equal to our virtual tag */
			if(bbcode_opentags[i][0] == tag) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Function to close all tags in an editor
 */
function bbcodex_close_tags(editor_id) {
	var editor = document.getElementById(editor_id);

	/* Loop through the open tags array */
	for(var i = (bbcode_opentags.length - 1); i >= 0; i--) {
		
		if(bbcode_opentags[i]) { 
			/* Update the editor value */
			editor.value				+= bbcode_opentags[i][1];

			/* Change the look of this button */
			bbcode_opentags[i].value	= bbcode_opentags[i][2].name.toUpperCase();

			/* Remove this tag from the open tags array */
			bbcode_opentags.unset(bbcode_opentags[i]);
		}
	}
}

/** 
 * Function to make the opening tag of the textarea 
 */
function bbcodex_init(name, id, rows, cols, classname, style, button_style) {
	
	/* Do the buttons */
	document.write('<div id="bbcode_buttons_' + id + '" align="left">');
	
	/* loop the buttons array, then spit out the data */
	for(var i = 0; i < bbcode_buttons.length; i++) {
		document.write('<input type="button" name="' + bbcode_buttons[i] + '" id="' + bbcode_buttons[i] + '_' + id + 'codex" value="' + bbcode_buttons[i].toUpperCase() + '" class="' + button_style + '" accesskey="' + bbcode_buttons[i] + '" onclick="bbcodex_button_click(' + i + ')" onmouseover="bbcodex_helpline(\'' + bbcode_buttons[i] + '\', \'' + bbcode_buttons[i] + '_' + id + 'codex\')" />');
		bbcode_button_objects.push(bbcode_buttons[i] + '_' + id + 'codex');
	}

	document.write('<input type="button" name="URL" id="URL_' + id + 'codex" value="URL" class="' + button_style + '" onclick="BBCurl(\'' + id + '\')" onmouseover="bbcodex_helpline(\'w\', \'URL_' + id + 'codex\')" />');
	document.write('<input type="button" name="IMG" id="IMG_' + id + 'codex" value="IMG" class="' + button_style + '" onclick="BBCimg(\'' + id + '\')" onmouseover="bbcodex_helpline(\'p\', \'IMG_' + id + 'codex\')" />');
	
	document.write('<br />');

	/* Create the color selection box */
	create_color_select(id);
	/* Create the size selection box */
	create_size_select(id);
	/* Close all tags button */
	close_tags_button(id);
	
	document.write('<br />');

	/* Help Line */
	create_helpline(id);

	/* Close the buttons div */
	document.write('</div>');

	/* Create our textarea */
	document.write('<textarea name="' + name + '" id="' + id + 'codex" rows="' + rows + '" cols="' + cols + '" class="' + classname + '" style="' + style + '"></textarea>');
	
	/* Get the div and the textarea */
	var container			= document.getElementById(id);
	var editor				= document.getElementById(id + 'codex');
	
	/* Hide the div */
	container.style.display	= 'none';

	/* Insert the values of the div into the editor */
	editor.value			= container.innerHTML;

	/* Register this editor */
	bbcode_editors.push(editor);

}

/**
 * Function to dispay a select field
 */
function draw_select(name, id, values, styles, options) {
	if(values.length > 0) {
		
		/* Open the select tag */
		document.write('<select size="3" name="' + name + '" id="' + id + '" onchange="bbcodex_button_click(' + bbcode_button_objects.length + ')" onmouseover="bbcodex_helpline(\'' + name + '\', \'' + id + '\')">');
		
		/* Loop through the options and populate the select */
		for(var i = 0; i < values.length; i++) {
			document.write('<option value="' + values[i] + '" style="' + (styles[i] ? styles[i] : '') + '">' + options[i] + '</option>');
		}

		/* Close the select tag */
		document.write('</select>');

		bbcode_button_objects.push(id);
	}
}

/**
 * Function to change the Help Line value
 */
function bbcodex_helpline(name, id) {
	var editor			= id.split("_");
	
	if(editor.length > 0) {
		var helpline	= document.getElementById('helpline_' + editor[editor.length - 1]);
		helpline.value	= eval(name + '_help');
	}
}