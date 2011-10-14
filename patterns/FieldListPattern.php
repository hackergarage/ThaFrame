<?php
class FieldListPattern extends TemplatePattern
{
  /**
   * Holds the Row to be used
   * @var RowModel
   */
  protected $_Row = '';
  
  /**
   * Holds the field's configuration structure
   * @var array
   */
  protected $_fields  = array();
  
  /**
  * Holds the number of fields that this {@link Edit} has
   * @var int
   */
  protected $_no_fields = 0;
  
  /**
   * Holds actions that will be rendered at begining or/and end of the field list
   * @var array
   */
  protected $_general_actions = array();
  
  public function __construct($template = '')
  {
    if ( empty($template) ) {
      $this->setTemplate(THAFRAME . $this->_default_template , true);
    } else {
      $this->setTemplate($template);
    }
  }
  
  //public abstract function parseStructure();
  
  public function setRow(RowModel $Row){
    $this->_Row = $Row;
    $this->_no_fields = 0;
    $this->parseStructure();
  } 
  
 /**
   * Moves the given field to the start of the form
   * @param string $field the field to be moved
   * @return bool true in success and false otherwise
   */
  public function moveToStart($field)
  {
    if ( isset($this->_fields[$field]) ) {
      $aux = array ( $field => $this->_fields[$field] );
      unset($this->_fields[$field]);
      $this->_fields = $aux + $this->_fields;
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
    if ( isset($this->_fields[$field]) ) {
      $aux = array ( $field => $this->_fields[$field] );
      unset($this->_fields[$field]);
      $this->_fields = $this->_fields + $aux;
      return true;
    }
    return false;
  }
  
  public function disableField($field)
  {
    return $this->setFieldProperty($field, 'disabled', 'true');
  }
  
  /**
   * Moves the given field before another field
   * @param string $field The field to move
   * @param string $before_field The field before the $field will be located
   * @return bool true on success and false otherwise
   */
  public function moveBefore($field, $before_field)
  {
    if ( isset($this->_fields[$field]) ) {
      $field_data = $this->_fields[$field];
      unset($this->_fields[$field]);
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
    if ( isset($this->_fields[$field]) ) {
      $field_data = $this->_fields[$field];
      unset($this->_fields[$field]);
      return $this->insertField($field, $field_data, $after_field, 'after');
    }
    return false;
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
    reset($this->_fields);
    while (list($key, $value) = each($this->_fields) ) {
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
    $this->_fields = $new_fields;
    return $success;
  }
  
  public function setFieldOrder($fields)
  {
    $fields = explode(',', $fields);
    $fields = array_map('trim', $fields);
    if ( count($fields)!=$this->_no_fields) {
      throw new LogicException("The number of fields doesn't match the ones in the Row, you are missing some fields");
    }
    $new_fields= array();
    foreach($fields as $field)
    {
      if ( !isset($this->_fields[$field]) ) {
        throw new LogicException("The given field '$field' doesn't exist");
      }
      $new_fields[$field] =$this->_fields[$field];
    }
    $this->_fields = $new_fields;
  }
  
 /*public function setFieldProperty($field, $property, $value)
  {
    $this->_fields[$field][$property] = $value;
  }*/
  
  /**
   * Inserts an splitter (with optional content) at the given position.
   * @param string $target The field after the separator will be created
   * @param string $content The content that will be inside the splitter
   * @param string $position 'after' or 'before', Default: 'after'
   * @return bool true on success false otherwise
   */
  public function insertSplitter($target, $content='', $position='after', $id='')
  {
    $aux= array('type' => 'splitter', 'content' => $content, 'id' => $id);
    return $this->insertField("{$id}_splitter", $aux, $target, $position);
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
   * Sets the given field's property general for all the fields
   * @param string $field
   * @param string $property
   * @param mixed $value
   * @return bool true on success false otherwise
   */
  public function setFieldProperty($field, $property, $value)
  {
    if ( isset($this->_fields[$field]) ) {
      $this->_fields[$field][$property] = $value;
      return true;
    }
    return false;
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
   * Deletes a field from the list
   *
   * If you only wish to hide a field use {@link hideField}
   * @param string $field the name of the field to be deleted
   * @return void
   */
  public function deleteField($field) {
    if ( isset($this->_fields[$field]) ) {
      unset( $this->_fields[$field] );
      $this->_no_fields--;
      return true;
    }
    return false;
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
  public function AddGeneralAction($name,$action, $title, $icon='', $ajax=false)
  {
    $aux = array (
        'action'  => $action,
        'title'   => $title,
        'icon'    => $icon,
        'ajax'    => $ajax,
      );
    $this->_general_actions[$name] = $aux;
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
    $this->_fields[$field]['actions'][] = $aux;
  }
  
  public function getAsString(){
    $this->assign('__Row'       , $this->_Row);
    $this->assign('__data'      , $this->_Row->data);
    $this->assign('__fields'    , $this->_fields);
    $this->assign('__links'     , $this->_links);
    $this->assign('__general_actions', $this->_general_actions);
    
    return parent::getAsString();
  }
  
  
}