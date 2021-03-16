/*jslint browser: true */
/*global ace, jQuery */
/**
 * PHP Console
 *
 * A web-based php debug console
 *
 * Copyright (C) 2010, Jordi Boggiano
 * http://seld.be/ - j.boggiano@seld.be
 *
 * Licensed under the new BSD License
 * See the LICENSE file for details
 *
 * Source on Github http://github.com/Seldaek/php-console
 */
(function (require, $, ace) {
    "use strict";

    var updateStatusBar, prepareClippyButton, refreshKrumoState, handleSubmit, initializeAce, handleAjaxError,
        options, editor;
    options = {
        tabsize: 4,
        editor: 'editor'
    };

    /**
     * updates the text of the status bar
     */
    updateStatusBar = function (e) {
        var cursor_position = editor.getCursorPosition();
        $('.statusbar .position').text('Line: ' + (1 + cursor_position.row) + ', Column: ' + cursor_position.column);
    };

    /**
     * prepares a clippy button for clipboard access
     */
    prepareClippyButton = function (e) {
        var selection = editor.getSession().doc.getTextRange(editor.getSelectionRange());
        if (!selection) {
            $('.statusbar .copy').hide();
            return;
        }
        $('#clippy embed').attr('FlashVars', 'text=' + selection);
        $('#clippy param[name="FlashVars"]').attr('value', 'text=' + selection);
        $('.statusbar .copy').html($('.statusbar .copy').html()).show();
    };

    /**
     * adds a toggle button to expand/collapse all krumo sub-trees at once
     */
    refreshKrumoState = function () {
        if ($('.krumo-expand').length > 0) {
            $('<a class="expand" href="#">Toggle all</a>')
                .click(function (e) {
                    $('div.krumo-element.krumo-expand').each(function (idx, el) {
                        window.krumo.toggle(el);
                    });
                    e.preventDefault();
                })
                .prependTo('.output');
        }
    };

    /**
     * does an async request to eval the php code and displays the result
     */
    handleSubmit = function (e) {
        e.preventDefault();
        $('div.output').html('<img src="loader.gif" class="loader" alt="" /> Loading ...');

        // store session
        if (window.localStorage) {
            localStorage.setItem('phpCode', editor.getSession().getValue());
        }

        var controlChars = {
            'NUL' : /\x00/g, // Null char
            'SOH' : /\x01/g, // Start of Heading
            'STX' : /\x02/g, // Start of Text
            'ETX' : /\x03/g, // End of Text
            'EOT' : /\x04/g, // End of Transmission
            'ENQ' : /\x05/g, // Enquiry
            'ACK' : /\x06/g, // Acknowledgment
            'BEL' : /\x07/g, // Bell
            'BS'  : /\x08/g, // Back Space
            'SO'  : /\x0E/g, // Shift Out / X-On
            'SI'  : /\x0F/g, // Shift In / X-Off
            'DLE' : /\x10/g, // Data Line Escape
            'DC1' : /\x11/g, // Device Control 1 (oft. XON)
            'DC2' : /\x12/g, // Device Control 2
            'DC3' : /\x13/g, // Device Control 3 (oft. XOFF)
            'DC4' : /\x14/g, // Device Control 4
            'NAK' : /\x15/g, // Negative Acknowledgement
            'SYN' : /\x16/g, // Synchronous Idle
            'ETB' : /\x17/g, // End of Transmit Block
            'CAN' : /\x18/g, // Cancel
            'EM'  : /\x19/g, // End of Medium
            'SUB' : /\x1A/g, // Substitute
            'ESC' : /\x1B/g, // Escape
            'FS'  : /\x1C/g, // File Separator
            'GS'  : /\x1D/g, // Group Separator
            'RS'  : /\x1E/g, // Record Separator
            'US'  : /\x1F/g  // Unit Separator
        };

        // eval server-side
        $.post('?js=1', { code: editor.getSession().getValue() }, function (res, status, jqXHR) {
            var mem = jqXHR.getResponseHeader("X-Memory-Usage") || "", 
                rendertime = jqXHR.getResponseHeader("X-Rendertime") || "";
            
            if (mem || rendertime) {
                $('.statusbar .runtime-info').text('Memory usage: '+ mem + ' MB, Rendertime: ' + rendertime + 'ms');
            } else {
                $('.statusbar .runtime-info').text('');
            }
            
            if (res.match(/#end-php-console-output#$/)) {                
                var result = res.substring(0, res.length - 24);
                $.each(controlChars, function (identifier, regex) {
                    result = result.replace(regex, '<span class="control-char">' + identifier + '</span>');
                });
                $('div.output').html(result);
            } else {
                $('div.output').html(res + "<br /><br /><em>Script ended unexpectedly.</em>");
            }
            refreshKrumoState();
        });
    };

    handleAjaxError = function (event, jqxhr, settings, exception) {
        $('div.output').html("<em>Error occurred while posting your code.</em>");
        refreshKrumoState();
    };

    initializeAce = function () {
        var PhpMode, code, storedCode;

        code = $('#' + options.editor).text();

        // reload last session
        if (window.localStorage && code.match(/(<\?php)?\s*/)) {
            storedCode = localStorage.getItem('phpCode');
            if (storedCode) {
                code = storedCode;
            }
        }

        $('#' + options.editor).replaceWith('<div id="' + options.editor + '" class="' + options.editor + '"></div>');
        $('#' + options.editor).text(code);

        editor = ace.edit(options.editor);

        editor.focus();
        editor.gotoLine(3, 0);

        // set mode
        PhpMode = require("ace/mode/php").Mode;
        editor.getSession().setMode(new PhpMode());

        // tab size
        if (options.tabsize) {
            editor.getSession().setTabSize(options.tabsize);
            editor.getSession().setUseSoftTabs(true);
        } else {
            editor.getSession().setUseSoftTabs(false);
        }

        // events
        editor.getSession().selection.on('changeCursor', updateStatusBar);
        if (window.navigator.userAgent.indexOf('Opera/') === 0) {
            editor.getSession().selection.on('changeSelection', prepareClippyButton);
        }

        // reset button
        if (window.localStorage) {
            $('.statusbar .reset').on('click', function (e) {
                editor.getSession().setValue('<?php\n\n');
                editor.focus();
                editor.gotoLine(3, 0);
                window.localStorage.setItem('phpCode', '');
                e.preventDefault();
            });
        }
        
        //Save
        // Load and Save functions copied from:
        // https://thiscouldbebetter.wordpress.com/2012/12/18/loading-editing-and-saving-a-text-file-in-html5-using-javascrip/
        
        $('.statusbar .save').on('click', function (e) {
        	var textToWrite = editor.getSession().getValue();
        	var textFileAsBlob = new Blob([textToWrite], {type:'text/plain'});
        	var fileNameToSaveAs = document.getElementById("inputFileNameToSaveAs").value;
        	var downloadLink = document.createElement("a");
        	downloadLink.download = fileNameToSaveAs;
        	downloadLink.innerHTML = "Download File";
    		downloadLink.href = window.URL.createObjectURL(textFileAsBlob);
    		downloadLink.style.display = "none";
    		document.body.appendChild(downloadLink);
        	}

        	downloadLink.click();
        });
        
        
        //Load
        $('.statusbar .load').on('click', function (e) {
        	var fileToLoad = document.getElementById("fileToLoad").files[0];

        	var fileReader = new FileReader();
        	fileReader.onload = function(fileLoadedEvent) 
        	{
        		var textFromFileLoaded = fileLoadedEvent.target.result;
        		editor.getSession().setValue(textFromFileLoaded);
        		localStorage.setItem('phpCode',textFromFileLoaded);
        	};
        	fileReader.readAsText(fileToLoad, "UTF-8");
        });
        

        // commands
        editor.commands.addCommand({
            name: 'submitForm',
            bindKey: {
                win: 'Ctrl-Return|Alt-Return',
                mac: 'Command-Return|Alt-Return'
            },
            exec: function (editor) {
                $('form').submit();
            }
        });
    };

    $.console = function (settings) {
        $.extend(options, settings);

        $(function () {
            $(document).ready(initializeAce);
            $(document).ajaxError(handleAjaxError);

            $('form').submit(handleSubmit);
        });
    };
}(ace.require, jQuery, ace));
