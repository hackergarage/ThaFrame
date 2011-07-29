<?php
/**
 * Holds {@link TemplatePattern} class
 * @package ThaFrame
 * @author Argel Arias <levhita@gmail.com>
 * @copyright Copyright (c) 2007, Argel Arias <levhita@gmail.com>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */
/**
 * Provide basic template system
 * @package ThaFrame
 */
class TemplatePattern
{
  /**
   * Holds the variables to be passed to the template as $Data object
   * @var array
   */
  protected $_variables = array();
  
  /**
   * Holds the javascripts that should be added at the head section
   * @var array
   */
  protected $_javascripts = array();
  
  /**
   * Holds the relative path to the template
   * @var string
   */
  protected $_template = '';
  
  /**
   * Variables that belongs only to this pattern, used to customize the text and
   * appareance of the page
   * @var array
   */
  protected $_pattern_variables = array();
  
  public function __construct($template='')
  {
    if ( empty($template) ) {
      $template = $this->getScriptName();
    }
    $this->setTemplate($template);
  }

  /**
   * Adds a javascript that will be added in the head section
   * @param string $javascript the javascript code
   * @return void
   */
  public function addJavascript($javascript)
  {
    $this->_javascripts[] = $javascript;
  }
  
  /**
   * Sets the template file to be used.
   *
   * Take for granted that the file is under the relative path "templates/" and
   * has a "tpl.php" extension, unless you set $fullpath to true
   * @param string  $template The name of the template to be used
   * @param bool    $fullpath overrides the naming convention and allows you to set any file
   * @return void
   */
  public function setTemplate($template, $fullpath = false)
  {
    if ( !$fullpath) {
      $this->_template = "templates/$template.tpl.php";
    } else {
      $this->_template = $template;
    }
  }
  
  public function assign($variable, $value)
  {
    $this->_variables[$variable] = $value;
  }
  
  public function getScriptName(){
    return basename($_SERVER['SCRIPT_FILENAME'], '.php');
  }
  
  /**
   * Sets a pattern specific variable, variables set by this function aren't
   * mandatory, and are only to provide customization to the default template
   *
   * @param string $variable the variable to be set
   * @param string $value the content that will override the default value
   * @return void
   */
  public function setPatternVariable($variable, $value)  {
    $this->_pattern_variables[$variable] = $value;
  }
  
  /**
   * Shows the given template
   *
   * Converts the $variables array into $Data object and sets any message that may
   * be in the $_SESSION and finally calls the given template
   * @return void
   */
  public function getAsString()
  {
    if ( !file_exists($this->_template) ) {
      throw new InvalidArgumentException("template '$this->_template' doesn't exists");
    }
    
    $this->assign('__PatternVariables', (object)$this->_pattern_variables);
    $this->assign('__javascripts', $this->_javascripts);
    
    return self::runTemplate($this->_template, $this->_variables);
  }
  
  /**
   * Run the Template in the cleanest enviroment posible
   * @param string $template
   * @param array $data
   * @return string
   */
  protected static function runTemplate($_template_, $_data_) {
    $Helper = new HelperPattern((object)$_data_);
    extract($_data_);
    
    if ( !file_exists($_template_) ) {
      throw new InvalidArgumentException("template '$_template_' doesn't exists");
    }
    
    ob_start();
      include $_template_;
      $_content_ = ob_get_contents();
    ob_end_clean();

    return $_content_;
  }
}