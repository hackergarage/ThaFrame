<?php
  $Vars = $__PatternVariables;
  
  if($Vars->list_title){
    echo "<h3>".t($Vars->list_title)."</h3>";
  }
  if ($Vars->before_text) {
    echo "<p>".t($Vars->before_text)."</p>\n";
  }
  
    if ( !empty($__filters)) {
      //echo "<pre>".htmlentities(print_r($__filters,1))."</pre>";
      echo "<form id=\"filters_form\" name='filters' method='get' action='' class='list_filters'><div>\n<strong>".t('Filter'). " &gt;&gt;</strong>\n";
      
      foreach($__filters AS $field => $filter){
        $Filter = (object)$filter;
        if($Filter->type=='custom'){
          echo $Filter->label.": ";
          $options = array();
          foreach($Filter->options AS $option) {
            $options[$option['value']] = $option['label'];
          }
          $selected = (!isset($Filter->selected))? $Filter->default:$Filter->selected;
          echo HelperPattern::createComboBox($options, $field, $selected);
        }else if($Filter->type=='hidden'){
          ?> <input type='hidden' name='<?php echo $field ?>' value='<?php echo $Filter->value?>'/><?php
        }
        echo "\n";
      }
      echo "<input type=\"submit\" value=\"".t("Apply")."\"/>";
      echo "</div></form>";
      
   }
   
   $Helper->loadSubTemplate('table_pattern_actions',false,'place:top');
   
   if ( $__rows ) {
      echo "\n<table>\n";
      echo "<tr>";
      
      if (!count($__order_by)) {
        foreach($__fields as $field_title)
        {
          echo "<th>" . t($field_title) . "</th>";
        }
      } else {
        foreach($__fields as $field_title)
        {
          $original_field_title = strtolower(str_replace(' ','_', $field_title));
          foreach($_GET AS $key=>$value){
            if(substr($key, 0, 9)=='order_by_') {
              unset($_GET[$key]);
            }
          }
          
          echo "<th";
          $sort='DESC';
          if ( $original_field_title==$__selected_order_by ) {
            if( $__selected_order=='ASC' ) {
              echo ' class="asc"';
            } else {
              echo ' class="desc"';
              $sort='ASC';
            }
          }
          if ( array_search($original_field_title, $__order_by) !== FALSE ) {
            
            $url = HelperPattern::createSelfUrl(array('order_by_'. $original_field_title=>$sort),1);
            
            echo "><a href=\"{$url}\"";
          }
          echo '>'.t($field_title) . "</th>";
        }
      }
      if ( count($__actions) ) {
        echo "<th class=\"action\">".t('Actions')."</th>";
      }
      echo "</tr>\n";
      $count=0;
      foreach($__rows AS $row)
      {
        echo "<tr";
        if($__prefix)
          echo " id=\"{$__prefix}_{$row[$__row_id]}\" ";
        echo ">";
        $count++;
        foreach($__fields as $field => $field_title)
        {
          
          if( isset($__links[$field]) ) {
            $link = (object)$__links[$field];
            if(strpos($link->action,'?') === FALSE) {
              echo "<td><a href=\"$link->action?$link->value={$row[$link->value]}\" title=\"$link->title\">".htmlspecialchars($row[$field])."</a></td>";
            } else {
              echo "<td><a href=\"$link->action&$link->value={$row[$link->value]}\" title=\"$link->title\">".htmlspecialchars($row[$field])."</a></td>";
            }
          } else {
            echo "<td>".htmlspecialchars($row[$field])."</td>";
          }
          
        }
        if ( !empty($__actions) ) {
          echo "<td class=\"action\">";
          foreach ( $__actions as $action)
          {
            $action = (object)$action;
            $action->title = t($action->title);
            if ( !$action->ajax) {
              if( !is_array($action->value) ) {
                if ( strpos($action->action,'?') === FALSE) {
                  echo "<a href=\"$action->action?";
                } else {
                  echo "<a href=\"$action->action&";
                }
                echo "$action->value={$row[$action->value]}\" title=\"$action->title\">";
                if ( !$action->icon ) {
                  echo "{$action->title}";
                } else {
                  $action->icon = $Helper->createFrameLink($action->icon, 1, 1);
                  echo "<img src=\"$action->icon\" alt=\"{$action->title}\"/>";
                }
              } else {
                if ( strpos($action->action,'?') === FALSE) {
                  echo "<a href=\"$action->action?";
                } else {
                  echo "<a href=\"$action->action&";
                }
                foreach($action->value as $single_value) {
                  echo "$single_value={$row[$single_value]}&";
                }
                echo "\" title=\"$action->title\">";
                
                if ( !$action->icon ) {
                  echo "{$action->title}";
                } else {
                  $action->icon = $Helper->createFrameLink($action->icon, 1, 1);
                  echo "<img src=\"$action->icon\" alt=\"{$action->title}\"/>";
                }
              }
              echo "</a> ";
            } else {
              echo "<a href=\"javascript:void(xajax_{$action->action}('";
              if ( !is_array($action->value) ) {
                echo "{$row[$action->value]}";
              } else {
                $values_array = array();
                foreach ($action->value AS $single_value) {
                  $values_array[]=$row[$single_value];
                }
                echo $values_string = implode('\',\'',$values_array);
              }
              echo "'));\" title=\"$action->title\">";
              if ( !$action->icon ) {
                echo "{$action->title}";
              } else {
                $action->icon = $Helper->createFrameLink($action->icon, 1, 1);
                echo "<img src=\"$action->icon\" alt=\"{$action->title}\"/>";
              }
              echo "</a> ";
            }
          }
          echo "</td>";
        }
        echo "</tr>\n";
      }
      echo "</table>\n";
    } else {
      if ($Vars->no_items_message) {
        echo "\n<p><strong>".t($Vars->no_items_message)."</strong></p>\n";
      } else {
        echo "\n<p><strong>".t("There are no items")."</strong\n</p>";
      }
    }
    
    $Helper->loadSubTemplate('table_pagination');
    
    echo ($Vars->after_text)?"\n<p>$Vars->after_text</p>\n":'';
    
    $Helper->loadSubTemplate('table_pattern_actions',false,'place:bottom');