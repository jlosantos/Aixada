<?php

require_once('inc/database.php');
require_once('local_config/config.php');
require_once('inc/caching.inc.php');
$language = ( (isset($_SESSION['userdata']['language']) and 
               $_SESSION['userdata']['language'] != '') ? 
              $_SESSION['userdata']['language'] : 
              configuration_vars::get_instance()->default_language );
require_once('local_config/lang/' . $language . '.php');


//require_once('FirePHPCore/lib/FirePHPCore/FirePHP.class.php');
//ob_start(); // Starts FirePHP output buffering
//$firephp = FirePHP::getInstance(true);

//DBWrap::get_instance()->debug = true;

function purge_cache($sql_func)
{
    $cv = configuration_vars::get_instance();
//     global $firephp;
//     $firephp->log($cv);
//     $firephp->log($cv->tables_modified_by);
//     exit();
/*
    if (array_key_exists($sql_func, $cv->tables_modified_by)) {
        $cache_dir = $cv->cache_dir;
        foreach($cv->tables_modified_by[$sql_func] as $table) {
            foreach(glob($cache_dir . $table . '*') as $filename) {
                unlink($filename);
            }
        }
    }
*/

}

function clean_zeros($value)
{
  return ((strpos($value, '.') !== false) ?
	  rtrim(rtrim($value, '0'), '.') 
	  : $value);
}

/**
 * Execute a stored query
 * @param array $args the arguments to be passed to the stored query; possibly empty
 * @return the result set
 */

function do_stored_query()
{
  $args = func_get_args();
  if (is_array($args[0])) {
    $args = $args[0];
  }
  for ($i=1; $i<count($args); ++$i) {
    if (is_array($args[$i])) {
      $args[$i] = $args[$i][0];
    }
  }
//     global $firephp;
//     $firephp->log($args, 'do_stored_query args');
  $sql_func = array_shift($args);
  purge_cache($sql_func);

  $strSQL = 'CALL ' . $sql_func . '(';
  foreach ($args as $arg) {
      if (strpos($arg, "'") !== false) {
          if (strpos($arg, '"') !== false)
              throw new DataException('Cannot use both symbols \' and " in text');
          $strSQL .= '"' . $arg . '",';
      } else 
          $strSQL .= "'" . $arg . "',";
  }
  if (count($args))
    $strSQL = rtrim($strSQL, ',');
  $strSQL .= ')';

  return DBWrap::get_instance()->do_stored_query($strSQL);
}

class output_formatter {
  public function rowset_to_jqgrid_XML($rs, $total_entries=0, $page=0, $limit=0, $total_pages=0, $cache_name='')
  {
    $strXML = '';
    if ($rs) {
      $strXML .= '<rowset>';
      if ($page) 
	$strXML .= '<page>' . $page . '</page>'; 
      if ($total_pages)
	$strXML .= '<total>' . $total_pages . '</total>';
      $strXML .= '<records>' . $total_entries . '</records>';
      $strXML .= "<rows>";
      while ($row = $rs->fetch_assoc()) 
	$strXML .= $this->row_to_XML($row, $cache_name);
      $rs->free();
      $strXML .= "</rows>";
      $strXML .= "</rowset>";
    }
    return $strXML;
  }

  public function row_to_XML($row, $cache_name='')
  {
      //      global $firephp;
      global $Text;
      $strXML = '<row id="' . $row['id'] . '">';
      $rowXML = '';
      foreach ($row as $field => $value) {
          if (isset($Text[$value]))
              $value = $Text[$value];
          $rowXML 
              .= '<' . $field . ' f="' . $field 
              . '"><![CDATA[' . clean_zeros($value) . "]]></$field>";
      }
      //      $firephp->log($rowXML, 'row_xml'));
      $strXML .= $rowXML . '</row>';
      return $strXML;
  }
}

function stored_query_XML() //$queryname, $group_tag, $row_tag, $param)
{
  $params = func_get_args();
  $strSQL = array_shift($params);
  $group_tag = array_shift($params);
  $row_tag = array_shift($params);
  array_unshift($params, $strSQL);

  $cache = new QueryCache($params);
  if ($cache->exists())
      return $cache->read();

  $strXML = "<$group_tag>";
//   $rs = ((count($params)>0) ? 
// 	 do_stored_query($strSQL, $params)
// 	 : do_stored_query($strSQL));
  $rs = do_stored_query($params);
  global $Text;
  while ($row = $rs->fetch_array()) {
      $value = ( ($row_tag == 'description' and isset($Text[$row[1]])) ? 
                 $Text[$row[1]] : $row[1] );
      $strXML 
          .= '<row><id f="id">' . $row[0] 
          . '</id><' . $row_tag 
          . ' f="' . $row_tag 
          . '"><![CDATA[' . clean_zeros($value) . ']]></' . $row_tag 
          . '></row>'; 
  }
  $strXML .= "</$group_tag>";
  $cache->write($strXML);
  return $strXML;
}

// make variable argument list
function stored_query_XML_fields()
{
    $cache = new QueryCache(func_get_args());
    if ($cache->exists())
        return $cache->read();
    
    $strXML = '<rowset>';
    global $Text;
    $rs = do_stored_query(func_get_args());
    while ($row = $rs->fetch_assoc()) {
        $strXML .= '<row';
        if (isset($row['id'])) 
            $strXML .= ' id ="' . $row['id'] . '"';
        $strXML .= '>';
        foreach ($row as $field => $value) {
            if ($field == 'description' and isset($Text[$value])) 
                $value = $Text[$value];
            $strXML 
                .= '<' . $field . ' f="' . $field 
                . '"><![CDATA[' . clean_zeros($value) . "]]></$field>";
        }
        $strXML .= '</row>';
    }
    $strXML .= '</rowset>';
    $cache->write($strXML);
    return $strXML;
}

function query_XML() //$strSQL, $group_tag, $row_tag, $param1=0, $param2=0)
{
  $params = func_get_args();
  $strSQL = array_shift($params);
  $group_tag = array_shift($params);
  $row_tag = array_shift($params);
  array_unshift($params, $strSQL);

  $cache = new QueryCache($params);
  if ($cache->exists())
      return $cache->read();

  $strXML = "<$group_tag>";
  $rs = DBWrap::get_instance()->Execute($params);
  while ($row = $rs->fetch_array()) {
      $value = ( ($row_tag == 'description' and isset($Text[$row[1]])) ? 
                 $Text[$row[1]] : $row[1] );
      $strXML 
          .= '<row><id f="id">' . $row[0] 
          . '</id><' . $row_tag 
          . ' f="' . $row_tag 
          . '"><![CDATA[' . $value . ']]></' . $row_tag 
          . '></row>'; 
  }
  $strXML .= "</$group_tag>";
  $cache->write($strXML);
  return $strXML;
}

function query_XML_compact()
{
  $params = func_get_args();
  $strSQL = array_shift($params);
  $group_tag = array_shift($params);
  $row_tag = array_shift($params);
  array_unshift($params, $strSQL);

  $cache = new QueryCache($params);
  if ($cache->exists())
      return $cache->read();

  $strXML = "<$group_tag>";
  $rs = DBWrap::get_instance()->Execute($params);
  while ($row = $rs->fetch_array()) {
    $strXML 
      .= '<' . $row_tag 
      . ' f="' . $row_tag 
      . '"><![CDATA[' . $row[0] . ']]></' . $row_tag 
      . '>'; 
  }
  $strXML .= "</$group_tag>";
  $cache->write($strXML);
  return $strXML;
}

function query_XML_noparam($queryname)
{
  $cache = new QueryCache($queryname);
  if ($cache->exists())
      return $cache->read();

  $strXML = "<$queryname>";
  $rs = do_stored_query($queryname);
  while ($row = $rs->fetch_assoc()) {
  	 $strXML .= "<row>";
      foreach ($row as $field => $value) {
          if ($field == 'description' and isset($Text[$value])) 
              $value = $Text[$value];
          $strXML .= "<{$field}>{$value}</{$field}>";
      }
       $strXML .= "</row>";
  }
  $strXML .= "</$queryname>";
  $cache->write($strXML);
  return $strXML;
}

function printXML($str){
  $newstr = '<?xml version="1.0" encoding="utf-8"?>';  
  $newstr .= $str; 
  header('Content-Type: text/xml');
  header('Last-Modified: '.date(DATE_RFC822));
  header('Pragma: no-cache');
  header('Cache-Control: no-cache, must-revalidate');
  header('Expires: '. date(DATE_RFC822, time() - 3600));
  header('Content-Length: ' . strlen($newstr));
  echo $newstr;
}

function HTMLwrite($strHTML, $filename)
{
  if(is_writeable($filename)) {
    if (!$handle = fopen($filename, 'w')) {
      echo "Cannot open file ($filename)";
      exit;
    }
    if (fwrite($handle, $strHTML) === FALSE) {
      echo "Cannot write to file ($filename)";
      exit;
    }
    fclose($handle);
  } else {
      echo "The file $filename is not writable";
  }
}

function get_config_menu($user_role)
{
    $XML = "<navigation>\n";
    $mconf = configuration_vars::get_instance()->menu_config;
    if (!isset($mconf[$user_role])) {
        throw new Exception("Role '" . $user_role . "' not defined in local_config/config.php");
    }
    foreach ($mconf[$user_role] as $navItem => $status) {
        $XML .= '<' . $navItem . '>' . $status . '</' . $navItem . ">\n";
    } 
    return $XML . '</navigation>';
}


/**
 * Returns $num=10 possible sales dates later than the given start
 * date. The dates are guaranteed to be active. 
 * @param $_start_date date defaults to today
 * @param $num int how many sales days to output. Defaults to 10.
 * @return $sales_dates array(date) 
 */
function get_10_sales_dates_XML($_start_date=0, $num=10)
{
    $xml = stored_query_XML_fields('get_sales_dates', 0, $num);
    if (strpos('<rowset></rowset>', $xml) !== false) {
        $today = '<row><date_for_order f="date_for_order"><![CDATA[' 
            . strftime('%Y-%m-%d', strtotime("now")) 
            . ']]></date_for_order></row>';
        $xml = str_replace('<rowset></rowset>', 
                           '<rowset>' . $today . '</rowset>', $xml);
    }
    return $xml;
}

function get_next_equal_shop_date_XML()
{
    return stored_query_XML_fields('get_next_equal_shop_date');
}

/**
 * Calculate the date of the next sales day not including the given
 * $start_date (which defaults to the current date). 
 * @param $start_date date default 0 means 'counting from today'.
 */ 
function get_next_shop_date_XML($start_date=0)
{
    return  stored_query_XML_fields('get_sales_dates', $start_date, 1);
}

function get_next_shop_date($start_date=0)
{
    $rs = do_stored_query('get_sales_dates', $start_date, 1);
    $row = $rs->fetch_array();  // putative date of sale
    DBWrap::get_instance()->free_next_results();
    return $row[0];
}

function get_field_options_live($table, $field1, $field2)
{
    global $Text;
    $strXML = '<select>';
    $rs = DBWrap::get_instance()->Execute('select :1, :2 from :3', $field1, $field2, $table);
    if ($table == 'aixada_uf') {
        $strXML .= "<option value=''></option>";
    }
    while ($row = $rs->fetch_array()) {
        $ot = (isset($Text[$row[1]]) ? $Text[$row[1]] : $row[1]);
        if ($table == 'aixada_uf')
            $ot = //$Text['uf_short'] . ' ' . 
                $row[0] . ' ' . $ot;
        $strXML .= "<option value='{$row[0]}'>{$ot}</option>";
    }
    return $strXML . '</select>';
}

function existing_languages()
{
    // We require that a line of the form 
    // $Text['es_es'] = 'Español'
    // exists in each language file
    $languages = array();
    foreach (glob("local_config/lang/*.php") as $lang_file) {
        $a = strpos($lang_file, 'lang/');
        $lang = substr($lang_file, $a+5, strpos($lang_file, '.')-$a-5);
        $handle = @fopen($lang_file, "r");
        $line = fgets($handle);
        while (strpos($line, "Text['{$lang}']") === false and !feof($handle)) {
            $line = fgets($handle);            
        }
        if (feof($handle))
            $lang_desc = '';
        else {
            $tmp = trim(substr($line, strpos($line, '=')));
            $lang_desc = trim($tmp, " =;'\"");
        }
        $languages[$lang] = $lang_desc;
    }
    return $languages;
}

function existing_languages_XML()
{
    // We require that a line of the form 
    // $Text['es_es'] = 'Español'
    // exists in each language file
    $XML = '<languages>';
    foreach (existing_languages() as $lang => $lang_desc) {
        $XML .= "<language><id>{$lang}</id><description>{$lang_desc} ({$lang})</description></language>";
    }
    return $XML . '</languages>';
}

function get_roles()
{
    $XML = '<roles>';
    foreach (array_keys(configuration_vars::get_instance()->forbidden_pages) as $role) {
        $XML .= "<role><description>{$role}</description></role>";
    }
    return $XML . '</roles>';
}

function get_commissions()
{
    //    global $firephp;
    $XML = '<rows>';
    foreach (array_keys(configuration_vars::get_instance()->forbidden_pages) as $role) {
        if (!in_array($role, array('Consumer', 'Checkout', 'Producer'))) {
            //            $firephp->log($role);
            $XML .= "<row><description>{$role}</description></row>";
        }
    }
    return $XML . '</rows>';
}

?>