<?php
//gsasearch.php
//Aaron Averett
//2012-05-31
//Plugin for Joomla 1.5 that provides support for searching using a Google Search Appliance or Google Mini.

//Prevent someone trying to access this file directly.
defined('_JEXEC') or die('Restricted access.');

class plgSearchGsasearch extends JPlugin
{
	//Constructor
	function plgSearchGsasearch(&$subject, $config)
	{
		parent::__construct($subject, $config);
		
		$this->_plugin = JPluginHelper::getPlugin('search','gsasearch');
		
		$this->_params = new JParameter($this->_plugin->params);
	}

	//handle a request to perform a search
	function onSearch($text, $phrase='', $ordering='', $areas=null)
	{			
		//Make sure that the areas is valid.  if not, return an empty result.
		if (is_array( $areas )) {
			if (!array_intersect( $areas, array_keys( plgSearchGSAAreas() ) )) {
					return array();
			}
		}
		
		//Define params here.  Presently, I don't think there is anything.
		$hostname = $this->_params->get('gsa_address');
		$frontendname = $this->_params->get('frontend_name');
		$resultcount = $this->_params->get('result_count');
		$sourcename = $this->_params->get('search_source_name');
		$collection = $this->_params->get('collection');
		$gsa_username = $this->_params->get('gsa_username');
		$gsa_password = $this->_params->get('gsa_password');	

		//If $text is empty, return nothing.
		if($text == '') return array();
		
		//Hit the GSA, and capture the results.
		$url = "https://".$hostname ."/search?access=a&collection=".$collection."&q=".urlencode($text)."&output=xml&client=".$frontendname."&filter=0";
		
		if(is_numeric($resultcount)) $url .= "&num=30";
		
		$googleresult = $this->gsaDoSearch($url, $gsa_username, $gsa_password);
		
		echo htmlspecialchars($googleresult);
		
		//Parse the XML returned from the Mini.
		$xmlobj = simplexml_load_string($googleresult);
				
		//Convert the XML object into an associative array.
		$arrxml = $this->gsaSearch_objectsIntoArray($xmlobj);

		$res = $arrxml["RES"];
		
		$results = array();
		
		for($i=0; $i < sizeof($res["R"]); $i++)
		{
			$row = array();
			
			if(isset($res["R"][$i]["U"]))
			{
				$row['href'] = $res["R"][$i]["U"];
			}
			else $row['href'] = '';
			
			
			if(isset($res["R"][$i]["T"]))
			{
				$row['title'] = strip_tags($res["R"][$i]["T"]);
			}
			else $row['title'] = '<Title Unknown>';
			
			if(isset($res["R"][$i]["S"]) && is_string($res["R"][$i]["S"]))
			{
				$row['text'] = strip_tags($res["R"][$i]["S"]);
			}
			else $row['text'] = '';
			
			if(isset($res["R"][$i]["FS"]["@attributes"]["VALUE"]))
			{
				$row['created'] = $res["R"][$i]["FS"]["@attributes"]["VALUE"];
			}
			else $row['created'] = '';
			
			$row['section'] = $sourcename;
			$row['browsernav'] = '1';
			
			$results[] = $this->gsaSearch_arrayToObject($row);
		}
		
		//Now, we handle the sorting.
		if($ordering != '')
		{
			if($ordering == "newest")
			{
				$this->gsaSort($results, "created", true);
			}
			else if($ordering == "oldest")
			{
				$this->gsaSort($results, "created", false);
			}
			else if($ordering == "popular")
			{
				//Can't do much with this one, so we'll ignore it.
			}
			else if($ordering == "alpha")
			{
				$this->gsaSort($results, "title", false);
			}
			else if($ordering == "category")
			{
				//I think this really only applies to the Joomla-native search, so we'll ignore it.
			}
			
		}
		
		return $results;
	}
	
	//Returns the "areas" within joomla that this plugin is able to search.  We provide a custom area here, called GSA.
	function onSearchAreas()
	{
		$areas = array('GSA' => 'GSA');
	
		return $areas;
	}
	
	//Various other functions
	
	function gsaDoSearch($url, $gsa_username, $gsa_password)
	{
		//$googleresult = file_get_contents($url);
		
		$options = array();
		$info = array();
		
		$options["httpauth"] = $gsa_username.":".$gsa_password;
		$options["httpauthtype"] = HTTP_AUTH_BASIC;
		$options["cookies"] = array("COOKIETEST"=>1);
		
		$googleresult = http_parse_message(http_get($url, $options, $info))->body;
		
		return $googleresult;
	}
	
	function gsa_do_http_request($url, $data, $optional_headers = null)
	{
	  $params = array('http' => array(
				  'method' => 'GET',
				  'content' => $data
				));
	  if ($optional_headers !== null) {
		$params['http']['header'] = $optional_headers;
	  }
	  $ctx = stream_context_create($params);
	  $fp = @fopen($url, 'rb', false, $ctx);
	  if (!$fp) {
		throw new Exception("Problem with $url, $php_errormsg");
	  }
	  $response = @stream_get_contents($fp);
	  if ($response === false) {
		throw new Exception("Problem reading data from $url, $php_errormsg");
	  }
	  return $response;
	}

	function gsaSort(&$data, $colname, $descending = false)
	{
		//If they didn't pass in an array, abort.
		if(!is_array($data)) return;

		//Bubble sort, I guess?
		
		$sorted = false;
		
		do
		{
			$sorted = true;
			
			for($i =0; $i < sizeof($data); $i++)
			{
				if($descending)
				{
					if(($i + 1) < sizeof($data) && $data[$i]->$colname < $data[$i+1]->$colname) 
					{
						$holder = &$data[$i];
						$data[$i] = &$data[$i + 1];
						$data[$i + 1] = $holder;
						
						$sorted = false;
					}
				}
				else
				{
					if($i + 1 < sizeof($data) && $data[$i]->$colname > $data[$i+1]->$colname) 
					{
						$holder = &$data[$i];
						$data[$i] = &$data[$i + 1];
						$data[$i + 1] = $holder;
						
						$sorted = false;
					}
				}
			}
		}
		while($sorted == false);
	}

	function gsaSearch_arrayToObject($array) {
		if(!is_array($array)) {
			return $array;
		}
		
		$object = new stdClass();
		if (is_array($array) && count($array) > 0) {
		  foreach ($array as $name=>$value) {
			 $name = strtolower(trim($name));
			 if (!empty($name)) {
				$object->$name = $this->gsaSearch_arrayToObject($value);
			 }
		  }
		  return $object;
		}
		else {
		  return FALSE;
		}
	}

	function gsaSearch_objectsIntoArray($arrObjData, $arrSkipIndices = array())
	{
		$arrData = array();
	   
		// if input is object, convert into array
		if (is_object($arrObjData)) {
			$arrObjData = get_object_vars($arrObjData);
		}
	   
		if (is_array($arrObjData)) {
			foreach ($arrObjData as $index => $value) {
				if (is_object($value) || is_array($value)) {
					$value = $this->gsaSearch_objectsIntoArray($value, $arrSkipIndices); // recursive call
				}
				if (in_array($index, $arrSkipIndices)) {
					continue;
				}
				$arrData[$index] = $value;
			}
		}
		return $arrData;
	}
}

//Register the event handlers.
/*$mainframe->registerEvent('onSearch','plgSearchGSA');
$mainframe->registerEvent('onSearchAreas','plgSearchGSAAreas');

//Define the language file
JPlugin::loadLanguage('plg_search_GSA');

//This function returns the set of search "areas"
function &plgSearchGSAAreas()
{
	static $areas = array('GSA' => 'GSA');
	
	return $areas;
}

function gsaDoSearch($url, $gsa_username, $gsa_password)
{
	//$googleresult = file_get_contents($url);
	
	$options = array();
	$info = array();
	
	$options["httpauth"] = $gsa_username.":".$gsa_password;
	$options["httpauthtype"] = HTTP_AUTH_BASIC;
	$options["cookies"] = array("COOKIETEST"=>1);
	
	$googleresult = http_parse_message(http_get($url, $options, $info))->body;
	
	return $googleresult;
}

//This is the entry point, more or less
function plgSearchGSA($text, $phrase='', $ordering='', $areas=null)
{
	
	//Make sure that the areas is valid.  if not, return an empty result.
	if (is_array( $areas )) {
		if (!array_intersect( $areas, array_keys( plgSearchGSAAreas() ) )) {
				return array();
		}
	}

	//Define the parameters
	//First step:  Get the plugin
	$plugin = &JPluginHelper::getPlugin('search','gsasearch');
	
	$pluginParams = new JParameter($plugin->params);
	
	//Define params here.  Presently, I don't think there is anything.
	$hostname = $pluginParams->def('gsa_address', 'gsa.mydomain.org');
	$frontendname = $pluginParams->def('frontend_name', 'default_frontend');
	$resultcount = $pluginParams->def('result_count','30');
	$sourcename = $pluginParams->def('search_source_name','Google Search Appliance');
	$gsa_username = $pluginParams->def('gsa_username','');
	$gsa_password = $pluginParams->def('gsa_password', '');	

	//If $text is empty, return nothing.
	if($text == '') return array();
	
	//Hit the GSA, and capture the results.
	$url = "https://".$hostname ."/search?access=a&collection=qcl&q=".urlencode($text)."&output=xml&client=".$frontendname."&filter=0";
	
	if(is_numeric($resultcount)) $url .= "&num=30";
	
	$googleresult = gsaDoSearch($url, $gsa_username, $gsa_password);
	
	//Parse the XML returned from the Mini.
	$xmlobj = simplexml_load_string($googleresult);
	
	//Convert the XML object into an associative array.
	$arrxml = gsaSearch_objectsIntoArray($xmlobj);

	$res = $arrxml["RES"];
	
	$results = array();
	
	for($i=0; $i < sizeof($res["R"]); $i++)
	{
		$row = array();
		
		if(isset($res["R"][$i]["U"]))
		{
			$row['href'] = $res["R"][$i]["U"];
		}
		else $row['href'] = '';
		
		
		if(isset($res["R"][$i]["T"]))
		{
			$row['title'] = strip_tags($res["R"][$i]["T"]);
		}
		else $row['title'] = '<Title Unknown>';
		
		if(isset($res["R"][$i]["S"]) && is_string($res["R"][$i]["S"]))
		{
			$row['text'] = strip_tags($res["R"][$i]["S"]);
		}
		else $row['text'] = '';
		
		if(isset($res["R"][$i]["FS"]["@attributes"]["VALUE"]))
		{
			$row['created'] = $res["R"][$i]["FS"]["@attributes"]["VALUE"];
		}
		else $row['created'] = '';
		
		$row['section'] = $sourcename;
		$row['browsernav'] = '1';
		
		$results[] = gsaSearch_arrayToObject($row);
	}
	
	//Now, we handle the sorting.
	if($ordering != '')
	{
		if($ordering == "newest")
		{
			gsaSort($results, "created", true);
		}
		else if($ordering == "oldest")
		{
			gsaSort($results, "created", false);
		}
		else if($ordering == "popular")
		{
			//Can't do much with this one, so we'll ignore it.
		}
		else if($ordering == "alpha")
		{
			gsaSort($results, "title", false);
		}
		else if($ordering == "category")
		{
			//I think this really only applies to the Joomla-native search, so we'll ignore it.
		}
		
	}

	//print_r($results);
	
	
	return $results;
}

function gsa_do_http_request($url, $data, $optional_headers = null)
{
  $params = array('http' => array(
              'method' => 'GET',
              'content' => $data
            ));
  if ($optional_headers !== null) {
    $params['http']['header'] = $optional_headers;
  }
  $ctx = stream_context_create($params);
  $fp = @fopen($url, 'rb', false, $ctx);
  if (!$fp) {
    throw new Exception("Problem with $url, $php_errormsg");
  }
  $response = @stream_get_contents($fp);
  if ($response === false) {
    throw new Exception("Problem reading data from $url, $php_errormsg");
  }
  return $response;
}

function gsaSort(&$data, $colname, $descending = false)
{
	//If they didn't pass in an array, abort.
	if(!is_array($data)) return;

	//Bubble sort, I guess?
	
	$sorted = false;
	
	do
	{
		$sorted = true;
		
		for($i =0; $i < sizeof($data); $i++)
		{
			if($descending)
			{
				if(($i + 1) < sizeof($data) && $data[$i]->$colname < $data[$i+1]->$colname) 
				{
					$holder = &$data[$i];
					$data[$i] = &$data[$i + 1];
					$data[$i + 1] = $holder;
					
					$sorted = false;
				}
			}
			else
			{
				if($i + 1 < sizeof($data) && $data[$i]->$colname > $data[$i+1]->$colname) 
				{
					$holder = &$data[$i];
					$data[$i] = &$data[$i + 1];
					$data[$i + 1] = $holder;
					
					$sorted = false;
				}
			}
		}
	}
	while($sorted == false);
}

function gsaSearch_arrayToObject($array) {
    if(!is_array($array)) {
        return $array;
    }
    
    $object = new stdClass();
    if (is_array($array) && count($array) > 0) {
      foreach ($array as $name=>$value) {
         $name = strtolower(trim($name));
         if (!empty($name)) {
            $object->$name = gsaSearch_arrayToObject($value);
         }
      }
      return $object;
    }
    else {
      return FALSE;
    }
}

function gsaSearch_objectsIntoArray($arrObjData, $arrSkipIndices = array())
{
    $arrData = array();
   
    // if input is object, convert into array
    if (is_object($arrObjData)) {
        $arrObjData = get_object_vars($arrObjData);
    }
   
    if (is_array($arrObjData)) {
        foreach ($arrObjData as $index => $value) {
            if (is_object($value) || is_array($value)) {
                $value = gsaSearch_objectsIntoArray($value, $arrSkipIndices); // recursive call
            }
            if (in_array($index, $arrSkipIndices)) {
                continue;
            }
            $arrData[$index] = $value;
        }
    }
    return $arrData;
}*/


?>