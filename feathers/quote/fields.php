<?php
	if (isset($args['selection']))
		$post->quote = $args['selection'];
	if (isset($args['url']) and isset($args['title']))
		$post->source = '<a href="'.$args['url'].'">'.$args['title'].'</a>';

	fallback($post->quote);
	fallback($post->source);
?>
<p>
	<label for="quote"><?php echo __("Quote", "quote"); ?></label>
	<textarea name="quote" rows="4" cols="40" class="wide" tabindex="1" id="quote"><?php echo fix($post->quote, "html"); ?></textarea>
</p>
<p>
	<label for="source"><?php echo __("Source", "quote"); ?><span class="sub"> <?php echo __("(optional)"); ?></span></label>
	<textarea name="source" rows="4" cols="40" class="wide preview_me" id="source" tabindex="2"><?php echo fix($post->source, "html"); ?></textarea>
</p>