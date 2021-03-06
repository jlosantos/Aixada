<?php 
	
ob_start(); // Starts FirePHP output buffering
//require_once('FirePHPCore/lib/FirePHPCore/FirePHP.class.php');
//$firephp = FirePHP::getInstance(true);

require_once('inc/cookie.inc.php');
require_once('local_config/config.php');

$language = ( (isset($_SESSION['userdata']['language']) and $_SESSION['userdata']['language'] != '') ? $_SESSION['userdata']['language'] : configuration_vars::get_instance()->default_language );
$default_theme = configuration_vars::get_instance()->default_theme; 
$dev = configuration_vars::get_instance()->development;

require_once('local_config/lang/' . $language . '.php');

//should be deleted in the end, and globally set. 
$_SESSION['dev'] = true;

   try {
     $cookie = new Cookie();
     $cookie->validate();
     
     if (isset($_SESSION['userdata']) and isset($_SESSION['userdata']['current_role'])) {
         $fp = configuration_vars::get_instance()->forbidden_pages;
         $uri = $_SERVER['REQUEST_URI'];
         $role = $_SESSION['userdata']['current_role'];
         $forbidden = false;
         foreach($fp[$role] as $page) {
             if (strpos($uri, $page) !== false) {
                 $forbidden = true;
                 break;
             }
         }
         if ($forbidden) {
             /* $firephp->log($uri, 'uri'); */
             /* $firephp->log($role, 'role'); */
             /* $firephp->log($_SESSION, 'session'); */
             /* $firephp->log($_SERVER, 'server'); */
             header("Location: index.php");
         }
     }
     
   }   
   catch (AuthException $e) {
     echo("caught AuthException: $e");
     header("Location: login.php?originating_uri=".$_SERVER['REQUEST_URI']);
     exit;
   }

?>