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
$(function() {

    var updateStatusBar;

    // updates the text of the status bar
    updateStatusBar = function() {
        var caret, part, matches;
        caret = $('textarea[name="code"]').getCaret();
        part = $('textarea[name="code"]').val().substring(0, caret);
        matches = part.match(/(\r?\n)?([^\r\n]*)/g);
        part = matches.length > 1 ? matches[matches.length - 2] : matches[0];
        $('.statusbar').text('Line: ' + Math.max(1, matches.length-1) + ', Column: ' + (matches.length > 2 ? part.length : part.length + 1));
    };

    $('textarea[name="code"]')
        .keydown(function(e) {
            var caret, part, matches;
            switch(e.keyCode) {
            case 9:
                // add 4 spaces when tab is pressed
                e.preventDefault();
                $(this).injectText("    ");
                break;
            case 13:
                // submit form on ctrl-enter or alt-enter
                if (e.metaKey || e.altKey) {
                    e.preventDefault();
                    $('form').submit();
                    return;
                }

                // indent automatically the new lines
                // skip because buggy in opera until they fix the preventDefault bug
                if ($.browser.opera) {
                    return;
                }
                caret = $(this).getCaret();
                part = $(this).val().substring(0, caret);
                matches = part.match(/(\r?\n +)[^\r\n]*$/);
                if (matches) {
                    $(this).val(function(idx, val) {
                        return val.substring(0, caret) + matches[1] + val.substring(caret);
                    });
                    $(this).setCaret(caret + matches[1].length);
                    e.preventDefault();
                }
                break;
            }

            updateStatusBar();
        })
        .keyup(updateStatusBar)
        .click(updateStatusBar)
        .focus();

    updateStatusBar();

    $('input[name="subm"]').keyup(function(e) {
        // set the focus back to the textarea if pressing tab moved
        // the focus to the submit button (opera bug)
        if (e.keyCode === 9) {
            $('textarea[name="code"]').focus();
        }
    });

    $('form').submit(function(e){
        e.preventDefault();
        $('div.output').html('<img src="loader.gif" class="loader" alt="" /> Loading ...');
        $.post('?js=1', $(this).serializeArray(), function(res) {
            if (res.match(/#end-php-console-output#$/)) {
                $('div.output').html(res.substring(0, res.length-24));
            } else {
                $('div.output').html(res + "<br /><br /><em>Script ended unexpectedly.</em>");
            }
        });
    });

    // adds a toggle button to expand/collapse all krumo sub-trees at once
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
});
