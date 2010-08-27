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
                if (matches = part.match(/(\r?\n +)[^\r\n]*$/)) {
                    $(this).val(function(idx, val) {
                        return val.substring(0, caret) + matches[1] + val.substring(caret);
                    });
                    $(this).setCaret(caret + matches[1].length);
                    e.preventDefault();
                }
                break;
            }
        })
        .focus();

    $('input[name="subm"]').keyup(function(e) {
        // set the focus back to the textarea if pressing tab moved
        // the focus to the submit button (opera bug)
        if (e.keyCode === 9) {
            $('textarea[name="code"]').focus();
        }
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
