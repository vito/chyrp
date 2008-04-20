<?php
	class Audio extends Feather {
		public function __construct() {
			$this->setFilter("description", "markup_post_text");
			$this->respondTo("delete_post", "delete_file");
			$this->respondTo("javascript", "player_js");
			$this->respondTo("feed_item", "enclose_mp3");
			$this->respondTo("filter_post", "filter_post");
		}
		static function submit() {
			$filename = "";
			if (isset($_FILES['audio']) and $_FILES['audio']['error'] == 0)
				$filename = upload($_FILES['audio'], "mp3");
			else
				error(__("Error"), __("Couldn't upload audio file."));

			$values = array("filename" => $filename, "description" => $_POST['description']);
			$clean = (!empty($_POST['slug'])) ? $_POST['slug'] : "" ;
			$url = Post::check_url($clean);

			$post = Post::add($values, $clean, $url);

			# Send any and all pingbacks to URLs in the description
			$config = Config::current();
			if ($config->send_pingbacks)
				send_pingbacks($_POST['description'], $post->id);

			$route = Route::current();
			if (isset($_POST['bookmarklet']))
				$route->redirect($route->url("bookmarklet/done/"));
			else
				$route->redirect($post->url());
		}
		static function update() {
			$post = new Post($_POST['id']);

			if (isset($_FILES['audio']) and $_FILES['audio']['error'] == 0) {
				delete_audio_file($_POST['id']);
				$filename = upload($_FILES['audio'], "mp3");
			} else
				$filename = $post->filename;

			$values = array("filename" => $filename, "description" => $_POST['description']);

			$post->update($values);
		}
		static function title($post) {
			return fallback($post->title, $post->title_from_excerpt(), true);
		}
		static function excerpt($post) {
			return $post->description;
		}
		static function feed_content($post) {
			return $post->description;
		}
		static function delete_file($post) {
			if ($post->feather != "audio") return;

			unlink(MAIN_DIR."/upload/".$post->filename);
		}
		static function filter_post() {
			global $post;
			if ($post->feather != "audio") return;

			$post->audio_player = flash_player_for($post->filename);
		}
		static function player_js() {
?>
//<script>
var ap_instances = new Array();

function ap_stopAll(playerID) {
	for(var i = 0;i<ap_instances.length;i++) {
		try {
			if(ap_instances[i] != playerID) document.getElementById("audioplayer" + ap_instances[i].toString()).SetVariable("closePlayer", 1);
			else document.getElementById("audioplayer" + ap_instances[i].toString()).SetVariable("closePlayer", 0);
		} catch( errorObject ) {
			// stop any errors
		}
	}
}

function ap_registerPlayers() {
	var objectID;
	var objectTags = document.getElementsByTagName("object");
	for(var i=0;i<objectTags.length;i++) {
		objectID = objectTags[i].id;
		if(objectID.indexOf("audioplayer") == 0) {
			ap_instances[i] = objectID.substring(11, objectID.length);
		}
	}
}

var ap_clearID = setInterval( ap_registerPlayers, 100 );
//</script>
<?php
		}
		static function enclose_mp3($id) {
			$post = new Post($id);
			if ($post->feather != "audio") return;

			$length = filesize(MAIN_DIR."/upload/".$post->filename);
			$config = Config::current();
			echo '			<enclosure url="'.$config->url.'/upload/'.$post->filename.'" type="audio/mpeg" length="'.$length.'" />'."\n";
		}
	}
	function flash_player_for($filename, $params = array()) {
		global $post;

		$vars = "";
		foreach ($params as $name => $val)
			$vars.= "&amp;".$name."=".$val;

		$config = Config::current();
		$player = '<script src="'.$config->url.'/feathers/audio/lib/audio-player.js" type="text/javascript" charset="utf-8"></script>'."\n";
		$player.= '<object type="application/x-shockwave-flash" data="'.$config->url.'/feathers/audio/lib/player.swf" id="audio_player_'.$post->id.'" height="24" width="290">'."\n\t";
		$player.= '<param name="movie" value="'.$config->url.'/feathers/audio/lib/player.swf" />'."\n\t";
		$player.= '<param name="FlashVars" value="playerID='.$post->id.'&amp;soundFile='.$config->url.'/upload/'.$filename.$vars.'" />'."\n\t";
		$player.= '<param name="quality" value="high" />'."\n\t";
		$player.= '<param name="menu" value="false" />'."\n\t";
		$player.= '<param name="wmode" value="transparent" />'."\n";
		$player.= '</object>'."\n";

		return $player;
	}