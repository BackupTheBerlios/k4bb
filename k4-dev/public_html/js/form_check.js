//<![CDATA[
//<!--
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
function Reset(form_Id) {
	for(var i = 0; i < form_Id.length; i++) {
		var the_type = form_Id.elements[i].type;
		if((the_type != 'submit') && (the_type != 'reset') && (the_type != 'checkbox') && (the_type != 'radio') && (the_type != 'button')) {
			form_Id[i].value = "";
			form_Id[i].className = "inputbox";
		}
		if(document.all) {
			var error_holder = document.all.form_errors;
			error_holder.style.display = "none";
		} else if(document.getElementById) {
			var error_holder = document.getElementById("form_errors");
			error_holder.style.display = "none";
		}
	}
	return true;
}

var elements = new Array();
var matches = new Array();
var regexs = new Array();
var errors = new Array();
var messages = new Array();
var error_classes = new Array();
var base_classes = new Array();

function resetErrors() {
	for (var i = 0; i < elements.length; i++)
	{
		var error = document.getElementById(errors[i]);
		if (error) error.style.display = 'none';

		var element = document.getElementById(elements[i]);
		if (element) element.className = base_classes[i];

		var message = document.getElementById(messages[i]);
		if (message) message.style.display = 'block';
	}
}

function showError(num)
{
	var error = document.getElementById(errors[num]);
	if (error) error.style.display = 'block';

	var element = document.getElementById(elements[num]);
	if (element) element.className = error_classes[num];

	var message = document.getElementById(messages[num]);
	if (message) message.style.display = 'none';
}
function checkForm(form)
{
	var valid = true;

	resetErrors();

	for (var i = 0; i < form.elements.length; i++)
	{
		var element = form.elements[i];
		for (var j = 0; j < elements.length; j++)
		{
			if (elements[j] == element.id)
			{
				if (regexs[j] != '' && !regexs[j].test(element.value))
				{
					showError(j);
					valid = false;
					break;
				}
				if (matches[j]) {
					var match = document.getElementById(matches[j]);

					if (element.value != match.value)
					{
						element.value = '';
						match.value = '';

						showError(j);
						valid = false;
						break;
					}
				}
			}
		}
	}

	return valid;
}
function addMessage(id, message)
{
	for (var i = 0; i < elements.length; i++) {
		if (elements[i] == id)
		{
			messages[i] = message;
		}
	}
}
function addVerification(id, regex, error, classname)
{
	var num = elements.length;

	elements[num] = id;
	regexs[num] = new RegExp('^'+regex+'$');
	matches[num] = '';
	errors[num] = error;

	element = document.getElementById(id);
	base_classes[num] = element.className;
	error_classes[num] = (classname) ? classname : element.className;
}
function addCompare(id, match, error, classname)
{
	var num = elements.length;

	elements[num] = id;
	regexs[num] = '';
	matches[num] = match;
	errors[num] = error;

	element = document.getElementById(id);
	base_classes[num] = element.className;
	error_classes[num] = (classname) ? classname : element.className;
}

//-->
//]]>