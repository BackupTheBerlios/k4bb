<?php

if(isset($dba) && isset($css)) {
	$dba->Query("INSERT INTO ". STYLES ." (name, description) VALUES ('Descent Liquid', 'Liquid Descent theme for the k4 Bulletin Board.')");

	$styleset = $dba->GetValue("SELECT MAX(id) FROM ". STYLES );

	$css->AddClass("*", "font-family: verdana, geneva, lucida, arial, helvetica, sans-serif;font-size: 12px;", $styleset, "This applies to every tag.");
	$css->AddClass(".alt1", "background-color: #f7f7f7;color: #000000;", $styleset, "This goes for some of the lighter background colored things.");
	$css->AddClass(".alt2", "background-color: #dedfdf;color: #000000;", $styleset, "This goes for some of the darker background colored things.");
	$css->AddClass(".alt3", "background-color: #E4E7F5;color: #000000;", $styleset, "This goes for some of the darkest background colored things.");
	$css->AddClass(".answer", "border: 1px solid #999999;list-style-type: none;background-color: #FFFFFF;padding: 5px;top:-2px;position: relative;width: 90%;text-align: left;", $styleset, "What an answer looks like in the FAQ section.");
	$css->AddClass(".button", "font-size: 11px;font-family: verdana, geneva, lucida, arial, helvetica, sans-serif;", $styleset, "This applies to all form buttons.");
	$css->AddClass(".cat_name", "cursor: pointer;cursor: hand;", $styleset, "What a category looks like on the FAQ.");
	$css->AddClass(".color_picker", "position: absolute;top: 1em;left: 0;", $styleset, "Another class added to the color picker.");
	$css->AddClass(".colorpicker", "border: 1px solid black;", $styleset, "What the general color picker looks like.");
	$css->AddClass(".colorpicker td", "width: 10px;height: 10px;max-width: 10px;max-height: 10px;cursor: hand;cursor: pointer;", $styleset, "This goes for all the colors in the color picker.");
	$css->AddClass(".forum_base", "width: 99%;", $styleset, "This is the table which surounds the entire forum.");
	$css->AddClass(".forum_base table", "background-color: #FFFFFF;", $styleset, "This just applies a white background to tables within the forum.");
	$css->AddClass(".forum_footer", "padding-left: 10px; padding-right: 10px; padding-bottom: 10px; background-color: #FFFFFF;", $styleset, "The footer stuff in the forum, not including contact info, etc.");
	$css->AddClass(".forum_header", "padding-left: 10px; padding-right: 10px; background-color: #FFFFFF;", $styleset, "The header, not including logo image");
	$css->AddClass(".forum_main", "padding-left: 10px; padding-right: 10px; background-color: #FFFFFF;", $styleset, "The main body part of the forum.");
	$css->AddClass(".hidden", "display: none;", $styleset, "A category in the Advanced CSS editor which is hidden.");
	$css->AddClass(".inputbox", " border : 1px solid #999999;font-size:11px;", $styleset, "This applies to all of the inputfields.");
	$css->AddClass(".inputbox:focus", "border : 1px solid #666666;font-size:11px;", $styleset, "This only works in Mozilla browsers. It makes all input fields wit hthis class, when clicked, have a highlighted border.");
	$css->AddClass(".inputfailed", "border: 1px solid #FF0000;font-size:11px;background-color:#FFEEFF;", $styleset, "This is for failed input fields. It''s only toggled by JavaScript.");
	$css->AddClass(".inputnone", "background-color: #E4E7F5;font-size:11px;text-decoration: underline;border: 0px;color: #003366;", $styleset, "This is for listing the attachments.");
	$css->AddClass(".logo_header td", "background-color: #E1E1E2;margin:0px 0px 0px 0px;padding: 0px 0px 0px 0px;", $styleset, "This applies to the area which contains the k4 (or yours if set) logo.");
	$css->AddClass(".minitext, .minitext *", "font-size:10px; padding:0px;text-decoration: none;", $styleset, "This is the smallest text, and this applies to all elements within the smalltext.");
	$css->AddClass(".outset_box", "border: 2px outset;background-color: #f7f7f7;padding: 10px;", $styleset, "This goes for the white box with outset borders.");
	$css->AddClass(".pagination", "font-size:10px;background-color: #5C7099;border: 1px solid #363636;", $styleset, "The thing that goes around the pagination box.");
	$css->AddClass(".pagination td", "padding: 3px;border:0px;font-size:10px;color: #FFFFFF;", $styleset, "Table columns within the pagination boxes.");
	$css->AddClass(".panel", "background-color: #E4E7F5;color: #000000;padding: 10px;border: outset 2px;", $styleset, "This is the main light background colored region, and when put on a table column directly; applies an outset border.");
	$css->AddClass(".panelsurround", "background-color: #D5D8E5;color: #000000;", $styleset, "I don''t remember.");
	$css->AddClass(".question", "list-style-type: none;margin: 0px;background-color: #fcfcfc;padding: 5px;border: 1px solid #999999;color: #000000;font-size: 11px;width: 90%;margin-bottom: 1px;cursor: pointer;cursor: hand;", $styleset, "What a question looks like in the FAQ section.");
	$css->AddClass(".quote", "border: 1px solid #999999;", $styleset, "This is for the bbcode quote elements.");
	$css->AddClass(".quote *", "font-size: 11px;color: #666666;", $styleset, "This applies to all quote elemenets, and all elements inside quotes.");
	$css->AddClass(".quote legend", "font-weight: bold;color: #333333;", $styleset, "This applies to the quote''s legend.");
	$css->AddClass(".smalltext, .smalltext *", "font-size:11px; padding:0px;color: #000000; text-decoration: none;", $styleset, "This is the second smallest text, and this applies to all elements within the minitext.");
	$css->AddClass(".special_panel", "background-color: #FFDDDD;color: #000000;padding: 10px;border: outset 2px;", $styleset, "Shows on suspended forums/categories.");
	$css->AddClass(".tcat a:hover", "color: #FFFF66;text-decoration: underline;", $styleset, "This applies to all links in the main header regions when you hover your mouse over them.");
	$css->AddClass(".tcat a:link, .tcat a:visited, .tcat a:active", "color: #FFFFFF;text-decoration: none;", $styleset, "This applies to all links within the main header regions, on the default template, they are black.");
	$css->AddClass(".tcat, .tcat td", "background-color: #333333;color: #FFFFFF;font-weight: bold;font-family: verdana, geneva, lucida, arial, helvetica, sans-serif;", $styleset, "This is the main header region.");
	$css->AddClass(".thead", "background-color: #045975;color: #FFFFFF;font-size: 11px;font-weight: bold;font-family: tahoma, verdana, geneva, lucida, arial, helvetica, sans-serif;", $styleset, "This is the secondary header region.");
	$css->AddClass(".thead a", "color: #FFFFFF;", $styleset, "This applies to all links within the secondary header regions.");
	$css->AddClass(".threaded_off td", "background-color: #FEFEFE;padding: 0px;margin:0px;color: #000000;", $styleset, "This goes for the table rows in the threaded and hybrid thread views when they are not selected.");
	$css->AddClass(".threaded_on, .threaded_on td", "background-color: #CCCCCC;padding: 0px;margin:0px;height: 20px;", $styleset, "This goes for the table rows in the threaded and hybrid thread views when you select one of them.");
	$css->AddClass(".visible", "display: block;border: 1px solid #BDD786;padding: 3px;width: 95%;", $styleset, "A category in the Advanced CSS editor which is visible.");
	$css->AddClass("a", "color: #000000;text-decoration: none;", $styleset, "This applies to every link.");
	$css->AddClass("a:hover", "text-decoration: underline;", $styleset, "This applies to every link when you hover your mouse over them.");
	$css->AddClass("body", "background-color: #E1E1E2; padding: 0px 0px 0px 0px; margin: 0px 0px 0px 0px;", $styleset, "This sets the body of the page''s properties.");
	$css->AddClass("fieldset", "border: 1px solid #003366;padding: 5px;margin: 3px;", $styleset, "This applies to every fieldset. (those cool table like things with a name which intersects the top border.");
	$css->AddClass("form", "margin:0px 0px 0px 0px;padding: 0px 0px 0px 0px;", $styleset, "This applies to every form.");
	$css->AddClass("h1", "margin: 0px;padding: 0px;font-size: 20px;", $styleset, "This applies to every h1 element.");
	$css->AddClass("legend", "color: #22229C;font: 11px tahoma, verdana, geneva, lucida, arial, helvetica, sans-serif;", $styleset, "This applies to the names within the top border of all fieldsets.");
	$css->AddClass("td", "padding: 3px;", $styleset, "This applies to every table column.");
	$css->AddClass("td, th, p, li", "font-size: 10pt;font-family: verdana, geneva, lucida, arial, helvetica, sans-serif;", $styleset, "This applies to all table headers, table columns, paragraphs and list items simultaneously.");
	$css->AddClass("td.thead, div.thead", "padding: 4px;", $styleset, "This applies to all secondary header divs and table columns.");
	
} else {
	echo 'The $dba and $css variables have not been set.';}
?>