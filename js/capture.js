/**
 * PhantomJS routines for printable table snapshot generation
 */
var page = require('webpage').create(),
    system = require('system');
    page.onConsoleMessage = function(msg, lineNum, sourceId) {
     console.log('CONSOLE: ' + msg + ' (from line #' + lineNum + ' in "' + sourceId + '")');
    };    
    page.open(system.args[1], function (status) {
    page.viewportSize = { width:1700, height:900 };
    var clipRect = page.evaluate(function () { return document.querySelector(".table").getBoundingClientRect(); });
    page.clipRect ={
                    width: parseInt(clipRect.width)+30,
                    height: parseInt(clipRect.height)+180,
                    top:clipRect.top-50,
                    left:0
                    };
    page.render(system.args[2])
    phantom.exit();
});
