<?php
class Query
{
  /**
   * Holds filter options and configuration
   * @var array
   */
  private $_filters   = array();

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
  private $_page_size = 50;

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
   * Wich is the final SQL query to be called
   *
   * @var string
   */
  private $_sql = '';

  /**
   * The conditions string that is generated after take in account the applied filters
   *
   * @var string
   */
  private $_conditions = '';
  
  /**
   * @var DbConnection
   */
  private $_DbConnection = null;
  
  private $_rows = null;

  public function __construct($sql, DbConnection $DbConnection=null, $paginate = false) {
    $this->_sql       = $sql;
    if (is_null($DbConnection) ) {
      $this->_DbConnection = DbConnection::getInstance();
    }
    $this->_paginate  = $paginate;
  }
  
  /**
   * Adds a filter form to the list
   * @param $field
   * @param $label
   * @param $type
   * @return bool
   */
  public function addFilter($field, $label, $type='custom')
  {
    $aux = array (
        'label' => $label,
        'type'  => $type,
        'empty' => $empty,
        'options' => array()
    );
    $this->_filters[$field] = $aux;
  }

  public function addHiddenFilter($field, $value, $condition){
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
      $this->_filters[$field]['default']= $value;
    }
    $this->_filters[$field]['options'][] = $aux;
  }

  public function setClass($field, $value) {
    $this->_classes[$field] = $value;
  }

  /**
   * Adds a group of options to a filter
   *
   * @param $field Field where the filter will be applied.
   * @param $values Array in the form of array(value=>label, value=>label);
   * @param $condition condition to be applied, {value}s and {label}s in the string will be
   * replaced with the corresponding value and label.
   */
  public function addFilterOptions($field, $values, $condition){
    if (is_array($values) ) {
      foreach($values as $value=>$label) {
        $search  = array('{value}', '{label}');
        $replace = array(mysql_escape_string($value), mysql_escape_string($label));
        $replaced_condition = str_replace($search, $replace, $condition);
        $this->addFilterOption($field, $value, $label, FALSE, $replaced_condition);
      }
    }
  }

  public function processQuery() {
    $Request = GetRequest::getInstance();

    if ($paginate) {
      $this->_paginate = true;

      //Get a Grip of the whole thing
      $sql_without_conditions = str_replace('{conditions}','',$sql);
      $count_sql = "SELECT count(*) FROM ($sql_without_conditions) AS count_table;";
      $total_rows = $DbConnection->getOneValue($count_sql);

      //Create some basic pattern variables
      $this->_page_number = (empty($Request->__page_number))?'0':$Request->__page_number;
      $this->_page_size = (empty($Request->__page_size))?$this->_page_size:$Request->__page_size;
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
    if ( count($this->_filters) ) {

      foreach($this->_filters AS $field => $filter) {
        $Filter = (object)$filter;
        if($Filter->type=='custom') {
          //echo "Checking for filter on '$field'\n";
          if( isset($Request->$field) ) {
            $selected = stripslashes($Request->$field);
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

    $this->_conditions = $conditions;
    $this->_sql        = $sql;
  }

  public function getConditions() {
    return $this->_conditions;
  }

  public function getQuery(){
    return $this->_sql;
  }
  
  public function getRows() {
    if(!isset($this->_rows)) {
      $this->_rows = $this->_DbConnection->getAllRows($this->_sql);
    }
    return $this->_rows;
  }
}
?>
