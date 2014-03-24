<?php
namespace nxnw;

class tweetEntity {
	private $start;
	private $end;
	private $text;
	private $url;
	
	function __construct($start, $end, $text, $url){
		$this->start = $start;
		$this->end = $end;
		$this->text = $text;
		$this->url = $url;
	} /* \__construct */
	
	function linkified_text(){
		return "<a href=\"".$this->url."\">".$this->text."</a>";
	} /* \linkified_text */
	
	/* getter functions */
	public function get_start(){
		return $this->start;
	} /* \tweet->get_start */
	
	public function get_end(){
		return $this->end;
	} /* \tweet->get_end */
	
	public function get_text(){
		return $this->text;
	} /* \tweet->get_text */
	
	public function get_url(){
		return $this->url;
	} /* \tweet->get_url */
	
} /* \entity */

class tweet {
	private $textContent;
	private $author;
	private $timePosted;
	private $id;
	private $authorImg;
	private $authorProfileLink;
	private $profile_location;
	
	function __construct($text, $author, $time, $id, $profileImg, $profileLink, $profile_location=null){ 
		$this->textContent = $text;
		$this->author = $author;
		//echo 'time: '.$time.'<br>';
		$this->timePosted = $time;
		$this->id = $id;
		$this->authorImg = $profileImg;
		$this->authorProfileLink = $profileLink;
		$this->profile_location = $profile_location;
	}
	
	/*public static function cmp($a, $b){
		$a_start = $a->get_start();
		$b_start = $b->get_start();
		return ($a_start< $b_start) ? -1 : 1;
	}*/
		
	public static function linkify($tweet, $parseList) {
		$regCopy = array();
		$entities = array();
		$text = $tweet->text;
		//echo $text;
		//replacing MS characters, do it this way instead encoding because encoding throws off the index based parsing
		$text = str_replace("‘", "'", $text);
		$text = str_replace("’", "'", $text);
		$text = str_replace("”", '"', $text);
		$text = str_replace("“", '"', $text);
		$text = str_replace("–", "-", $text);
		$text = str_replace("…", "...", $text);
		
		$tweetLen = strlen($text);
		
		if($parseList['user_mentions'] && isset($tweet->entities->user_mentions)){
			foreach($tweet->entities->user_mentions as $user_mention){
				$start = $user_mention->indices[0];
				$end = $user_mention->indices[1];
				//test
				//echo $start.'|'.substr($text, $start, ($end-$start)).'|'.$end.'<br>';
				// \test
				$mention = $user_mention->screen_name;
				$displayMention = '@'.$mention;
				$href = 'https://twitter.com/#!/'.urlencode($mention);
				$entities[] = new tweetEntity($start, $end, $displayMention, $href);
			}
		}
		if($parseList['hashtags'] && isset($tweet->entities->hashtags)){
			foreach($tweet->entities->hashtags as $hashtag){
				$start = $hashtag->indices[0];
				$end = $hashtag->indices[1];
				$displayHash = '#'.$hashtag->text;
				$href = 'https://twitter.com/#!/search/'.urlencode($displayHash);
				$entities[] = new tweetEntity($start, $end, $displayHash, $href);
			}
		}
		if($parseList['urls'] && isset($tweet->entities->urls)){
			foreach($tweet->entities->urls as $url){
				$start = $url->indices[0];
				$end = $url->indices[1];
				$displayUrl = $url->display_url != '' ? $url->display_url : $url->expanded_url;
				$x = preg_replace('/^http(s)?:\/\//', '', $url->url);
				$urlDiff = (strlen($x) > strlen($url->display_url)) ? strlen($x) - strlen($url->display_url) : strlen($url->display_url) - strlen($x);
				$href = $url->expanded_url;
				$entities[] = new tweetEntity($start, $end, $displayUrl, $href);
			}
		}
		if($parseList['media'] && isset($tweet->entities->media)){
			foreach($tweet->entities->media as $media){
				$start = $media->indices[0];
				$end = $media->indices[1];
				$displayUrl = $media->display_url != '' ? $media->display_url : $media->expanded_url;
				$href = $media->expanded_url;
				$entities[] = new tweetEntity($start, $end, $displayUrl, $href);
			}
		}
		
		//sort tweet entities in asc order based on their start index
		usort($entities, function($a, $b){
			$a_start = (int)$a->get_start();
			$b_start = (int)$b->get_start();
			if($a_start == $b_start){
				return 0;
			}
			return ($a_start < $b_start) ? -1 : 1;
		});
		
		
		//usort($entities, array('\nxnwi\tweet', 'cmp'));
		
		//loop through entities array, creating array of the non-entities
		$pointer = 0;
		foreach($entities as $entity){
			if($entity->get_start() == 0){
				$pointer = $entity->get_end();
				continue;
			}
			$regText = substr($text, $pointer, ($entity->get_start()-$pointer));
			$regCopy[] = $regText;
			//increment pointer
			$pointer = $entity->get_end();
		}
		//check for if there is any reg text at end of tweet
		if($pointer != $tweetLen){
			$regCopy[] = substr($text, $pointer, ($tweetLen-$pointer));
		}
		
		//sanity check if there are any entities
		if(empty($entities)){
			return $text;
		}
		
		//create linkified tweet
		$linkifiedTweet = '';
		$i = 0;
		if($entities[$i]->get_start() == 0){ //tweet starts w/ an entity
			$linkifiedTweet = $entities[$i]->linkified_text();
			$i++;
		}
		foreach($regCopy as $copy){
			$linkifiedTweet .= isset($entities[$i]) ? $copy.$entities[$i++]->linkified_text() : $copy;
		}
		//echo $linkifiedTweet.'<br>';
		return $linkifiedTweet;
	}
	
	/* getter functions */
	public function get_text(){
		return $this->textContent;
	} /* \tweet->get_text */
	
	public function get_author(){
		return $this->author;
	} /* \tweet->get_author */
	
	public function get_time($format=null){
		return $format === null ? $this->timePosted : date($format, $this->timePosted);
	} /* \tweet->get_time */
	
	public function get_id(){
		return $this->id;
	} /* \tweet->get_id */
	
	public function get_img(){
		return $this->authorImg;
	} /* \tweet->get_img */
	
	public function get_profile_uri(){
		return $this->authorProfileLink;
	} /* \tweet->get_profile_uri */

	public function get_profile_location(){
		return $this->profile_location;
	} /* \tweet->get_profile_uri */

} /* \tweet */

class twitter {
	/* class properties */
	private $oauth_consumer_key;
	private $oauth_token;
	private $oauth_consumer_secret;
	private $oauth_token_secret;
	private $nonce;
	private $signature_method = 'HMAC-SHA1';
	private $oauth_version = '1.0';
	private $http_method = 'POST';
	private $searchBaseURL = 'https://api.twitter.com/1.1/search/tweets.atom?q=';
	private $userStreamURL = 'http://api.twitter.com/1/statuses/user_timeline.json?screen_name=';
	private $tweets;
	
	/* constructor */
	function __construct() {
  }

  /* private methods */
  private function _gen_nonce() {
  	//generate nonce token
		if(@is_readable('/dev/urandom')) {
			$fp = fopen('/dev/urandom', 'r'); 
			$nonce = md5(fread($fp, 128));
			fclose($fp);
		}
		else{
			$nonce = md5(mt_rand() . mt_rand() . mt_rand() . mt_rand());
		}

		return $nonce;
  }

  private function _gen_oauth_signature($base_url, $params=array(), $nonce, $timestamp) {
  	$signature_components = $params;
  	$signature_base_string = '';

  	$signature_components['oauth_consumer_key'] = $this->oauth_consumer_key;
  	$signature_components['oauth_nonce'] = $nonce;
  	$signature_components['oauth_signature_method'] = $this->signature_method;
  	$signature_components['oauth_timestamp'] = $timestamp;
  	$signature_components['oauth_token'] = $this->oauth_token;
  	$signature_components['oauth_version'] = $this->oauth_version;

  	//sort array asc by key per oauth standards
  	ksort($signature_components);

  	foreach($signature_components as $k => $v) {
  		$signature_base_string .= rawurlencode($k).'='.rawurlencode($v).'&';
  	}
  	//remove last &
  	$signature_base_string = substr($signature_base_string, 0, -1);
  	//echo $signature_base_string;

  	$signature_base_string = $this->http_method.'&'.rawurlencode($base_url).'&'.rawurlencode($signature_base_string);
  	echo $signature_base_string;

  	$signing_key = rawurlencode($this->oauth_consumer_secret).'&'.rawurlencode($this->oauth_token_secret);
  	return base64_encode(hash_hmac('sha1', $signature_base_string, $signing_key));
  }

  public function _get_stream($base_url, $params) {

  	$nonce = $this->_gen_nonce();
  	$timestamp = time();
  	$oauth_signature = $this->_gen_oauth_signature($base_url, $params, $nonce, $timestamp);

  	//create header string
  	$header = 'OAuth ';
  	$header .= rawurlencode('oauth_consumer_key').'='.'"'.rawurlencode($this->oauth_consumer_key).'", ';
  	$header .= rawurlencode('oauth_nonce').'='.'"'.rawurlencode($nonce).'", ';
  	$header .= rawurlencode('oauth_signature').'='.'"'.rawurlencode($oauth_signature).'", ';
  	$header .= rawurlencode('oauth_signature_method').'='.'"'.rawurlencode($this->signature_method).'", ';
  	$header .= rawurlencode('oauth_timestamp').'='.'"'.rawurlencode($timestamp).'", ';
  	$header .= rawurlencode('oauth_token').'='.'"'.rawurlencode($this->oauth_token).'", ';
  	$header .= rawurlencode('oauth_version').'='.'"'.rawurlencode($this->oauth_version);


  	$ch = curl_init($base_url);
  	$headers = array('Authorization: '.$header);
  	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  	curl_setopt($ch, CURLINFO_HEADER_OUT, true);
  	curl_setopt($ch, CURLOPT_POST, 1);
  	curl_setopt($ch, CURLOPT_VERBOSE, true);
  	//curl_setopt($ch,CURLOPT_USERAGENT,'OAuth gem v0.4.4');
  	curl_setopt($ch, CURLOPT_POSTFIELDS, 'track=twitter');
    //curl_setopt ($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $response = curl_exec($ch);
    echo '<fieldset><legend>request headers</legend>
  <pre>', htmlspecialchars(curl_getinfo($ch, CURLINFO_HEADER_OUT)), '</pre>
</fieldset>';

    curl_close ($ch);

    var_dump($response);



    








  	//send POST to twitter
  	/*
  	$token = base64_encode("Anthony:77c9902c159770379dcb56aea72e542c");
$authHeaderString = 'Authorization: Basic ' . $token;
$contentType = 'Content-Type: application/xml';
$lastTime = gmdate("M d Y H:i:s", mktime(0, 0, 0, date("m")  , date("d")-1, date("Y")));
$ifModified = 'If-Modified-Since '.$lastTime;


$orderUrl = 'https://www.evokeapparelcompany.com/api/v2/orders?status_id=11&is_deleted=false';

$oc = curl_init($orderUrl);
$headers = array($authHeaderString);
curl_setopt($oc, CURLOPT_HTTPHEADER, $headers);
curl_setopt($oc, CURLOPT_RETURNTRANSFER, 1); 
$output = curl_exec($oc);
curl_close($oc);
*/


  	

  	
  } /* \twitter->_get_stream */


	
	public function search_hash($hash, $params){
		//construct search URL
		$searchURL = $this->searchBaseURL.urlencode('#'.$hash).$params;
		
		//post to twitter to retrieve the results
		$ch = curl_init($searchURL);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $response = curl_exec ($ch);
    curl_close ($ch);
    
    //parse and store results
    $this->parse_response($response);
    
	} /* \twitter->search_hash */
	
	public function retrieve_user_tweets($userID, $params, $parseList=null){
		//construct search URL
		$searchURL = $this->userStreamURL.urlencode($userID).$params;
		//post to twitter to retrieve the results
		$ch = curl_init($searchURL);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $response = curl_exec ($ch);
    curl_close ($ch);
    $this->parse_response($response, true, $parseList);
	}
	
	public function parse_tweets($tweets, $parseList=null){
		/*echo '<pre>';
		var_dump($tweets);
		echo '</pre>';
		echo gettype($tweets);
		echo '<hr>';*/
		foreach ($tweets as $tweet) {
			//echo gettype($tweet).'<br>';
			//continue;
			/*echo '<pre>';
			var_dump($tweet);
			echo '</pre>';
			die();*/
			//$tweet = $tweet[0];
			if($parseList !== null){ //we have to linkify some entities
				$text = tweet::linkify($tweet, $parseList);
			}
			else{
				$text = trim($tweet->text);
			}
			$handle = $tweet->user->screen_name;
			$authorLink = "https://twitter.com/#!/".$handle;
			$time = strtotime($tweet->created_at);
			$id = $tweet->id_str;
			$profileImg = $tweet->user->profile_image_url;
			//$user_location = $tweet->user->location;
			//echo 'time: '.Date('r', $time).PHP_EOL;
			$this->tweets[] = new tweet($text, $handle, $time, $id, $profileImg, $authorLink);
		}
	} /* \twitter->parse_response */

	private function parse_response($response, $json=false, $parseList=null){
		if($json){
			$tweets = json_decode($response);
			foreach ($tweets as $tweet) {
				if($parseList !== null){ //we have to linkify some entities
					$text = tweet::linkify($tweet, $parseList);
				}
				else{
					$text = trim($tweet->text);
				}
				$handle = $tweet->user->screen_name;
				$authorLink = "https://twitter.com/#!/".$handle;
				$time = strtotime($tweet->created_at);
				$id = $tweet->id_str;
				$profileImg = $tweet->user->profile_image_url;
				//$user_location = $tweet->user->location;
				$this->tweets[] = new tweet($text, $handle, $time, $id, $profileImg, $authorLink);
			}
		}
		else{
			$tweets = new \SimpleXMLElement($response);
			foreach ($tweets->entry as $singleTweet) {
				//die(var_dump($singleTweet));
				$text = trim($singleTweet->title);
				$authorTmp = explode(' ', $singleTweet->author->name);
				$authorLink = $singleTweet->author->uri;
				$handle = $authorTmp[0];
				$time = strtotime($singleTweet->published);
				$id = $singleTweet->id;
				$profileImg = $singleTweet->link[1]['href'];
				$this->tweets[] = new tweet($text, $handle, $time, $id, $profileImg, $authorLink);
			}
		}
	} /* \twitter->parse_response */
	
	public function retrieve_tweets($limit=0, $reverse=true){
		//bail if there are no tweets to return
		if(empty($this->tweets)){
			return false;
		}
		if($limit == 0 || !is_numeric($limit)){ /* return all tweets if they dont pass in a valid limit var */
			return $reverse ? array_reverse($this->tweets) : $this->tweets;
		}
		else{
			$i = 0;
			$end = sizeof($this->tweets);
			$returnTweets = array();
			while($i < $limit && $i < $end){
				$returnTweets[] = $this->tweets[$i++];
			}
			return $reverse ? array_reverse($returnTweets) : $returnTweets;
			//return $returnTweets;
		}
			
	} /* \twitter->retrieve_tweets */
	
} /* \twitter */
	
	
	
?>