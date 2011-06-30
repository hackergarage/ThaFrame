<?php
  $Vars = $Data->PatternVariables;
  foreach($Data->_javascripts as $javascript) {
    echo "<script type=\"text/javascript\">$javascript</script>";
  }
  if($Vars->form_title){
    echo "<h3>".t($Vars->form_title)."</h3>";
  }
  if ($Vars->before_text) {
    echo "<p>".t($Vars->before_text)."</p>\n";
  }
  
  if( $Data->type== 'single') {
    echo "<form action=\"$Data->action\" method=\"$Data->method\" id=\"$Data->form_id\" name=\"$Data->form_id\" >\n";
  } else {
    echo "<form action=\"$Data->action\" method=\"$Data->method\" enctype=\"multipart/form-data\" id=\"$Data->form_id\" name=\"$Data->form_id\">\n";
  }
  echo "<p>\n";
  
  foreach($Data->fields as $field=>$properties){
    $Properties = (object) $properties;
    $input_parameters = "";
    if ( count($Properties->input_parameters) ) {
      foreach($Properties->input_parameters AS $property => $value)
      {
        $input_parameters .= " $property=\"$value\"";
      }
    }
    if ( $Properties->dependent ) {
      echo "</p>\n\n<div class=\"dependent\" id=\"{$field}_dependent\" style=\"display:none\">\n";
    }
    if ($Properties->parent) {
      $input_parameters .= " onchange=\"update".str_replace(' ', '',ucwords(str_replace('_', ' ', $Data->form_id)))."Dependents();\"";
    }
    
    if ($Properties->type == 'splitter') {
      if ( $Properties->dependent ) {
        echo ($Properties->content=='')?"\n":"  <div class=\"splitter\">$Properties->content</div>\n";
      } else {
        echo ($Properties->content=='')?"\n":"</p>\n\n<div class=\"splitter\">$Properties->content</div>\n\n<p>\n";
      }
    } elseif ($Properties->type != 'hidden') {
      switch($Properties->type){//For PreLabels
        case "date":
          echo "<label for=\"{$field}_year\">".t($Properties->label).":</label> ";
          break;
        case "radio":
          echo "<label>".t($Properties->label).":</label> ";
          break;
        default:
          echo "<label for=\"$field\">".t($Properties->label).":</label> ";
      }
      if ($Properties->help_text){
        switch($Properties->type){//For help text following the label
          case "textarea":
            echo "<span class=\"input_help\">".t($Properties->help_text).".</span>";
            break;
        }
      }
      $readonly = ($Properties->disabled == 'true')?'readonly="readonly"':'';
      switch ($Properties->type) {
        case "select":
          if ( !empty($readonly)) {
            echo $Properties->parameters['options'][$Properties->value];
            echo "<input type=\"hidden\" name=\"$field\" id=\"$field\" value=\"".htmlspecialchars($Properties->value)."\" $input_parameters/>";
          } else {
            echo HelperPattern::createComboBox($Properties->parameters['options'], $field, $Properties->value, $input_parameters);
          }
          break;
        case "radio":
          if ( !empty($readonly) ) {
            echo htmlspecialchars($Properties->parameters['options'][$Properties->value]);
            echo "<input type=\"hidden\" name=\"$field\" id=\"$field\" value=\"".htmlspecialchars($Properties->value)."\" $input_parameters/>";
          } else {
            echo HelperPattern::createRadioButton($Properties->parameters['options'], $field, $Properties->value, $input_parameters);
          }
          break;
        case "date":
          if ( !empty($readonly)) {
            echo $Properties->value;
            echo "<input type=\"hidden\" name=\"$field\" id=\"$field\" value=\"".htmlspecialchars($Properties->value)."\" $input_parameters/>";
          } else {
            echo HelperPattern::createDateComboBox($Properties->value, $Properties->parameters['before'], $Properties->parameters['after'], $field);
          }
          break;
        case "textarea":
          echo "<br/>\n<textarea name=\"$field\" id=\"$field\" $input_parameters $readonly>".htmlspecialchars($Properties->value)."</textarea>";
          break;
        case "password":
          echo "<input type=\"password\" name=\"$field\" id=\"$field\" value=\"".htmlspecialchars($Properties->value)."\" $input_parameters $readonly/>";
          if ( $Properties->repeat && empty($readonly)) {
            echo "<br/>\n<label for=\"{$field}_repeat\">" . t('Repeat the %1%', t($Properties->label) ) . ":</label> ";
            echo "<input type=\"password\" name=\"{$field}_repeat\" id=\"{$field}_repeat\" value=\"".htmlspecialchars($Properties->value)."\" $input_parameters/>";
          }
          break;
        case "file":
          echo "<input type=\"file\" name=\"$field\" id=\"$field\" $input_parameters $readonly/>";
          break;
        default:
          echo "<input type=\"text\" name=\"$field\" id=\"$field\" value=\"".htmlspecialchars($Properties->value)."\" $input_parameters $readonly/>";
          break;
      }
      if ($Properties->help_text){
        switch($Properties->type){//For help text following the field
          case "textarea":
            break;
          default:
            echo " <span class=\"input_help\">".t($Properties->help_text).".</span>";
        }
      }
      if ( count($Properties->actions) ){
        foreach ( $Properties->actions  AS $action) {
          $action=(object)$action;
          $action->icon = $Helper->createFrameLink($action->icon, TRUE, TRUE);
          if($action->ajax) {
            echo " <a href=\"javascript:void(xajax_$action->action(xajax.getFormValues('$Data->form_id')));\" class=\"input_action\" title=\"".t($action->title)."\"><img src=\"$action->icon\" alt=\"".t($action->title)."\"/></a>";
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
      echo "</div>\n\n<p>\n";
    }
  }
  echo "</p>\n</form>\n";
  
  if ($Vars->after_text) {
    echo "<p>".t($Vars->after_text)."</p>\n";
  }
  
  if ( !empty($Data->general_actions) ) {
    echo "<ul class=\"action\">";
    foreach ( $Data->general_actions as $action)
    {
      echo "<li>";
      $action = (object)$action;
      $action->title = t($action->title);
      $action->icon = $Helper->createFrameLink($action->icon, TRUE, TRUE);
      if ( !$action->ajax) {
        echo "<a href=\"$action->action\" title=\"$action->title\">";
        if ( !$action->icon ) {
          echo "{$action->title}";
        } else {
          echo "<img src=\"$action->icon\" alt=\"{$action->title}\"/>  {$action->title}";
        }
        echo "</a> ";
      } else {
        echo "<a href=\"javascript:void(xajax_{$action->action}(xajax.getFormValues('$Data->form_id')));\" title=\"$action->title\">";
        if ( !$action->icon ) {
          echo "{$action->title}";
        } else {
          echo "<img src=\"$action->icon\" alt=\"{$action->title}\"/> {$action->title}";
        }
        echo "</a> ";
      }
      echo "</li>\n";
    }
    echo "</ul>\n\n";
  }
  ?>

<?php if( count($Data->dependents) ) { ?>
  <script type="text/javascript">
  <?="update".str_replace(' ', '',ucwords(str_replace('_', ' ', $Data->form_id)))."Dependents();"?>
  </script>
<?php } ?>