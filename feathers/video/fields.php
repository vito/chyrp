<?php
	if (isset($args['selection']))
		$post->caption = $args['selection'];
	
	fallback($post->embed);
	fallback($post->caption);
?>
<p>
	<label for="video"><?php echo __("Embed code or YouTube URL", "video"); ?></label>
	<textarea name="video" rows="4" cols="40" class="wide" id="video" tabindex="1"><?php echo fix($post->embed, "html"); ?></textarea>
</p>
<p>
	<label for="caption"><?php echo __("Caption", "video"); ?><span class="sub"> <?php echo __("(optional)", "video"); ?></span></label>
	<textarea name="caption" rows="4" cols="40" class="wide preview_me" id="caption" tabindex="2"><?php echo fix($post->caption, "html"); ?></textarea>
</p>