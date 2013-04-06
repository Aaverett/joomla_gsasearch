<?php
//systemhttpbasic.php
//System plugin for Joomla 1.5 that allows the user credentials to be provided via http basic authentication, vs. the session
//Intended for use with Google Search Appliance
//Aaron Averett
//2013-04-05

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

class plgSystemGsahttpbasic extends JPlugin
{
	//Constructor
	function plgSystemSystemhttpbasic(&$subject, $config)
	{
		parent::__construct($subject, $config);
		
		//Nothing else for now...
	}

	function onAfterInitialise()
	{		
		//Get a handle on the currently logged in user.
		$session = JFactory::getSession();
		$user = $session->get("user");
		
		//If no user is logged in...
		if($user->id == 0)
		{		
			//Retrieve the username and password from the server superglobal, if we can.
			if(isset($_SERVER["PHP_AUTH_USER"]) && isset($_SERVER["PHP_AUTH_PW"]))
			{
				$un = $_SERVER["PHP_AUTH_USER"];
				$pw = $_SERVER["PHP_AUTH_PW"];
				
				//Grab a handle on the mainframe
				global $mainframe;
				
				//Log in the user.
				$mainframe->login(array('username'=>$un, 'password'=>$pw), array('silent'=>true));
			}
			else
			{
				//Does the user agent start with "gsa-crawler"?
				if(strpos($_SERVER["HTTP_USER_AGENT"], "gsa-crawler") === 0)
				{				
					header('WWW-Authenticate: Basic realm="My Realm"');
					header('HTTP/1.0 401 Unauthorized');
					echo 'Please set the password on your Google Search Appliance';
					exit;
				}
				
				
			}
		}
	}
}
?>