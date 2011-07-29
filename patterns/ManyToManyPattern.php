<?php
/**
 * Holds {@link ManyToMany} class
 * @author Argel Arias <levhita@gmail.com>
 * @package ThaFrame
 * @copyright Copyright (c) 2007, Argel Arias <levhita@gmail.com>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */


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
  private $_Form    = null;
  
    /**
   * Tha MainRow
   * @var RowModel
   */
  private $_Row    = null;
  
  /**
   * Tha Detail
   * @var DetailPattern
   */
  private $_Detail    = null;
  
  /**
   * Tha Table
   * @var TablePattern
   */
  private $_Table    = null;
  
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
      $this->setTemplate(THAFRAME . '/patterns/templates/ManyToMany.tpl.php', true);
    } else {
      $this->setTemplate($template);
    }
    $this->assign('page_name', $page_name);
  }
  
  public function setTable(TablePattern $Table) {
    $this->_Table = $Table;
  }
  
  public function setForm(FormPattern $Form){
    $this->_Form = $Form;
  }
  
  public function setRow(RowModel $Row) {
    $this->_Row = $Row;
    $this->_Form = new FormPattern();
    $this->_Form->setRow($Row);
    
    $this->_Detail = new DetailPattern();
    $this->_Detail->setRow();
  }
  
  public function loadFormConfig($config_name='default', $use_class_name = true) {
    $this->Form->loadConfig($config, $use_class_name);
  }
  
  public function setDetail(DetailPattern $Detail){
    $this->Detail = $Detail;
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
    $this->assign('Form' , $this->Form);
    $this->assign('Detail' , $this->Detail);
    $this->assign('Table' , $this->Table);
    $this->assign('general_actions' , $this->general_actions);
    parent::display();
  }
  
}