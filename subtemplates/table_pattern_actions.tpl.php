<?php
if ( !empty($__general_actions) ) {
  echo "<ul class=\"action\">";
  foreach ( $__general_actions as $action)
  {
    $action = (object)$action;
    /**
     * Show action buttons when:
     * * Configured at a given place
     * * Configured at both an the place is top
     * * Configured at both and row count is larger
     *   The action overflow trigger.
     */
    if (
          ( $action->show_in == $__sub_place) ||
          ( $action->show_in == 'both' &&
            ( $__sub_place == 'top' ||
              ( $__sub_place == 'bottom' && ( $__rows_count >= $__actions_overflow_trigger ) )
            )
          )
        ) {
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