						<form class="delete" action="<?php url("delete_user_real"); ?>" method="post" accept-charset="utf-8">
							<blockquote class="noitalics">
								<h4><?php echo $user->login; ?></h4>
								<ul>
									<li><strong><?php echo __("Real Name:"); ?></strong> <?php echo $user->full_name; ?></li>
									<li><strong><?php echo __("E-Mail:"); ?></strong> <a href="mailto:<?php echo $user->email; ?>"><?php echo $user->email; ?></a></li>
									<li><strong><?php echo __("Website:"); ?></strong> <a href="<?php echo $user->website; ?>"><?php echo $user->website; ?></a></li>
									<?php $trigger->call("user_delete_list"); ?>
								</ul>
							</blockquote>
							<div class="center pad">
								<input type="hidden" name="hash" value="<?php echo $config->secure_hashkey; ?>" id="hash" />
								<input type="hidden" name="id" value="<?php echo fix($_GET['id'], "html"); ?>" id="id" />
								<input type="submit" value="<?php echo __("Yes, delete this user!"); ?>" class="margin-right" />
								<a href="<?php echo $config->url; ?>/admin/?action=manage&amp;sub=user"><?php echo __("No, don't delete them!"); ?></a>
							</div>
						</form>
