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

var editor;
(function(require, $, ace) {
    "use strict";

    var updateStatusBar, prepareClippyButton, refreshKrumoState, handleSubmit, initializeAce,
        options;
    options = {
        tabsize: 4,
        editor: 'editor'
    };

    /**
     * updates the text of the status bar
     */
    updateStatusBar = function(e) {
        var cursor_position = editor.getCursorPosition();
        $('.statusbar .position').text('Line: ' + (1+cursor_position.row) + ', Column: ' + cursor_position.column);
    };

    /**
     * prepares a clippy button for clipboard access
     */
    prepareClippyButton = function(e) {
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
    refreshKrumoState = function() {
        if ($('.krumo-expand').length > 0) {
            $('<a class="expand" href="#">Toggle all</a>')
                .click(function(e) {
                    $('div.krumo-element.krumo-expand').each(function(idx, el) {
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
    handleSubmit = function(e) {
        e.preventDefault();
        $('div.output').html('<img src="../assets/img/loader.gif" class="loader" alt="" /> Loading ...');

        var params = '?js=1&' + window.location.href.slice(window.location.href.indexOf('?') + 1) + '&isAdmin=' + ($('#run_as_admin').is(':checked') ? '0' : '1');

        $.post(params, { code: editor.getSession().getValue() }, function(res) {
            if (res.match(/#end-php-console-output#$/)) {
                $('div.output').html('<pre class="prettyprint linenums">' + res.substring(0, res.length-24) + '</pre>');
            } else {
                $('div.output').html(res + "<br /><br /><em>Script ended unexpectedly.</em>");
            }
            refreshKrumoState();
        });
    };

    initializeAce = function() {
        var PhpMode, code;

        code = $('#' + options.editor).text();
        $('#' + options.editor)
            .replaceWith('<div id="'+options.editor+'" class="'+options.editor+'"></div>')
            .text(code);

        editor = ace.edit(options.editor);

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

        // commands
        editor.commands.addCommand({
            name: 'submitForm',
            bindKey: {
                win: 'Ctrl-Return|Alt-Return',
                mac: 'Command-Return|Alt-Return'
            },
            exec: function(editor) {
                $('form').submit();
            }
        });
    };

    $.console = function(settings) {
        $.extend(options, settings);

        $(function() {
            $(document).ready(initializeAce);

            $('form').submit(handleSubmit);
        });
    };
}(require, jQuery, ace));
