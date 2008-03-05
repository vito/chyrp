				</div>
				<?php $paginate->next_link(__("Next &rarr;", "theme"), "right"); ?>
				<?php $paginate->prev_link(__("&larr; Previous", "theme")); ?>
				<br />
				<div class="footer">
					<p><a href="<?php echo $route->url("feed/"); ?>">Feed</a> / <?php echo __("theme &copy; <a href=\"http://lofisoami.com/\">Aaron MacDonald</a> 2007", "theme"); ?></p>
					<span><?php echo __("Powered by <a href=\"http://chyrp.net/\">Chyrp</a>", "theme"); ?></span>
				</div>
		    </div>
		    <div class="sidebar">
				<form action="<?php echo $route->url("search/"); ?>" method="get" accept-charset="utf-8">
					<input type="hidden" name="action" value="search" id="action" />
					<input type="text" id="search" name="query" />
				</form>
				<h1><?php echo __("Pages", "theme"); ?></h1>
<?php $theme->list_pages(); ?>
				<h1><?php echo sprintf(__("Welcome, %s!", "theme"), $user->info('login', null, 'Guest')); ?></h1>
				<ul>
<?php if (!$user->logged_in()): ?>
					<li><a href="<?php echo $route->url("login/"); ?>"><?php echo __("Log In", "theme"); ?></a></li>
<?php 	if ($config->can_register): ?>
					<li><a href="<?php echo $route->url("register/"); ?>"><?php echo __("Register", "theme"); ?></a></li>
<?php 	endif; ?>
<?php else: ?>
					<li><a href="<?php echo $route->url("controls/"); ?>"><?php echo __("User Controls", "theme"); ?></a></li>
<?php 	if ($user->can('add_post') or $user->can('add_page') or $user->can('view_draft') or $user->can('change_settings')): ?>
					<li><a class="toggle_admin" href="<?php echo $route->url("toggle_admin/"); ?>"><?php echo __("Toggle Admin Bar", "theme"); ?></a></li>
<?php 	endif; ?>
					<li><a href="<?php echo $route->url("logout/"); ?>"><?php echo __("Log Out", "theme"); ?></a></li>
<?php endif; ?>
				</ul>
				<?php $trigger->call("sidebar"); ?>
<?php if (module_enabled("tags")): ?>
				<h1><?php echo __("Tags", "theme"); ?></h1>
				<ul id="tags_list">
<?php 	foreach (list_tags() as $tag): ?>
					<li><a href="<?php echo $route->url("tag/".$tag["url"]."/"); ?>"><?php echo $tag["name"]; ?></a></li>
<?php 	endforeach; ?>
					<li><a href="<?php echo $route->url("tags/"); ?>"><?php echo __("All &rarr;", "theme"); ?></a></li>
				</ul>
<?php endif; ?>
				<h1><?php echo __("Archives", "theme"); ?></h1>
				<ul>
<?php foreach ($theme->list_archives() as $archive): ?>
					<li><a href="<?php echo $archive["url"]; ?>"><?php echo $archive["month"]." ".$archive["year"]; ?></a></li>
<?php endforeach; ?>
				</ul>
				<h1><?php echo __("Stats", "theme"); ?></h1>
				<ul>
					<li><?php echo sprintf(__("Queries: %s", "theme"), $sql->queries); ?></li>
					<li><?php echo sprintf(__("Load Time: %s", "theme"), timer_stop()); ?></li>
				</ul>
			</div>
		</div>
<?php $trigger->call("end_content"); ?>
	</body>
</html>