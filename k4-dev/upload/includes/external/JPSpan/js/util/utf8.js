// $Id: utf8.js,v 1.1 2005/05/26 18:41:10 k4st Exp $
// Currently unused but adding it as util
// From http://aktuell.de.selfhtml.org/artikel/javascript/utf8b64/utf8.htm
function JPSpan_Util_Utf8(s) {
    var utf8s = '';
    for(var n=0; n<s.length; n++) {
        var c=s.charCodeAt(n);
        if (c<128) {
            utf8s += String.fromCharCode(c);
        } else if((c>127) && (c<2048)) {
            utf8s += String.fromCharCode((c>>6)|192);
            utf8s += String.fromCharCode((c&63)|128);
        } else {
            utf8s += String.fromCharCode((c>>12)|224);
            utf8s += String.fromCharCode(((c>>6)&63)|128);
            utf8s += String.fromCharCode((c&63)|128);
        }
    }
    return utf8s;
}