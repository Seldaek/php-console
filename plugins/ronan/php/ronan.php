<?php
/** Ronan's plugin PHP file
 * Created on 16 2011-11-16 2011 at 22:31
 * @copyright Ronan Guilloux 2011
 * @author Ronan Guilloux (ronan.guilloux@gmail.com)
 * @link http://www.toog.fr
 * @license :  see the LICENSE file in the plugin dir
 * @version 0.1
 */

if(!empty($_POST['code'])){
    $tmpFile = tempnam(sys_get_temp_dir(), 'phpconsole.');
    file_put_contents($tmpFile, $_POST['code']);
    $result = passthru("php -l $tmpFile > $tmpFile.output");
    $result = str_replace($tmpFile, 'your code', str_replace($tmpFile, '', file_get_contents("$tmpFile.output")));
    $unWanted = array(
        "Errors parsing",
        "detected in",
        "<?php ",
        " ?>");
    $result = str_replace($unWanted, '', $result);
    unlink($tmpFile);
    unlink("$tmpFile.output");
    echo trim($result);
}

?>

