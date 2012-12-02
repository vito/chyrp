<?php
    class Audio extends Feathers implements Feather {
        public function __init() {
            $this->setField(array("attr" => "audio",
                                  "type" => "file",
                                  "label" => __("MP3 File", "audio"),
                                  "note" => "<small>(Max. file size: ".ini_get('upload_max_filesize').")</small>"));
            if (isset($_GET['action']) and $_GET['action'] == "bookmarklet")
                $this->setField(array("attr" => "from_url",
                                      "type" => "text",
                                      "label" => __("From URL?", "audio"),
                                      "optional" => true,
                                      "no_value" => true));
            $this->setField(array("attr" => "description",
                                  "type" => "text_block",
                                  "label" => __("Description", "audio"),
                                  "optional" => true,
                                  "preview" => true,
                                  "bookmarklet" => "selection"));

            $this->setFilter("description", array("markup_text", "markup_post_text"));

            $this->respondTo("delete_post", "delete_file");
            $this->respondTo("javascript", "player_js");
            $this->respondTo("feed_item", "enclose_mp3");
            $this->respondTo("filter_post", "filter_post");
            $this->respondTo("admin_write_post", "swfupload");
            $this->respondTo("admin_edit_post", "swfupload");
            $this->respondTo("post_options", "add_option");
        }

        public function swfupload($admin, $post = null) {
            if (isset($post) and $post->feather != "audio" or
                isset($_GET['feather']) and $_GET['feather'] != "audio")
                return;

            Trigger::current()->call("prepare_swfupload", "audio", "*.mp3");
        }

        public function submit() {
            if (!isset($_POST['filename'])) {
                if (isset($_FILES['audio']) and $_FILES['audio']['error'] == 0)
                    $filename = upload($_FILES['audio'], "mp3");
                elseif (!empty($_POST['from_url']))
                    $filename = upload_from_url($_POST['from_url'], "mp3");
                else
                    error(__("Error"), __("Couldn't upload audio file."));
            } else
                $filename = $_POST['filename'];

            return Post::add(array("filename" => $filename,
                                   "description" => $_POST['description']),
                             $_POST['slug'],
                             Post::check_url($_POST['slug']));
        }

        public function update($post) {
            if (!isset($_POST['filename']))
                if (isset($_FILES['audio']) and $_FILES['audio']['error'] == 0) {
                    $this->delete_file($post);
                    $filename = upload($_FILES['audio'], "mp3");
                } elseif (!empty($_POST['from_url'])) {
                    $this->delete_file($post);
                    $filename = upload_from_url($_POST['from_url'], "mp3");
                } else
                    $filename = $post->filename;
            else {
                $this->delete_file($post);
                $filename = $_POST['filename'];
            }

            $post->update(array("filename" => $filename,
                                "description" => $_POST['description']));
        }

        public function title($post) {
            return oneof($post->title, $post->title_from_excerpt());
        }

        public function excerpt($post) {
            return $post->description;
        }

        public function feed_content($post) {
            return $post->description;
        }

        public function delete_file($post) {
            if ($post->feather != "audio") return;
            unlink(MAIN_DIR.Config::current()->uploads_path.$post->filename);
        }

        public function filter_post($post) {
            if ($post->feather != "audio") return;
            $post->audio_player = $this->audio_player($post->filename, array(), $post);
        }

        public function player_js() {
?>//<script>
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
<?php
        }

        public function enclose_mp3($post) {
            $config = Config::current();
            if ($post->feather != "audio" or !file_exists(uploaded($post->filename, false)))
                return;

            $length = filesize(uploaded($post->filename, false));

            echo '          <link rel="enclosure" href="'.uploaded($post->filename).'" type="audio/mpeg" title="MP3" length="'.$length.'" />'."\n";
        }

        public function audio_player($filename, $params = array(), $post) {
            $vars = "";
            foreach ($params as $name => $val)
                $vars.= "&amp;".$name."=".$val;

            $config = Config::current();
            $player = '<audio id="audio_with_controls" controls>'."\n\t";
            $player.= '<source src="'.$config->chyrp_url.$config->uploads_path.$filename.$vars.'" type="audio/mpeg" />'."\n\t";

            $player.= '<object type="application/x-shockwave-flash" data="'.$config->chyrp_url.'/feathers/audio/lib/player.swf" id="audioplayer'.$post->id.'" height="24" width="290">'."\n\t";
            $player.= '<param name="movie" value="'.$config->chyrp_url.'/feathers/audio/lib/player.swf" />'."\n\t";
            $player.= '<param name="FlashVars" value="playerID='.$post->id.'&amp;soundFile='.$config->chyrp_url.$config->uploads_path.$filename.$vars.'" />'."\n\t";
            $player.= '<param name="quality" value="high" />'."\n\t";
            $player.= '<param name="menu" value="false" />'."\n\t";
            $player.= '<param name="wmode" value="transparent" />'."\n";
            $player.= '</object>'."\n";
            $player.= '</audio>'."\n";

            $player.= '<div id="player_fallback"></div>'."\n\t";
            $player.= '<script src="http://ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js" type="text/javascript" charset="utf-8"></script>'."\n\t";
            $player.= '<script>'."\n\t";
            $player.= "if (document.createElement('audio').canPlayType) {"."\n\t";
            $player.= "    if (!document.createElement('audio').canPlayType('audio/mpeg')) {"."\n\t";
            $player.= '        swfobject.embedSWF("'.$config->chyrp_url.'/feathers/audio/lib/player.swf",
                               "player_fallback", "290", "24", "9.0.0", "",
                               {"playerID":"'.$post->id.'&soundFile='.$config->chyrp_url.$config->uploads_path.$filename.$vars.'"});'."\n\t";
            $player.= "        document.getElementById('audio_with_controls').style.display = 'none'; }"."\n\t";
            $player.= '}'."\n\t";
            $player.= '</script>'."\n\t";

            return $player;
        }

        public function add_option($options, $post = null) {
            if (isset($post) and $post->feather != "audio") return;
            elseif (Route::current()->action == "write_post")
                if (!isset($_GET['feather']) and Config::current()->enabled_feathers[0] != "audio" or
                    isset($_GET['feather']) and $_GET['feather'] != "audio") return;

            $options[] = array("attr" => "from_url",
                               "label" => __("From URL?", "audio"),
                               "type" => "text");

            return $options;
        }
    }
