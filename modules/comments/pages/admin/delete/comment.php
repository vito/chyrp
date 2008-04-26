						<form class="delete" action="<?php url("delete_comment_real"); ?>" method="post" accept-charset="utf-8">
							<blockquote>
								<h4><?php echo Comment::info("author", $_GET['id']); ?></h4>
								<?php echo $trigger->filter("markup_comment_text", truncate(Comment::info("body", $_GET['id']), 500)); ?>
							</blockquote>
							<div class="center pad">
								<input type="hidden" name="id" value="<?php echo fix($_GET['id'], "html"); ?>" id="id" />
								<input type="submit" value="<?php echo __("Yes, delete this comment!"); ?>" class="margin-right" />
								<a href="<?php echo $config->url; ?>/admin/?action=manage&amp;sub=comments"><?php echo __("No, don't delete it!"); ?></a>
							</div>
							<input type="hidden" name="hash" value="<?php echo $config->secure_hashkey; ?>" id="hash" />
						</form>