<?php
	if (isset($args['selection']))
		$post->caption = $args['selection'];
	
	fallback($post->caption);
?>
<p style="margin-bottom: 1em">
	<label for="photo"><?php echo __("Photo", "photo"); ?></label>
	<input type="file" name="photo" value="" id="photo" tabindex="1" />
	<br />
</p>
<p>
	<label for="caption"><?php echo __("Caption", "photo"); ?><span class="sub"> <?php echo __("(optional)"); ?></span></label>
	<textarea name="caption" rows="4" cols="40" class="wide preview_me" id="caption" tabindex="2"><?php echo fix($post->caption, "html"); ?></textarea>
</p>