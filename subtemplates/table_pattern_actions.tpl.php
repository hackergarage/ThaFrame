<?php
if ( !empty($__general_actions) ) {
  echo "<ul class=\"action\">";
  foreach ( $__general_actions as $action)
  {
    $action = (object)$action;
    if($action->show_in == $__sub_place || $action->show_in == 'both'){
      echo "<li>";
      $action->title = t($action->title);
      $action->icon = $Helper->createFrameLink($action->icon, 1, 1);
      if ( !$action->ajax) {
        echo "<a href=\"$action->action\" title=\"$action->title\">";
        if ( !$action->icon ) {
          echo "{$action->title}";
        } else {
          echo "<img src=\"$action->icon\" alt=\"{$action->title}\"/>  {$action->title}";
        }
        echo "</a> ";
      } else {
        echo "<a href=\"javascript:void(xajax_{$action->action});\" title=\"$action->title\">";
        if ( !$action->icon ) {
          echo "{$action->title}";
        } else {
          echo "<img src=\"$action->icon\" alt=\"{$action->title}\"/> {$action->title}";
        }
        echo "</a> ";
      }
      echo "</li>\n";
    }
  }
  echo "</ul>\n\n";
}