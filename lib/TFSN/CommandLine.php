<?php
/**
 * Blueacorn
 *
 * Description:
 *
 * @category    Integration
 * @package     Blueacorn
 * @subpackage  Sales
 * @author  Thomas Slade <thomas@blueacorn.com>
 */
class CommandLine{

    public function getStoreName($dirName = null){
        if($dirName){
            chdir($dirName);
            return exec('zf show mage-core-config general/store_information/name');
        }
        return false;
    }

}