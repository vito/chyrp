<?php
    class Emailblog extends Modules {
        public function __init() {
           $this->addAlias("runtime", "getMail");
           //run on every page load- not very efficient, but it works.
        }
		function getMail(){
       		$hostname = '{imap.gmail.com:993/imap/ssl}INBOX';
        	$username = 'davidwalshblog@gmail.com';
        	$password = 'davidwalsh';
        	$inbox = imap_open($hostname,$username,$password) or die('Cannot connect to Gmail: ' . imap_last_error());
        	$emails = imap_search($inbox,'ALL');
        	if($emails) {
        	    rsort($emails);
        	    foreach($emails as $email_number) {    
        	        $message = imap_fetchbody($inbox,$email_number,2);  
        	        $overview = imap_fetch_overview($inbox,$email_number,0);
        	        imap_delete($inbox,$email_number);
        	        $title=htmlspecialchars($overview[0]->subject);
        	        $body=htmlspecialchars($message);
        	        Post::add(array("title" => $title,
        	        "body" => $message));
        	    }
        	} 
        	
        	/* close the connection */
        	imap_close($inbox,CL_EXPUNGE);
		}
		static function __install() {
            $config = Config::current();
            $config->set("email_blog_address", "example@gmail.com");
            $config->set("email_blog_pass", "password");
        }
        static function __uninstall($confirm) {
             $config = Config::current();
            $config->remove("email_blog_address");
            $config->remove("email_blog_pass");
        }
		static function manage_nav_pages($pages) {
            array_push($pages, "email_blog_settings");
            return $pages;
        }
        static function settings_nav($navs) {
            if (Visitor::current()->group->can("change_settings"))
                $navs["email_blog_settings"] = array("title" => __("Email", "emailblog"));

            return $navs;
        }
        static function admin_email_blog_settings($admin) {
            if (!Visitor::current()->group->can("change_settings"))
                show_403(__("Access Denied"), __("You do not have sufficient privileges to change settings."));

            if (empty($_POST))
                return $admin->display("email_blog_settings");

            if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
                show_403(__("Access Denied"), __("Invalid security key."));

            $config = Config::current();
            $set = array($config->set("email_blog_address", $_POST['email']), $config->set("email_blog_pass", $_POST['pass']));
            if (!in_array(false, $set))
                Flash::notice(__("Settings updated."), "/admin/?action=email_blog_settings");
        }
    }
?>