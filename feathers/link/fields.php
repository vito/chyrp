<?php
	if (isset($args['url']))
		$post->source = $args['url'];
	if (isset($args['title']))
		$post->name = $args['title'];
	if (isset($args['selection']))
		$post->description = $args['selection'];

	fallback($post->source);
	fallback($post->name);
	fallback($post->description);
?>
<p>
	<label for="source"><?php echo __("URL", "link"); ?></label>
	<input class="text" type="text" name="source" value="<?php echo fix($post->source, "html"); ?>" id="source" tabindex="1" />
</p>
<p>
	<label for="name"><?php echo __("Name", "link"); ?></label>
	<input class="text" type="text" name="name" value="<?php echo fix($post->name, "html"); ?>" id="name" tabindex="2" />
</p>
<p>
	<label for="description"><?php echo __("Description", "link"); ?><span class="sub"> <?php echo __("(optional)"); ?></span></label>
	<textarea name="description" rows="4" cols="40" class="wide preview_me" id="description" tabindex="3"><?php echo fix($post->description, "html"); ?></textarea>
</p>