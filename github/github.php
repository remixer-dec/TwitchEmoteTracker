<?

function uploadToGithub($user,$repo,$path,$content,$token){
	$url = "https://api.github.com/repos/$user/$repo/contents/$path";
	$opts = ['http' => ['method' => 'GET','header' => ['User-Agent: xD']]];
	$ctx = stream_context_create($opts);
	$obj = array(
		"path" => $path,  
        "message" => "change ".time(), 
        "content" => base64_encode($content),
        "sha" => json_decode(file_get_contents($url,false,$ctx))->sha,
        "branch" => "master"
        );
    $json = json_encode($obj);

	$ch = curl_init();
	var_dump($json);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: token $token"));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT"); 
	curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_USERAGENT, "xD");
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$data = curl_exec($ch);
	var_dump($data);
	curl_close($ch);
}

?>
