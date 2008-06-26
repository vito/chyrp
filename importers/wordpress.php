<?php
	require_once "../includes/common.php";

	if (ini_get("memory_limit") < 20) # Some imports are relatively large.
		ini_set("memory_limit", "20M");

	if (!in_array("text", $config->enabled_feathers))
		error(__("Missing Feather"), __("Importing from WordPress requires the Text feather to be installed and enabled."));

	if (!$visitor->group()->can("add_post"))
		error(__("Access Denied"), __("You do not have sufficient privileges to create posts."));

	$errors = array();
	if (!empty($_POST)) {
		switch($_POST['step']) {
			case "1":
				$stupid_xml = utf8_encode(file_get_contents($_FILES['xml_file']['tmp_name']));
				$sane_xml = preg_replace(array("/<wp:comment_content>(?!<!\[CDATA\[)/", "/(?!\]\]>)<\/wp:comment_content>/"),
				                         array("<wp:comment_content><![CDATA[", "]]></wp:comment_content>"),
				                         $stupid_xml);

				$fix_amps_count = 1;
				while ($fix_amps_count)
					$sane_xml = preg_replace("/<wp:meta_value>(.+)&(?!amp;)(.+)<\/wp:meta_value>/m",
					                         "<wp:meta_value>\\1&amp;\\2</wp:meta_value>",
					                         $sane_xml, -1, $fix_amps_count);

				$xml = simplexml_load_string($sane_xml, "SimpleXMLElement", LIBXML_NOCDATA);

				if ($xml and strpos($xml->channel->generator, "wordpress.org")) {
					foreach ($xml->channel->item as $item) {
						$wordpress = $item->children("http://wordpress.org/export/1.0/");
						$content   = $item->children("http://purl.org/rss/1.0/modules/content/");
						if ($wordpress->status == "attachment" or $item->title == "zz_placeholder")
							continue;

						if (!empty($_POST['media_url']) and preg_match_all("/".preg_quote($_POST['media_url'], "/")."([^ \t\"]+)/", $content->encoded, $media))
							foreach ($media[0] as $matched_url) {
								$file = tempnam(sys_get_temp_dir(), "chyrp");
								file_put_contents($file, get_remote($matched_url));
								$fake_file = array("name" => basename(parse_url($matched_url, PHP_URL_PATH)),
								                   "tmp_name" => $file);
								$filename = upload($fake_file, null, "", true);
								$content->encoded = str_replace($matched_url, $config->url.$config->uploads_path.$filename, $content->encoded);
							}

						$clean = (isset($wordpress->post_name)) ? $wordpress->post_name : sanitize($item->title) ;

						if (empty($wordpress->post_type) or $wordpress->post_type == "post") {
							$status_translate = array("publish"    => "public",
							                          "draft"      => "draft",
							                          "private"    => "private",
							                          "static"     => "public",
							                          "object"     => "public",
							                          "inherit"    => "public",
							                          "future"     => "draft",
							                          "pending"    => "draft");

							$_POST['status']  = str_replace(array_keys($status_translate), array_values($status_translate), $wordpress->status);
							$_POST['pinned']  = false;
							$_POST['created_at'] = ($wordpress->post_date == "0000-00-00 00:00:00") ? datetime() : $wordpress->post_date ;

							$data = $values = array("title" => (string) $item->title, "body" => (string) $content->encoded);
							$post = Post::add($data, $clean, Post::check_url($clean));

							$trigger->call("import_wordpress_post", array($item, $post));
						} elseif ($wordpress->post_type == "page") {
							$page = Page::add((string) $item->title, (string) $content->encoded, 0, true, 0, $clean, Page::check_url($clean));
							$trigger->call("import_wordpress_page", array($item, $post));
						}
					}
					$step = "2";
				} else
					$errors[] = __("File does not seem to be a valid WordPress export file.");

				break;
		}
	}

	$step = (isset($step)) ? $step : "1" ;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
		<title><?php echo __("Chyrp: Import: WordPress"); ?></title>
		<style type="text/css" media="screen">
			body {
				font: .8em/1.5em normal "Lucida Grande", "Trebuchet MS", Verdana, Helvetica, Arial, sans-serif;
				color: #333;
				background: #eee;
				margin: 0;
				padding: 0;
			}
			.window {
				width: 250px;
				margin: 25px auto;
				padding: 1em;
				border: 1px solid #ddd;
				background: #fff;
			}
			h1 {
				font-size: 1.75em;
				margin-top: 0;
				color: #aaa;
				font-weight: bold;
			}
			label {
				display: block;
				font-weight: bold;
				border-bottom: 1px dotted #ddd;
				margin-bottom: 2px;
			}
			input.text, textarea {
				margin-bottom: 1em;
				font-size: 1.25em;
				width: 242px;
				padding: 3px;
				border: 1px solid #ddd;
			}
			select {
				margin-bottom: 1em;
			}
			.sub, small {
				font-size: .8em;
				color: #777;
				font-weight: normal;
			}
			small {
				margin-top: -1em;
				float: left;
				line-height: 1.2em;
			}
			code {
				font-size: 1.2em;
			}
			.center {
				text-align: center;
			}
			.error {
				margin: 0 0 1em 0;
				padding: 6px 8px 5px 30px;
				cursor: pointer;
				border-bottom: 1px solid #FBC2C4;
				color: #D12F19;
				background: #FBE3E4 url('../admin/images/icons/failure.png') no-repeat 7px center;
			}
			.done {
				font-size: 1.25em;
				font-weight: bold;
				text-decoration: none;
				color: #555;
			}
		</style>
	</head>
	<body>
<?php foreach ($errors as $error): ?>
		<div class="error"><?php echo $error; ?></div>
<?php endforeach; ?>
		<div class="window">
<?php
	switch($step):
		case "1":
?>
			<h1><?php echo __("Import WordPress"); ?></h1>
			<p><?php echo __("Please upload your WordPress eXtended RSS .xml file."); ?></p>
			<form action="wordpress.php" method="post" accept-charset="utf-8" enctype="multipart/form-data">
				<label for="xml_file"><?php echo __("eXtended .XML File"); ?></label>
				<input type="file" name="xml_file" value="" id="xml_file" />
				<br />
				<br />
				<label for="media_url"><?php echo __("What URL is used for attached/embedded media?"); ?> <span class="sub"><?php echo __("(optional)"); ?></span></label>
				<input class="text" type="text" name="media_url" value="<?php echo fallback($_POST['media_url'], "", true); ?>" id="media_url" />
				<small>
					<?php echo __("Usually something like <code>http://example.com/wp-content/uploads/</code>"); ?>
				</small>
				<br />
				<br />
				<input type="hidden" name="step" value="1" id="step" />
				<input type="hidden" name="feather" value="text" id="feather" />
				<p class="center"><input type="submit" value="<?php echo __("Import &rarr;"); ?>" /></p>
				<input type="hidden" name="hash" value="<?php echo $config->secure_hashkey; ?>" id="hash" />
			</form>
<?php
			break;
		case "2":
?>
			<h1><?php echo __("All done!"); ?></h1>
			<p><?php echo __("All entries have been successfully imported."); ?></p>
			<a href="<?php echo $config->url; ?>" class="done"><?php echo __("View Site &raquo;"); ?></a>
<?php
			break;
	endswitch;
?>
		</div>
	</body>
</html>
