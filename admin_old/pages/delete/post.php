						<form class="delete" action="<?php url("delete_post_real"); ?>" method="post" accept-charset="utf-8">
							<blockquote>
								<?php echo $trigger->filter("markup_post_text", array(truncate($post->excerpt($_GET['id']), 500), $post)); ?>
							</blockquote>
							<div class="center pad">
								<input type="hidden" name="hash" value="<?php echo $config->secure_hashkey; ?>" id="hash" />
								<input type="hidden" name="id" value="<?php echo fix($_GET['id'], "html"); ?>" id="id" />
								<input type="submit" value="<?php echo __("Yes, delete this post!"); ?>" class="margin-right" />
								<a href="<?php echo $config->url; ?>/admin/?action=manage&amp;sub=post"><?php echo __("No, don't delete it!"); ?></a>
							</div>
						</form>
