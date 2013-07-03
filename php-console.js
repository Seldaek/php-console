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
            'NUL' : /\x00/g,
            'SOH' : /\x01/g,
            'STX' : /\x02/g,
            'ETX' : /\x03/g,
            'EOT' : /\x04/g,
            'ENQ' : /\x05/g,
            'ACK' : /\x06/g,
            'BEL' : /\x07/g,
            'BS'  : /\x08/g,
            'SUB' : /\x1A/g,
        };

        // eval server-side
        $.post('?js=1', { code: editor.getSession().getValue() }, function (res) {
            if (res.match(/#end-php-console-output#$/)) {
                var result = res.substring(0, res.length - 24);
                for (var k in controlChars) {
                    result = result.replace(controlChars[k], '<span class="control-char">'+ k +'</span>');
                }
                $('div.output').html(result);
            } else {
                $('div.output').html(res + "<br /><br /><em>Script ended unexpectedly.</em>");
            }
            refreshKrumoState();
        });
    };

    handleAjaxError = function (event, jqxhr, settings, exception) {
        $('div.output').html("<em>Error occured while posting your code.</em>");
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
        editor.getSession().setTabSize(options.tabsize);
        editor.getSession().setUseSoftTabs(true);

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
