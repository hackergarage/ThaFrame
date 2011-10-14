<?php
class FormPattern Extends FieldListPattern
{
   
  /**
   * field's configuration structure
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
  protected $_fields  = array();
  
  /**
   * Holds dependents information
   * @var array
   */
  private $_dependents = array();
  
  /**
   * Defaults to Single but can be changes to multipart with setType
   * @var $type string
   */
  private $_type='single';
  
  private $_action='';
  
  private $_method='post';
  
  /**
  * Holds conditions information
   * @var array
   */
  private $_conditions = array();
  
  /**
   * The id of the form, {@link Edit} forms id are hardcoded as 'main_form'
   * this one is 'secondary_form' by default.
   * @var string
   */
  private $_form_id = "secondary_form";
  
  protected $_default_template = '/patterns/templates/FormPattern.tpl.php';
  
  /**
   * Loads information from a config file
   * 
   * $config_name maps to TO_ROOT/configs/models/{class_name}_{config_name}.ini by default
   * 
   * @param string $config_name 
   * @param boolean $use_class_name selects if the class_name prefix should be added.
   */
  public function loadConfig($config_name='default', $use_class_name = true, $vars=array() ) {
    $DbConnection = DbConnection::getInstance();
    if($use_class_name) {
      $prefix = strtolower(get_class($this->_Row));
      $file_name = TO_ROOT."/configs/models/{$prefix}_{$config_name}.yaml";
    } else {
      $file_name = TO_ROOT."/configs/models/{$config_name}.yaml";
    }
    if(!file_exists($file_name)){
      Logger::log("Couldn't find config file", $file_name, LOGGER_ERROR);
      return false;
    }
    $config = @ConfigParser::parsea_mesta($file_name, $vars);
    //echo "<pre>Processed:\n".print_r($config,1)."</pre>";
    
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
  
    if( isset($config['__pattern']['add']) ) {
      if ($this->_Row->getId()==0) {
        foreach($config['__pattern']['add'] AS $field=>$value) {
          $this->setPatternVariable($field, $value);
        }
      }
    }
    
    if( isset($config['__pattern']['edit']) ) {
      if ($this->_Row->getId()!=0) {
        foreach($config['__pattern']['edit'] AS $field=>$value) {
          $this->setPatternVariable($field, $value);
        }
      }
    }
    unset($config['__pattern']);
    
    $commands = $config['__commands'];
    unset($config['__commands']);
    
    if( isset($config['__generalAction']) ) {
      $new = ($this->_Row->getId()==0)?true:false;
      
      foreach($config['__generalAction'] AS $action=> $properties) {
        $appears_in = (isset($properties['appears_in']))?$properties['appears_in']:'both';
        
        if( $appears_in =='both' || ($new && $appears_in=='add') || (!$new && $appears_in=='edit') ) {
          $this->AddGeneralAction($action, $properties['action'], $properties['title'], $properties['icon'], $properties['ajax']);
        }
      }
    }
    unset($config['__generalAction']);
    
    
    foreach($config AS $field => $properties) {
      if ( strpos($field, ':') !== false ) {
        list($field, $action) = explode(':', $field);
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
          $id = (empty($properties['id']))?$field:$properties['id'];
          $this->insertSplitter($field, $properties['content'], $properties['position'], $id);
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
      } else {
       if(is_array($properties)) {
         foreach($properties AS $property => $value) {
           $this->setFieldProperty($field, $property, $value);
         }
       }
      }
    }
    if( isset($commands['delete']) ) {
      foreach($commands['delete'] AS $delete) {
         $this->deleteField($delete);
      }
    }
    if( isset($commands['hide']) ) {
      foreach($commands['hide'] AS $hide) {
        $this->hideField($hide);
      }
    }
    if( isset($commands['disable']) ) {
      foreach($commands['disable'] AS $disable) {
        $this->disableField($disable);
      }
    }
    return true;
  }
    
  /**
   * Parse table structure into template friendly data
   *
   * @return void
   */
  public function parseStructure() {
    $structure = $this->_Row->getStructure();
    foreach($structure AS $field)
    {
      $aux = array();
      $name = $field['Field'];
      $aux['label'] = ucwords(str_replace('_', ' ', $name));
      $aux['value'] = (isset($this->_Row->data[$name]))?$this->_Row->data[$name]:$field['Default'];
         
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
      $this->_fields[$name] = $aux;
    }
  }
    
  /**
   * Sets the given field's parameter specific for the given type of the field
   * 
   * Parameters are values specific for the given type of the field
   * @param string $field
   * @param string $parameter
   * @param mixed $value
   * @return bool true on success false otherwise
   */
  public function setFieldParameter($field, $parameter, $value)
  {
    if ( isset($this->_fields[$field]) ) {
      $this->_fields[$field]['parameters'][$parameter] = $value;
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
    if ( isset($this->_fields[$field]) ) {
      $this->_fields[$field]['input_parameters'][$input_parameter] = $value;
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
    unset($this->_fields[$field]['input_parameters'][$input_parameter]);
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
      $options = array();
    }
    
    $this->setFieldProperty($field, 'type', 'select');
    
    $this->unsetFieldInputParameter($field, 'maxlength');
    
    if ( count($options)<=3 ) {
      $this->setFieldProperty($field, 'type', 'radio');
    }
    $this->setFieldParameter($field, 'options', $options);
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
    
    $this->_dependents[$field]['all_fields'] = array_unique(array_merge((array)$this->_dependents[$field]['all_fields'] , $aux['dependents']));
    $this->_dependents[$field]['conditions'][] = $aux;
    
    return true;
  }
  
  /**
   * Creates the javascript that powers the dependent engine
   * @return string the code that should be added to the template using {@link addJavascript()}
   */
  public function createDependentJavascript($run_update=false)
  {
    $code = false;
    if( count($this->_dependents) ) {
      
      $code .= "\n  function update".str_replace(' ', '',ucwords(str_replace('_', ' ', $this->_form_id)))."Dependents()\n  {";
      
      foreach($this->_dependents as $field => $parameters)
      {
        switch( $this->_fields[$field]['type'])
        {
          case 'select':
            $get_value_string = "valSelect(document.forms['{$this->_form_id}'].$field)";
            break;
          case 'radio':
            $get_value_string = "valRadioButton(document.forms['{$this->_form_id}'].$field)";
            break;
          default:
            $get_value_string = "document.forms['{$this->_form_id}'].$field.value";
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
  
  /**
   * Gives an unique id to the html's form markup
   * @param string $form_id
   * @return void
   */
  public function setFormId($form_id){
    $this->_form_id = $form_id;
  }

  public function setType($type) {
    $this->_type=$type;
  }
  
  public function setAction($action) {
    $this->_action=$action;
  }
  
  public function setMethod($method) {
    $this->_method=$method;
  }
  /**
   * Display the selected template with the given data and customization
   * @return void
   */
  public function getAsString() {
    $this->assign('__dependents', $this->_dependents);
    $this->assign('__form_id',    $this->_form_id);
    $this->assign('__action',     $this->_action);
    $this->assign('__type',       $this->_type);
    $this->assign('__method',     $this->_method);
    
    return parent::getAsString();
  }
}