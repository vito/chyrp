						<form class="delete" action="<?php url("delete_page_real"); ?>" method="post" accept-charset="utf-8">
							<blockquote>
								<h4><?php echo $page->title; ?></h4>
								<?php echo $trigger->filter("markup_page_text", truncate($page->body, 500)); ?>
							</blockquote>
							<div class="center pad">
								<strong><?php echo __("Warning: This will also delete any sub-pages of this page."); ?></strong>
								<br />
								<br />
								<input type="hidden" name="hash" value="<?php echo $config->secure_hashkey; ?>" id="hash" />
								<input type="hidden" name="id" value="<?php echo fix($_GET['id'], "html"); ?>" id="id" />
								<input type="submit" value="<?php echo __("Yes, delete this page!"); ?>" class="margin-right" />
								<a href="<?php echo $config->url; ?>/admin/?action=manage&amp;sub=page"><?php echo __("No, don't delete it!"); ?></a>
							</div>
						</form>
