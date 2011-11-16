var ronanKeyNumber = 91; // SuperKey, the one with the (tm) Windows logo

$(document).ready(function() {

    var ronanNotify = function(msg){
        alert(msg);// ... jQuery notification, anyone ?
    }

    var ronanCheckPhpCode = function(e){
        var url = "./plugins/ronan/php/ronan.php";
        var code = $.trim($('textarea[name="code"]').val());
        if('' != $.trim(code)){
            code = "<?php "  + code + " ?>";
            $.post(url, {'code':code}, function(result) {
                ronanNotify(result);
            });
        } else {
            ronanNotify('Nothing to check');
        }
    }

    var ronanHandleKeyPress = function(e) {
        if(ronanKeyNumber == e.keyCode) {
            ronanCheckPhpCode();
        }
    }

    $('.ronanBtn').live('click', function(e){
        ronanCheckPhpCode();
        e.preventDefault();
    });

    var button = "<button class='ronanBtn' title='keyboard shortcut: SuperKey '>check PHP code</button>";
    $('input[type=submit]').before(button);
    if ($.browser.opera) {
        $('body').keypress(ronanHandleKeyPress);
    } else {
        $('body').keydown(ronanHandleKeyPress);
    }

});
