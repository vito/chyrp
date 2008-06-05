<?php
	require_once "common.php";

	if (!$visitor->group()->can("add_post"))
		error(__("Access Denied"), __("You do not have sufficient privileges to create posts."));

	if (empty($config->enabled_feathers))
		error(__("No Feathers"), __("Please install a feather or two in order to add a post."));

	$feather = $config->enabled_feathers[0];
	fallback($_GET['status']);

	$args['url'] = (isset($_GET['url'])) ? urldecode(stripslashes($_GET['url'])) : null ;
	$args['title'] = (isset($_GET['title'])) ? urldecode(stripslashes($_GET['title'])) : null ;
	$args['selection'] = (isset($_GET['selection'])) ? urldecode(stripslashes($_GET['selection'])) : null ;

	$post = new Post();

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
			body {
				font: .8em/1.5em normal "Lucida Grande", "Trebuchet MS", Verdana, Helvetica, Arial, sans-serif;
				color: #333;
				background: #eee;
				margin: 0;
				padding: 1em;
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
			.navigation {
				margin: 0;
				padding: 0;
			}
			.navigation li {
				float: left;
				margin: 0 0 -1px .5em;
				padding: .25em .5em;
				background: #ddd;
				border: 1px solid #ddd;
				border-bottom: 0;
			}
			.navigation li.selected {
				background: #fff;
			}
			.navigation li a {
				color: #888;
				border: none;
			}
			.content {
				border: 1px solid #ddd;
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
		<script type="text/javascript">
			function activate_nav_tab(id) {
				$("[id^='nav_']").removeClass("selected")
				$("[id$='_form']").hide()
				$("#"+id+"_form").show()
				$("#nav_" + id).addClass("selected")
			}
			$(function(){
				$("input.text").keyup(function(){
					if ($(this).val().length > 10 && ($(this).parent().width() - $(this).width()) < 10)
						return;

					$(this).attr("size", $(this).val().length)
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

		$info = Spyc::YAMLLoad(FEATHERS_DIR."/".$the_feather."/info.yaml");
?>
			<li id="nav_<?php echo $the_feather; ?>"<?php if ($feather == $the_feather) echo ' class="selected"'; ?>>
				<a href="javascript:activate_nav_tab('<?php echo $the_feather; ?>')"><?php echo $info["name"]; ?></a>
			</li>
<?php
	}
?>
		</ul>
		<br class="clear" />
<?php endif; ?>
		<div class="content">
<?php
	foreach ($config->enabled_feathers as $the_feather) {
		$style = ($feather == $the_feather) ? "block" : "none" ;

		if (file_exists(FEATHERS_DIR."/".$the_feather."/locale/".$config->locale.".mo"))
			load_translator($the_feather, FEATHERS_DIR."/".$the_feather."/locale/".$config->locale.".mo");

		$info = Spyc::YAMLLoad(FEATHERS_DIR."/".$the_feather."/info.yaml");
?>
			<form action="<?php echo $config->chyrp_url."/admin/?action=add_post"; ?>" id="<?php echo $the_feather; ?>_form" style="display: <?php echo $style; ?>" method="post" accept-charset="utf-8" enctype="multipart/form-data">
<?php
		foreach ($feathers[$the_feather]->fields as $field):
			$optional = isset($field["optional"]) and $field["optional"];
			$help = isset($field["help"]) and $field["help"];
?>
				<p>
					<label for="<?php echo $field["attr"]; ?>">
						<?php echo $field["label"]; ?>
						<?php if ($optional): ?><span class="sub"><?php echo __("(optional)"); ?></span><?php endif; ?>
						<?php if ($help): ?>
						<span class="sub">
							<a href="<?php echo $config->chyrp_url."/admin/?action=help&feather=".$the_feather."&field=".$field["attr"]; ?>" target="_blank" class="help emblem"><img src="<?php echo $config->chyrp_url."/admin/images/icons/help.png"; ?>" alt="help" /></a>
						</span>
						<?php endif; ?>
					</label>
					<?php if ($field["type"] == "text" or $field["type"] == "file"): ?>
					<input class="<?php echo $field["type"]; ?> <?php echo implode(" ", $field["classes"]); ?>" type="<?php echo $field["type"]; ?>" name="<?php echo $field["attr"]; ?>" id="<?php echo $field["attr"]; ?>" />
					<?php endif; ?>
					<?php if ($field["type"] == "text_block"): ?>
					<textarea class="wide <?php echo implode(" ", $field["classes"]); ?>" rows="<?php echo fallback($field["rows"], 12, true); ?>" name="<?php echo $field["attr"]; ?>" id="<?php echo $field["attr"]; ?>"></textarea>
					<?php endif; ?>
				</p>
<?php endforeach; ?>
				<input type="hidden" name="feather" value="<?php echo $the_feather; ?>" id="feather" />
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
