<?php

class Projects {

    protected $_directory;
    protected $_colCount = 3;


    public function __construct(){
        $this->_directory = realpath(dirname(__FILE__) . '/../../../') . '/';
    }

    public function getDirectoryFromSiteName($siteName = null){
        return ($siteName) ? $this->_directory . $siteName . '/' : false;
    }


    public function getProjectsArray($dir = null){
    
        $dirArray = array();
        if(!$dir){
            $dir = $this->_directory;
        }
        
        $files = glob($dir . "*");
        foreach($files as $file)
        {
            if(is_dir($file) && strrpos($file, '.dev') !== false){$dirArray[] = $file;}
        }
        
        return $dirArray;   
    }

    public function renderProjects(){
        $projectsDirectoryArray = $this->getProjectsArray();

        $content = '';

        $i = 0;
        $mod = 0;
        foreach($projectsDirectoryArray as $project){
            if($i++%$this->_colCount == 0){
                $content .= "<div class='row-fluid'>";
            }
            $content .= $this->beautifyProject($project);
            if($i%$this->_colCount == 0 || $i == sizeof($projectsDirectoryArray)){
                $content .= '</div>';
            }
        }

        return $content;
    }

    public function beautifyProject($dirName = null){
        $html = '';
        if($dirName){
            if(strripos($dirName, '/')){
                $siteName = substr($dirName, strripos($dirName, '/') + 1);
                $html .= "
                        <div class='span4'>
                                <p><a class='btn' href='./index.php?a=debug&site=$siteName'>$siteName</a></p>

                        </div>";

            }
        }
        return $html;
    }
}
