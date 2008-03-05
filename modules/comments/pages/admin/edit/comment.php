<?php $comment->find($_GET['id']); ?>
					<form action="<?php url("update_comment"); ?>" method="post" accept-charset="utf-8">
						<p>
							<label for="body"><?php echo __("Body", "comments"); ?></label>
							<textarea name="body" rows="13" cols="40" class="wide"><?php echo fix($comment->body, "html"); ?></textarea>
						</p>
						<br id="after_options" />
						<button type="submit" class="positive right" accesskey="s">
							<img src="<?php echo $config->url; ?>/admin/icons/success.png" alt="" /> <?php echo __("Save"); ?>
						</button>
						<br class="clear" />
						<br class="js_disabled" />
						<div id="more_options" class="more_options js_disabled">
							<p>
								<label for="author"><?php echo __("Author"); ?></label>
								<input class="text" type="text" name="author" value="<?php echo fix($comment->author, "html"); ?>" id="author" />
							</p>
							<p>
								<label for="author_url"><?php echo __("Author URL", "comments"); ?></label>
								<input class="text" type="text" name="author_url" value="<?php echo fix($comment->author_url, "html"); ?>" id="author_url" />
							</p>
							<p>
								<label for="author_email"><?php echo __("Author E-Mail", "comments"); ?></label>
								<input class="text" type="text" name="author_email" value="<?php echo fix($comment->author_email, "html"); ?>" id="author_email" />
							</p>
							<p>
								<label for="status"><?php echo __("Status"); ?></label>
								<select name="status" id="status">
									<option value="approved"<?php selected($comment->status, "approved"); ?>><?php echo __("Approved", "comments"); ?></option>
									<option value="denied"<?php selected($comment->status, "denied"); ?>><?php echo __("Denied", "comments"); ?></option>
									<option value="spam"<?php selected($comment->status, "spam"); ?>><?php echo __("Spam", "comments"); ?></option>
								</select>
							</p>
							<p>
								<label for="created_at"><?php echo __("Timestamp"); ?></label>
								<input class="text" type="text" name="created_at" value="<?php echo when("F jS, Y H:i:s", $comment->created_at); ?>" id="created_at" />
							</p>
							<br class="clear" />
						</div>
						<input type="hidden" name="hash" value="<?php echo $config->secure_hashkey; ?>" id="hash" />
						<input type="hidden" name="id" value="<?php echo $_GET['id']; ?>" id="id" />
					</form>