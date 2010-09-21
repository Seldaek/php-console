/**
 * jQuery Selections plugin
 *
 * Provides text input utilities
 *
 * Note that most functions act only on the first of
 * the given elements, since browsers can only have
 * one in focus at a time
 *
 * Copyright (C) 2010, Jordi Boggiano
 * http://seld.be/ - j.boggiano@seld.be
 *
 * Licensed under the new BSD License
 * See the LICENSE file for details
 *
 * Source on Github http://github.com/Seldaek/jquery-selections
 */
(function($) {
    /**
     * sets the caret position
     *
     * @param int index
     * @see $.fn.setSelection
     */
    $.fn.setCaret = function(index) {
        this.setSelection(index, index);
    };

    /**
     * returns the caret position, or 0 if the element has no caret
     *
     * @return int
     */
    $.fn.getCaret = function() {
        var elem, range, elemRange, elemRangeCopy, value, caret, pos, match;
        elem = this.get(0);

        value = $(elem).val();

        // standard browsers
        if (elem.selectionStart) {
            caret = elem.selectionStart;
        } else if (document.selection) {
            // old IE handling
            elem.focus();
            range = document.selection.createRange();

            // handle input texts
            if (elem.nodeName === 'INPUT') {
                elemRange = elem.createTextRange();
                elemRangeCopy = elemRange.duplicate();
                elemRange.moveToBookmark(range.getBookmark());
                elemRangeCopy.setEndPoint('EndToStart', elemRange);
                caret = elemRangeCopy.text.length;
            } else {
                // handle textareas
                elemRangeCopy = range.duplicate();
                elemRangeCopy.moveToElementText(elem);

                pos = 0;
                if (range.text.length > 1) {
                    pos = Math.max(0, pos - range.text.length);
                }

                caret = -1 + pos;
                elemRangeCopy .moveStart('character', pos);

                while (elemRangeCopy.inRange(range)) {
                    elemRangeCopy.moveStart('character');
                    caret++;
                }
            }
        } else {
            caret = 0;
        }

        if ($.browser.opera) {
            match = value.replace(/\r?\n/g, "\r\n").substr(0, caret).match(/\r\n/g);
            if (match) {
                caret -= match.length;
            }
        }

        return caret;
    };

    /**
     * selects a range of text
     *
     * @param int start index of the beginning of the selection
     * @param int end index of the end of the selection
     * @return object chainable
     */
    $.fn.setSelection = function(start, end) {
        var elem, range, match;
        elem = this.get(0);
        end = end || start;

        // standard browsers
        if (elem.setSelectionRange) {
            elem.focus();

            if ($.browser.opera) {
                match = this.val().replace(/\r?\n/g, "\n").substr(0, start).match(/\n/g);
                if (match) {
                    start += match.length;
                }
                match = this.val().replace(/\r?\n/g, "\n").substr(0, end).match(/\n/g);
                if (match) {
                    end += match.length;
                }
            }
            elem.setSelectionRange(start, end);
        } else if (elem.createTextRange) {
            // old IE handling
            range = elem.createTextRange();
            range.collapse(true);
            range.moveEnd('character', end);
            range.moveStart('character', start);
            range.select();
        }

        return this;
    };

    /**
     * reads the text the user selected
     *
     * @param int start index of the beginning of the selection
     * @param int end index of the end of the selection
     * @return string
     */
    $.fn.getSelectedText = function() {
        var elem = this.get(0);

        // standard browsers
        if (elem.selectionStart) {
            return elem.value.substring(elem.selectionStart, elem.selectionEnd);
        }

        // old IE
        if (document.selection) {
            elem.focus();
            return document.selection.createRange().text;
        }

        return '';
    };

    /**
     * injects the given text in place of the current selection
     *
     * @param string text
     * @return object chainable
     */
    $.fn.replaceSelection = function(text) {
        var elem = this.get(0);

        // standard browsers
        if (elem.selectionStart) {
            elem.value = elem.value.substr(0, elem.selectionStart) + text + elem.value.substr(elem.selectionEnd);
            return this;
        }

        // old IE
        if (document.selection) {
            elem.focus();
            document.selection.createRange().text = text;
            return this;
        }

        return this;
    };

    /**
     * injects the given text at the current caret position
     *
     * @param string text
     * @return object chainable
     */
    $.fn.injectText = function(text) {
        var $elem, caret;
        $elem = this.first();
        caret = $elem.getCaret();
        $elem.val(function(idx, val) {
            return val.substring(0, caret) + text + val.substring(caret);
        });
        $elem.setCaret(caret + text.length);

        return this;
    };
}(jQuery));
