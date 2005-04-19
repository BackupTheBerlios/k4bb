u_enter_url				= "<?php echo $context->getVar("L_ENTERURL"); ?>";
u_enter_pagetitle		= "<?php echo $context->getVar("L_ENTERPAGETITLE"); ?>";
s_webpage_title			= "<?php echo $context->getVar("L_PAGETITLE"); ?>";
u_error_enter_url		= " <?php echo $context->getVar("L_ERRORENTERURL"); ?>";
s_found_errors			= " <?php echo $context->getVar("L_ERRORENTERTITLE"); ?>";
s_error					= "<?php echo $context->getVar("L_ERROR"); ?>";
u_error_img				= "<?php echo $context->getVar("L_ENTERIMG"); ?>";
u_error_enter_img		= " <?php echo $context->getVar("L_ERRORENTERIMG"); ?>";

b_help					= "<?php echo $context->getVar("L_BBCODE_B_HELP"); ?>"; // Bold
i_help					= "<?php echo $context->getVar("L_BBCODE_I_HELP"); ?>"; // Italic
u_help					= "<?php echo $context->getVar("L_BBCODE_U_HELP"); ?>"; // Underline
quote_help				= "<?php echo $context->getVar("L_BBCODE_Q_HELP"); ?>"; // Quote
code_help				= "<?php echo $context->getVar("L_BBCODE_C_HELP"); ?>"; // Code
php_help				= "<?php echo $context->getVar("L_BBCODE_PHP_HELP"); ?>"; // PHP
l_help					= "<?php echo $context->getVar("L_BBCODE_L_HELP"); ?>"; // List
o_help					= "<?php echo $context->getVar("L_BBCODE_O_HELP"); ?>"; // Ordered List
p_help					= "<?php echo $context->getVar("L_BBCODE_P_HELP"); ?>"; // Image
w_help					= "<?php echo $context->getVar("L_BBCODE_W_HELP"); ?>"; // URL
a_help					= "<?php echo $context->getVar("L_BBCODE_A_HELP"); ?>"; // Close all tags
color_help				= "<?php echo $context->getVar("L_BBCODE_S_HELP"); ?>"; // Color
size_help				= "<?php echo $context->getVar("L_BBCODE_F_HELP"); ?>"; // Font Size
//n_help				= "<?php echo $context->getVar("L_BBCODE_N_HELP"); ?>"; // Font

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
	document.write('<input type="text" name="helpline" value="<?php echo $context->getVar("L_STYLES_TIP"); ?>" id="helpline_' + editor_id + 'codex" readonly="readonly" style="border: 0px; background-color: #FFFFFF; font-size: 11px; width: 500px;" />');
}

/**
 * Function to create the font color selector
 */
function create_color_select(id) {
	var options = new Array('<?php echo $context->getVar("L_FONT_COLOR"); ?>', '<?php echo $context->getVar("L_SKYBLUE"); ?>', '<?php echo $context->getVar("L_ROYALBLUE"); ?>', '<?php echo $context->getVar("L_BLUE"); ?>', '<?php echo $context->getVar("L_DARKBLUE"); ?>', '<?php echo $context->getVar("L_ORANGE"); ?>', '<?php echo $context->getVar("L_ORANGERED"); ?>', '<?php echo $context->getVar("L_CRIMSON"); ?>', '<?php echo $context->getVar("L_RED"); ?>', '<?php echo $context->getVar("L_FIREBRICK"); ?>', '<?php echo $context->getVar("L_DARKRED"); ?>', '<?php echo $context->getVar("L_GREEN"); ?>', '<?php echo $context->getVar("L_LIMEGREEN"); ?>', '<?php echo $context->getVar("L_SEAGREEN"); ?>', '<?php echo $context->getVar("L_DEEPPINK"); ?>', '<?php echo $context->getVar("L_TOMATO"); ?>', '<?php echo $context->getVar("L_CORAL"); ?>', '<?php echo $context->getVar("L_PURPLE"); ?>', '<?php echo $context->getVar("L_INDIGO"); ?>', '<?php echo $context->getVar("L_BURLYWOOD"); ?>', '<?php echo $context->getVar("L_SANDYBROWN"); ?>', '<?php echo $context->getVar("L_SIENNA"); ?>', '<?php echo $context->getVar("L_CHOCOLATE"); ?>', '<?php echo $context->getVar("L_TEAL"); ?>', '<?php echo $context->getVar("L_SILVER"); ?>')
	var values	= new Array('black', 'skyblue', 'royalblue', 'blue', 'darkblue', 'orange', 'orangered', 'crimson', 'red', 'firebrick', 'darkred', 'green', 'limegreen', 'seagreen', 'deeppink', 'tomato', 'coral', 'purple', 'indigo', 'burlywood', 'sandybrown', 'sienna', 'chocolate', 'teal', 'silver')
	var styles	= new Array('color: black;', 'color: skyblue;', 'color: royalblue;', 'color: blue;', 'color: darkblue;', 'color: orange;', 'color: orangered;', 'color: crimson;', 'color: red;', 'color: firebrick;', 'color: darkred;', 'color: green;', 'color: limegreen;', 'color: seagreen;', 'color: deeppink;', 'color: tomato;', 'color: coral;', 'color: purple;', 'color: indigo;', 'color: burlywood;', 'color: sandybrown;', 'color: sienna;', 'color: chocolate;', 'color: teal;', 'color: silver;')
	
	/* Create our select menu */
	draw_select('color', 'color_' + id + 'codex', values, styles, options);
}

/**
 * Function to create font size selector
 */
function create_size_select(id) {
	
	var options = new Array('<?php echo $context->getVar("L_FONT_SIZE"); ?>', '<?php echo $context->getVar("L_FONT_TINY"); ?>', '<?php echo $context->getVar("L_FONT_SMALL"); ?>', '<?php echo $context->getVar("L_FONT_NORMAL"); ?>', '<?php echo $context->getVar("L_FONT_LARGE"); ?>', '<?php echo $context->getVar("L_FONT_HUGE"); ?>')
	var values	= new Array(12, 7, 9, 12, 18, 24)
	var styles	= new Array('font-size: auto;', 'font-size: 8px;', 'font-size: 9px;', 'font-size: 12px;')
	
	/* Create our select menu */
	draw_select('size', 'size_' + id + 'codex', values, styles, options);
}

/**
 * Function to close all open bbcode tags
 */
function close_tags_button(id) {
	document.write('&nbsp;&nbsp;<a href="javascript:;" title="<?php echo $context->getVar("L_BBCODE_CLOSE_TAGS"); ?>" onclick="bbcodex_close_tags(\'' + id + 'codex\')" onmouseover="bbcodex_helpline(\'a\', \'closetags_' + id + 'codex\')"><?php echo $context->getVar("L_BBCODE_CLOSE_TAGS"); ?></a>');
}