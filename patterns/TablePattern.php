<?php
class TablePattern Extends TemplatePattern
{
  /**
   * Stores if the page should be paginated
   *
   * @var boolean
   */
  private $_paginate = false;
  
  /**
   * Holds the number of elements for each page
   *
   * @var integer
   */
  private $_page_size = 10;
  
  /**
   * Which page to show
   *
   * @var integer
   */
  private $_page_number = 0;
  
  /**
   * Number of pages
   *
   * @var integer
   */
  private $_pages = 0;
  
  /**
   * Holds all the raw data that will be listed
   * @var array
   */
  private $_rows    = array();
  
  /**
   * Holds total rows to be listed
   * @var int
   */
  private $_rows_count    = 0;
  
  /**
   * Holds how many items in the list are needed to trigger that
   * actions configured to appear in both bottom and top, actually
   * apears in both places..
   * @var int
   */
  private $_actions_overflow_trigger   = 10;
   
  /**
   * Holds the field names that will form the table header
   * @var array
   */
  private $_fields  = array();
  
  /**
   * Holds the field names for wich the query can be ordered by
   * @var array
   */
  private $_order_by  = array();
  
  /**
   * Holds the links that will be embedded into some fields
   * @var array
   */
  private $_links   = array();
  
  /**
   * Holds the links that will be added in the last column
   * @var array
   */
  private $_actions = array();
  
  /**
   * Holds the prefix that'll be used to create an unique id for every row
   * @var string
   */
  private $_prefix  = '';
  
  /**
   * Holds Key Bindings for the General Actions
   * Key => Action
   * @var array
   */
  private $_keybindings = array();
  
  /**
   * Holds filter options and configuration
   * @var array
   */
  private $_filters   = array();
  
  /**
   * Points to the field that will be used to create the unique id
   * @var string
   */
  private $_row_id  = '';
  
  private $_loaded = false;
    
  public function __construct($template = '')
  {
    if ( empty($template) ) {
      $this->setTemplate(THAFRAME . '/patterns/templates/TablePattern.tpl.php', true);
    } else {
      $this->setTemplate($template);
    }
  }
   
  public function setQuery($sql, DbConnection $DbConnection=null, $paginate = false)
  {
    if ( !isset($DbConnection) ) {
      $DbConnection = DbConnection::getInstance();
    }
    if ($paginate) {
      $this->_paginate = true;
      //Get a Grip of the whole thing
      $sql_without_conditions = str_replace('{conditions}','',$sql);
      $count_sql = "SELECT count(*) FROM ($sql_without_conditions) AS count_table;";
      $total_rows = $DbConnection->getOneValue($count_sql);
      
      //Create some basic pattern variables
      $this->_page_number = (empty($_GET['__page_number']))?'0':$_GET['__page_number'];
      $this->_page_size = (empty($_GET['__page_size']))?10:$_GET['__page_size'];
      $this->_pages = ceil($total_rows/$this->_page_size);
      
      if($this->_page_number > $this->_pages){
        $this->_page_number = $this->_pages-1;
      }
      
      //Reformat the query to use MySQL Limit clause
      $page_start = $this->_page_number * $this->_page_size;
      $sql = "$sql
            LIMIT $page_start, $this->_page_size";
      
      $this->setPatternVariable('paginate', $this->_paginate);
      $this->setPatternVariable('page_number', $this->_page_number);
      $this->setPatternVariable('page_size',$this->_page_size);
      $this->setPatternVariable('pages', $this->_pages);
    }
    
    $conditions = '';
    if( count($this->_filters) ) {
      foreach($this->_filters AS $field => $filter) {
        $Filter = (object)$filter;
        if($Filter->type=='custom') {
          //echo "Checking for filter on '$field'\n";
          if( isset($_GET[$field]) ) {
            $selected = stripslashes($_GET[$field]);
          } else {
            $selected = $this->_filters[$field]['default'];
          }
          //echo "Selected:$selected\n";
          foreach($Filter->options AS $option){
            //echo "Comparing value: {$option['value']}\n";
            if ( $option['value'] == $selected) {
                //echo "Match!, condition added '{$option['condition']}'\n\n";
                $conditions .= "\nAND ";
                $conditions .= $option['condition'];
                $this->_filters[$field]['selected']= $selected;
            }
          }
        } else if($Filter->type=='hidden') {
          $conditions .= "\nAND ";
          $conditions .= $Filter->condition;
        }
      }
    }

    if ( empty($conditions) ) {
      $sql = str_replace('{conditions}','',$sql);
    } else {
      $sql = str_replace('{conditions}', $conditions, $sql);
    }
    if ( count($this->_order_by) ) {
      foreach($this->_order_by AS $field) {
        if( isset($_GET["order_by_{$field}"]) ) {
          $this->_selected_order_field = $field; 
          $this->_selected_order = $_GET["order_by_{$field}"];
        }
      }
    }
    
    if ( empty($this->_selected_order_field) ) {
      $sql = str_replace('{order_by}','',$sql);
    } else {
      $order_by = "ORDER BY {$this->_selected_order_field} {$this->_selected_order}";
      $sql = str_replace('{order_by}', $order_by, $sql);
    }
    
    $rows = $DbConnection->getAllRows($sql);
    $this->setRows($rows);
  }
  
  /**
   * Names each row with an unique id.
   *
   * It's formed with the prefix + the given field's value. Used to provide
   * named items that can be referred easily with {@link xajax} and javascript
   * @param string $prefix the prefix
   * @param string $field the field
   * @return void
   */
  public function setRowId($prefix, $field)
  {
    $this->_prefix = $prefix;
    $this->_row_id = $field;
  }
  
  /**
   * Sets the name of a field as will be show in the table header.
   *
   * If not customized this name is created by replacing underscores with espaces
   * and capitalizing each word in the field name.
   * @param string $field the field where the name will be changed
   * @param string $name the new name
   * @return void
   */
  public function setName($field, $name)
  {
    if ( isset($this->_fields[$field]) ) {
      $this->_fields[$field] = $name;
    }
  }
  
  public function setFormat($field, $function)
  {
    if( function_exists($function) && is_array($this->_rows) ) {
      for($i=0; $i<count($this->_rows); $i++)
      {
        $this->_rows[$i][$field]=$function($this->_rows[$i][$field]);
      }
    } else {
      if(!function_exists($function)) {
       Logger::log("Missing function on field:'$field'", $function, LOGGER_NOTICE); 
      }
    }
  }
  
  /**
   * Avoid the given field to be show.
   *
   * Commonly used to hide the table id, but still allow its use in
   * {@link addAction()} or {@link addLink()} as $value.
   * @param string $field the name of the field to hide
   * @return void
   */
  public function hideField($field)
  {
    unset( $this->_fields[$field] );
  }
  
  /**
   * Adds an action link embedded into the given field.
   * @param string $field The field that will have the embedded link (ie name).
   * @param string $value The field that will serve as the value (ie item_id).
   * @param string $action The action to be performed (aka URL).
   * @param string $title An optional title for the link.
   * @return void
   */
  public function addLink($field, $value, $action, $title='' )
  {
    $aux = array (
        'value'   => $value ,
        'action'  => $action ,
        'title'   => $title
      );
    $this->_links[$field] = $aux;
  }
  
  /**
   * Adds an action link at the end of the row
   * @param string $value The field that will serve as the value (ie item_id)
   * @param string $action The action to be performed, can be xajax or an URL
   * @param string $title The title of the link
   * @param string $icon An optional icon, if not provided a regular link is created
   * @param bool   $ajax Tells if the action is xajax or a regular URL
   * @return void
   * @todo create a multiple parameter action creator
   */
  public function addAction($value, $action, $title, $icon='', $ajax=false)
  {
    $aux = array (
        'value'   => $value ,
        'action'  => $action ,
        'title'   => $title,
        'icon'    => $icon,
        'ajax'    => $ajax,
      );
    if(strpos($value,',')!==false){
      $single_values = explode(',', $value);
      $values = array();
      foreach($single_values AS $single_value){
        $values[]=trim($single_value);
      }
      $aux['value'] = $values;
    }
    $this->_actions[] = $aux;
  }
  
  /**
   * Add an action to the end & start of the Form, commonly used to add a
   * "Delete" link
   *
   * @param string $action The action that will be called after clicking (url)
   * @param string $title The text to show and will be added to the url title as well
   * @param string $field The field to add into de URL
   * @param string $value The value that such field should take usally 0 for new elements
   * @param string $icon  The optional icon that could go with the text
   * @return void
   */
  public function AddGeneralAction($action, $title, $icon='', $ajax=false, $show_in='bottom', $keybinding='')
  {
    $show_in = (!empty($show_in))?$show_in:'bottom';
    $aux = array (
        'action'  => $action,
        'title'   => $title,
        'icon'    => $icon,
        'ajax'    => $ajax,
        'show_in' => $show_in,
      );
    if ( !empty($keybinding) ) {
      $this->addKeyBinding($keybinding, $action);
    }
    $this->_general_actions[] = $aux;
  }
  
  public function addKeyBinding($keybinding, $action) {
    $this->_keybinding[$keybinding]=$action;
  }
  
  /**
   * Adds a filter form to the list
   * @param $field
   * @param $label
   * @param $type
   * @return bool
   */
  public function addFilter($field, $label, $type='custom', $extra_data=array())
  {
    $aux = array (
        'label' => $label,
        'type'  => $type,
        'empty' => $empty,
        'options' => array()
    );
    if($type == 'active' || $type == 'yes_no') {
      $aux['type'] = 'custom';
      $aux['options'][] = array('label'=>'Yes', 'value'=>'1' , 'condition'=>$extra_data['prefix']."$field='1'");
      $aux['options'][] = array('label'=>'No',  'value'=>'0' , 'condition'=>$extra_data['prefix']."$field='0'");
    }
    $this->_filters[$field] = $aux;
  }
  
  public function addHiddenFilter($field, $value, $condition)
  {
    $aux = array (
        'type'  => 'hidden',
        'value' => $value,
        'condition' => $condition
    );
    $this->_filters[$field] = $aux;
  }
  
  /**
   * Adds a filter option
   * @param $field
   * @param $value
   * @param $label
   * @param $default
   * @param $condition
   * @return bool
   */
  public function addFilterOption($field, $value, $label, $default=FALSE, $condition='')
  {
    $aux = array (
        'label' => $label,
        'value' => $value,
        'condition' => $condition,
    );
    if($default) {
      $this->_filters[$field]['default'] = $value;
    }
    $this->_filters[$field]['options'][] = $aux;
  }
  
  public function setFilterDefault($field, $value) {
    $this->_filters[$field]['default'] = $value;
  }
  public function addFilterOptions($field, $values, $condition)
  {
    if (is_array($values) ) {
      foreach($values as $value=>$label) {
        $search  = array('{value}', '{label}');
        $replace = array(mysql_escape_string($value), mysql_escape_string($label));
        $replaced_condition = str_replace($search, $replace, $condition);
        $this->addFilterOption($field, $value, $label, false, $replaced_condition);
      }
    }
  }
  
  /**
   * Set the raw data that will be show.
   *
   * Field names to be used as table headers are extracted and formatted in this
   * phase, they can of course be overrride using {@link setName}
   * @param  array $rows the raw data
   * @return void
   */
  private function setRows($rows) {
    $this->_rows = $rows;
    $this->_rows_count = count($this->_rows);
    
    if ( $rows ) {
      $fields_names = array_keys($rows[0]);
      foreach($fields_names AS $field_name)
      {
        $this->_fields[$field_name] = ucwords(str_replace('_', ' ',$field_name));
      }
    }
    
    $this->_loaded = true;
  }
    
  /**
   * Display the selected template with the given data and customization
   * @return void
   */
  public function getAsString()
  {
    $this->assign('__rows'       , $this->_rows);
    $this->assign('__rows_count' , $this->_rows_count);
    $this->assign('__fields'     , $this->_fields);
    $this->assign('__links'      , $this->_links);
    $this->assign('__actions'    , $this->_actions);
    $this->assign('__prefix'     , $this->_prefix);
    $this->assign('__row_id'     , $this->_row_id);
    $this->assign('__filters'    , $this->_filters);
    $this->assign('__general_actions'         , $this->_general_actions);
    $this->assign('__actions_overflow_trigger', $this->_actions_overflow_trigger);
    $this->assign('__order_by',          $this->_order_by);
    $this->assign('__selected_order_by', $this->_selected_order_field);
    $this->assign('__selected_order',    $this->_selected_order);
    return parent::getAsString();
  }
  
  /**
   * Loads information from a config file
   * 
   * $config_name maps to TO_ROOT/configs/models/{class_name}_{config_name}.ini by default
   * 
   * @param string $config_name 
   * @param boolean $use_class_name selects if the class_name prefix should be added.
   */
  public function loadConfig($config_name='default', $vars=array()) {
    $DbConnection = DbConnection::getInstance();
    
    $file_name = TO_ROOT."/configs/models/{$config_name}_list.yaml";
    
    if(!file_exists($file_name)){
      Logger::log("Couldn't find config file", $file_name, LOGGER_ERROR);
      return false;
    }
    $config = ConfigParser::parsea_mesta($file_name, $vars);
    
    if( isset($config['__general']['page_name']) ) {
      $this->setPageName($config['__general']['page_name']); 
    }
    unset($config['__general']);
    
    if( isset($config['__row_id']) ) {
      $this->setRowId($config['__row_id']['prefix'], $config['__row_id']['field']);
    }
    unset($config['__row_id']);
    
    if( isset($config['__links']) ) {
      foreach($config['__links'] AS $field => $properties) {
        $this->addLink($field, $properties['value'], $properties['action'], $properties['title']);
      }
    }
    unset($config['__links']);
    
    if( isset($config['__filters']) ) {
      foreach($config['__filters'] AS $field => $properties) {
        if ($properties['delete'] != true){
        if($properties['type']=='hidden') {
          $this->addHiddenFilter($field, $properties['value'], $properties['condition']);
        } else {
          
          if ( isset($properties['prefix']) ) {
            $this->addFilter($field, $properties['label'], $properties['type'], array('prefix'=>$properties['prefix']));
          } else {
            $this->addFilter($field, $properties['label'], $properties['type']);
          }
          if($properties['add_all']) {
            $this->addFilterOption($field, 'all', 'All', false, '1');
          }
          
          if( isset($properties['default']) ) {
            $this->setFilterDefault($field, $properties['default']);
          }
          
          if($properties['type']=='active' && $properties['type']=='yes_no') {
            
          } else if ($properties['type']=='custom') {
            if ( isset($properties['options_query']) ) {
              $DbConnection == DbConnection::getInstance();
              $options =  $DbConnection->getArrayPair($properties['options_query']);
              $this->addFilterOptions($field, $options, $properties['condition']);
            }else if(isset($properties['options'])) {
              $this->addFilterOptions($field, $properties['options'], $properties['condition']); 
            }
          }
        }
      }
      }
    }
    unset($config['__filters']);
    
    if( isset($config['__commands']['order_by']) ) {
      $this->orderBy($config['__commands']['order_by']); 
    }
    
    if( isset($config['__query']['sql']) ) {
      $paginate = (isset($config['__query']['paginate']))?$config['__query']['paginate']:false;
      $this->setQuery($config['__query']['sql'], DbConnection::getInstance(), $paginate); 
    }
    unset($config['__query']);
    
    if( isset($config['__pattern']) ) {
      foreach($config['__pattern'] AS $field=>$value) {
        $this->setPatternVariable($field, $value);
      }
    }
    unset($config['__pattern']);
    
    if( isset($config['__commands']['hide']) ) {
      foreach($config['__commands']['hide'] AS $hide) {
        $this->hideField($hide);
      }
    }
    unset($config['__commands']);
    
    if( isset($config['__generalAction']) ) {
      foreach($config['__generalAction'] AS $properties) {
        $this->AddGeneralAction($properties['action'], $properties['title'], $properties['icon'], $properties['ajax'], $properties['show_in'], $properties['keybinding']);
      }
    }
    unset($config['__generalAction']);

    if( isset($config['__action']) ) {
      foreach($config['__action'] AS $properties) {
        $this->addAction($properties['value'], $properties['action'], $properties['title'], $properties['icon'], $properties['ajax']);
      }
    }
    unset($config['__action']);
    
    foreach($config AS $field => $properties){
      if (is_array($properties)) {
        foreach($properties AS $property => $value) {
          if ($property=='format') {
            $this->setFormat($field, $value);
          } else if($property=='name') {
            $this->setName($field, $value);
          } else if($property=='class') {
            $this->setClass($field, $value);
          }
        }
      }
    }
    return true;
  }
  public function getKeyBindingsScript() {
    $script = "";
    foreach($this->_keybinding AS $key => $action) {
      $script .= "myBindings[\"$key\"] =\"". $action ."\";\n";
    }
    return $script;
  }
  
  public function isLoaded() {
    return $this->_loaded;
  }
  
  public function orderBy($fields) {
    if ( !is_array($fields) ) {
      throw new InvalidArgumentException('Fields are supposed to be an array');
    }
    $this->_order_by = $fields; 
  }
}