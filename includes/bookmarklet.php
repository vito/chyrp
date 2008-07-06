<?php
	require_once "common.php";

	if (!$visitor->group()->can("add_post"))
		error(__("Access Denied"), __("You do not have sufficient privileges to create posts."));

	if (empty($config->enabled_feathers))
		error(__("No Feathers"), __("Please install a feather or two in order to add a post."));

	$feather = $config->enabled_feathers[0];
	fallback($_GET['status']);

	$args["url"] = $args["page_url"] = (isset($_GET['url'])) ? urldecode(stripslashes($_GET['url'])) : null ;
	$args["title"] = $args["page_title"] = (isset($_GET['title'])) ? urldecode(stripslashes($_GET['title'])) : null ;
	$args["selection"] = (isset($_GET['selection'])) ? urldecode(stripslashes($_GET['selection'])) : null ;

	switch($_GET['status']) {
		default:
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title><?php echo __("Chyrp!"); ?></title>
		<style type="text/css">
			/* Reset */
			body,div,dl,dt,dd,ul,ol,li,h1,h2,h3,h4,h5,h6,pre,code,form,fieldset,legend,input,textarea,p,blockquote,th,td { margin: 0; padding: 0; }
			table { border-collapse: collapse; border-spacing: 0; }
			fieldset,img { border: 0; }
			address,caption,cite,code,dfn,em,strong,th,var { font-style: normal; font-weight: normal; }
			li { list-style: none; }
			caption,th { text-align: left; }
			h1,h2,h3,h4,h5,h6 { font-size: 100%; font-weight: normal; }
			abbr,acronym { border: 0; font-variant: normal; }
			input, textarea, select { font-family: inherit; font-size: inherit; font-weight: inherit; }
			/* End Reset */
			html {
				font-size: 62.5%;
			}
			body {
				font: 1.25em/1.5em normal "Verdana", Helvetica, Arial, sans-serif;
				color: #626262;
				background: #e8e8e8;
				margin: 0;
				padding: 1.25em;
			}
			a:link, a:visited {
				text-decoration: none;
				color: #222;
				border-bottom: 1px solid #ddd;
			}
			a:hover {
				color: #555;
				border-bottom-color: #aaa;
			}
			label {
				display: block;
				font-weight: bold;
			}
			p {
				margin: 0 0 1em;
			}
			input.text, textarea {
				font-size: 1.25em;
				padding: 3px;
				border: 1px solid #ddd;
				background: #fff;
			}
			input.code, code {
				font-family: "Consolas", "Monaco", monospace;
			}
			.navigation li a {
				float: left;
				padding: .4em .75em;
				background: #dfdfdf;
				border-top: .2em solid #e8e8e8;
				border-bottom: 0;
				color: #737373;
			}
			.navigation li.selected a {
				background: #fff;
				border-top-color: #c7c7c7;
			}
			.navigation li.right {
				margin: .75em 0 0;
			}
			.navigation li.right a {
				float: none;
				background: transparent;
				padding: 0;
				font-size: .95em;
				color: #777;
			}
			.navigation li.right a {
				color: #444;
			}
			.content {
				background: #fff;
				padding: 1em;
				height: 27.75em;
<?php if (count($config->enabled_feathers) == 1): ?>
				position: absolute;
				border: 0;
				top: 1em;
				left: 1em;
				bottom: 1em;
				right: 1em;
				height: auto;
<?php endif; ?>
			}
			.clear {
				clear: both;
			}
			.wide {
				width: 100%;
			}
			textarea.wide, input.text.wide {
				width: 98.5%; /* Compensating for the 6px added from the padding */
				_width: 100%; /* Hooray for IE6's randomized box model. */
			}
			.sub {
				display: none;
			}
		</style>
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.2.6/jquery.min.js" type="text/javascript" charset="utf-8"></script>
		<script src="<?php echo $config->chyrp_url; ?>/includes/lib/gz.php?file=plugins.js" type="text/javascript" charset="utf-8"></script>
		<script type="text/javascript">
			function activate_nav_tab(id) {
				$("[class^='nav_']").removeClass("selected")
				$("[id$='_form']").hide()
				$("#"+id+"_form").show()
				$(".nav_" + id).addClass("selected")
			}
			$(function(){
				$("input.text").each(function(){
					$(this).css("min-width", $(this).width()).Autoexpand()
				})
				$(".navigation li").css("float", "left")
				$(".navigation").sortable({
					axis: "x",
					containment: ".navigation",
					placeholder: "feathers_sort",
					opacity: 0.8,
					delay: 1,
					revert: true,
					update: function(){
						$.post("<?php echo $config->chyrp_url; ?>/includes/ajax.php", "action=reorder_feathers&"+$(".navigation").sortable("serialize"))
					}
				})
			})
		</script>
	</head>
	<body>
<?php if (count($config->enabled_feathers) > 1): ?>
		<ul class="navigation">
<?php
	foreach ($config->enabled_feathers as $the_feather) {
		if (file_exists(FEATHERS_DIR."/".$the_feather."/locale/".$config->locale.".mo"))
			load_translator($the_feather, FEATHERS_DIR."/".$the_feather."/locale/".$config->locale.".mo");

		$info = Horde_Yaml::loadFile(FEATHERS_DIR."/".$the_feather."/info.yaml");
?>
			<li id="list_feathers[<?php echo $the_feather; ?>]" class="nav_<?php echo $the_feather; ?><?php if ($feather == $the_feather) echo ' selected'; ?>">
				<a href="javascript:activate_nav_tab('<?php echo $the_feather; ?>')"><?php echo $info["name"]; ?></a>
			</li>
<?php
	}
?>
		</ul>
		<div class="clear"></div>
<?php endif; ?>
		<div class="content">
<?php
	foreach ($config->enabled_feathers as $the_feather) {
		$style = ($feather == $the_feather) ? "block" : "none" ;

		if (file_exists(FEATHERS_DIR."/".$the_feather."/locale/".$config->locale.".mo"))
			load_translator($the_feather, FEATHERS_DIR."/".$the_feather."/locale/".$config->locale.".mo");

		$info = Horde_Yaml::loadFile(FEATHERS_DIR."/".$the_feather."/info.yaml");
?>
			<form action="<?php echo $config->chyrp_url."/admin/?action=add_post"; ?>" id="<?php echo $the_feather; ?>_form" style="display: <?php echo $style; ?>" method="post" accept-charset="utf-8" enctype="multipart/form-data">
<?php foreach ($feathers[$the_feather]->fields as $field): ?>
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
					<input class="<?php echo $field["type"]; ?><?php if (isset($field["classes"])): ?> <?php echo join(" ", $field["classes"]); ?><?php endif; ?>" type="<?php echo $field["type"]; ?>" name="<?php echo $field["attr"]; ?>" value="<?php if (isset($field["bookmarklet"]) and isset($args[$field["bookmarklet"]])) echo fix($args[$field["bookmarklet"]]); ?>" id="$field["attr"]" />
					<?php elseif ($field["type"] == "text_block"): ?>
					<textarea class="wide<?php if (isset($field["classes"])): ?> <?php echo join(" ", $field["classes"]); ?><?php endif; ?>" rows="<?php echo fallback($field["rows"], 10, true); ?>" name="<?php echo $field["attr"]; ?>" id="<?php echo $field["attr"]; ?>" cols="50"><?php if (isset($field["bookmarklet"]) and isset($args[$field["bookmarklet"]])) echo fix($args[$field["bookmarklet"]]); ?></textarea>
					<?php elseif ($field["type"] == "select"): ?>
					<select name="<?php echo $field["attr"]; ?>" id="<?php echo $field["attr"]; ?>"<?php if (isset($field["classes"])): ?> class="<?php echo join(" ", $field["classes"]); ?>"<?php endif; ?>>
						<?php foreach ($field["options"] as $value => $name): ?>
						<option value="<?php echo fix($value); ?>"<?php if (isset($field["bookmarklet"]) and isset($args[$field["bookmarklet"]])) selected($value, $args[$field["bookmarklet"]]); ?>><?php echo fix($name); ?></option>
						<?php endforeach; ?>
					</select>
					<?php endif; ?>
				</p>
<?php endforeach; ?>
				<input type="hidden" name="feather" value="<?php echo $the_feather; ?>" id="feather" />
				<input type="hidden" name="slug" value="" id="slug" />
				<input type="hidden" name="bookmarklet" value="true" id="bookmarklet" />
				<input type="hidden" name="hash" value="<?php echo $config->secure_hashkey; ?>" id="hash" />
				<input type="submit" value="<?php echo __("Publish"); ?>" />
			</form>
<?php
	}
?>
		</div>
	</body>
</html>
<?php
			break;
		case "done":
			# This one is 100% credited to Tumblr. They did it perfectly, didn't want to muck it up.
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title><?php echo __("Chyrp!"); ?></title>
        <style type="text/css">
            body {
                background-color: #e1e1e1;
                margin:           0px;
                font:             15px normal 'Trebuchet MS',Verdana,Helvetica,sans-serif;
                text-align:       center;
            }

            div#content {
                margin:           137px 30px 0px 30px;
                padding:          15px;
            }
        </style>
        <script type="text/javascript">
			function countdown_func() {
			    countdown--;
			    el = document.getElementById('countdown');
			    if (countdown == 1) {
			        el.firstChild.nodeValue =
			            '<?php echo __("or wait 1 second."); ?>';
			    } else if (countdown > 0) {
			        el.firstChild.nodeValue =
			            '<?php echo __("or wait 2 seconds."); ?>';
			    } else {
                    self.close();
			    }
			    if (countdown > 0) setTimeout('countdown_func()', 1000);
			}

              var countdown = 3;
        </script>
    </head>
    <body>
        <div id="content">
            <div style="margin-bottom:10px; font-size:40px; color:#777;"><?php echo __("Done!"); ?></div>

            <a href="javascript:void(0)" onclick="javascript:self.close(); return false;"
            style="color:#777;"><?php echo __("Close this window"); ?></a>

            <span id="countdown" style="color:#777;">
                <?php echo __("or wait 3 seconds."); ?>
            </span>
        </div>
        <script type="text/javascript">
            setTimeout('countdown_func()', 1000);
        </script>
    </body>
</html>
<?php
			break;
	}
?>
