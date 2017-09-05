<?PHP
require "twitter/autoload.php";
use Abraham\TwitterOAuth\TwitterOAuth;


function uploadToTwitter($pics,$text){
	define('CONSUMER_KEY', '');
	define('CONSUMER_SECRET', '');
	define('OAUTH_CALLBACK', '');
	$access_token = '';
	$access_token_secret = '';

	$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token, $access_token_secret);
	$urlt = array_slice($pics,0,4);
	$medias = [];
	foreach($pics as $link){
		array_push($medias,($connection->upload('media/upload', ['media' => $link])));
	}
	$mapped =  array_map(
		function($o){
			return $o->media_id_string;
		}, $medias);
	$parameters = 
		[  
			'status' => $text,
	    	'media_ids' => implode(',', $mapped)
	    ];

	$result = $connection->post('statuses/update', $parameters);
}
?>