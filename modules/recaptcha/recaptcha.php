<?php
     require_once INCLUDES_DIR."/class/Captcha.php";
     class ReCaptcha extends Modules {
          public function __init() {
              global $captchaHooks;
              $captchaHooks[] = "ReCaptchaCaptcha";
          }
     }

     class ReCaptchaCaptcha implements Captcha {
         public static function getCaptcha() {
            require_once INCLUDES_DIR."/lib/recaptchalib.php";
            $publickey = "6LeNvdESAAAAANcv1-lPGCDDfcKUI02HSVEUAq3F";
            return recaptcha_get_html($publickey);
         }

         public static function verifyCaptcha() {
            require_once INCLUDES_DIR."/lib/recaptchalib.php";
            $privatekey = "6LeNvdESAAAAAFWWO1-uXQZF-1MTp3L9U1P-X6mG";
            $resp = recaptcha_check_answer ($privatekey,
                                 $_SERVER['REMOTE_ADDR'],
                                 $_POST['recaptcha_challenge_field'],
                                 $_POST['recaptcha_response_field']);
            if (!$resp->is_valid) 
                return false;
            else
                return true;
         }
     }
