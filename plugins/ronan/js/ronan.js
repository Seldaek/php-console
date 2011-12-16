$(document).ready(function() {

    var notify = function(msg){
        alert(msg);// ... jQuery notification, anyone ?
    }

    var checkPhpCode = function(e){
        var url = "./plugins/ronan/php/ronan.php";
        var code = $.trim($('textarea[name="code"]').val());
        if('' != $.trim(code)){
            code = "<?php "  + code + " ?>";
            $.post(url, {'code':code}, function(result) {
                notify(result);
            });
        } else {
            notify('Nothing to check');
        }
    }

    shortcut.add("Ctrl+Shift+L",function() {
        checkPhpCode();
    });


    $('.Btn').live('click', function(e){
        checkPhpCode();
        e.preventDefault();
    });

    var button = "<button class='ronanBtn' title='keyboard shortcut: SuperKey '>check PHP code</button>";
    $('input[type=submit]').before(button);

});
