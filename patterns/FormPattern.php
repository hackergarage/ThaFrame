<?php
class FormPattern Extends TemplatePattern
{
  
  /**
   * This is the Row to be edited
   * @var RowModel
   */
  private $Row    = null;
   
  /**
   * Holds the field's configuration structure
   *
   * fields['name'] {
   *   + label: The label.
   *   + value: The default value.
   *   + help:  Little text to be show next to the field.
   *   + error_message: If set this message will be show in red below the field.
   *   + type: text, hidden, radio, select, textarea, date
   *   + parameters {}: type specific parameter.
   *   + input_parameters {}: Auto parsed input parameters.
   *   + validation: Function to be applied as validation, the function must get a
   *               string and return true for success or false for invalid input.
   * }
   * @var array
   */
  private $fields  = array();
  
  /**
  * Holds the number of fields that this {@link Edit} has
   * @var int
   */
  private $no_fields = 0;
  
  /**
   * Holds actions that will be rendered at begining or/and end of the form
   * @var array
   */
  private $general_actions = array();
  
  /**
   * Holds dependents information
   * @var array
   */
  private $dependents = array();
  
  /**
   * Defaults to Single but can be changes to multipart with setType
   * @var $type string
   */
  private $type='single';
  
  private $action='';
  
  private $method='post';
  
  /**
  * Holds conditions information
   * @var array
   */
  private $conditions = array();
  
  /**
   * The id of the form, {@link Edit} forms id are 'main_form' by hardcoded
   * this one is 'secondary_form'.
   * @var string
   */
  private $form_id = "secondary_form";
  
  
  /**
   * Construct a {@link Edit} page
   * @param string $page_name the page name to be shown
   * @param string $template by default it uses Edit.tpl.php
   * @return Edit
   */
  public function __construct($template='')
  {
    if ( empty($template) ) {
      $this->setTemplate(THAFRAME . '/patterns/templates/FormPattern.tpl.php', true);
    } else {
      $this->setTemplate($template);
    }
  }
  
  public function setType($type) {
    $this->type=$type;
  }
  
  public function setAction($action) {
    $this->action=$action;
  }
  
  public function setMethod($method) {
    $this->method=$method;
  }
  
  /**
   * Loads information from a config file
   * 
   * $config_name maps to TO_ROOT/configs/models/{class_name}_{config_name}.ini by default
   * 
   * @param string $config_name 
   * @param boolean $use_class_name selects if the class_name prefix should be added.
   */
  public function loadConfig($config_name='default', $use_class_name = true) {
    $DbConnection = DbConnection::getInstance();
    
    if($use_class_name) {
      $prefix = strtolower(get_class($this->Row));
      $file_name = TO_ROOT."/configs/models/{$prefix}_{$config_name}.ini";
    } else {
      $file_name = TO_ROOT."/configs/models/{$config_name}.ini";
    }
    if(!file_exists($file_name)){
      Logger::log("Couldn't find config file", $file_name, LOGGER_ERROR);
      return false;
    }
    $config = parse_ini_file($file_name, true);
    
    if( isset($config['__general']['form_id']) ) {
      $this->setFormId($config['__general']['form_id']); 
      
    }
    if( isset($config['__general']['action']) ) {
      $this->setAction($config['__general']['action']); 
      
    }
    if( isset($config['__general']['type']) ) {
      $this->setType($config['__general']['type']); 
    }
    if( isset($config['__general']['method']) ) {
      $this->setMethod($config['__general']['method']); 
    }
    unset($config['__general']);
  
    if( isset($config['__pattern']) ) {
      foreach($config['__pattern'] AS $field=>$value) {
        $this->setPatternVariable($field, $value);
      }
    }
    unset($config['__pattern']);
    
    if( isset($config['__commands']['delete']) ) {
      foreach($config['__commands']['delete'] AS $delete) {
        $this->deleteField($delete);
      }
    }
    if( isset($config['__commands']['hide']) ) {
      foreach($config['__commands']['hide'] AS $hide) {
        $this->hideField($hide);
      }
    }
    if( isset($config['__commands']['disable']) ) {
      foreach($config['__commands']['disable'] AS $disable) {
        $this->disableField($disable);
      }
    }
    unset($config['__commands']);
    
    foreach($config AS $field => $properties){
      
      if ( strpos($field, ':')!==false ) {
        list($field, $action) = explode(':', $field);
        
        if ( $field=='__generalAction' ) {
          $this->AddGeneralAction($properties['action'], $properties['title'], $properties['icon'], $properties['ajax']);
        } else {
         if  ($action == 'parameters') {
            foreach($properties AS $parameter => $value) {
              $this->setFieldParameter($field, $parameter, $value);
            }
          } else if($action == 'input_parameters') {
              foreach($properties AS $parameter => $value) {
                $this->setFieldInputParameter($field, $parameter, $value);
              }
          } else if($action == 'action') {
            $this->addAction($field, $properties['action'], $properties['title'], $properties['icon'], $properties['ajax']);
          } else if($action == 'splitter') {
            $this->insertSplitter($field, $properties['content'], $properties['position'], $field);
          } else if($action == 'linked') {
            $this->setAsLinked($field, $properties['table_name'], $DbConnection, $properties['table_id'], $properties['name_field'], $properties['condition']);
          } else if($action == 'dependent') {
            $this->setFieldDependents($field, $properties['condition'], $properties['value'], $properties['dependants']);
          } else if($action == 'add') {
            $data = array(
                'label' => ucwords(str_replace('_', ' ', $field)),
                'type' => 'text',
                'input_parameters'=> array('maxlength' => 45),
              );
            $this->insertField($field, $data, $properties['target'], $properties['position']);
          }
        }
      } else {
        foreach($properties AS $property => $value) {
        $this->setFieldProperty($field, $property, $value);
        }
      }
    }
    return true;
  }
  /**
   * Adds an action link at the end of the field
   * @param string $value The field that will serve as the value (ie item_id)
   * @param string $action The action to be performed, can be xajax or an URL
   * @param string $title The title of the link
   * @param string $icon An optional icon, if not provided a regular link is created
   * @param bool   $ajax Tells if the action is xajax or a regular URL
   * @return void
   * @todo create a multiple parameter action creator
   */
  public function addAction($field, $action, $title, $icon='', $ajax=true)
  {
    $aux = array (
        'action'  => $action ,
        'title'   => $title,
        'icon'    => $icon,
        'ajax'    => $ajax,
      );
    $this->fields[$field]['actions'][] = $aux;
  }
  
  /**
   * Set the Row Object to be edited
   *
   * @param  Row $Row the Row to be edited
   * @return void
   */
  public function setRow(RowModel $Row) {
    $this->Row = $Row;
    $this->no_fields = 0;
    
    /** Parse table structure into template friendly data **/
    $structure = $Row->getStructure();
    foreach($structure AS $field)
    {
      $aux = array();
      $name = $field['Field'];
      $aux['label'] = ucwords(str_replace('_', ' ', $name));
      $aux['value'] = (isset($Row->data[$name]))?$Row->data[$name]:$field['Default'];
         
      $this->no_fields++;
      /**
       * Extract type information.
       * $match[0] The whole string. ie: int(11) unsigned
       * $match[1] The type. ie: int
       * $match[2] The type parameters ie: 11
       * $match[3] Extra. ie: unsigned
       */
      preg_match('/^([a-z]*)(?:\((.*)\))?\s?(.*)$/', $field['Type'], $match);
      switch($match[1]){
        case 'varchar':
          if ( $match[2] <= 100 ) {
            $aux['type'] = 'text';
            $aux['size'] = $match[2]; 
            $aux['input_parameters']['maxlength'] = $match[2];
          } else {
            $aux['type'] = 'textarea';
            $aux['input_parameters']['cols'] = '60';
            $aux['input_parameters']['rows'] = '3';
          }
          break;
        case 'char':
          if ( $match[2] <= 100 ) {
            $aux['type'] = 'text';
            $aux['size'] = $match[2]; 
            $aux['input_parameters']['maxlength'] = $match[2];
          } else {
            $aux['type'] = 'textarea';
            $aux['input_parameters']['cols'] = '60';
            $aux['input_parameters']['rows'] = '3';
          }
          break;
        case 'text':
          $aux['type'] = 'textarea';
          $aux['input_parameters']['cols'] = '60';
          $aux['input_parameters']['rows'] = '6';
          break;
        case 'int':
          $aux['type'] = 'text';
          $aux['input_parameters']['maxlength'] = $match[2];
          break;
        case 'date':
          $aux['type'] = 'date';
          $aux['parameters']['before'] = '5';
          $aux['parameters']['after'] = '5';
          break;
        case 'enum':
        case 'set'://Testing
          if ($match[2] == "'0','1'") {
            $options = array('1'=> t('Yes'), '0'=> t('No') );
          } else {
            /** Retrive and parse Options **/
            $options = array();
            $params  = explode("','", $match[2]);
            $params[0] = substr($params[0], 1); //remove the first quote
            $params[ count($params)-1 ] = substr($params[count($params)-1], 0, -1);//remove the second quote
            $options=array_combine($params, $params);//creates a createCombox compatible array
          }
          $aux['type'] = 'select';
          if ( count($options)<=3 ) {
            $aux['type'] = 'radio';
          }
          $aux['parameters']['options']= $options;
          break;
      }
      $this->fields[$name] = $aux;
    }
  }
  
  /**
   * Moves the given field to the start of the form
   * @param string $field the field to be moved
   * @return bool true in success and false otherwise
   */
  public function moveToStart($field)
  {
    if ( isset($this->fields[$field]) ) {
      $aux = array ( $field => $this->fields[$field] );
      unset($this->fields[$field]);
      $this->fields = $aux + $this->fields;
      return true;
    }
    return false;
  }
  
  /**
   * Moves the given field to the end of the form
   * @param string $field the field to be moved
   * @return bool true in success and false otherwise
   */
  public function moveToEnd($field)
  {
    if ( isset($this->fields[$field]) ) {
      $aux = array ( $field => $this->fields[$field] );
      unset($this->fields[$field]);
      $this->fields = $this->fields + $aux;
      return true;
    }
    return false;
  }
  
  /**
   * Moves the given field before another field
   * @param string $field The field to move
   * @param string $before_field The field before the $field will be located
   * @return bool true on success and false otherwise
   */
  public function moveBefore($field, $before_field)
  {
    if ( isset($this->fields[$field]) ) {
      $field_data = $this->fields[$field];
      unset($this->fields[$field]);
      return $this->insertField($field, $field_data, $before_field, 'before');
    }
    return false;
  }
  
  /**
   * Moves the given field before another field
   * @param string $field The field to move
   * @param string $after_field The field after the $field will be located
   * @return bool true on success and false otherwise
   */
  public function moveAfter($field, $after_field)
  {
    if ( isset($this->fields[$field]) ) {
      $field_data = $this->fields[$field];
      unset($this->fields[$field]);
      return $this->insertField($field, $field_data, $after_field, 'after');
    }
    return false;
  }
  
  /**
   * Set as dependent of certain field condition a set of fields.
   * @param string $field The field wich they depend.
   * @param string $condition JavaScript valid condition.
   * @param string $value The value that must match(javascript).
   * @param string $dependents Comma separated list of fields that depend on
   *                           this field value.
   * @return bool true on success and false otherwise.
   */
  public function setFieldDependents($field, $condition, $value, $dependents)
  {
    $aux = array();
    $aux['condition'] = $condition;
    $aux['value']     = $value;
    
    /** Transverse comma separated values into an Array **/
    $aux['dependents'] = array_reverse( array_map('trim', explode(',', $dependents) ) );
    
    /** Locate the dependents after their parent field **/
    foreach ( $aux['dependents'] AS $dependent )
    {
      if( !$this->moveAfter($dependent, $field) ){
        return false;
      }
      $this->setFieldProperty($dependent, 'dependent', true);
    }
    
    $this->setFieldProperty($field, 'parent', true);
    
    $this->dependents[$field]['all_fields'] = array_unique(array_merge((array)$this->dependents[$field]['all_fields'] , $aux['dependents']));
    $this->dependents[$field]['conditions'][] = $aux;
    
    return true;
  }
  
  /**
   * Insert a Field after or before the given target
   * @param string $field_name How will be named the field
   * @param array $field_data a complete field array
   * @param string $target The name of the field after or before we'll
   *                       insert the new field.
   * @param string $position 'after' or 'before', Default: 'after'
   * @return bool true on success false otherwise
   */
  public function insertField($field_name, $field_data, $target, $position='after')
  {
    $success = false;
    /** there is no easy way to insert an element into an array, so we need to
    recreate it, inserting the field when we detect the $target **/
    $new_fields = array();
    reset($this->fields);
    while (list($key, $value) = each($this->fields) ) {
      if($position=='after') {
        $new_fields[$key] = $value;
      }
      if ( $key === $target) {
        if ( $field_name!='' ) {
          $new_fields[$field_name] = $field_data;
        } else {
          $new_fields[] = $field_data;
        }
        $success = true;
      }
      if($position=='before') {
        $new_fields[$key] = $value;
      }
    }
    $this->fields = $new_fields;
    
    return $success;
  }
  
  public function setFieldOrder($fields)
  {
    $fields = explode(',', $fields);
    $fields = array_map('trim', $fields);
    if ( count($fields)!=$this->no_fields) {
      throw new LogicException("The number of fields doesn't match the ones in the Row, you are missing some fields");
    }
    $new_fields= array();
    foreach($fields as $field)
    {
      if ( !isset($this->fields[$field]) ) {
        throw new LogicException("The given field '$field' doesn't exist");
      }
      $new_fields[$field] =$this->fields[$field];
    }
    $this->fields = $new_fields;
  }
  
  /**
   * Inserts an splitter (with optional content) at the given position.
   * @param string $target The field after the separator will be created
   * @param string $content The content that will be inside the splitter
   * @param string $position 'after' or 'before', Default: 'after'
   * @return bool true on success false otherwise
   */
  public function insertSplitter($target, $content='', $position='after', $name='')
  {
    $aux= array('type' => 'splitter', 'content' => $content);
    return $this->insertField("{$name}_splitter", $aux, $target, $position);
  }
  
  /**
   * Sets the given field's property
   * 
   * General values which apply for all field types
   * @param string $field help_text, label, type, etc..
   * @param string $property
   * @param mixed $value
   * @return bool true on success false otherwise
   */
  public function setFieldProperty($field, $property, $value)
  {
    if ( isset($this->fields[$field]) ) {
      $this->fields[$field][$property] = $value;
      return true;
    }
    return false;
  }
  
  /**
   * Sets the given field's parameter
   * 
   * Parameters are values specific for the given type of the field
   * @param string $field
   * @param string $parameter
   * @param mixed $value
   * @return bool true on success false otherwise
   */
  public function setFieldParameter($field, $parameter, $value)
  {
    if ( isset($this->fields[$field]) ) {
      $this->fields[$field]['parameters'][$parameter] = $value;
      return true;
    }
    return false;
  }
  
  /**
   * Sets the given field's input parameter
   * 
   * Input parameters are pasted as is inside the html tag
   * @param string $field
   * @param string $input_parameter
   * @param mixed $value
   * @return bool true on success false otherwise
   */
  public function setFieldInputParameter($field, $input_parameter, $value)
  {
    if ( isset($this->fields[$field]) ) {
      $this->fields[$field]['input_parameters'][$input_parameter] = $value;
      return true;
    }
    return false;
  }
  
    
  /**
   * Unsets the given field's input parameter
   * @param string $field
   * @param string $input_parameter
   * @return void
   */
  public function unsetFieldInputParameter($field, $input_parameter)
  {
    unset($this->fields[$field]['input_parameters'][$input_parameter]);
  }
  
  /**
   * Sets the name of a field as will be show in the Label
   *
   * If not customized this name is created by replacing underscores with espaces
   * and capitalizing each word in the field name.
   * @param string $field the field where the name will be changed
   * @param string $name the new name
   * @return bool true on success false otherwise
   */
  public function setName($field, $name)
  {
    return $this->setFieldProperty($field, 'label', $name);
  }
  
  /**
   * Sets a help text that will be put besides the field
   *
   * @param string $field the field where the help text will be added
   * @param string $help_text the text
   * @return bool true on success false otherwise
   */
  public function setHelpText($field, $help_text)
  {
    return $this->setFieldProperty($field, 'help_text', $help_text);
  }
  
  /**
   * Sets the field as hidden
   *
   * Commonly used with the row id.
   * To really delete the field from the Form use {@link deleteField}.
   * @param string $field the name of the field to hide
   * @return bool true on success false otherwise
   */
  public function hideField($field)
  {
    return $this->setFieldProperty($field, 'type', 'hidden');
  }
  
  /**
   * Deletes a field from the Form
   *
   * If you only wish to hide a field use {@link hideField}
   * @param string $field the name of the field to be deleted
   * @return void
   */
  public function deleteField($field) {
    if ( isset($this->fields[$field]) ) {
      unset( $this->fields[$field] );
      $this->no_fields--;
      return true;
    }
    return false;
  }
  
  public function setAsLinked($field, $table_name, DbConnection $DbConnection=null, $table_id='', $name_field='', $condition='')
  {
    if ( !isset($DbConnection) ) {
      $DbConnection = DbConnection::getInstance();
    }
    if ($table_id=='') {
      $table_id = "{$table_name}_id";
    }
    
    if ($name_field=='') {
      $Config = Config::getInstance();
      $name_field = $Config->name_field;
    }
    
    if($condition=='') {
      $condition='1';   
    }
    
    $sql = "SELECT $table_id, $name_field
            FROM $table_name
            WHERE $condition
            ORDER BY $name_field";
    
    if ( !$options = $DbConnection->getArrayPair($sql) ) {
      $options=array();
    }
    
    $this->setFieldProperty($field, 'type', 'select');
    
    $this->unsetFieldInputParameter($field, 'maxlength');
    
    if ( count($options)<=3 ) {
      $this->setFieldProperty($field, 'type', 'radio');
    }
    $this->setFieldParameter($field, 'options', $options);
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
  public function AddGeneralAction($action, $title, $icon='', $ajax=false)
  {
    $aux = array (
        'action'  => $action,
        'title'   => $title,
        'icon'    => $icon,
        'ajax'    => $ajax,
      );
    $this->general_actions[] = $aux;
  }
  
  /**
   * Creates the javascript that powers the depedent engine
   * @return string the code that should be added to the template using {@link addJavascript()}
   */
  public function createDependentJavascript($run_update=False)
  {
    $code = false;
    if( count($this->dependents) ) {
      
      $code .= "\n  function update".str_replace(' ', '',ucwords(str_replace('_', ' ', $this->form_id)))."Dependents()\n  {";
      
      foreach($this->dependents as $field => $parameters)
      {
        switch( $this->fields[$field]['type'])
        {
          case 'select':
            $get_value_string = "valSelect(document.forms['{$this->form_id}'].$field)";
            break;
          case 'radio':
            $get_value_string = "valRadioButton(document.forms['{$this->form_id}'].$field)";
            break;
          default:
            $get_value_string = "document.forms['{$this->form_id}'].$field.value";
        }
        
        $code .= "\n    field_value = $get_value_string;\n";
        
        $first_run = true;
        
        foreach ( $parameters['conditions'] AS $condition )
        {
          $Condition = (object)$condition;
          
          $code .= ($first_run)?'    if':' else if';
          $code .= " ( field_value $Condition->condition $Condition->value ) {\n";
          
          $hide_fields = array_diff($parameters['all_fields'], $Condition->dependents);
          foreach ( $hide_fields AS $hide)
          {
             $code .= "      dependent = document.getElementById('{$hide}_dependent');\n";
             $code .= "      dependent.style.display = 'none';\n";
          }
          foreach ( $Condition->dependents AS $show)
          {
             $code .= "      dependent = document.getElementById('{$show}_dependent');\n";
             $code .= "      dependent.style.display = 'block';\n";
          }
          $code .= "    } ";
          $first_run = false;
        }
        
        $code .= "else {\n";
        foreach ( $parameters['all_fields'] AS $all)
        {
          $code .= "      dependent = document.getElementById('{$all}_dependent');\n";
          $code .= "      dependent.style.display = 'none';\n";
        }
        $code .= "    }\n";
      }
      $code .="  }\n";
    }
    if($run_update==true) {
      $code .= "update".str_replace(' ', '',ucwords(str_replace('_', ' ', $this->form_id)))."Dependents();\n";
    }
    return $code ;
  }
  
  public function disableField($field)
  {
    return $this->setFieldProperty($field, 'disabled', 'true');
  }
  /**
   * Gives an unique id to the html's form markup
   * @param string $form_id
   * @return void
   */
  public function setFormId($form_id){
    $this->form_id = $form_id;
  }

  /**
   * Display the selected template with the given data and customization
   * @return void
   */
  public function getAsString() {
    $this->assign('data'      , $this->Row->data);
    $this->assign('dependents', $this->dependents);
    
    $this->assign('fields'    , $this->fields);
    $this->assign('actions'     , $this->actions);

    $this->assign('general_actions', $this->general_actions);
    $this->assign('form_id',         $this->form_id);
    $this->assign('action',          $this->action);
    $this->assign('type',            $this->type);
    $this->assign('method',          $this->method);
    //$this->assign('links'     , $this->links);    
    //$this->addJavascript($this->createDependentJavascript());
    return parent::getAsString();
  }
}