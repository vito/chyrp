<?php
	define('AJAX', true);
	require_once "common.php";

	switch($_POST['action']) {
		case "edit_post":
			if (!isset($_POST['id']))
				error(__("Unspecified ID"), __("Please specify an ID of the post you would like to edit."));

			$post = new Post($_POST['id'], array("filter" => false));

			if (!$post->editable())
				error(__("Access Denied"), __("You do not have sufficient privileges to edit posts."));

			$title = call_user_func(array(Post::feather_class($_POST['id']), "title"), $post);
			$theme_file = THEME_DIR."/forms/feathers/".$post->feather.".php";
			$default_file = FEATHERS_DIR."/".$post->feather."/fields.php";
?>
<form id="post_edit_<?php echo $post->id; ?>" class="inline_post_edit" action="<?php echo $config->chyrp_url."/admin/?action=update_post&amp;sub=text&amp;id=".$post->id; ?>" method="post" accept-charset="utf-8">
	<h2><?php echo _f("Editing &#8220;%s&#8221;", array(truncate($title, 40, false))); ?></h2>
	<br />
<?php foreach ($feathers[$post->feather]->fields as $field): ?>
		<p>
			<label for="$field.attr">
				<?php echo $field["label"]; ?>
				<?php if (isset($field["optional"]) and $field["optional"]): ?><span class="sub"><?php echo __("(optional)"); ?></span><?php endif; ?>
				<?php if (isset($field["help"]) and $field["help"]): ?>
				<span class="sub">
					<a href="<?php echo $route->url("/admin/?action=help&id=".$field["help"]); ?>" class="help emblem"><img src="<?php echo $config->chyrp_url; ?>/admin/images/icons/help.png" alt="help" /></a>
				</span>
				<?php endif; ?>
			</label>
			<?php if ($field["type"] == "text" or $field["type"] == "file"): ?>
			<input class="<?php echo $field["type"]; ?><?php if (isset($field["classes"])): ?> <?php echo join(" ", $field["classes"]); ?><?php endif; ?>" type="<?php echo $field["type"]; ?>" name="<?php echo $field["attr"]; ?>" value="<?php echo fix($post->$field["attr"]); ?>" id="$field["attr"]" />
			<?php elseif ($field["type"] == "text_block"): ?>
			<textarea class="wide<?php if (isset($field["classes"])): ?> <?php echo join(" ", $field["classes"]); ?><?php endif; ?>" rows="<?php echo fallback($field["rows"], 12, true); ?>" name="<?php echo $field["attr"]; ?>" id="<?php echo $field["attr"]; ?>" cols="50"><?php echo fix($post->$field["attr"]); ?></textarea>
			<?php elseif ($field["type"] == "select"): ?>
			<select name="<?php echo $field["attr"]; ?>" id="<?php echo $field["attr"]; ?>"<?php if (isset($field["classes"])): ?> class="<?php echo join(" ", $field["classes"]); ?>"<?php endif; ?>>
				<?php foreach ($field["options"] as $value => $name): ?>
				<option value="<?php echo fix($value); ?>"><?php echo fix($name); ?></option>
				<?php endforeach; ?>
			</select>
			<?php endif; ?>
		</p>
<?php endforeach; ?>
	<a id="more_options_link_<?php echo $post->id; ?>" href="javascript:void(0)" class="more_options_link"><?php echo __("More Options &raquo;"); ?></a>
	<div id="more_options_<?php echo $post->id; ?>" class="more_options" style="display: none">
<?php if ($visitor->group()->can("add_post")): ?>
		<p>
			<label for="status"><?php echo __("Status"); ?></label>
			<select name="status" id="status">
				<option value="draft"<?php selected("draft", $post->status); ?>><?php echo __("Draft"); ?></option>
				<option value="public"<?php selected("public", $post->status); ?>><?php echo __("Public"); ?></option>
				<option value="private"<?php selected("private", $post->status); ?>><?php echo __("Private"); ?></option>
				<option value="registered_only"<?php selected("registered_only", $post->status); ?>><?php echo __("Registered Only"); ?></option>
			</select>
		</p>
<?php endif; ?>
		<p>
			<label for="pinned"><?php echo __("Pinned?"); ?></label>
			<input type="checkbox" name="pinned" id="pinned"<?php checked($post->pinned); ?> />&nbsp;<span class="sub"> <?php echo __("(shows this post above all others)"); ?></span>
		</p>
		<p>
			<label for="slug"><?php echo __("Slug"); ?></label>
			<input class="text" type="text" name="slug" value="<?php echo fix($post->url, "html"); ?>" id="slug" />
		</p>
		<p>
			<label for="created_at"><?php echo __("Timestamp"); ?></label>
			<input class="text" type="text" name="created_at" value="<?php echo when("F jS, Y H:i:s", $post->created_at); ?>" id="created_at" />
		</p>
		<?php $trigger->call("edit_post_options", $post);?>
		<br class="clear" />
	</div>
	<br />
	<input type="hidden" name="id" value="<?php echo fix($post->id, "html"); ?>" id="id" />
	<input type="hidden" name="ajax" value="true" id="ajax" />
	<div class="buttons">
		<input type="submit" value="<?php echo __("Update"); ?>" accesskey="s" /> <?php echo __("or"); ?>
		<a href="javascript:void(0)" id="post_cancel_edit_<?php echo $post->id; ?>" class="cancel"><?php echo __("Cancel"); ?></a>
	</div>
	<input type="hidden" name="hash" value="<?php echo $config->secure_hashkey; ?>" id="hash" />
</form>
<?php
			break;
		case "delete_post":
			$post = new Post($_POST['id']);
			if (!$post->deletable())
				error(__("Access Denied"), __("You do not have sufficient privileges to delete this post."));

			Post::delete($_POST['id']);
			break;
		case "view_post":
			fallback($_POST['offset'], 0);
			fallback($_POST['context']);

			$id = (isset($_POST['id'])) ? "`__posts`.`id` = :id" : false ;
			$reason = (isset($_POST['reason'])) ? "_".$_POST['reason'] : "" ;

			if (isset($_POST['id']))
				$post = new Post($_POST['id']);
			else
				switch($_POST['context']) {
					default:
						$post = new Post(null, array("where" => $private,
						                             "offset" => $_POST['offset'],
						                             "limit" => 1));
						break;
					case "drafts":
						$post = new Post(null, array("where" => "`__posts`.`status` = 'draft'",
						                             "offset" => $_POST['offset'],
						                             "limit" => 1));
						break;
					case "archive":
						$post = new Post(null, array("where" => "`__posts`.`created_at` like :created_at",
						                             "params" => array(":created_at" => "'".$_POST['year']."-".$_POST['month']."%'"),
						                             "offset" => $_POST['offset'],
						                             "limit" => 1));
						break;
					case "search":
						$post = new Post(null, array("where" => "`__posts`.`xml` like :query",
						                             "params" => array(":query" => "'%".urldecode($_POST['query'])."%'"),
						                             "offset" => $_POST['offset'],
						                             "limit" => 1));
						break;
				}

			if ($post->no_results) {
				header("HTTP/1.1 404 Not Found");
				$trigger->call("not_found");
				exit;
			}

			$date_shown = true;
			$last = false;

			$trigger->call("above_post".$reason);
			$theme->load("content/post", array("post" => $post));
			$trigger->call("below_post");
			break;
		case "preview":
			$content = ($trigger->exists("preview_".$_POST['feather'])) ?
			            $trigger->filter("preview_".$_POST['feather'], urldecode(stripslashes($_POST['content']))) :
			            $trigger->filter("markup_post_text", urldecode(stripslashes($_POST['content']))) ;
			echo "<h2 class=\"preview-header\">".__("Preview")."</h2>\n<div class=\"preview-content\">".$content."</div>";
			break;
		case "check_confirm":
			if (!$visitor->group()->can("change_settings"))
				error(__("Access Denied"), __("You do not have sufficient privileges to enable/disable extensions."));

			$dir = ($_POST['type'] == "module") ? MODULES_DIR : FEATHERS_DIR ;
			$info = Spyc::YAMLLoad($dir."/".$_POST['check']."/info.yaml");
			fallback($info["confirm"], "");

			if ($_POST['type'] == "module")
				if (!module_enabled($_POST['check']))
					exit;
			else
				if (!feather_enabled($_POST['check']))
					exit;

			if (!empty($info["confirm"]))
				echo __($info["confirm"], $_POST['check']);

			break;
		case "organize_pages":
			foreach ($_POST['parent'] as $id => $parent)
				$sql->update("pages", "`__pages`.`id` = :id", "`parent_id` = :parent", array(":id" => $id, ":parent" => $parent));

			foreach ($_POST['sort_pages'] as $index => $page)
				$sql->update("pages", "`__pages`.`id` = :id", "`list_order` = :index", array(":id" => str_replace("page_list_", "", $page), ":index" => $index));

			break;
		case "enable_module": case "enable_feather":
			$type = ($_POST['action'] == "enable_module") ? "module" : "feather" ;

			if (!$visitor->group()->can("change_settings"))
				if ($type == "module")
					exit("{ notifications: ['".__("You do not have sufficient privileges to enable/disable modules.")."'] }");
				else
					exit("{ notifications: ['".__("You do not have sufficient privileges to enable/disable feathers.")."'] }");

			if (($type == "module" and module_enabled($_POST['extension'])) or
			    ($type == "feather" and feather_enabled($_POST['extension'])))
				exit("{ notifications: [] }");

			$enabled_array = ($type == "module") ? "enabled_modules" : "enabled_feathers" ;
			$folder        = ($type == "module") ? MODULES_DIR : FEATHERS_DIR ;

			if (file_exists($folder."/".$_POST["extension"]."/locale/".$config->locale.".mo"))
				load_translator($_POST["extension"], $folder."/".$_POST["extension"]."/locale/".$config->locale.".mo");

			$info = Spyc::YAMLLoad($folder."/".$_POST["extension"]."/info.yaml");
			fallback($info["uploader"], false);
			fallback($info["notifications"], array());

			foreach ($info["notifications"] as &$notification)
				$notification = addslashes(__($notification, $_POST["extension"]));

			require $folder."/".$_POST["extension"]."/".$type.".php";

			if ($info["uploader"])
				if (!file_exists(MAIN_DIR.$config->uploads_path))
					$info["notifications"][] = _f("Please create the <code>%s</code> directory at your Chyrp install's root and CHMOD it to 777.", array($config->uploads_path));
				elseif (!is_writable(MAIN_DIR.$config->uploads_path))
					$info["notifications"][] = _f("Please CHMOD <code>%s</code> to 777.", array($config->uploads_path));

			$class_name = camelize($_POST["extension"]);
			if (method_exists($class_name, "__install"))
				call_user_func(array($class_name, "__install"));

			$new = $config->$enabled_array;
			array_push($new, $_POST["extension"]);
			$config->set($enabled_array, $new);

			exit('{ notifications: ['.(!empty($info["notifications"]) ? '"'.implode('", "', $info["notifications"]).'"' : "").'] }');

			break;
		case "disable_module": case "disable_feather":
			$type = ($_POST['action'] == "disable_module") ? "module" : "feather" ;

			if (!$visitor->group()->can("change_settings"))
				if ($type == "module")
					exit("{ notifications: ['".__("You do not have sufficient privileges to enable/disable modules.")."'] }");
				else
					exit("{ notifications: ['".__("You do not have sufficient privileges to enable/disable feathers.")."'] }");

			if (($type == "module" and !module_enabled($_POST['extension'])) or
			    ($type == "feather" and !feather_enabled($_POST['extension'])))
				exit("{ notifications: [] }");

			$class_name = camelize($_POST["extension"]);
			if (method_exists($class_name, "__uninstall"))
				call_user_func(array($class_name, "__uninstall"), ($_POST['confirm'] == "1"));

			$enabled_array = ($type == "module") ? "enabled_modules" : "enabled_feathers" ;
			$config->set($enabled_array,
			             array_diff($config->$enabled_array, array($_POST['extension'])));

			exit('{ notifications: [] }');

			break;
	}

	$trigger->call("ajax");
