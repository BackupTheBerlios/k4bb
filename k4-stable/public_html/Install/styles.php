<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     styles.php
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

error_reporting(E_STRICT | E_ALL);

/*
---------------------------------------------------
		 INSTALL the k4 "Descent" Style Set
---------------------------------------------------
*/

AddClass("*", "font-family: verdana, geneva, lucida, arial, helvetica, sans-serif;font-size: 12px;", 1, "This applies to every tag.", $db);
AddClass(".alt1", "background-color: #f7f7f7;color: #000000;", 1, "This goes for some of the lighter background colored things.", $db);
AddClass(".alt2", "background-color: #dedfdf;color: #000000;", 1, "This goes for some of the darker background colored things.", $db);
AddClass(".alt3", "background-color: #E4E7F5;color: #000000;", 1, "This goes for some of the darkest background colored things.", $db);
AddClass(".answer", "border: 1px solid #999999;list-style-type: none;background-color: #FFFFFF;padding: 5px;top:-2px;position: relative;width: 90%;text-align: left;", 1, "What an answer looks like in the FAQ section.", $db);
AddClass(".button", "font-size: 11px;font-family: verdana, geneva, lucida, arial, helvetica, sans-serif;", 1, "This applies to all form buttons.", $db);
AddClass(".cat_name", "cursor: pointer;cursor: hand;", 1, "What a category looks like on the FAQ.", $db);
AddClass(".color_picker", "position: absolute;top: 1em;left: 0;", 1, "Another class added to the color picker.", $db);
AddClass(".colorpicker", "border: 1px solid black;", 1, "What the general color picker looks like.", $db);
AddClass(".colorpicker td", "width: 10px;height: 10px;max-width: 10px;max-height: 10px;cursor: hand;cursor: pointer;", 1, "This goes for all the colors in the color picker.", $db);
AddClass(".forum_base", "width: 770px;", 1, "This is the table which surounds the entire forum.", $db);
AddClass(".forum_base table", "background-color: #FFFFFF;", 1, "This just applies a white background to tables within the forum.", $db);
AddClass(".forum_footer", "padding-left: 10px; padding-right: 10px; padding-bottom: 10px; background-color: #FFFFFF;", 1, "The footer stuff in the forum, not including contact info, etc.", $db);
AddClass(".forum_header", "padding-left: 10px; padding-right: 10px; background-color: #FFFFFF;", 1, "The header, not including logo image", $db);
AddClass(".forum_main", "padding-left: 10px; padding-right: 10px; background-color: #FFFFFF;", 1, "The main body part of the forum.", $db);
AddClass(".hidden", "display: none;", 1, "A category in the Advanced CSS editor which is hidden.", $db);
AddClass(".inputbox", " border : 1px solid #999999;font-size:11px;", 1, "This applies to all of the inputfields.", $db);
AddClass(".inputbox:focus", "border : 1px solid #666666;font-size:11px;", 1, "This only works in Mozilla browsers. It makes all input fields wit hthis class, when clicked, have a highlighted border.", $db);
AddClass(".inputfailed", "border: 1px solid #FF0000;font-size:11px;background-color:#FFEEFF;", 1, "This is for failed input fields. It''s only toggled by JavaScript.", $db);
AddClass(".inputnone", "background-color: #E4E7F5;font-size:11px;text-decoration: underline;border: 0px;color: #003366;", 1, "This is for listing the attachments.", $db);
AddClass(".logo_header td", "background-color: #E1E1E2;margin:0px 0px 0px 0px;padding: 0px 0px 0px 0px;", 1, "This applies to the area which contains the k4 (or yours if set) logo.", $db);
AddClass(".minitext, .minitext *", "font-size:10px; padding:0px;text-decoration: none;", 1, "This is the smallest text, and this applies to all elements within the smalltext.", $db);
AddClass(".outset_box", "border: 2px outset;background-color: #f7f7f7;padding: 10px;", 1, "This goes for the white box with outset borders.", $db);
AddClass(".pagination", "font-size:10px;background-color: #5C7099;border: 1px solid #363636;", 1, "The thing that goes around the pagination box.", $db);
AddClass(".pagination td", "padding: 3px;border:0px;font-size:10px;color: #FFFFFF;", 1, "Table columns within the pagination boxes.", $db);
AddClass(".panel", "background-color: #E4E7F5;color: #000000;padding: 10px;border: outset 2px;", 1, "This is the main light background colored region, and when put on a table column directly; applies an outset border.", $db);
AddClass(".panelsurround", "background-color: #D5D8E5;color: #000000;", 1, "I don''t remember.", $db);
AddClass(".question", "list-style-type: none;margin: 0px;background-color: #fcfcfc;padding: 5px;border: 1px solid #999999;color: #000000;font-size: 11px;width: 90%;margin-bottom: 1px;cursor: pointer;cursor: hand;", 1, "What a question looks like in the FAQ section.", $db);
AddClass(".quote", "border: 1px solid #999999;", 1, "This is for the bbcode quote elements.", $db);
AddClass(".quote *", "font-size: 11px;color: #666666;", 1, "This applies to all quote elemenets, and all elements inside quotes.", $db);
AddClass(".quote legend", "font-weight: bold;color: #333333;", 1, "This applies to the quote''s legend.", $db);
AddClass(".smalltext, .smalltext *", "font-size:11px; padding:0px;color: #000000; text-decoration: none;", 1, "This is the second smallest text, and this applies to all elements within the minitext.", $db);
AddClass(".special_panel", "background-color: #FFDDDD;color: #000000;padding: 10px;border: outset 2px;", 1, "Shows on suspended forums/categories.", $db);
AddClass(".tcat a:hover", "color: #FFFF66;text-decoration: underline;", 1, "This applies to all links in the main header regions when you hover your mouse over them.", $db);
AddClass(".tcat a:link, .tcat a:visited, .tcat a:active", "color: #FFFFFF;text-decoration: none;", 1, "This applies to all links within the main header regions, on the default template, they are black.", $db);
AddClass(".tcat, .tcat td", "background-color: #333333;color: #FFFFFF;font-weight: bold;font-family: verdana, geneva, lucida, arial, helvetica, sans-serif;", 1, "This is the main header region.", $db);
AddClass(".thead", "background-color: #045975;color: #FFFFFF;font-size: 11px;font-weight: bold;font-family: tahoma, verdana, geneva, lucida, arial, helvetica, sans-serif;", 1, "This is the secondary header region.", $db);
AddClass(".thead a", "color: #FFFFFF;", 1, "This applies to all links within the secondary header regions.", $db);
AddClass(".threaded_off td", "background-color: #FEFEFE;padding: 0px;margin:0px;color: #000000;", 1, "This goes for the table rows in the threaded and hybrid thread views when they are not selected.", $db);
AddClass(".threaded_on, .threaded_on td", "background-color: #CCCCCC;padding: 0px;margin:0px;height: 20px;", 1, "This goes for the table rows in the threaded and hybrid thread views when you select one of them.", $db);
AddClass(".visible", "display: block;border: 1px solid #BDD786;padding: 3px;width: 95%;", 1, "A category in the Advanced CSS editor which is visible.", $db);
AddClass("a", "color: #000000;text-decoration: none;", 1, "This applies to every link.", $db);
AddClass("a:hover", "text-decoration: underline;", 1, "This applies to every link when you hover your mouse over them.", $db);
AddClass("body", "background-color: #E1E1E2; padding: 0px 0px 0px 0px; margin: 0px 0px 0px 0px;", 1, "This sets the body of the page''s properties.", $db);
AddClass("fieldset", "border: 1px solid #003366;padding: 5px;margin: 3px;", 1, "This applies to every fieldset. (those cool table like things with a name which intersects the top border.", $db);
AddClass("form", "margin:0px 0px 0px 0px;padding: 0px 0px 0px 0px;", 1, "This applies to every form.", $db);
AddClass("h1", "margin: 0px;padding: 0px;font-size: 20px;", 1, "This applies to every h1 element.", $db);
AddClass("legend", "color: #22229C;font: 11px tahoma, verdana, geneva, lucida, arial, helvetica, sans-serif;", 1, "This applies to the names within the top border of all fieldsets.", $db);
AddClass("td", "padding: 3px;", 1, "This applies to every table column.", $db);
AddClass("td, th, p, li", "font-size: 10pt;font-family: verdana, geneva, lucida, arial, helvetica, sans-serif;", 1, "This applies to all table headers, table columns, paragraphs and list items simultaneously.", $db);
AddClass("td.thead, div.thead", "padding: 4px;", 1, "This applies to all secondary header divs and table columns.", $db);

?>