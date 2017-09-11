<?php 
$CLIENTID =''; //twitch client ID

function trackEmotes(){
  global $removed; global $added; global $changed; global $pics; global $CLIENTID;
  $ttv=file_get_contents('https://api.twitch.tv/kraken/chat/global/emoticons?client_id='.$CLIENTID);
  $bttv=file_get_contents('https://api.betterttv.net/2/emotes');
  $txt ="";
  $ttv=json_decode($ttv)->emoticons;
  $bttv=json_decode($bttv)->emotes;
  $thash=md5(serialize($ttv));
  $btthash=md5(serialize($bttv));
  $ttold=json_decode(file_get_contents('twitch/ttv.json'));
  $bttold=json_decode(file_get_contents('twitch/bttv.json'));
  $hash=json_decode(file_get_contents('twitch/hash.json'));
  $pics = $removed = $added = $changed = [];

  /**
   * compare 2 emote lists from twitch
   *
   * @param      <array>  $a1     new list
   * @param      <array>  $a2     old list
   *
   * @return     0
   */
  function checkEmotes($a1, $a2) {
    global $removed; global $added; global $changed; global $pics;
    foreach ($a1 as $k => $v) { 
      $fn=false; //emote is not in the list
      foreach($a2 as $k2 => $v2){
        if($v->regex==$v2->regex){//if emote is in the list
     	    if($v->url != $v2->url){//if url was changed
            array_push($changed, $v->regex);
            array_push($pics, $v2->url);
            array_push($pics, $v->url);
     	    }
     	    $fn = true;//emote is in the list
        }
      }
      if(!$fn){//if an emote was not in the list
   	    array_push($added,$v->regex);
        array_push($pics, $v->url);
      }
    }
    foreach ($a2 as $k => $v) { //similar for removed emotes
      if($v->regex==$v2->regex){
         $fn = true;
      }
    }
    if(!$fn){
   	  array_push($removed,$v->regex);
      array_push($pics, $v->url);
    }
    return 0;   
  }

  /**
   * compare 2 emote lists from bttv
   *
   * @param      <array>  $a1     new list
   * @param      <array>  $a2     old list
   *
   * @return     0
   */
  function checkBTTVEmotes($a1, $a2) { //same for bttv
    global $removed; global $added; global $changed; global $pics;
    foreach ($a1 as $k => $v) { 
      $fn=false;
      foreach($a2 as $k2 => $v2){
        if($v->code==$v2->code){
     	    if($v->id != $v2->id){
            array_push($changed, $v->code."(BTTV)");
            array_push($pics, 'https://cdn.betterttv.net/emote/'.$v->id.'/1x');
     	    }
     	    $fn = true;
        }
      }
      if(!$fn){
   	    array_push($added,$v->code."(BTTV)");
        array_push($pics, 'https://cdn.betterttv.net/emote/'.$v->id.'/1x');
      }
    }
    foreach ($a2 as $k => $v) { 
      $fn=false;
      foreach($a1 as $k2 => $v2){
        if($v->code==$v2->code){
     	    $fn = true;
        }
      }
      if(!$fn){
   	    array_push($removed,$v->code."(BTTV)");
        array_push($pics, 'https://cdn.betterttv.net/emote/'.$v->id.'/1x');
      }
    }
    return 0;   
  }

  /**
   * generates localized text
   *
   * @param      string  $a      list with new emotes 
   * @param      string  $r      list with removed emotes
   * @param      string  $c      list with changed emotes
   * @param      int     $l      language id
   *
   * @return     string  ( full text )
   */
  function localize($a,$r,$c,$l){
    $t = '';
    $w = [["New","Removed","Changed"],["Добавлены","Удалены","Изменены"]];
    if($r != ""){
       $t .= $w[$l][1].": ".$r.PHP_EOL;
    }
    if($a != ""){
      $t .= $w[$l][0].": ".$a.PHP_EOL;
    }
    if($c != ""){
      $t .= $w[$l][2].": ".$c.PHP_EOL;
    }
    return $t;
  }

  if($hash[0]!=$thash){//if something changed in twitch emote list
  	checkEmotes($ttv,$ttold);
  	file_put_contents("twitch/ttv.json", json_encode($ttv));
  	file_put_contents("twitch/hash.json", json_encode(Array($thash,$btthash)));
  }
    
  if($hash[1]!=$btthash){//if something changed in bttv emote list
  	checkBTTVEmotes($bttv,$bttold);
  	file_put_contents("twitch/bttv.json", json_encode($bttv)); 
  	file_put_contents("twitch/hash.json", json_encode(Array($thash,$btthash)));
  }

  $removed = implode(", ",$removed);
  $added = implode(", ",$added);
  $changed = implode(", ",$changed);
  $txt = localize($added,$removed,$changed,0);
  $rutxt = localize($added,$removed,$changed,1);

  if(count($pics)!=0 && count($pics)<20){ //not an error
    require_once('vk/vk.php');
    require_once('twitter/twitter.php');
    uploadToVK($pics,$rutxt);
    uploadToTwitter($pics,$txt);
    //uploadToGithub(); not implemented 
    
  } else return false;
}

trackEmotes();
?>