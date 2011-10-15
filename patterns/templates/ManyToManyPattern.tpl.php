<? $Vars = $__PatternVariables; ?>
   
    <div id="many_to_many_detail">
      <?=($__mode==MTM_MODE_EDIT_ROW)?$__Form->getAsString():$__Detail->getAsString();?>
    </div>
       
    <?if($Vars->before_text):?>
      <p><?=t($Vars->before_text)?></p>
    <?endif;?>
    
    <?php
    if ( !empty($__general_actions) ) {
      echo "<ul class=\"action\">";
      foreach ( $__general_actions as $action)
      {
        $action = (object)$action;
        $action->title = t($action->title);
        echo "<li>";
        if( !empty($action->field) ) {
          if ( strpos($action->action,'?') === FALSE) {
            echo "<a href=\"$action->action?$action->field={$action->value}\" title=\"$action->title\">";
          } else {
            echo "<a href=\"$action->action&$action->field={$action->value}\" title=\"$action->title\">";
          }
        } else {
          echo "<a href=\"$action->action\" title=\"$action->title\">";
        }
        if ( !$action->icon ) {
          echo "{$action->title}";
        } else {
          $action->icon = $Helper->createFrameLink($action->icon, TRUE);
          echo "<img src=\"$action->icon\" alt=\"{$action->title}\"/> {$action->title}";
        }
        echo "</a></li> ";
       }
      echo "</ul>\n";
    }
    ?>
    
    <div id="many_to_many_table">
      <?if( $__mode== MTM_MODE_EDIT_TABLE ):?>
        <? $__Table->getAsString();?>
      <?endif;?>
    </div>
    
    <?if($Vars->after_text):?>
      <p><?=t($Vars->after_text)?></p>
    <?endif;?>