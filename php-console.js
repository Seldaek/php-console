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
(function() {

    var updateStatusBar, handleKeyPress, options, editor;

    options = {
        tabsize: 4,
        editor: 'editor'
    };

    /**
     * updates the text of the status bar
     */
    updateStatusBar = function(e) {
        var cursor_position = editor.getCursorPosition();
        $('.statusbar').text('Line: ' + (1+cursor_position.row) + ', Column: ' + cursor_position.column);
    };

    /**
     * adds a toggle button to expand/collapse all krumo sub-trees at once
     */
    refreshKrumoState = function() {
        if ($('.krumo-expand').length > 0) {
            $('<a class="expand" href="#">Toggle all</a>')
                .click(function(e) {
                    $('div.krumo-element.krumo-expand').each(function(idx, el) {
                        krumo.toggle(el);
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
        $('div.output').html('<img src="loader.gif" class="loader" alt="" /> Loading ...');

        $.post('?js=1', { code: editor.getSession().getValue() }, function(res) {
            if (res.match(/#end-php-console-output#$/)) {
                $('div.output').html(res.substring(0, res.length-24));
            } else {
                $('div.output').html(res + "<br /><br /><em>Script ended unexpectedly.</em>");
            }
            refreshKrumoState();
        });
    };

    initializeAce = function() {
        editor = ace.edit(options.editor);

        // set mode
        var PhpMode = require("ace/mode/php").Mode;
        editor.getSession().setMode(new PhpMode());

        // tab size
        editor.getSession().setTabSize(options.tabsize);

        // events
        editor.getSession().selection.on('changeCursor', updateStatusBar);

        // commands
        editor.commands.addCommand({
            name: 'submitForm',
            bindKey: {
                win: 'Ctrl-Enter|Alt-Enter',
                mac: 'Command-Enter|Alt-Enter',
                sender: 'editor'
            },
            exec: function(env, args, request) {
                $('form').submit();
            }
        });
    };


    $.console = function(settings) {
        $.extend(options, settings);

        $(function() {
            $(document).ready(initializeAce);

            $('form').submit(handleSubmit);
/*
            // set the focus back to the textarea if pressing tab moved
            // the focus to the submit button (opera bug)
            $('input[name="subm"]').keyup(function(e) {
                if (e.keyCode === 9) {
                    $('textarea[name="code"]').focus();
                }
            });
            */
        });
    };
}());