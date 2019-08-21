<?php
$CLIENTID =''; //twitch client ID
class DB{
    public $BUCKET = ''; //GCP GCS Bucket
    public function readFile($fn,$GCP=true){
        return $GCP ? file_get_contents("gs://$this->BUCKET/$fn") : file_get_contents("./$fn");
    }
    public function writeFile($fn,$data,$GCP=true){
        return $GCP ? file_put_contents("gs://$this->BUCKET/$fn",$data) : file_put_contents("./$fn",$data);
    }
}
$DB = new DB(); //DB wrapper

/**
 * Checks changes in Twitch and BTTV emotes
 *
 * @return     array (string with change descriptions, string with change descriptions in russian, array of emote image urls)
 */
function trackEmotes(){
  global $removed; global $added; global $changed; global $pics; global $CLIENTID; global $DB;
  $opts = [
    "http" => [
        "method" => "GET",
        "header" => "Accept: application/vnd.twitchtv.v5+json"
    ]
  ];
  $context = stream_context_create($opts);
  $ttv=file_get_contents("https://api.twitch.tv/kraken/chat/emoticon_images?emotesets=0&client_id=$CLIENTID",false,$context);
  $bttv=file_get_contents('https://api.betterttv.net/2/emotes');
  $ttv=json_decode($ttv)->emoticon_sets->{"0"};
  $bttv=json_decode($bttv)->emotes;
  $thash=md5(serialize($ttv));
  $btthash=md5(serialize($bttv));
  $hash=json_decode($DB->readFile('twitch/hash.json'));

  $pics = $removed = $added = $changed = [];

function generateEmoteURL($id){
    return "https://static-cdn.jtvnw.net/emoticons/v1/$id/1.0";
}
function generateBTTVEmoteURL($id){
    return "https://cdn.betterttv.net/emote/$id/1x";
}

  /**
   * compare 2 emote lists from twitch
   *
   * @param      <array>  $a1     new list
   * @param      <array>  $a2     old list
   *
   * @return     0
   */
  function checkEmotes($a1, $a2) {
    if(!$a1||!$a2){syslog(LOG_CRIT,"Twitch emote array is empty!") and die("something is wrong");}
    global $removed; global $added; global $changed; global $pics;
    foreach ($a1 as $k => $v) {
      $fn=false; //emote is not in the list
      foreach($a2 as $k2 => $v2){
        if($v->code==$v2->code){//if emote is in the list
     	    if($v->id != $v2->id){//if id was changed
            array_push($changed, $v->code);
            array_push($pics, generateEmoteURL($v2->id));
            array_push($pics, generateEmoteURL($v->id));
     	    }
     	    $fn = true;//emote is in the list
        }
      }
      if(!$fn){//if an emote was not in the list
        array_push($added,$v->code);
        array_push($pics, generateEmoteURL($v->id));
      }
    }
    foreach ($a2 as $k => $v) { //similar for removed emotes
      $fn=false;
      foreach($a1 as $k2 => $v2){
        if($v->code==$v2->code){
     	    $fn = true;
        }
      }
      if(!$fn){
        array_push($removed,$v->code);
        array_push($pics, generateEmoteURL($v->id));
      }
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
    if(!$a1||!$a2){syslog(LOG_CRIT,"BTTV emote array is empty!") and die("something is wrong");}
    global $removed; global $added; global $changed; global $pics;
    foreach ($a1 as $k => $v) {
      $fn=false;
      foreach($a2 as $k2 => $v2){
        if($v->code==$v2->code){
     	    if($v->id != $v2->id){
            array_push($changed, $v->code."(BTTV)");
            array_push($pics, generateBTTVEmoteURL($v->id));
     	    }
     	    $fn = true;
        }
      }
      if(!$fn){
        array_push($added,$v->code."(BTTV)");
        array_push($pics, generateBTTVEmoteURL($v->id));
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
        array_push($pics, generateBTTVEmoteURL($v->id));
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
    $ttold=json_decode($DB->readFile('twitch/ttv.json'));
  	checkEmotes($ttv,$ttold);
  }

  if($hash[1]!=$btthash){//if something changed in bttv emote list
    $bttold=json_decode($DB->readFile('twitch/bttv.json'));
  	checkBTTVEmotes($bttv,$bttold);
  }

  if(count($pics)!=0 && count($pics)<20){ //not an error
    $removed = implode(", ",$removed);
    $added = implode(", ",$added);
    $changed = implode(", ",$changed);
    $txt = localize($added,$removed,$changed,0);
    $rutxt = localize($added,$removed,$changed,1);
    syslog(LOG_DEBUG,$txt.json_encode($pics));
    $DB->writeFile("twitch/ttv.json", json_encode($ttv));
    $DB->writeFile("twitch/bttv.json", json_encode($bttv));
    $DB->writeFile("twitch/hash.json", json_encode(array($thash,$btthash)));
    return array($txt,$rutxt,$pics);

    } else{
        if(count($pics)>20){
            print(var_export($pics,true));
            syslog(LOG_CRIT,"TOO MANY EMOTES!");
        }
        return false;
    }
}
//$result = trackEmotes();
?>
