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
        this.setSelection(index);
    }

    /**
     * returns the caret position, or 0 if the element has no caret
     *
     * @return int
     */
    $.fn.getCaret = function() {
        var elem, range, elemRange, elemRangeCopy;
        elem = this.get(0);

        if (elem.selectionStart) {
            return elem.selectionStart;
        } else if (document.selection) {
            elem.focus();

            range = document.selection.createRange();
            if (range === null) {
                return 0;
            }

            elemRange = elem.createTextRange(),
            elemRangeCopy = elemRange.duplicate();
            elemRange.moveToBookmark(range.getBookmark());
            elemRangeCopy.setEndPoint('EndToStart', elemRange);

            return elemRangeCopy.text.length;
        }

        return 0;
    };

    /**
     * selects a range of text
     *
     * @param int start index of the beginning of the selection
     * @param int end index of the end of the selection
     * @return object chainable
     */
    $.fn.setSelection = function(start, end) {
        var elem, range;
        elem = this.get(0);
        end = end || start;

        if (elem.setSelectionRange) {
            elem.focus();
            elem.setSelectionRange(start, end);
        } else if (elem.createTextRange) {
            range = elem.createTextRange();
            range.collapse(true);
            range.moveEnd('character', end);
            range.moveStart('character', start);
            range.select();
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
    }

}(jQuery));