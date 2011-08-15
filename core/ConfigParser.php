<?php

define("SHOW_DEBUG",0);
if(!defined('TO_ROOT'))
	define('TO_ROOT', '..');	

define('__commands','__commands');	

class ConfigParser {
  
  public static $__imports  = array();
  public static $__vars     = array();
  
  /**
   *
   *
   * @param string $str
   */
  public static function debug($str)
  {
  if(SHOW_DEBUG)
  	{
  	if(is_string($str))	
  		echo $str."\n";
  	else 
  		var_dump($str);
  	}
  }
  /**
   * Advandced Parse Ini 
   *
   * @param FILE $file
   * @param boolean $sections
   * @return array
   */
  public static function parse_ini_adv($file,$sections=true)
  {
  	$dir=pathinfo($file,PATHINFO_DIRNAME);
  	if(file_exists($file))	
  		{
  		self::debug("parsing: ".$file);
  		self::$__imports[$file] = parse_ini_file($file,$sections);
  		}
  	else 
  		{
  		self::debug("NOT parsing: ".$file);
  		
  		}
  	self::debug(self::$__imports[$file]);
  	
  	if(self::$__imports[$file] && is_array(self::$__imports[$file]))
  		{
  		if(isset(self::$__imports[$file][__commands]) && is_array(self::$__imports[$file][__commands]))
  			{
  			$m=count(self::$__imports[$file][__commands]);
  			for($i=0;$i<$m&&isset(self::$__imports[$file][__commands][$i]);$i++)
  				{
  			
  				$str_import=strstr(self::$__imports[$file][__commands][$i],"import:");
  				if($str_import && strpos(self::$__imports[$file][__commands][$i],"import:")===0)	
  					{
  					
  					$x= preg_match("/^(.*)[\.](ini)(.*)/",substr($str_import,7),$res);
  					if(count($res)==4 && $res[2]==='ini' && !isset(self::$__imports[$res[0]]))
  						{			
  						$new_include=$res[1].".".$res[2];
  				
  						self::debug(__LINE__." dir: ".pathinfo($new_include,PATHINFO_DIRNAME));						
  						if(file_exists($dir."/".$new_include))
  							{	
  							if(self::parse_ini_adv($dir."/".$new_include) && $res[3])
  								self::$__imports[$res[0]]=&self::$__imports[$dir."/".$new_include][str_replace(array("<",">"),"",$res[3])];
  							}
  						else 
  							self::debug("not found: ".$dir.$new_include);
  						}
  					
  					}
  				}
  			}
  	
  		$final=array_reduce(self::$__imports,'self::array_merger');
  
  		}
  	return $final;
  }
  /**
   * Enter description here...
   *
   * @param array $a
   * @param array $b
   * @return array
   */
  public static function array_merger($a,$b)
  {
  	if(!isset($a))
  		$a=$b;
  		
  	if(!isset($b))
  		return $b=$a;
  	$a=array_merge($a,$b);
  	return $a;
  	
  	
  }
  
  public static function remplaza_mesta(&$item,$key)
  {
  	global $__vars,$keys;
  	if(is_string($item))
  		$item=str_replace($keys,$__vars,$item);
  	return $item;
  }
  
  public static function limpia_mesta($a)
  {
  	return $a;
  }
  public static function parsea_mesta($file)
  {
    global $__vars,$keys;
    
    $a=self::parse_ini_adv($file);
    $m=count($a);
    if(!$a || !is_array($a) || $m==0) return false;
    		foreach ($a as $key=>&$value)
    			{			
    			if(!is_array($value))
    				{
    				self::$__vars[$key]=$value;			
    				self::$keys[]="{".$key."}";
    				}
    			}
    		
    array_walk_recursive($a,'remplaza_mesta');
    return self::limpia_mesta($a);
  }
}
