<?php
/**
 * @autor cyberocioso
 * @version b.0001
 * @todo 
 */
require_once(THAFRAME."/vendors/spyc/spyc.php");
define("SHOW_DEBUG",0);
if(!defined('TO_ROOT'))
	define('TO_ROOT', '..');	

define('__commands','__commands');	

class ConfigParser {
  
  public static $__imports  = array();
  public static $__vars     = array();
  public static $__keys     = array();
  public static $__stringvars     = array();
  
  
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
   * Advandced Parse Yaml
   *
   * @param FILE $file
   * @param boolean $sections
   * @return array
   */
  public static function parse_yaml_adv($file)
  {
  	$args=func_get_args();
  	
  	if(isset(self::$__imports[$file]))
  		return self::$__imports[$file];
  	$dir=pathinfo($file,PATHINFO_DIRNAME);
  	if(isset($args[2]))
  		$dir=$args[2].($dir!='.'?("/".$dir):"");  	
  		
  	$res=null;
  	$x= preg_match("/^(.*)[\\.](yaml)(.*)/",pathinfo($file,PATHINFO_BASENAME),$res);
	$file_x=$file;
  	if($x && is_array($res) && count($res)>2)
  		{
  		$file_x=$dir."/".$res[1].".".$res[2];
  		}
  	self::debug("[".__LINE__."] \t loading: ".$file_x);
  	if(file_exists($file_x))
  		{  		
  		self::debug("[".__LINE__."] \t parsing: ".$file);
  		self::$__imports[$file_x] = Spyc::YAMLLoad($file_x);
  		}
  	else 
  		{
  		self::debug("[".__LINE__."] \t not found: ".$file);  		
  		}
  	
  	if(self::$__imports[$file_x] && is_array(self::$__imports[$file_x]))
  		{
  		if(isset($res[3]) && isset(self::$__imports[$file_x][str_replace(array("<",">"),"",$res[3])]))  		
  			$array =&self::$__imports[$file_x][str_replace(array("<",">"),"",$res[3])];
  		
  		else 
  			$array =&self::$__imports[$file_x];
  		
  		foreach($array as $k=>&$value)
  			{
  			$typo=gettype($value);
  			if($typo=='array' && strpos($k,'_import')===0)
  				{
  				array_walk($value,"ConfigParser::parse_yaml_adv",$dir);
  				}
  			else 
	  			{
	  			switch ($typo)
	  				{
	  					case 'array':
	  							(self::array_merger(self::$__vars[$k],$value));
									
	  							break;
	  					case 'string':
	  					default:
	  							self::$__vars[$k]=$value;
	  							break;
	  				}
	  			}
  			}
  		}  	
  	return self::$__imports;
  }
  public static function ismport($type,&$value)
  {
  	switch ($type)
  	{
  		case 'ini':
  			return  (isset($value) && is_array($value));
  	/*	case 'yaml':
  			return  (isset(self::$__imports[$file][__commands]) && is_array(self::$__imports[$file][__commands]));
  		*/	
  	}
  	return false;
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
  		if(self::ismport('ini',self::$__imports[$file][__commands]))
  			{
  			$m=count(self::$__imports[$file][__commands]);
  			for($i=0;$i<$m&&isset(self::$__imports[$file][__commands][$i]);$i++)
  				{
  			
  				$str_import=strstr(self::$__imports[$file][__commands][$i],"import:");
  				if($str_import && strpos(self::$__imports[$file][__commands][$i],"import:")===0)	
  					{
  					$res=null;
  					$x= preg_match("/^(.*)\\.(ini)(.*)/",substr($str_import,7),$res);
  					if($x && count($res)==4 && $res[2]==='ini' && !isset(self::$__imports[$res[0]]))
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
  	
  		$final=array_reduce(self::$__imports,'ConfigParser::array_merger');
  
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
  public static function reduce_mesta(&$b,$a)
  {
 	
  	if(is_array($b) && count($b))
  		{
  		if(strpos($a,'__commands')===0)
	  		{
	  		
	  		foreach ($b as $k=>&$bs) { if(is_array($bs))
	  					$bs=array_unique($bs);
							  		}
	  		}
  		else 
	  		{
	  		$j=0;
	  		for($i=0;$i<count($b);$i++)
	  			if(isset($b[$i]) && !is_array($b[$i])) $j++;
	  		
	  		if($j==count($b))
	  			{
	  			
	  			$b=$b[count($b)-1];
	  			}
	  		else 
	  			foreach ($b as $k=>&$bs) { if(is_array($bs))
	  					$bs=self::reduce_mesta($bs,$k);
	  				}
	  		}
  		}
  	return $b;  
  }
  
  public static function array_merger(&$a,&$b)
  {
  	if(!isset($a))
  		$a=$b;
  		
  	if(!isset($b))
  		return $b=$a;  	
  	$a=@array_merge_recursive($a,$b);
  	return $a;
  	
  	
  }
  
  public static function remplaza_mesta(&$item,$key)
  {  	
  	
  	if(is_string($item))
  		{
  		$old_item=$item;
  		self::debug(__FUNCTION__."[$item]");
  		$item=str_replace(self::$__keys,self::$__stringvars,$item);
  		if($old_item!==$item)
  			{
  			self::debug($key." replaced : ".$item);
  			}
  		}
  	return $item;
  }
  
  public static function limpia_mesta(&$a)
  {
  
  	
  	return $a;
  }
  public static function parsea_mesta($file)
  {
    
    $extension=pathinfo($file,PATHINFO_EXTENSION);
    switch ($extension)
    	{
    		case 'yaml':
    			self::parse_yaml_adv($file);
    			$a=&self::$__vars;
    			break;
    		case 'ini':
    			
    		default:
    			$a=self::parse_ini_adv($file);
    		break;
    	
    	}
    $m=count($a);
    if(!$a || !is_array($a) || $m==0) return false;
    		foreach ($a as $key=>&$value)
    			{			
    			if(!is_array($value) && is_string($value))
    				{
    				self::$__stringvars[$key]=$value;			
    				self::$__keys[]="{".$key."}";
    				}
    			}
    		
    switch ($extension)
    	{
    		case 'yaml':
    			array_walk_recursive(self::$__vars,'ConfigParser::remplaza_mesta');    	
    			array_walk(self::$__vars,"ConfigParser::reduce_mesta");
    			return self::limpia_mesta(self::$__vars);	
			case 'ini':    		
    			array_walk_recursive($a,'ConfigParser::remplaza_mesta');    	
    			return self::limpia_mesta($a);	
    		
    	}
    	
  }
}
