<?php


//require_once('FirePHPCore/lib/FirePHPCore/FirePHP.class.php');
ob_start(); // Starts FirePHP output buffering

require_once("local_config/config.php");
require_once("inc/database.php");
require_once("utilities.php");

if (!isset($_SESSION)) {
    session_start();
 }

$language = ( (isset($_SESSION['userdata']['language']) and 
               $_SESSION['userdata']['language'] != '') ? 
              $_SESSION['userdata']['language'] : 
              configuration_vars::get_instance()->default_language );
require_once('local_config/lang/' . $language . '.php');
require_once("utilities_tables.php");

//$firephp = FirePHP::getInstance(true);
$use_session_cache = configuration_vars::get_instance()->use_session_cache;
$use_canned_responses = configuration_vars::get_instance()->use_canned_responses;

//DBWrap::get_instance()->debug = true;

// utility functions
/*
function get_special_names_and_model ()
{
  return array('col_names' => "['id', 'uf_id', 'uf_name', 'member_id', 'member_name', 'provider_id', 'provider_name', 'roles']",
	       'col_model' => 
	       "[{name: 'id', index: 'id', width: 100, xmlmap: 'id', editable: false, formoptions: {label: 'id'}}," .
	       "{name: 'uf_name', index: 'uf_name', width: 255, xmlmap: 'uf_name', editable: true, formoptions: {label: 'uf_name'}}," .
	       "{name: 'member_name', index: 'member_name', width: 255, xmlmap: 'member_name', editable: true, formoptions: {label: 'name'}}," .
	       "{name: 'name', index: 'name', width: 255, xmlmap: 'name', editable: true, formoptions: {label: 'name'}}," .
 "]");
}
*/

function get_columns_as_JSON()
{
//   global $firephp;
  global $special_table;
//   switch ($use_canned_responses) {
//   case false:
// //     $firephp->log(true, 'including table manager');
//     require_once 'lib/table_manager.php';
//     $tm = new table_manager($_REQUEST['table'], 
// 			    configuration_vars::get_instance()->use_session_cache);
//     $response = $special_table ? get_special_names_and_model()
//       : array('col_names' => get_names($tm), 
// 	      'col_model' => get_model($tm, $use_canned_responses), 
// 	      'active_fields' => get_active_field_names($tm));
// //     $firephp->log($response, 'response');
//     return json_encode($response);
    
//   case true:
//     $firephp->log(true, 'including canned responses');
  global $language;
  global $Text;
  //  global $firephp;
  $Text = array();
  require 'canned_responses_' . $language . '.php';
  //  $firephp->log($Text['please_select'], 'please select');
  // $firephp->log($language, 'language'); 
  // $firephp->log($_SESSION['userdata'], 'userdata'); 
  $ctm = new canned_table_manager();
  $table = $_REQUEST['table'];
  return '{"col_names":"' . $ctm->get_col_names_as_JSON($table)
    . '","col_model":"' . $ctm->get_col_model_as_JSON($table)
    . '","active_fields":"' . $ctm->get_active_fields_as_JSON($table)
    . '"}';
}

function get_options()
{    
  $options = array( 'filter' => '' );
  if (isset($_REQUEST['filter']) and substr($_REQUEST['filter'],0,8) != 'function') {
    $options['filter'] .= $_REQUEST['filter'];
  }
  
  switch ($_REQUEST['table']) {
  case 'aixada_account':
    if (strlen($options['filter'])>0) {
      $options['filter'] .= ' and ';
    }
    $uf_id = 1000 + (int)($_SESSION['userdata']['uf_id']);
    $options['filter'] .= "aixada_account.account_id=$uf_id";
    // we do this here so that the user can't hijack other users' account data 
    //by mangling the request in the browser
    break;

  case 'aixada_incident':
    switch ($options['filter']) {
    case 'today': 
      $options['filter'] = 'date(aixada_incident.ts)=date(sysdate())';
      break;
    case 'this_month':
      $options['filter'] = 'date(aixada_incident.ts)>=date_add(sysdate(), interval -32 day)';
      break;
    case 'all':
      $options['filter'] = '';
      break;
    default:
      throw new Exception('Filter option not supported in aixada_incident');
    }
    break;
  }
  if (isset($_REQUEST['fields'])) {
    $options['fields'] = str_replace('"', '', $_REQUEST['fields']);
  }

  return $options;
}

function get_list_all_XML()
{
  $req_page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : '');
  $req_rows = (isset($_REQUEST['rows']) ? $_REQUEST['rows'] : '');
  $req_sidx = (isset($_REQUEST['sidx']) ? $_REQUEST['sidx'] : '');
  $req_sord = (isset($_REQUEST['sord']) ? $_REQUEST['sord'] : '');

  if ($_REQUEST['table'] == 'aixada_account' and
      !isset($_REQUEST['sidx'])) {
    $req_sidx = 'ts';
  }
  if ($_REQUEST['table'] == 'aixada_order_cart') {
    $count_querySQL = 
      'select count(distinct order_cart_id) from aixada_order_item where ts_validated=0';
    $real_querySQL = 
      'select distinct aixada_order_cart.* from aixada_order_cart left join aixada_order_item on aixada_order_cart.id = aixada_order_item.order_cart_id where aixada_order_item.ts_validated=0';
    $page = $req_page;
    $limit = $req_rows;
    list($rs, $total_entries, $total_pages) = 
      DBWrap::get_instance()->canned_select($count_querySQL, $real_querySQL, $page, $limit);
    $of = new output_formatter();
    return $of->rowset_to_jqGrid_XML($rs, $total_entries, $page, $limit, $total_pages); 
  }

  $options = get_options();
//   if (!$use_canned_responses) {
//     require_once 'lib/table_manager.php';
//     $tm = new table_manager($_REQUEST['table'], 
// 			    configuration_vars::get_instance()->use_session_cache);
//     return do_list_all($tm, $req_page, $req_rows, $req_sidx, $req_sord, $options);
//   }

  // else we use the canned responses

  $db = DBWrap::get_instance();
  $filter_str = $options['filter'];
  $strSQL = 'SELECT COUNT(*) AS count FROM :1';
  if ($filter_str != '') {
    $strSQL .= ' WHERE ' . $filter_str;
  }
  
  $row = $db->Execute($strSQL, $_REQUEST['table'])->fetch_array();
  $total_entries = $row[0];
  list($start, $total_pages) = $db->calculate_page_limits($total_entries, $req_page, $req_rows); 
  $rs = do_stored_query($_REQUEST['table'] . '_list_all_query', $req_sidx, $req_sord, $start, $req_rows, $filter_str);
  $of = new output_formatter();
  return $of->rowset_to_jqgrid_XML($rs, $total_entries, $req_page, $req_rows, $total_pages);
}


// code starts here


try{
  $special_table = ($_REQUEST['table'] == "aixada_user");
  $ignore_keys = array('oper' => 1, 'table' => 2, 'key' => 3, 'val' => 4, 
                       'USERAUTH' => 5, 'PHPSESSID' => 6, 'logintheme' => 7, 
                       'cprelogin' => 8, 'cpsession' => 9, 'lang' => 10, 
                       'langedit' => 11);
    // we need the => 1 etc values for array_diff_key to work later on.

  if (!isset($_REQUEST['oper']))
    throw new Exception("ctrlTableManager: variable oper not set in query");

  switch($_REQUEST['oper']) {
  case 'getColumnsAsJSON':
      echo get_columns_as_JSON();
      exit;

  case 'listAll':
    printXML(get_list_all_XML());
    exit;

  case 'get_by_key':
    printXML(query_XML("select * from {$_REQUEST['table']} where :1=:2q", 'rowset', 'row', $_REQUEST['key'], $_REQUEST['val']));
    exit;

  case 'edit':
    $arrData = array_diff_key($_REQUEST, $ignore_keys); 
    DBWrap::get_instance()->Update($_REQUEST['table'], $arrData);
    echo '1';
    exit;
    
  case 'add':
    $arrData = array_diff_key($_REQUEST, $ignore_keys);
    return DBWrap::get_instance()->Insert($_REQUEST['table'], $arrData);
//     $tm->create($_REQUEST);
//     echo '1';
    break;
    
  case 'del':
      DBWrap::get_instance()->Delete($_REQUEST['table'], $_REQUEST['id']);
      echo '1';
      exit;
  }

  require_once 'lib/table_manager.php';
  if (!$special_table)
    $tm = new table_manager($_REQUEST['table'], 
			    configuration_vars::get_instance()->use_session_cache);
  //  $firephp->log($tm, 'tm');
  //  $firephp->log($_REQUEST, 'request');

  switch ($_REQUEST['oper']) {
  case 'get_by_id':
    $id = $_REQUEST['id'];  // FIXME
    $rs = $tm->get_by_id($id);
    $strXML = $tm->rowset_to_jqGrid_XML($rs); 
    
    printXML($strXML);
    break;

    
  case 'get_empty':
    $strXML = $tm->get_empty();
    printXML($strXML);
    break;
        
  }
} 

catch(Exception $e) {
  header('HTTP/1.0 401 ' . $e->getMessage());
  die($e->getMessage());
}  


?>