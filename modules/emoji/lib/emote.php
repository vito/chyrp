<?php
function convertEmoticons($text) {
   $emoticons = array(
		'>_<' => 'Angry.gif',
		':angry:' => 'Angry.gif',
		':blush:' => 'Blush.gif',
		  ':bored:' => 'Bored.gif',
		  'C8' => 'see-eight.gif',
		  ':coffee:' => 'Coffee-cup.gif',
		    'B)' => 'Cool.gif',
		   '8)' => 'Cool.gif',
		   ':\'(' => 'Cry.gif',
		   ':cry:' => 'Cry.gif',
		   ':3' => 'Cute.gif',
		   'x_x' => 'Dead.gif',
		   '>:-)' => 'Evil.gif',
		   ':-*' => 'Flirt.gif',
		   ':-D' => 'Grin.gif',
		   ':D' => 'Grin.gif',
		   ':love:' => 'Love.gif',
		   ':P' => 'Tongue.gif',
		   ':-P' => 'Tongue.gif',
		   ':ooo:' => 'Oooo.gif',
		   ':-(' => 'Sad.gif',
		   ':(' => 'Sad.gif',
		   ':O' => 'Shocked.gif',
		   ':-O' => 'Shocked.gif',
		   ':)' => 'Smile.gif',
		   ':-)' => 'Smile.gif',
		   ':>' => 'Small-Smile.gif',
		);
   
   
  foreach($emoticons as $key => $value) {
   $text =  str_ireplace($key,'<img src="'. Config::current()->chyrp_url . '/modules/emoticons/images/' . $value .'" class="smiley" alt="">', $text);
  }
   
   return $text;
}
?>