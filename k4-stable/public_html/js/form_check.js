//<![CDATA[
//<!--


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

function switchClass(object, objects) {
	var size = objects.length;
	for(var i = 0; i < size; i++) {
		var obj = objects[i];
		obj.className = 'inputbox';
	}
	return object.className = 'inputfailed';
}
function switchClasses(object) {
	return object.className = 'inputfailed';
}
function changeBack(obj) {
	if(obj.className == 'inputfailed') {
		return obj.className = 'inputbox';
	} else {
		return true;
	}
}
function take_errors(error_string) {
	if(error_string) {
		if(error_string != "<ul>") {
			var error_holder = document.getElementById("form_errors");
			error_holder.style.display = "block";
			error_holder.innerHTML = error_string;
		} else { return false; }
	} else { return false; }
}
function exit(val) {
	if(val) {
		alert(val);
		return false;
	} else {
		return false;
	}
}
function ResetAttach(form_Id) {
	try {
		var ids		= "attach1 attach2 attach3 attach4".split(" ");
		var finals	= "attach1_final attach2_final attach3_final attach4_final".split(" ");
		var imgs	= "attach1_img attach2_img attach3_img attach4_img".split(" ");
		for(var i=0; i<ids.length;i++) {
			document.getElementById(ids[i]).style.display = 'block';
			document.getElementById(finals[i]).style.display = 'none';
			document.getElementById(imgs[i]).src = '';
		}
	} catch (e) {
		return false;
	}
}
function Reset(form_Id) {
	for(var i = 0; i < form_Id.length; i++) {
		var the_type = form_Id.elements[i].type;
		if((the_type != 'submit') && (the_type != 'reset') && (the_type != 'checkbox') && (the_type != 'radio') && (the_type != 'button')) {
			form_Id[i].value = "";
			form_Id[i].className = "inputbox";
		}
		if(document.getElementById) {
			var error_holder = document.getElementById('form_errors');
			error_holder.style.display = "none";
		} else if(document.all) {
			var error_holder = document.all.form_errors;
			error_holder.style.display = "none";
		}
	}
	return true;
}
/* Edit a Profile */
function checkEPForm(form) {
	var formErrors = "<ul>";
	var objects = new Array()
	
	var email = form.email;
	var pass = form.pass;
	var pass2 = form.pass_check;
	
	objects[0] = email;
	objects[1] = pass;
	objects[2] = pass2;
	var size = objects.length;
	
	if(email.value.length == 0) {
		switchClasses(email, objects);
		formErrors += "<li>The following field must be filled in: <strong>E-mail address</strong></li>";
	}
	if((pass2.value.length != 0) && (pass.value.length == 0)) {
		switchClasses(pass, objects);
		formErrors += "<li>The following field must be filled in: <strong>Password</strong></li>";
	}
	if(pass.value.length != 0) {
		if((pass2.value.length == 0) && (pass.value.length != 0)) {
			switchClasses(pass2, objects);
			formErrors += "The following field must be filled in: <strong>Verification Password</strong>";
		}
	}
	take_errors(formErrors);
	if(formErrors != "<ul>") { return false; } else { return true; }
}
//-->
//]]>