<?php
    class Text extends Feathers implements Feather {
        public function __init() {
            $this->setField(array("attr" => "title",
                                  "type" => "text",
                                  "label" => __("Title", "text"),
                                  "optional" => true,
                                  "bookmarklet" => "title"));
            $this->setField(array("attr" => "featured_image",
                                  "type" => "file",
                                  "label" => __("Featured Image", "text"),
                                  "note" => "<small>(Max. file size: ".ini_get('upload_max_filesize').")</small>"));
            $this->setField(array("attr" => "body",
                                  "type" => "text_block",
                                  "label" => __("Body", "text"),
                                  "preview" => true,
                                  "bookmarklet" => "selection"));

            $this->setFilter("title", array("markup_title", "markup_post_title"));
            $this->setFilter("body", array("markup_text", "markup_post_text"));

            $this->respondTo("delete_post", "delete_file");
            $this->respondTo("post_options", "add_option");
            $this->respondTo("filter_post", "filter_post");
        }

        public function submit() {
            if (empty($_POST['body']))
                error(__("Error"), __("Body can't be blank."));

           if (!isset($_POST['filename'])) {
               if (isset($_FILES['featured_image']) and $_FILES['featured_image']['error'] == 0)
                   $filename = upload($_FILES['featured_image'], array("jpg", "jpeg", "png", "gif", "bmp"));
               else
                   error(__("Error"), __("Couldn't upload photo."));
           } else
               $filename = $_POST['filename'];

            fallback($_POST['slug'], sanitize($_POST['title']));

            return Post::add(array("title" => $_POST['title'],
                                   "filename" => $filename,
                                   "body" => $_POST['body']),
                             $_POST['slug'],
                             Post::check_url($_POST['slug']));
        }

        public function update($post) {
            if (empty($_POST['body']))
                error(__("Error"), __("Body can't be blank."));

            if (!isset($_POST['filename']))
                if (isset($_FILES['featured_image']) and $_FILES['featured_image']['error'] == 0) {
                    $this->delete_file($post);
                    $filename = upload($_FILES['featured_image'], array("jpg", "jpeg", "png", "gif", "tiff", "bmp"));
                } else
                    $filename = $post->filename;
            else {
                $this->delete_file($post);
                $filename = $_POST['filename'];
            }

            $post->update(array("title" => $_POST['title'],
                                "filename" => $filename,
                                "body" => $_POST['body']));
        }

        public function title($post) {
            return oneof($post->title, $post->title_from_excerpt());
        }

        public function excerpt($post) {
            return $post->body;
        }

        public function feed_content($post) {
            return $post->body;
        }

        public function delete_file($post) {
            if ($post->feather != "text") return;
            unlink(MAIN_DIR.Config::current()->uploads_path.$post->filename);
        }

        public function filter_post($post) {
            if ($post->feather != "text") return;
            $post->image = $this->image_tag($post, 510);
        }

        public function image_link($post, $max_width = 500, $max_height = null, $more_args = "quality=100") {
            return '<a href="'.uploaded($post->filename).'">'.$this->image_tag($post, $max_width, $max_height, $more_args).'</a>';
        }

        public function image_tag($post, $max_width = 500, $max_height = null, $more_args = "quality=100") {
            $filename = $post->filename;
            $config = Config::current();
            $alt = !empty($post->alt_text) ? fix($post->alt_text, true) : $filename ;
            return '<img src="'.$config->chyrp_url.'/includes/thumb.php?file=..'.$config->uploads_path.urlencode($filename).'&amp;max_width='.$max_width.'&amp;max_height='.$max_height.'&amp;'.$more_args.'" alt="'.$alt.'" />';
        }

        public function add_option($options, $post = null) {
            if (isset($post) and $post->feather != "text") return;
            elseif (Route::current()->action == "write_post")
                if (!isset($_GET['feather']) and Config::current()->enabled_feathers[0] != "text" or
                    isset($_GET['feather']) and $_GET['feather'] != "text") return;

            $options[] = array("attr" => "option[alt_text]",
                               "label" => __("Alt-Text", "text"),
                               "type" => "text",
                               "value" => oneof(@$post->alt_text, ""));

            return $options;
        }
    }
