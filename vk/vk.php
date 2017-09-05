<? 
$token = '';
function vkapi($method,$token='',$parameters='',$post=false){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"https://api.vk.com/method/$method?access_token=$token&$parameters&v=3.0");  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    if($post){
    	curl_setopt($ch,CURLOPT_POST, true); curl_setopt($ch,CURLOPT_POSTFIELDS, $post);
    }
    $result = curl_exec($ch);  
    curl_close($ch); 
    return $result;  
}

function uploadPicsToVK($urls){
	global $token;

	function getServer(){
		global $token;
		$server = json_decode(vkapi('photos.getWallUploadServer',$token,'group_id=13211793'));
		if($server->response){
			return $server->response->upload_url;
		}else die('error on getting server stage: '.$server->error->error_msg);
	}

	function doPost($url,$files){
		$boundary = md5(microtime());
		$request = array();
		$i = 0;
	    foreach($files as $imageUri){
            $content = file_get_contents($imageUri);
            $item = '--'.$boundary . PHP_EOL;
            $item .= 'Content-Disposition: form-data; name="file'.$i++.'"; filename="'.rand(0,65536).'.png"'. PHP_EOL;
            $item .= 'Content-Type: image/png'. PHP_EOL;
            $item .= 'Content-Length: ' . strlen($content). PHP_EOL;
            $item .= PHP_EOL;
            $item .= $content . PHP_EOL;
            $request[] = $item;
        }
        $request[] = '--' . $boundary . '--' . PHP_EOL;
        $context = stream_context_create(Array('http' => Array(
                'method'    => 'POST',
                'header'    => 'Content-Type: multipart/form-data; boundary='.$boundary,
                'content'   => implode('', $request),
            )));
        $post_data = json_decode(file_get_contents($url, null, $context), true);
        return $post_data;
	}

	function uploadPicsToVk($server,$data){
		global $token;
		$p = doPost($server,$data);
		$s = json_decode(vkapi('photos.saveWallPhoto',$token,'gid=13211793&photo='.$p['photo'].'&server='.$p['server'].'&hash='.$p['hash']));
		if($s->response){
			return $s->response;
		} else die('error on saving stage: '.$s->error->error_msg);
	}
	$serv = getServer();
	$res = uploadPicsToVk($serv,$urls);
	return $res;
}

function uploadAndAttach($urlarr){
	$xd = uploadPicsToVK($urlarr);
	$att='';
	foreach ($xd as $key => $v) {
		$att .= $v->id.',';
	}
	return $att;
}

function uploadToVK($pics,$text){
    $urlt = array_slice($pics,0,10);
    $att = uploadAndAttach($urlt);
    if(txt!=""){
    	$vkreq=json_decode(vkapi('wall.post',$token,'owner_id=-132117932&message='.urlencode($text).'&attachments='.$att));
    }
}


 ?>