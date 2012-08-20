<?php

ini_set('display_errors', 1);

$options = array(
    // which string should represent a tab for indentation
    'tabsize' => 4,
);



require_once 'lib/TFSN/Projects.php';
require_once 'lib/TFSN/Get.php';
require_once 'lib/TFSN/CommandLine.php';

$get = new Get();
$params = $get->getParams();

$commandLine = new CommandLine();
$projects = new Projects();


$content = $title = $errors = '';
$content .= '<h3 id="slideToggle"><i id="expand-icon" class="icon-plus-sign"></i>Projects: </h3><div id="expandable" style="display: none">' . $projects->renderProjects() . '</div>';

$siteDirectory = '';
if(array_key_exists('a', $params) && $params['a'] == 'debug' && array_key_exists('site', $params)){
    $site = $params['site'];
    $siteDirectory = $projects->getDirectoryFromSiteName($site);

    $mageFile = $siteDirectory . 'app/Mage.php';
    if(file_exists($mageFile)){
        require_once $mageFile;
        Varien_Profiler::enable();
        Mage::setIsDeveloperMode(true);
        umask(0);
        Mage::app();
        $title = '<img src="' . Mage::getDesign()->getSkinUrl() . Mage::getStoreConfig('design/header/logo_src') .  '" /><br />';
        $title .= (array_key_exists('site', $params) ? $params['site'] . ' (<a target="_blank" href="' . Mage::app()->getStore()->getBaseUrl() . '">' . Mage::app()->getStore()->getBaseUrl() . '</a>)': '');
    }


}
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
if (!in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1'), true)) {
    header('HTTP/1.1 401 Access unauthorized');
    die('ERR/401 Go Away');
}

define('PHP_CONSOLE_VERSION', '1.3.0-dev');
require 'krumo/class.krumo.php';

ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);

$debugOutput = '';

if (isset($_POST['code'])) {
    $code = $_POST['code'];

    if (get_magic_quotes_gpc()) {
        $code = stripslashes($code);
    }

    // if there's only one line wrap it into a krumo() call
    if (preg_match('#^(?!var_dump|echo|print|< )([^\r\n]+?);?\s*$#is', $code, $m) && trim($m[1])) {
        $code = 'krumo('.$m[1].');';
    }

    // replace '< foo' by krumo(foo)
    $code = preg_replace('#^<\s+(.+?);?[\r\n]?$#m', 'krumo($1);', $code);

    // replace newlines in the entire code block by the new specified one
    // i.e. put #\r\n on the first line to emulate a file with windows line
    // endings if you're on a unix box
    if (preg_match('{#((?:\\\\[rn]){1,2})}', $code, $m)) {
        $newLineBreak = str_replace(array('\\n', '\\r'), array("\n", "\r"), $m[1]);
        $code = preg_replace('#(\r?\n|\r\n?)#', $newLineBreak, $code);
    }

    ob_start();
    eval($code);
    $debugOutput = ob_get_clean();

    if (isset($_GET['js'])) {
        header('Content-Type: text/plain');
        echo $debugOutput;
        die('#end-php-console-output#');
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Magento/Zend/PHP Debug Console</title>
    <link rel="stylesheet" type="text/css" href="./assets/styles/styles.css" />
    <link rel="stylesheet" type="text/css" href="./assets/styles/bootstrap.css" />
    <link rel="stylesheet" type="text/css" href="./assets/styles/bootstrap-responsive.css" />
    <link rel="stylesheet" type="text/css" href="./assets/styles/docs.css" />
    <script src="./assets/js/jquery-1.7.1.min.js"></script>
    <script src="./assets/js/jquery-tmpl-min.js"></script>
    <script src="./assets/js/ace/ace.js"></script>
    <script src="./assets/js/ace/mode-php.js"></script>
    <script src="./assets/js/php-console.js"></script>
    <script src="./assets/js/google-code-prettify/prettify.js"></script>
    <script src="./assets/js/storage.js"></script>
    <link rel="stylesheet" type="text/css" href="./assets/js/google-code-prettify/prettify.css" />
    <script>
        $.console({
            tabsize: <?php echo json_encode($options['tabsize']) ?>
        });
    </script>
</head>
<body>
<div class="container">
    <div class="navbar navbar-fixed-top">
        <div class="navbar-inner">
            <div class="container">
                <a class="brand" href="./index.php">TFSN</a>
            </div>
        </div>
    </div>
    <h1><?php echo ($title != '') ? $title . '<a class="" href="./index.php"><i class="icon icon-remove-sign"></i></a>' : ''; ?></h1>
    <div class="row">
        <div class="span6">
            <?php echo $content; ?>
        </div>
        <div class="span6">
            <h3 id="slideToggleSnippets">
                <i id="expand-snippets-icon" class="icon-plus-sign"></i>Snippets: </h3>
            <div id="expandable-snippets" style="display: none">
                <script id="snippetsTemplate" type="text/x-jQuery-tmpl">
                    <ul class="nav nav-pills nav-stacked">
                        <li class="active row">
                            <a
                                class="load-snippet span2"
                                data-project="${snippetProject}"
                                data-label="${snippetLabel}"
                                onClick="TFSN.LocalStorageHelper.checkSnippet(this)"
                                >
                                ${snippetProject}: ${snippetLabel}
                            </a>
                            <i class="preview-snippet icon-plus-sign"></i>
                            <pre class="prettyprint linenums span6" style="display: none;">${snippetCode}</pre>
                        </li>
            </div>
            </script>
        </div>
    </div>
</div>

<div class="output"><pre><?php echo $debugOutput ?></pre></div>
<form id="code-form" method="POST" action="">
    <div class="input">
        <label for="editor"></label>
        <textarea class="editor" id="editor" name="code"></textarea>
        <div class="statusbar">
            <span class="position">Line: 1, Column: 1</span>
                    <span class="copy">
                        Copy selection: <object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" width="110" height="14" id="clippy">
                        <param name="movie" value="clippy/clippy.swf"/>
                        <param name="allowScriptAccess" value="always" />
                        <param name="quality" value="high" />
                        <param name="scale" value="noscale" />
                        <param NAME="FlashVars" value="text=">
                        <param name="bgcolor" value="#E8E8E8">
                        <embed src="clippy/clippy.swf"
                               width="110"
                               height="14"
                               name="clippy"
                               quality="high"
                               allowScriptAccess="always"
                               type="application/x-shockwave-flash"
                               pluginspage="http://www.macromedia.com/go/getflashplayer"
                               FlashVars="text="
                               bgcolor="#E8E8E8"
                            />
                    </object>
                    </span>
        </div>
    </div>
    <input id="try-this" type="submit" name="subm" value="Try this!" class="btn btn-large btn-success" />
    <input id="save-snippet" type="button" name="save-snippet" value="Save Snippet!" class="btn btn-primary" />
</form>
<div class="help">
    debug:
    &lt; foo()
    krumo(foo());
</div>
<div class="help">
    commands:
    krumo::backtrace();
    krumo::includes();
    krumo::functions();
    krumo::classes();
    krumo::defines();
</div>
<div class="help">
    misc:
    press ctrl-enter to submit
    put '#\n' on the first line to enforce
    \n line breaks (\r\n etc work too)
</div>
</div>

<script type="text/javascript">

    $(document).ready(function() {
        var localStorageKey = 'PhpSnippets';

        TFSN.LocalStorageHelper = new TFSN.LocalStorageHelper();
        TFSN.LocalStorageHelper.initialize(localStorageKey);

        $('#slideToggle').click(function() {
            $('#expandable').slideToggle();
            $('#expand-icon').toggleClass('icon-minus-sign');
        });

        $('#slideToggleSnippets').click(function() {
            $('#expandable-snippets').slideToggle();
            $('#expand-snippets-icon').toggleClass('icon-minus-sign');
        });

        $('i.preview-snippet').click(function(){
            $(this).next('pre').slideToggle();
            $(this).toggleClass('icon-minus-sign');
        });
    });

</script>
</body>
</html>
