<?
	$id = $_GET["id"];
	$page = new Page($id);
?>
							<p>
								<label for="title"><?php echo __("Title"); ?></label>
								<input class="text" type="text" name="title" value="<?php echo fix($page->title, "html"); ?>" id="title" tabindex="1" />
							</p>
							<p>
								<label for="body"><?php echo __("Body"); ?></label>
								<textarea name="body" rows="15" cols="40" class="wide preview_me" id="body" tabindex="2"><?php echo fix($page->body, "html"); ?></textarea>
							</p>
