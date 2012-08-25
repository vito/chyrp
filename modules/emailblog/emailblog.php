<?php
    class EmailBlog extends Modules {
        public function __init() {
            $config = Config::current();
            $username = $config->emailblog_address;
            $password = $config->emailblog_pass;

            # this isn't working well on localhost
            # run on every page load - not very efficient, but it works
            if ($username != "example@gmail.com" && $password != "password")
                $this->addAlias("runtime", "getMail");
        }

        static function __install() {
                $config = Config::current();
                $config->set("emailblog_server", "imap.gmail.com:993/ssl/novalidate-cert");
                $config->set("emailblog_address", "example@gmail.com");
                $config->set("emailblog_pass", "password");
                $config->set("emailblog_subjpass", "BlogPost");
                $config->set("emailblog_mail_checked", time());
                $config->set("emailblog_minutes", 60);
            }

        static function __uninstall($confirm) {
            $config = Config::current();
            $config->remove("emailblog_address");
            $config->remove("emailblog_pass");
            $config->remove("emailblog_subjpass");
            $config->remove("emailblog_mail_checked");
            $config->remove("emailblog_minutes");
            $config->remove("emailblog_server");
        }

        static function admin_emailblog_settings($admin) {
            if (!Visitor::current()->group->can("change_settings"))
                show_403(__("Access Denied"), __("You do not have sufficient privileges to change settings."));
    
            if (empty($_POST))
                return $admin->display("emailblog_settings");
    
            if (!isset($_POST['hash']) or $_POST['hash'] != Config::current()->secure_hashkey)
                show_403(__("Access Denied"), __("Invalid security key."));
    
            $config = Config::current();
            $set = array($config->set("emailblog_address", $_POST['email']),
                         $config->set("emailblog_pass", $_POST['pass']),
                         $config->set("emailblog_minutes", $_POST['minutes']),
                         $config->set("emailblog_subjpass", $_POST['subjpass']),
                         $config->set("emailblog_server", $_POST['server']));

            if (!in_array(false, $set))
                Flash::notice(__("Settings updated."), "/admin/?action=emailblog_settings");
        }

        static function settings_nav($navs) {
            if (Visitor::current()->group->can("change_settings"))
                $navs["emailblog_settings"] = array("title" => __("EmailBlog", "emailblog"));
            return $navs;
        }

        /**
         * Gets the mail from the inbox
         * Reads all the messages there, and adds posts based on them. Then it deletes the entire mailbox.
         */
        function getMail(){
            $config = Config::current();
            if (time() - (60 * $config->emailblog_minutes) >= $config->emailblog_mail_checked) {
                    $hostname = '{'.$config->emailblog_server.'}INBOX';
                    # this isn't working well on localhost
                    $username = $config->emailblog_address;
                    $password = $config->emailblog_pass;
                    $subjpass = $config->emailblog_subjpass;
                    $inbox = imap_open($hostname, $username, $password) or exit("Cannot connect to Gmail: " . imap_last_error());
                    $emails = imap_search($inbox, 'SUBJECT "'.$subjpass.'"');
                    if ($emails) {
                        rsort($emails);
                        foreach ($emails as $email_number) {
                            $message = imap_body($inbox, $email_number);
                            $overview = imap_headerinfo($inbox, $email_number);
                            imap_delete($inbox, $email_number);

                            $title = htmlspecialchars($overview->Subject);
                            $title = preg_replace($subjpass,"",$title);
                            $clean = strtolower($title);
                            $body = htmlspecialchars($message);

                            # The subject of the email is used as the post title
                            # the content of the email is used as the body
                            # not sure about compatibility with images or audio feathers
                            Post::add(array("title" => $title, 
                                            "body" => $message),
                                      $clean,
                                      Post::check_url($clean),
                                      "text");
                        }
                    }
    
                    # close the connection
                    imap_close($inbox, CL_EXPUNGE);
                    $config->set("emailblog_mail_checked", time());
                }
        }
    }
