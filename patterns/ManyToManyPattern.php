<?php 
/**
 * Holds {@link ManyToMany} class
 * @author Argel Arias <levhita@gmail.com>
 * @package ThaFrame
 * @copyright Copyright (c) 2007, Argel Arias <levhita@gmail.com>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

define('MTM_MODE_EDIT_TABLE', 1);
define('MTM_MODE_EDIT_ROW'  , 2);

/**
 * Provides a {@link Page} that shows a {@link Form} to edit a {@link Row} with an
 * {@link Table} to show the linked items and probably add more. 
 *
 * @package ThaFrame
 */
class ManyToManyPattern extends PagePattern
{
  /**
   * Tha Form
   * @var FormPattern
   */
  //private $_Form    = null;
  
    /**
   * Tha MainRow
   * @var RowModel
   */
  private $_Row    = null;
  
  /**
   * @var integer
   */
  private $_mode = MTM_MODE_EDIT_TABLE;
  
  /**
   * Tha Detail
   * @var DetailPattern
   */
  public $_Detail    = null;
  
  /**
   * Tha Table
   * @var TablePattern
   */
  public $_Table    = null;
  
  /**
   * Holds actions that will be rendered at begining or/and end of the list
   * actions that belong to the paga, and not to a specific row
   * @var array
   */
  private $_general_actions   = array();
  
  /**
   * Construct a {@link ManyToMany} page
   * @param string $page_name the page name to be shown
   * @param string $template by default it uses ManyToMany.tpl.php
   * @return ManyToMany
   */
  public function __construct($page_name, $template='')
  {
    if ( empty($template) ) {
      $this->setTemplate(THAFRAME . '/patterns/templates/ManyToManyPattern.tpl.php', true);
    } else {
      $this->setTemplate($template);
    }
    $this->assign('page_name', $page_name);
  }
  
  public function setTable(TablePattern $Table) {
    $this->_Table = $Table;
  }
  public function setMode($mode) {
    $this->_mode = $mode;
  }
  /*public function setForm(FormPattern $Form){
    $this->_Form = $Form;
  }*/
  
  public function setRow(RowModel $Row) {
    $this->_Row = $Row;
    
    $this->_Form = new FormPattern();
    $this->_Form->setRow($Row);
    
    $this->_Detail = new DetailPattern();
    $this->_Detail->setRow($Row);
  }
  
  public function setDetail(DetailPattern $Detail){
    $this->_Detail = $Detail;
  }
  
  /**
   * Add an action to the end & start of the Listing, commonly used to add a
   * "Create new item" link
   *
   * @param string $action The action that will be called after clicking (url)
   * @param string $title The text to show and will be added to the url title as well
   * @param string $field The field to add into de URL
   * @param string $value The value that such field should take usally 0 for new elements
   * @param string $icon  Tn optional icon that could go with the text
   * @return void
   */
  /*public function addGeneralAction($action, $title, $field='', $value='', $icon='')
  {
    $aux = array (
        'action'  => $action ,
        'title'   => $title,
        'field'   => $field ,
        'value'   => $value ,
        'icon'    => $icon,
      );
    $this->general_actions[] = $aux;
  }*/
  
  /**
   * Display the selected template with the given data and customization
   * @return void
   */
  public function display() {
    $this->assign('__mode', $this->_mode);
    $this->assign('__Form' , $this->_Form);
    $this->assign('__Detail' , $this->_Detail);
    $this->assign('__Table' , $this->_Table);
    $this->assign('__general_actions' , $this->_general_actions);
    
    parent::display();
  }
  
}