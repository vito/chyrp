<?php
	$sub = (isset($_GET['sub'])) ?
	       	$_GET['sub'] :
	       	((!empty($config->enabled_feathers) and $user->can("add_post")) ?
	       		$config->enabled_feathers[0] :
	       		"page")
	       ;
	$show_page = (($sub != "page" and !$user->can("add_post")) or ($sub == "page" and !$user->can("add_page"))) ? false : true ;
?>
		<ul class="sub-nav write-nav">
<?php
	if ($user->can("add_post")) {
		foreach ($config->enabled_feathers as $feather) {
			if (file_exists(FEATHERS_DIR."/".$feather."/locale/".$config->locale.".mo"))
				load_translator($feather, FEATHERS_DIR."/".$feather."/locale/".$config->locale.".mo");
			
			$info = Spyc::YAMLLoad(FEATHERS_DIR."/".$feather."/info.yaml");
?>
			<li<?php admin_selected("write", $feather); ?>><a href="<?php url("write", $feather); ?>"><?php echo (count($config->enabled_feathers) == 1) ? __("Post") : __($info["name"], $feather) ; ?></a></li>
<?php
		}
	}
	if ($user->can("add_page")):
?>
			<li<?php admin_selected("write", "page"); ?>><a href="<?php url("write", "page"); ?>"><?php echo __("Page"); ?></a></li>
<?php
	endif;
?>
		</ul>
		<br class="clear" />
		<div class="content">
<?php
	if ($show_page) {
		$action = ($sub == "page") ? "add_page" : "add_post" ; ?>
			<form action="<?php echo $config->url."/admin/?action=".$action; ?>" id="write_form" method="post" accept-charset="utf-8" enctype="multipart/form-data">
				<h1><?php echo ($sub == "page") ? __("Add Page") : __("Add Post") ; ?></h1>
<?php
		if ($sub != "page"):
			$post = new Post();
?>
				<input type="hidden" name="feather" value="<?php echo $sub; ?>" id="write_feather" />
<?php
			require FEATHERS_DIR."/".$sub."/fields.php";
			$trigger->call("below_post_fields");
		else:
?>
				<p>
					<label for="title"><?php echo __("Title"); ?></label>
					<input class="text" type="text" name="title" value="" id="title" tabindex="1" />
				</p>
				<p>
					<label for="body"><?php echo __("Body"); ?></label>
					<textarea name="body" rows="13" cols="40" class="wide preview_me" id="body" tabindex="2"></textarea>
				</p>
<?php
		endif;
?>
				<br id="after_options" />
				<button type="submit" id="publish" class="positive right" accesskey="s">
					<img src="<?php echo $config->url."/admin/icons/success.png"; ?>" alt="" /> <?php echo __("Publish"); ?>
				</button>
<?php if ($sub != "page"): ?>
				<button type="submit" name="draft" value="true" id="draft" class="right" accesskey="s">
					<img src="<?php echo $config->url."/admin/icons/save.png"; ?>" alt="" /> <?php echo __("Save"); ?>
				</button>
<?php endif; ?>
				<div class="clear"></div>
				<br class="js_disabled" />
				<div id="more_options" class="more_options js_disabled">
<?php
		if ($sub != "page"):
		 	new_post_options();
		 	$trigger->call("new_post_options", $sub);
		else:
?>
					<p>
						<label for="pinned"><?php echo __("Show in pages list?"); ?></label>
						<input type="checkbox" name="show_in_list" id="show_in_list" tabindex="3" checked="checked" />&nbsp;
					</p>
					<p>
						<label for="slug"><?php echo __("Slug"); ?></label>
						<input class="text" type="text" name="slug" value="" id="slug" />
					</p>
					<p>
						<label for="parent_id"><?php echo __("Parent"); ?></label>
						<select name="parent_id" id="parent_id">
							<option value="0">(none)</option>
<?php
	$get_pages = $sql->query("select `id`, `title` from `".$sql->prefix."pages`
	                          order by `id` asc");
	while ($the_page = $get_pages->fetchObject()):
?>
							<option value="<?php echo $the_page->id; ?>"><?php echo fix($the_page->title, "html"); ?></option>
<?php
	endwhile;
?>
						</select>
					</p>
					<?php $trigger->call("new_page_options"); ?>
<?php
		endif;
?>
					<div class="clear"></div>
				</div>
				<input type="hidden" name="hash" value="<?php echo $config->secure_hashkey; ?>" id="hash" />
			</form>
<?php
	} else {
?>
			<h1><?php echo __("Access Denied"); ?></h1>
			<?php echo sprintf(__("You do not have sufficient privileges to write %ss."), $sub); ?>
<?php
	}
?>
		</div>
