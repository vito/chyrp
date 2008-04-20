<?php
	if (isset($args['selection']))
		$post->body = $args['selection'];

	fallback($post->title);
	fallback($post->body);
?>
<p>
	<label for="title"><?php echo __("Title", "text"); ?><span class="sub"> <?php echo __("(optional)"); ?></span></label>
	<input class="text" type="text" name="title" value="<?php echo fix($post->title, "html"); ?>" id="title" tabindex="1" />
</p>
<p>
	<label for="body"><?php echo __("Body", "text"); ?></label>
	<textarea name="body" rows="10" cols="40" class="wide preview_me" id="body" tabindex="2"><?php echo fix($post->body, "html"); ?></textarea>
</p>