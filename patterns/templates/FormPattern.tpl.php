<?php
  $Vars = $__PatternVariables;
  foreach($__javascripts as $javascript) {
    echo "<script type=\"text/javascript\">$javascript</script>";
  }
  if($Vars->form_title){
    echo "<h3>".t($Vars->form_title)."</h3>";
  }
  if ($Vars->before_text) {
    echo "<p>".t($Vars->before_text)."</p>\n";
  }
  
  if( $__type == 'single') {
    echo "<form action=\"$__action\" method=\"$__method\" id=\"$__form_id\" name=\"$__form_id\" >\n";
  } else {
    echo "<form action=\"$__action\" method=\"$__method\" enctype=\"multipart/form-data\" id=\"$__form_id\" name=\"$__form_id\">\n";
  }
  echo "<p>\n";
  
  foreach($__fields as $field => $properties){
    $Properties = (object) $properties;
    $class= "class='{$Properties->class}'";
    $label_class = "class='{$Properties->label_class}'";
    
    $input_parameters = "";
    if ( count($Properties->input_parameters) ) {
      foreach($Properties->input_parameters AS $property => $value)
      {
        $input_parameters .= " $property=\"$value\"";
      }
    }
    if ( $Properties->dependent ) {
      echo "<div class=\"dependent\" id=\"{$field}_dependent\" style=\"display:none\">\n";
    }
    if ($Properties->parent) {
      $input_parameters .= " onchange=\"update".str_replace(' ', '',ucwords(str_replace('_', ' ', $__form_id)))."Dependents();\"";
    }
    
    if ($Properties->type == 'splitter') {
      if ( isset($Properties->dependent) && $Properties->dependent==TRUE) {
        echo ($Properties->content=='')?"\n":"</p>\n\n<div class=\"splitter\" id=\"{$Properties->id}_splitter\">$Properties->content</div>\n\n<p id=\"$Properties->id\">\n";        
      } else {
        echo ($Properties->content=='')?"\n":"  <div class=\"splitter\">$Properties->content</div>\n";
      }
    } elseif ($Properties->type != 'hidden') {
      switch($Properties->type){//For PreLabels
        case "radio":
          echo "<label>".t($Properties->label).":</label> ";
          break;
        default:
          echo "<label for=\"$field\" $label_class>".t($Properties->label).":</label> ";
      }
      if ($Properties->help_text){
        echo "<span class=\"input_help\">".t($Properties->help_text).".</span>";
      }
      echo "<br/>";
      $readonly = ($Properties->disabled == 'true')?'readonly="readonly"':'';
      switch ($Properties->type) {
        case "select":
          if ( !empty($readonly)) {
            echo $Properties->parameters['options'][$Properties->value];
            echo "<input type=\"hidden\" name=\"$field\" id=\"$field\" value=\"".htmlspecialchars($Properties->value)."\" $input_parameters $class/>";
          } else {
            echo HelperPattern::createComboBox($Properties->parameters['options'], $field, $Properties->value, $input_parameters);
          }
          break;
        case "radio":
          if ( !empty($readonly) ) {
            echo htmlspecialchars($Properties->parameters['options'][$Properties->value]);
            echo "<input type=\"hidden\" name=\"$field\" id=\"$field\" value=\"".htmlspecialchars($Properties->value)."\" $input_parameters $class/>";
          } else {
            echo HelperPattern::createRadioButton($Properties->parameters['options'], $field, $Properties->value, $input_parameters);
          }
          break;
        case "date":
          if ( !empty($readonly)) {
            echo $Properties->value;
            echo "<input type=\"hidden\" name=\"$field\" id=\"$field\" value=\"".htmlspecialchars($Properties->value)."\" $input_parameters $class/>";
          } else {
            echo HelperPattern::createDateComboBox($Properties->value, $Properties->parameters['before'], $Properties->parameters['after'], $field);
          }
          break;
        case "time":
          if ( !empty($readonly)) {
            echo $Properties->value;
            echo "<input type=\"hidden\" name=\"$field\" id=\"$field\" value=\"".htmlspecialchars($Properties->value)."\" $input_parameters $class/>";
          } else {
            echo "<input size=\"45\" type=\"text\" class=\"time\" name=\"$field\" id=\"$field\" value=\"".htmlspecialchars($Properties->value)."\" $input_parameters $readonly $class/>";
            //echo HelperPattern::createDateComboBox($Properties->value, $Properties->parameters['before'], $Properties->parameters['after'], $field);
          }
          break;
        case "textarea":
          echo "<textarea name=\"$field\" id=\"$field\" $input_parameters $readonly $class>".htmlspecialchars($Properties->value)."</textarea>";
          break;
        case "password":
          echo "<input type=\"password\" name=\"$field\" id=\"$field\" value=\"".htmlspecialchars($Properties->value)."\" $input_parameters $readonly $class/>";
          if ( $Properties->repeat && empty($readonly)) {
            echo "<br/>\n<label for=\"{$field}_repeat\">" . t('Repeat the %1%', t($Properties->label) ) . ":</label><br/>";
            echo "<input type=\"password\" name=\"{$field}_repeat\" id=\"{$field}_repeat\" value=\"".htmlspecialchars($Properties->value)."\" $input_parameters $class/>";
          }
          break;
        case "file":
          echo "<input type=\"file\" name=\"$field\" id=\"$field\" $input_parameters $readonly/>";
          break;
        default:
          if ( $Properties->size > 45 ) {
            echo "<input size=\"57\" type=\"text\" name=\"$field\" id=\"$field\" value=\"".htmlspecialchars($Properties->value)."\" $input_parameters $readonly $class/>";
          } else {
            echo "<input size=\"45\" type=\"text\" name=\"$field\" id=\"$field\" value=\"".htmlspecialchars($Properties->value)."\" $input_parameters $readonly $class/>";
          }
          break;
      }
      if ( count($Properties->actions) ){
        foreach ( $Properties->actions  AS $action) {
          $action=(object)$action;
          $action->icon = $Helper->createFrameLink($action->icon, TRUE, TRUE);
          if($action->ajax) {
            echo " <a href=\"javascript:void(xajax_$action->action(xajax.getFormValues('$__form_id')));\" class=\"input_action\" title=\"".t($action->title)."\"><img src=\"$action->icon\" alt=\"".t($action->title)."\"/></a>";
          } else {
            echo " <a href=\"$action->action\" class=\"input_action\" title=\"".t($action->title)."\"><img src=\"$action->icon\" alt=\"".t($action->title)."\"/></a>";
          }
        }
      }
      echo "<br/>\n";
    } else {
      echo "<input type=\"hidden\" name=\"$field\" id=\"$field\" value=\"".htmlspecialchars($Properties->value)."\" $input_parameters/>";
    }
    if ( $Properties->dependent ) {
      echo "</div>\n";
    }
  }
  echo "</p>\n</form>\n";
  
  if ($Vars->after_text) {
    echo "<p>".t($Vars->after_text)."</p>\n";
  }
  
  if ( !empty($__general_actions) ) {
    echo HelperPattern::CreateFormActionList($__general_actions, $__form_id);
  } 
  ?>

<?php if( count($__dependents) ) { ?>
  <script type="text/javascript">
  <?="update".str_replace(' ', '',ucwords(str_replace('_', ' ', $__form_id)))."Dependents();"?>
  </script>
<?php } ?>