<?php
	if (isset($args['selection']))
		$post->dialogue = $args['selection'];

	fallback($post->title);
	fallback($post->dialogue);
?>
<p>
	<label for="title"><?php echo __("Title", "chat"); ?><span class="sub"> <?php echo __("(optional)"); ?></span></label>
	<input class="text" type="text" name="title" value="<?php echo fix($post->title, "html"); ?>" id="title" tabindex="1" />
</p>
<p>
	<label for="dialogue"><?php echo __("Dialogue", "chat"); ?></label>
	<textarea name="dialogue" rows="10" cols="40" class="wide preview_me" id="dialogue" tabindex="2"><?php echo fix($post->dialogue, "html"); ?></textarea>
</p>
<div class="sub">
	<?php echo __("To give yourself a special CSS class, append \" (me)\" to your username, like so:", "chat"); ?>
	<ul class="list">
		<li>"&lt;Alex&gt;" &rarr; "&lt;Alex (me)&gt;"</li>
		<li>"Alex:" &rarr; "Alex (me):"</li>
	</ul>
	<?php echo __("This only has to be done to the first occurrence of the username.", "chat"); ?>
</div>