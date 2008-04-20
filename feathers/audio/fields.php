<?php
	if (isset($args['selection']))
		$post->description = $args['selection'];
	
	fallback($post->description);
?>
<p style="margin-bottom: 1em">
	<label for="audio"><?php echo __("MP3 File", "audio"); ?></label>
	<input type="file" name="audio" value="" id="audio" tabindex="1" />
	<br />
</p>
<p>
	<label for="description"><?php echo __("Description", "audio"); ?><span class="sub"> <?php echo __("(optional)"); ?></span></label>
	<textarea name="description" rows="4" cols="40" class="wide preview_me" id="description" tabindex="2"><?php echo fix($post->description, "html"); ?></textarea>
</p>