<?php
class DetailPattern Extends FieldListPattern
{
  
  /**
   * Holds the field's configuration structure
   *
   * fields['name'] {
   *   + label: The label.
   *   + help:  Little text to be show next to the field.
   *   + parameters {}: type specific parameter.
   *   + type: text, separator
   * }
   * @var array
   */
  protected $_fields  = array();
  
  protected $_default_template = '/patterns/templates/Detail.tpl.php';
  
  /**
   * Parse table structure into template friendly data
   * @return void
   */
  public function parseStructure(){
    $structure = $this->_Row->getStructure();
    
    foreach($structure AS $field)
    {
      $aux = array();
      $name = $field['Field'];
      $aux['label'] = ucwords(str_replace('_', ' ', $name));
      $aux['value'] = (isset($this->_Row->data[$name]))?$this->_Row->data[$name]:$field['Default'];
         
      $this->_no_fields++;
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
          } else {
            $aux['type'] = 'textarea';
          }
          break;
        case 'char':
          if ( $match[2] <= 100 ) {
            $aux['type'] = 'text';
          } else {
            $aux['type'] = 'textarea';
          }
          break;
        case 'text':
          $aux['type'] = 'textarea';
          break;
        case 'int':
          $aux['type'] = 'text';
          break;
        case 'date':
          $aux['type'] = 'date';
          break;
        case 'enum':
        case 'set'://Testing
          if ($match[2] == "'0','1'") {
            $aux['type'] = 'yesno';
          } else {
            $aux['type'] = 'text';
          }
          break;
      }
      $this->_fields[$name] = $aux;
    }
  } 
}