<?php


function create_directory_recurV2($path_dir){
    if (file_exists($path_dir)){
        return;
    }
    mkdir($path_dir, 0777, true);
}

class JsonV2 {
    static public function FromFile($path){
        try {
            $json = file_get_contents($path);
            return json_decode($json);
        }catch(Exception $error){
            echo $error;
        }
        return null;
    }
	
	static public function WriteFile($path, $file_name, $json){
		try{
            create_directory_recurV2($path);
			$fp = fopen($path . "/" . $file_name, 'w');
			fwrite($fp, json_encode($json));
			fclose($fp);
			return true;
		}catch(Exception $error){
            echo $error;
        }
		return null;
	}

}