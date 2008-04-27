						<form class="delete" action="<?php url("delete_group_real"); ?>" method="post" accept-charset="utf-8">
							<blockquote class="noitalics">
<?php if (Group::count_users($_GET['id']) > 0): ?>
								<h4><?php echo __("Move members to:"); ?></h4>
								<div class="center">
									<select name="move_group" id="move_group" class="big2">
<?php     $get_groups = $sql->query("select * from `".$sql->prefix."groups`
                                   order by `id` asc"); ?>
<?php     while ($group = $get_groups->fetchObject()): ?>
<?php         if ($group->id != $_GET['id']): ?>
										<option value="<?php echo $group->id; ?>"<?php selected($group->id, $config->default_group); ?>><?php echo $group->name; ?></option>
<?php         endif; ?>
<?php     endwhile; ?>
									</select>
								</div>
								<br />
<?php endif; ?>
<?php if ($config->default_group == $_GET['id']): ?>
								<h4><?php echo __("New default group:"); ?></h4>
								<div class="center">
									<select name="default_group" id="default_group" class="big2">
<?php     $get_groups = $sql->query("select * from `".$sql->prefix."groups`
                                   order by `id` asc"); ?>
<?php     while ($group = $get_groups->fetchObject()): ?>
<?php         if ($group->id != $_GET['id']): ?>
										<option value="<?php echo $group->id; ?>"<?php selected($group->id, $config->default_group); ?>><?php echo $group->name; ?></option>
<?php         endif; ?>
<?php     endwhile; ?>
									</select>
								</div>
								<br />
<?php endif; ?>
<?php if ($config->guest_group == $_GET['id']): ?>
								<h4><?php echo __("New &#8220;guest&#8221; group:"); ?></h4>
								<div class="center">
									<select name="guest_group" id="guest_group" class="big2">
<?php     $get_groups = $sql->query("select * from `".$sql->prefix."groups`
                                   order by `id` asc"); ?>
<?php     while ($group = $get_groups->fetchObject()): ?>
<?php         if ($group->id != $_GET['id']): ?>
										<option value="<?php echo $group->id; ?>"<?php selected($group->id, $config->default_group); ?>><?php echo $group->name; ?></option>
<?php         endif; ?>
<?php     endwhile; ?>
									</select>
								</div>
								<br />
<?php endif; ?>
							</blockquote>
							<div class="center pad">
								<input type="hidden" name="hash" value="<?php echo $config->secure_hashkey; ?>" id="hash" />
								<input type="hidden" name="id" value="<?php echo fix($_GET['id'], "html"); ?>" id="id" />
								<input type="submit" value="<?php echo __("Yes, delete this group!"); ?>" class="margin-right" />
								<a href="<?php echo $config->url; ?>/admin/?action=manage&amp;sub=group"><?php echo __("No, don't delete it!"); ?></a>
							</div>
						</form>
