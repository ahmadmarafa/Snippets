<?
/*
 * Uploader .
 * php class for file upload
 * $uploader = new Uploader ; 
 * $uploader->start("file") /
 *
 */
namespace itx ; 
class Uploader
{
    public $settings    = [
		"createFolder"	=> false ,
		"onDublicate"	=> "rename" , // rename , escape , overwrite ,
		"notAllowedChars" => [" " , "." , "(" , ")" , "&" , "%" , "$" , "#" , "@" , "!" , "\\" , "/" , "?" , ":" , ";" ,"|" , "\"" ,"'" , ">" , "<" , "*" ,"[","]","{","}" , "+","*"] ,
		"replaceWith" => "-" ,
		"replaceNotAllowedChars" => true ,
		"input"			=> "file" ,
		"saveTo"		=> "./" ,
		"validate"		=> [] 
	] ;
    public $latestValidateError = "" ;
	private $createdFolder = "" ;
	private $logs = [] ;
	private $uploaded = [] ;
	public function settings($new)
	{
		$this->settings = array_replace($this->settings , $new) ;
	}

	public function resetSettings()
	{
		$this->settings = [ "createFolder" => false , "onDublicate" => "rename" , "input" => "file" , "saveTo" => "./" ] ;
	}
	private function createSettings($currentUploadSettings)
	{
		return array_replace($this->settings , $currentUploadSettings) ;
	}
	public function isFilled($input , $allFilled = false)
    {		
		$data = [] ; 
        if(!is_array($_FILES[$input]["tmp_name"]))
        {
            return !empty($_FILES[$input]["tmp_name"]) || is_uploaded_file($_FILES[$input]["tmp_name"]) ;
        }
        else
        {
			
            foreach($_FILES[$input]["tmp_name"] as $key=>$value)
            {
				if($allFilled)
				{
					if(!empty($_FILES[$input]["tmp_name"][$key]) || is_uploaded_file($_FILES[$input]["tmp_name"][$key]))
					{
						$data[] = "filled" ; 
					}
				}
				else
				{
					return !empty($_FILES[$input]["tmp_name"][$key]) || is_uploaded_file($_FILES[$input]["tmp_name"][$key]) ;
				}
                
            }

			
	
			
            return $allFilled ? (count($data) == count($_FILES[$input]["tmp_name"])) : false  ; 
        }
    }
    public function start($input , $settings = [])
    {
		$settings = $this->createSettings($settings) ; 
		$this->uploaded = $this->logs = $uploaded = $errors = [] ;
		$counter = 0 ;
		$totalSize = 0 ;
		
		
	
		if(!is_array($_FILES[$input]["tmp_name"]))
        {
			if($this->isVaild($_FILES[$input]["tmp_name"] , $settings["validate"]))
			{
				$uploaded[$counter]["tmp_name"] = $_FILES[$input]["tmp_name"] ;
				$uploaded[$counter]["name"] = $_FILES[$input]["name"] ;
				$uploaded[$counter]["size"] = $_FILES[$input]["size"] ;
				$uploaded[$counter]["saveAs"] = $this->buildSaveAs($_FILES[$input]["name"] , $settings , $counter) ;
				$totalSize+=$_FILES[$input]["size"]  ;
				$counter+=1 ;
			}
			else
			{
				$errors[$_FILES[$input]["name"]] = $this->latestValidateError  ;
				@unlink($_FILES[$input]["tmp_name"]) ; 
			}
        }
		else
		{
			foreach($_FILES[$input]["tmp_name"] as $key=>$file)
			{
				
				if($this->isVaild($_FILES[$input]["tmp_name"][$key] ,  $settings["validate"]))
				{
					$uploaded[$counter]["tmp_name"] = $_FILES[$input]["tmp_name"][$key];
					$uploaded[$counter]["name"] = $_FILES[$input]["name"][$key];
					$uploaded[$counter]["size"] = $_FILES[$input]["size"][$key] ;
					$uploaded[$counter]["saveAs"] = $this->buildSaveAs($_FILES[$input]["name"][$key] , $settings , $counter) ;
					$totalSize+=$_FILES[$input]["size"][$key] ;
					$counter+=1 ;
				}
				else
				{
					$errors[$_FILES[$input]["name"][$key]] = $this->latestValidateError  ;
					@unlink($_FILES[$input]["tmp_name"][$key]) ; 
				}
			}	
		}

		$this->logs["totalSize"] = $totalSize ;
		$this->logs["errors"] = $errors ;
		if(isset($settings["validate"]))
		{
		
			if(in_array("isAllVaild" , $settings["validate"]))
			{
				if(count($errors) != 0)
				{
					
					$this->latestValidateError = "NotAllVaild"  ;
					foreach($uploaded as $file)
					{
						@unlink($file["tmp_name"]) ; 
					}
					return false ; 
				}
				
			}
			
			if(in_array("isAllFilled" , $settings["validate"]))
			{
				
				
				if(!$this->isFilled($input , true))
				{
					$this->latestValidateError = "NotAllFilled"  ;
					foreach($uploaded as $file)
					{
						@unlink($file["tmp_name"]) ; 
					}
					return false ; 
				}
				
			}
			
			return $this->store($uploaded);

			
		}
		else
		{
			return $this->store($uploaded) ;
		}
    }
	public function uploaded($which = "")
	{
		
		return $this->uploaded ;
		
		
	}
	private function store($files)
	{
		$errors = []  ;
		foreach($files as $file)
		{
			if($file["saveAs"]["escape"] === true)
			{
				continue; 
			}
			if(!@copy($file["tmp_name"],$file["saveAs"]["fullname"]))
			{
				
				if(!@move_uploaded_file($file["tmp_name"],$file["saveAs"]["fullname"]))
				{
					$error[$file["tmp_name"]] = "403" ;
					$this->latestValidateError = "403" ; 
				}
				else
				{
					$this->uploaded[] = $file["saveAs"] ; 
				}
			}
			else
			{
				$this->uploaded[] = $file["saveAs"] ; 
			}
			
			@unlink($file["tmp_name"]);
		}
		
		
		return count($errors) == 0 ;
	}
   	private function isVaild($file ,$validate)
	{
		
		if($validate === []) return true ; 
		if(array_key_exists("isVaildSize" , $validate))
		{
			$size = strtolower($validate["isVaildSize"]);
			$sizes = [
				"b"=> 1,
				"kb" => 1024 ,
				"mb" => 1024*1024 ,
				"gb" => 1024*1024*1024,
				"tb" => 1024*1024*1024*1024,
			];
			
			if(preg_match("/kb|mb|gb|tb|byte/" , $validate["isVaildSize"] , $output))
			{
				if(preg_match("/[\>\<\:\=]{1,2}/" ,$validate["isVaildSize"] , $x))
				{
					$validate["isVaildSize"] = str_replace($x[0] , "" , $validate["isVaildSize"]) ;
				}
				$size = (int) str_replace($output[0] , "" ,$validate["isVaildSize"]) ;
				$size*=$sizes[$output[0]] ;
				if(!$this->isVaildRange(fileSize($file) , $x[0].$size))
				{
					$this->latestValidateError = "inVaildSize" ;
					return false ; 
				}
			}
		} 
		
		if(in_array("isVaildImage" , $validate) || array_key_exists("isVaildDim" , $validate))
		{
			if($d = getimagesize($file))
			{
				if(array_key_exists("isVaildDim" , $validate))
				{
					$dims = explode("x" , strtolower($validate["isVaildDim"]));
					if(isset($dims[0]))
					{
						if(!$this->isVaildRange($d[0] , $dims[0]))
						{
							$this->latestValidateError = "notVaildWidth" ;
							return false ; 
						}
					}
					if(isset($dims[1]))
					{
						if(!$this->isVaildRange($d[1] , $dims[1]))
						{
							$this->latestValidateError = "notVaildHeight" ;
							return false ; 
						}
					}
					
				}
			}
			else
			{
				
				$this->latestValidateError = "notVaildImage" ;
				return false ;
				
			}
			
		}
		return true ; 
	}
	private function isVaildRange($input,$range)
	{
		

		if(substr($range,0,1) == "=" )
		{
			return ($input == (int)substr($range,1)) ? true : false ; 
		}
		if(substr($range,0,1) == ">" )
		{
			if(substr($range,0,2) == ">=" )
			{
				return ($input >= (int)substr($range,2)) ? true : false ; 
			}
			else
			{
				return ($input > (int)substr($range,1)) ? true : false ; 
			}
		}
		elseif(substr($range,0,1) == "<")
		{
			if(substr($range,0,2) == "<=" )
			{
				return ($input <= (int)substr($range,2)) ? true : false ; 
			}
			else
			{
				return ($input < (int)substr($range,1)) ? true : false ; 
			}
		}
		else
		{
			$text = explode(":",$range) ; 
			if(is_array($text) && count($text)==2)
			{
				
				return ($input>=$text[0] && $input<=$text[1]) ? true : false ; 
			}
			else
			{
				return ($input == $range) ? true : false ; 
			}
		}
	}
    private function createUploadDir($settings)
	{
		if(!$settings["createFolder"])
		{
			
			return $settings["saveTo"] ;
		}
		else
		{
			$folder_name = strtolower(date("M-y"));
			$this->createdFolder = $folder_name ;
			$dir = $settings["saveTo"].$folder_name ;
			if(is_dir($dir))
			{
				return $dir."/";
			}
			else
			{
				if(mkdir($dir,0777))
				{
					return $dir."/";
				}
				else
				{
					return $settings["saveTo"] ; 
				}
			}
		}
	}
    private function buildSaveAs($file , $settings , $key)
	{
		
		$escape = false ;
		$file_info = pathinfo($file);
		$file_info["extension"] = isset($file_info["extension"]) ? $file_info["extension"]  : "" ;
		$replace = [
			"{id}" , "{rand}" , "{name}" , "{ext}" 
		];
		$with = [
			1+$key , mt_rand() , $file_info["filename"] , $file_info["extension"]
		];
		
		
		
		if(isset($settings["saveAs"]))
		{
			
			if(is_array($settings["saveAs"]))
			{
				
				
				if(isset($settings["saveAs"][$key]))
				{
					$file = str_replace($replace , $with , $settings["saveAs"][$key]) ;
				}
				
			}
			else
			{
				$file = str_replace($replace , $with , $settings["saveAs"]) ;
			}
		}
		
		$temp = $this->createUploadDir($settings).$this->correctFileName($file , $settings);
		if(file_exists($temp))
		{
			if( $settings["onDublicate"] == "rename" )
			{
				$file = $this->renameFile($temp);				
			}
			elseif($settings["onDublicate"] == "escape")
			{
				$file = $temp ;
				$escape = true; 
			}
			else
			{
				$file = $temp ; 
			}
		}
		else
		{
			$file = $temp ;
		}
		
		return [
			"fullname" => $file ,
			"basename" => basename($file) ,
			"editedname" => $this->createdFolder.basename($file) ,
			"escape" => $escape 
		];		
	}
    private function correctFileName($file , $settings)
	{
		if(isset($settings["replaceNotAllowedChars"]) && $settings["replaceNotAllowedChars"])
		{
			
			$file_info = pathinfo($file) ;
			return strtolower(str_replace($file_info["filename"] , str_replace($settings["notAllowedChars"] , $settings["replaceWith"] , $file_info["filename"]) , $file));
		}
		else
		{
			
			return $file ; 
		}
		
	}
	private function renameFile($file)
	{
		$file_info = pathinfo($file) ;
		return strtolower(str_replace($file_info["filename"] , $file_info["filename"]."-".mt_rand() , $file));
		
	}
}
?>