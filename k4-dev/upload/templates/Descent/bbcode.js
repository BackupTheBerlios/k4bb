u_enter_url				= "{$L_ENTERURL}";
u_enter_pagetitle		= "{$L_ENTERPAGETITLE}";
s_webpage_title			= "{$L_PAGETITLE}";
u_error_enter_url		= " {$L_ERRORENTERURL}";
s_found_errors			= " {$L_ERRORENTERTITLE}";
s_error					= "{$L_ERROR}";
u_error_img				= "{$L_ENTERIMG}";
u_error_enter_img		= " {$L_ERRORENTERIMG}";

b_help					= "{$L_BBCODE_B_HELP}"; // Bold
i_help					= "{$L_BBCODE_I_HELP}"; // Italic
u_help					= "{$L_BBCODE_U_HELP}"; // Underline
quote_help				= "{$L_BBCODE_Q_HELP}"; // Quote
code_help				= "{$L_BBCODE_C_HELP}"; // Code
php_help				= "{$L_BBCODE_PHP_HELP}"; // PHP
l_help					= "{$L_BBCODE_L_HELP}"; // List
o_help					= "{$L_BBCODE_O_HELP}"; // Ordered List
p_help					= "{$L_BBCODE_P_HELP}"; // Image
w_help					= "{$L_BBCODE_W_HELP}"; // URL
a_help					= "{$L_BBCODE_A_HELP}"; // Close all tags
color_help				= "{$L_BBCODE_S_HELP}"; // Color
size_help				= "{$L_BBCODE_F_HELP}"; // Font Size
//n_help				= "{$L_BBCODE_N_HELP}"; // Font

/**
 * URL tag + button
 */
function BBCurl(editor_id) {
	var FoundErrors = '';
	var enterURL   = prompt(u_enter_url, "http://");
	var enterTITLE = prompt(u_enter_pagetitle, s_webpage_title);
	
	if (!enterURL)    {
		FoundErrors += u_error_enter_url;
	}
	if (!enterTITLE)  {
		FoundErrors += s_found_errors;
	}
	if (FoundErrors)  {
		alert(s_error+FoundErrors);
		return;
	}
	var ToAdd = "[URL="+enterURL+"]"+enterTITLE+"[/URL]";
	var editor	= document.getElementById(editor_id + 'codex');
	
	editor.value	+=ToAdd;
	editor.focus();
}

/**
 * Create Image tag + button
 */
function BBCimg(editor_id) {
	var FoundErrors = '';
	
	var enterURL   = prompt(u_error_img,"http://");
	
	if (!enterURL) {
		FoundErrors += u_error_enter_img;
	}
	if (FoundErrors) {
		alert(s_error+FoundErrors);
		return;
	}
	var ToAdd = "[IMG]"+enterURL+"[/IMG]";
	
	var editor	= document.getElementById(editor_id + 'codex');
	
	editor.value	+=ToAdd;
	editor.focus();
}

/**
 * Function to draw the helpline for the bbcode editor
 */
function create_helpline(editor_id) {
	document.write('<input type="text" name="helpline" value="{$L_STYLES_TIP}" id="helpline_' + editor_id + 'codex" readonly="readonly" style="border: 0px; background-color: #FFFFFF; font-size: 11px; width: 500px;" />');
}

/**
 * Function to create the font color selector
 */
function create_color_select(id) {
	var options = new Array('{$L_FONT_COLOR}', '{$L_SKYBLUE}', '{$L_ROYALBLUE}', '{$L_BLUE}', '{$L_DARKBLUE}', '{$L_ORANGE}', '{$L_ORANGERED}', '{$L_CRIMSON}', '{$L_RED}', '{$L_FIREBRICK}', '{$L_DARKRED}', '{$L_GREEN}', '{$L_LIMEGREEN}', '{$L_SEAGREEN}', '{$L_DEEPPINK}', '{$L_TOMATO}', '{$L_CORAL}', '{$L_PURPLE}', '{$L_INDIGO}', '{$L_BURLYWOOD}', '{$L_SANDYBROWN}', '{$L_SIENNA}', '{$L_CHOCOLATE}', '{$L_TEAL}', '{$L_SILVER}')
	var values	= new Array('black', 'skyblue', 'royalblue', 'blue', 'darkblue', 'orange', 'orangered', 'crimson', 'red', 'firebrick', 'darkred', 'green', 'limegreen', 'seagreen', 'deeppink', 'tomato', 'coral', 'purple', 'indigo', 'burlywood', 'sandybrown', 'sienna', 'chocolate', 'teal', 'silver')
	var styles	= new Array('color: black;', 'color: skyblue;', 'color: royalblue;', 'color: blue;', 'color: darkblue;', 'color: orange;', 'color: orangered;', 'color: crimson;', 'color: red;', 'color: firebrick;', 'color: darkred;', 'color: green;', 'color: limegreen;', 'color: seagreen;', 'color: deeppink;', 'color: tomato;', 'color: coral;', 'color: purple;', 'color: indigo;', 'color: burlywood;', 'color: sandybrown;', 'color: sienna;', 'color: chocolate;', 'color: teal;', 'color: silver;')
	
	/* Create our select menu */
	draw_select('color', 'color_' + id + 'codex', values, styles, options);
}

/**
 * Function to create font size selector
 */
function create_size_select(id) {
	
	var options = new Array('{$L_FONT_SIZE}', '{$L_FONT_TINY}', '{$L_FONT_SMALL}', '{$L_FONT_NORMAL}', '{$L_FONT_LARGE}', '{$L_FONT_HUGE}')
	var values	= new Array(12, 7, 9, 12, 18, 24)
	var styles	= new Array('font-size: auto;', 'font-size: 8px;', 'font-size: 9px;', 'font-size: 12px;')
	
	/* Create our select menu */
	draw_select('size', 'size_' + id + 'codex', values, styles, options);
}

/**
 * Function to close all open bbcode tags
 */
function close_tags_button(id) {
	document.write('&nbsp;&nbsp;<a href="javascript:;" title="{$L_BBCODE_CLOSE_TAGS}" onclick="bbcodex_close_tags(\'' + id + 'codex\')" onmouseover="bbcodex_helpline(\'a\', \'closetags_' + id + 'codex\')">{$L_BBCODE_CLOSE_TAGS}</a>');
}