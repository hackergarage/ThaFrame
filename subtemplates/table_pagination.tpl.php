<?php
  $Vars = $__PatternVariables;
  if ($Vars->paginate && $__rows) {
    echo "\n\n<div id=\"pagination\">\n";
    $string = '';
    if($Vars->page_number != 0) {
      $parameters = array(
          '__page_number' => $Vars->page_number-1,
          '__page_size' => $Vars->page_size,
        );
      $url = $Helper->createSelfUrl($parameters, TRUE);
      $string .="<a  class=\"previous\" href=\"".htmlspecialchars($url)."\" title=\"".t('Previous')."\"><span>".t('Previous')."</span></a>\n";
    }else {
      $string .="<a  class=\"previous_disabled\" href=\"javascript:void();\" title=\"".t('Previous')."\"><span>".t('Previous')."</span></a>\n";
    }
    
    $parameters = array(
        '__page_number' => "replace_with_page_number",
        '__page_size' => $Vars->page_size,
      );
    $url = $Helper->createSelfUrl($parameters, TRUE);
    $string .= HelperPattern::createComboBox(range(1,$Vars->pages), 'page_number', $Vars->page_number,"onchange=\"javascript:change_page(this, '".htmlspecialchars($url)."');\"");
    
    if($Vars->page_number != $Vars->pages - 1) {
      $parameters = array(
          '__page_number' => $Vars->page_number+1,
          '__page_size' => $Vars->page_size,
        );
      $url = $Helper->createSelfUrl($parameters, TRUE);
      $string .="<a class=\"next\" href=\"".htmlspecialchars($url)."\" title=\"".t('Next')."\"><span>".t('Next')."</span></a>\n";
    } else {
      $string .="<a class=\"next_disabled\" href=\"javascript:void();\" title=\"".t('Next')."\"><span>".t('Next')."</span></a>\n";
    }
    
    $parameters = array(
        '__page_number' => $Vars->page_number,
        '__page_size' => 'replace_with_page_size',
      );
    $url = $Helper->createSelfUrl($parameters, TRUE);
    $page_sizes = array(
        '10' => '10',
        '25' => '25',
        '50' => '50',
        '100' => '100'
      );
    $string .= HelperPattern::createComboBox($page_sizes, 'page_size', $Vars->page_size,"onchange=\"javascript:change_page_size(this, '".htmlspecialchars($url)."');\"");
    
    echo $string;
    echo "</div>\n";
  }