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
(function(require, $, ace) {
    "use strict";

    var updateStatusBar, prepareClippyButton, refreshKrumoState, handleSubmit, initializeAce,
        options, editor;
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
        var PhpMode, code;

        code = $('#' + options.editor).text();
        $('#' + options.editor).replaceWith('<div id="'+options.editor+'" class="'+options.editor+'"></div>');
        $('#' + options.editor).text(code);

        editor = ace.edit(options.editor);

        editor.focus();
        editor.gotoLine(3,0);

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
        
        /* 
        Make sure the .option checkboxes are selected when the page first loads.
        The browser may preserve the checkbox's last state before page reload without this.
        */
        var checkboxes = $(".option").find(":checkbox");
        checkboxes.each(function(){
            $(this).get()[0].checked = true;
        });
        
        /*
        The click event should respond to the checkbox changing state regardless
        of the method of changing it (click, touch, keyboard, etc).
        */
        $(".option").click(function(event){
            // The checkbox being toggled
            var input = $(this).find("input")[0];           
            
            /* 
            Toggle the checkbox if clicking on .option, but rely on the
            checkbox's default behavior if it is the actual target.
            */
            if(event.target !== input){
                input.checked = !input.checked;
            }
            
			// Change the appearance depending on the selected state
            if(input.checked){              
                $(this).addClass("selected");
            } else {
                $(this).removeClass("selected");
            }
            
			// Determine which option was toggled
            if($(input).attr("id") === "behaviours"){
                editor.setBehavioursEnabled(input.checked);
            } else if($(input).attr("id") === "widgets"){
                editor.setShowFoldWidgets(input.checked)
            }
            
			// Give focus to the editor
            editor.focus();
        });
        
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
}(ace.require, jQuery, ace));
