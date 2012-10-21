<?php
    require_once INCLUDES_DIR."/class/Captcha.php";

    class ReCaptcha extends Modules {
        public function __init() {
            global $captchaHooks;
            $captchaHooks[] = "ReCaptchaCaptcha";
        }
    }

    class ReCaptchaCaptcha implements Captcha {
        static function getCaptcha() {
            require_once INCLUDES_DIR."/lib/recaptchalib.php";
            $publickey = "6Lf6RsoSAAAAAEqUPsm4icJTg7Ph3mY561zCQ3l3";
            return recaptcha_get_html($publickey);
        }

        static function verifyCaptcha() {
            require_once INCLUDES_DIR."/lib/recaptchalib.php";
            $privatekey = "6Lf6RsoSAAAAAKn-wPxc1kE-DE0M73i206w56HEN";
            $resp = recaptcha_check_answer($privatekey,
                                 $_SERVER['REMOTE_ADDR'],
                                 $_POST['recaptcha_challenge_field'],
                                 $_POST['recaptcha_response_field']);
            if (!$resp->is_valid)
                return false;
            else
                return true;
        }
    }
