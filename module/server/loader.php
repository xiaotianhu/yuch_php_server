<?php 
declare(strict_types=1);

    function loadDir(string $dir, bool $recursive=false)
    {
        if(!is_dir($dir)) throw new \Exception($dir."not exist.");
        if(substr($dir, -1, 1) != "/") $dir = $dir."/";

        $files = scandir($dir);
        foreach($files as $file){
            if($file == "." || $file == "..") continue;
            if(is_dir($dir.$file) && $recursive) {
                loadDir($dir.$file, true);
            }else{
                include_once($dir.$file);
            }
        }
    }

