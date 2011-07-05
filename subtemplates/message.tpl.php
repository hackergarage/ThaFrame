    <?php if ( isset($__message) ) {
        $Message=(object)$__message;
        ?>
        <div id="message">
          <img id="level_image" src="<?php HelperPattern::createFrameLink("images/dialogs/$Message->level.png");?>" alt="<?=t(ucwords($Message->level))?>"/>
          <?=$Message->text;?>
        </div>
        <script type="text/javascript">
        	$('#message').dialog({
        		title: $('#level_image').attr('alt'),
        		buttons: {"Ok": function() { $(this).dialog("close"); } }
        	} );
    	</script>
      <?php } ?>