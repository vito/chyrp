<?php
    require_once "model.Like.php";

    class Likes extends Modules {
        static function __install() {
            Like::install();
        }

        static function __uninstall($confirm) {
            if ($confirm) Like::uninstall();
        }

        static function admin_like_settings($admin) {
            $config = Config::current();

            if (!Visitor::current()->group->can("change_settings"))
                show_403(__("Access Denied"), __("You do not have sufficient privileges to change settings."));

            if (empty($_POST))
                return $admin->display("like_settings");

            if (!isset($_POST['hash']) or $_POST['hash'] != $config->secure_hashkey)
                show_403(__("Access Denied"), __("Invalid security key."));

            $likeText = array();
            foreach($_POST as $key => $value) {
            	if (strstr($key, "likeText-")) {
            		$exploded_array = explode("-", $key, 2);
            		$likeText[$exploded_array[1]] = strip_tags(stripslashes($value));
            	}
            }

            $set = array($config->set("module_like",
                                array("showOnFront" => isset($_POST['showOnFront']),
                                      "likeWithText" => isset($_POST['likeWithText']),
                                      "likeImage" => $_POST['likeImage'],
                                      "isCacherOn" => isset($_POST['isCacherOn']),
                                      "likeText" => $likeText)));

            if (!in_array(false, $set))
                Flash::notice(__("Settings updated."), "/admin/?action=like_settings");
        }

        static function settings_nav($navs) {
            if (Visitor::current()->group->can("change_settings"))
                $navs["like_settings"] = array("title" => __("Like", "like"));

            return $navs;
        }

        static function route_like() {
            $request["action"] = $_GET['action'];
            $request["post_id"] = $_GET['post_id'];

            $like = new Like($request, Visitor::current()->id);
        }

        public function head() {
?>
        <link rel="stylesheet" href="<?php echo Config::current()->chyrp_url; ?>/modules/likes/style.css" type="text/css" media="screen" />
        <script type="text/javascript">
        <?php $this->likesJS(); ?>
        </script>
<?php
        }

        public static function likesJS(){
        ?>//<script>
        	var likes = {};
        	likes.action = "like";
        	likes.didPrevFinish = true;
        	likes.makeCall = function(post_id, callback, isUnlike) {
        		if (!this.didPrevFinish) return false;
        		if (isUnlike == true) this.action = "unlike"; else this.action = "like";
        		params = {};
        		params["action"] = this.action;
        		params["post_id"] = post_id;
        		jQuery.ajax({
        			type: "POST",
        			url: "<?php echo Config::current()->chyrp_url; ?>/includes/ajax.php",
        			data: params,
        			beforeSend: function() {
            			this.didPrevFinish = false;	
        			},
        			success:function(response){
        				if(response.success == true) {
        					callback(response);
        				}
        				else {
        					likes.log("unsuccessful request, response from server:"+ response)
        				}
        			},
        			error:function (xhr, ajaxOptions, thrownError){
                        likes.log('error in AJAX request.');
        				likes.log('xhrObj:'+xhr);
        				likes.log('thrownError:'+thrownError);
        				likes.log('ajaxOptions:'+ajaxOptions);
                    },
        			complete:function() {
        				this.didPrevFinish=true;
        			},
        			dataType:"json",
        			cache:false
        		})
        	}
        	likes.like = function(post_id) {
        		likes.log("like click for post-"+post_id)
        		$("#likes_post-"+post_id+" a.like").fadeTo(500,.2)
        		this.makeCall(post_id, function(response) {
        			var postDom = $("#likes_post-"+post_id)
        			postDom.children("span.text").html(response.likeText)
        			var thumbImg = postDom.children("a.like").children("img")
                    postDom.children("a.like").attr('title',"").removeAttr('href').text("").addClass("liked").removeClass("like")
        			thumbImg.appendTo(postDom.children("a.liked").eq(0))
        			postDom.children("a.liked").fadeTo("500",.80)
        			postDom.find(".like").hide("fast")
        			postDom.children("div.unlike").show("fast")
        		}, false)
        	}
            likes.unlike = function(post_id) {
            	likes.log("unlike click for post-"+post_id)
            	$("#likes_post-"+post_id+" a.liked").fadeTo(500,.2)
            	this.makeCall(post_id, function(response) {
            		var postDom = $("#likes_post-"+post_id)
            		postDom.children("span.text").html(response.likeText)
            		postDom.children("a.liked").attr("href","javascript:likes.like("+post_id+")").addClass("like").removeClass("liked").fadeTo("500",1)
            		postDom.children("div.unlike").hide("fast")
            		postDom.find(".like").show("fast")
            	}, true)
            }
        	likes.log = function(obj){
        		if(typeof console != "undefined")console.log(obj);
        	}
        </script>
        <?php
        }

        static function ajax() {
            header("Content-type: text/json");
            header("Content-Type: application/x-javascript", true);

            if (!isset($_REQUEST["action"]) or !isset($_REQUEST["post_id"])) exit();
            
            $user_id = Visitor::current()->id;
            $likeSetting = Config::current()->module_like;
            $responseObj = array();
            $responseObj["uid"] = $user_id;
            $responseObj["success"] = true;
            
            try {
                $like = new Like($_REQUEST, $user_id);
                $likeText = "";
                switch ($like->action) {
                	case "like":
                        $like->like();
                        $like->fetchCount();
                        if ($like->total_count == 1)
                        	# $this->text_default[0] = "You like this post.";
                            $likeText = $like->getText($like->total_count, $likeSetting["likeText"][0]);
                        elseif ($like->total_count == 2)
                        	# $this->text_default[1] = "You and 1 person like this post.";
                        	$likeText = $like->getText(1, $likeSetting["likeText"][1]);
                        else {
                            $like->total_count--;
                        	# $this->text_default[2] = "You and %NUM% people like this post.";
                        	$likeText = $like->getText($like->total_count, $likeSetting["likeText"][2]);
                        }
                	break;
                	case "unlike":
                        $like->unlike();
                        $like->fetchCount();
                        if ($like->total_count > 1) {
                            # $this->text_default[5] = "%NUM% people like this post.";
                            $likeText = $like->getText($like->total_count, $likeSetting["likeText"][5]);
                        } elseif ($like->total_count == 1) {
                        	# $this->text_default[4] = "1 person likes this post.";
                        	$likeText = $like->getText($like->total_count, $likeSetting["likeText"][4]);
                        } elseif ($like->total_count == 0)
                        	$likeText = $like->getText($like->total_count, $likeSetting["likeText"][3]);
                	break;
                	default: throw new Exception("invalid action");
                }

                if ($likeSetting["isCacherOn"] == "true") {
                        $GLOBALS["super_cache_enabled"] = 1;
                        $responseObj["cacheCleared"] = true;
                        wp_cache_post_change((int)$_REQUEST["post_id"]);
                }
                $responseObj["likeText"] = $likeText;
            }
            catch(Exception $e) {
                $responseObj["success"] = false;
                $responseObj["error_txt"] = $e->getMessage();
            }
            echo json_encode($responseObj);
        }

        static function delete_post($post) {
            SQL::current()->delete("likes", array("post_id" => $post->id));
        }

        static function delete_user($user) {
            SQL::current()->update("likes", array("user_id" => $user->id), array("user_id" => 0));
        }

        public function post($post) {
            $post->has_many[] = "likes";
        }

        public function post_getLikes_attr($attr, $post) {
            $config = Config::current();
            $route = Route::current();
            $visitor = Visitor::current();
            $likeSetting = $config->module_like;

            if (!$visitor->group->can("like_post")) return;
            if ($likeSetting["showOnFront"] == false and $route->action == "index") return;

            $request["action"] = $route->action;
            $request["post_id"] = $post->id;
            $like = new Like($request, $visitor->id);
            $like->cookieInit();
            $hasPersonLiked = false;

            if ($like->session_hash != null) {
                $people = $like->fetchPeople();
                if (count($people) != 0)
                    foreach ($people as $person)
                        if ($person["session_hash"] == $like->session_hash) {
                            $hasPersonLiked = true;
                            break;
                        }
            } else $like->fetchCount();

            $returnStr = "<div class='likes' id='likes_post-$post->id'>";

            if (!$hasPersonLiked) {
                $returnStr.= "<a class='like' href=\"javascript:likes.like($post->id);\" title='".
                    ($like->total_count ? $likeSetting["likeText"][6] : "")."' >";
                $returnStr.= "<img src=\"".$likeSetting["likeImage"]."\" alt='Like Post-$post->id' />";
                # $this->text_default[6] = "Like";
                $returnStr.= "</a>";
                $returnStr.= "<span class='text'>";
                if ($like->total_count == 0)
                    # $this->text_default[3] = "Be the first to like.";
                    $returnStr.= $like->getText($like->total_count, $likeSetting["likeText"][3]);
                elseif ($like->total_count == 1)
                    # $this->text_default[4] = "1 person likes this post.";
                    $returnStr= $returnStr.$like->getText($like->total_count, $likeSetting["likeText"][4]);
                elseif ($like->total_count > 1)
                    # $this->text_default[5] = "%NUM% people like this post.";
                    $returnStr.= $like->getText($like->total_count, $likeSetting["likeText"][5]);
                $returnStr.= "</span>";
            } else {
                $returnStr.= "<a class='liked'><img src=\"".$likeSetting["likeImage"]."\" alt='Like Post-$post->id' /></a><span class='text'>";
                if ($like->total_count == 1)
                    # $this->text_default[0] = "You like this post.";
                    $returnStr.= $like->getText($like->total_count, $likeSetting["likeText"][0]);
                elseif ($like->total_count == 2)
                    # $this->text_default[1] = "You and 1 person like this post.";
                    $returnStr.= $like->getText(1, $likeSetting["likeText"][1]);
                else {
                    $like->total_count--;
                    # $this->text_default[2] = "You and %NUM% people like this post.";
                    $returnStr.= $like->getText($like->total_count, $likeSetting["likeText"][2]);
                }
                $returnStr.= "</span>";
            }

            if ($likeSetting["likeWithText"]) {
                $returnStr.= "<div class='like' ".($hasPersonLiked ? 'style="display:none"' : "")."><a href=\"javascript:likes.like($post->id);\">".$likeSetting["likeText"][6]."</a></div>";
                if ($visitor->group->can("unlike_post"))
                    $returnStr.= "<div class='unlike' ".($hasPersonLiked ? 'style="display:block"' : "")."><a href=\"javascript:likes.unlike($post->id);\">".$likeSetting["likeText"][7]."</a></div>"; # $this->text_default[7] = "Unlike";
            }

            $returnStr.= "</div>";
            return $post->getLikes = $returnStr;
        }

/*
        public function post_likes_count_attr($attr, $post) {
                $req["post_id"] = $post->id;
                $like = new Like($req);
                return $count = $like->fetchCount();
        }
*/

        public function getLikeImages() {
            $imagesDir = MODULES_DIR."/likes/images/";
            $images = glob($imagesDir . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);

            foreach ($images as $image) {
                $pattern = "/\/(\w.*)\/images\//";
                $image = preg_replace($pattern, "", $images);
                while (list($key, $val) = each($image))
                    $arr[] = Config::current()->chyrp_url."/modules/likes/images/$val";

                return array_combine($image, $arr);
            }
        }
    }
