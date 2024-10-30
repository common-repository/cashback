<?php

/**
 * @author    Hotshopper <info@hotshopper.nl>
 * @license   GPL-2.0+
 * @date 2016.
 * @link      https://www.hotshopper.nl
 */
class CashBackContentScanner {

	/**
	 * ContentScanner constructor.
	 *
	 * @param $wpdb
	 */
	public function __construct( $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * @param $data
	 *
	 * @return WP_Post
	 * @internal param $content
	 *
	 * @internal param $id
	 */
	static public function ScanContent( $data ) {
		global $wpdb;
		$postId     = get_the_ID();
		$scanLinks  = get_option( 'c247_replace_existing_links' );
		$isDisabled = get_post_meta( $postId, 'c247_disabled', true );
		if ( $isDisabled != true ) {
			$contentScanner = new CashBackContentScanner( $wpdb );
			$data           = $contentScanner->scan( $data, $scanLinks );
		}

		return $data;
	}

    /**
     * @param $string
     *
     * @param $scanLinks
     * @return mixed
     */
	private function scan( $string, $scanLinks ) {
		$postId        = get_the_ID();
		$aWords = get_post_meta( $postId, 'c247_keywords', true );
        $createNewLinks = get_option( 'c247_create_new_links');
		if(empty($aWords)){
			return $string;
		}
		$target = "target='_blank' ";

		if(get_option( 'c247_new_window' ) != true){
			$target = "";
		}

		if ( $scanLinks == true ) {
			libxml_use_internal_errors(true);
			$dom           = new DOMDocument();
			$dom->loadHTML( $string );
			$x = new DOMXPath($dom);
			$search = $x->query("//a");

			foreach ($search as $item) {
				$result = $this->checkLink($item->getAttribute("href"),$aWords);

				if (!empty($result)) {
					if (!isset($result['disabled'])) {
						$string = str_replace($item->getAttribute("href"),$result['url'],$string);
					}
				}
				set_time_limit(30);
			}
			$string = str_replace("<a ","<a {$target} ",$string);
		}

		if(!empty($aWords) && $createNewLinks == true){
			foreach($aWords AS $word){
				if(!isset($word['disabled']) && !isset($word['type'])){
					$string = preg_replace('/(?!(?:[^<*]+>|[^>]+<\/h5|h1|h4|a|h2|h3|h6|img>))\b(' . $word['keyword'] . ')(?=\s|\<|,)/ius', "<a {$target} class='c247-clickout-link' data-id='".$word['rid']."' data-type='retailer' href='" . $word['url'] . "'>$0</a>", $string ,1);
				}
				set_time_limit(30);
			}
		}



		return $string;
	}

	public function checkLink( $link ,&$aWords = array()) {
		$parseurl = parse_url( $link );
		$host     = str_replace( 'www.', '', $parseurl['host'] );
        $host = preg_split('/(?=\.[^.]+$)/', $host);
        $host = $host[0];

		foreach($aWords AS $key => $word){
			if (strtolower($word['clean']) == $host){
				return $word;
			}
		}
		return false;
	}

	/**
	 * Recursive array_search function
	 *
	 * @param $needle
	 * @param $haystack
	 * @param string $currentKey
	 * @param string $requiredField
	 *
	 * @return bool|string
	 */
	public function recursive_array_search( $needle, $haystack, $currentKey = '', $requiredField = null ) {
		foreach ( $haystack as $key => $value ) {
			if ( is_array( $value ) ) {
				$nextKey = $this->recursive_array_search( $needle, $value, $currentKey . $key, $requiredField );
				if ( $nextKey ) {
					return $nextKey;
				}
			} else if ( $value == $needle ) {
				if ( ! empty( $requiredField ) ) {
					if ( $key == $requiredField ) {
						return (string) is_numeric( $key ) ? '[' . $currentKey . $key . ']' : '[' . $currentKey . ']';
					}
				} else {
					return (string) is_numeric( $key ) ? '[' . $currentKey . $key . ']' : '[' . $currentKey . ']';
				}

			}
		}

		return false;
	}

	private function scanLinks($string){
		$urlList = array();
		$aWords = array();
		libxml_use_internal_errors(true);
		$dom    = new DOMDocument();
		$dom->loadHTML( $string );
		$x        = new DOMXPath( $dom );
		$search = $x->query( '//a' );
		foreach ( $search as $item ) {
			$parseurl = parse_url( str_replace(array("\\",'"'),"",$item->getAttribute( "href" )) );
			$host     = str_replace( 'www.', '', $parseurl['host'] );
            $host = preg_split('/(?=\.[^.]+$)/', $host);
            $host = $host[0];
			$text = $item->nodeValue;
			$query  = $this->wpdb->prepare( "SELECT id, rid, keyword,url, LOWER(keyword) AS clean FROM " . $this->wpdb->prefix . "c247_keywords WHERE LOWER( keyword ) = '%s' OR LOWER( keyword ) = '%s' ;", array( $host,$text ) );
			$result = $this->wpdb->get_row( $query, 'ARRAY_A' );
			if ( ! empty( $result )  ) {
				$result['old_url'] = str_replace(array("\\",'"'),"",$item->getAttribute( "href" ));
				$result['id'] = $result['id'].'-'.$this->createSeoUrl($item->getAttribute( "href" ));
				$result['keyword'] = $item->nodeValue;
				$result['type'] = 'link';
				$aWords[]          = $result;
			}
		}
		return $aWords;
	}



	public function scanWords( $string ,$replaceExistingLinks = false, $disabled = array()) {
		$urlList = array();
		$aWords = array();
		$query  = $this->wpdb->get_results( "SELECT id,rid,keyword,url, LOWER(keyword) AS clean FROM " . $this->wpdb->prefix . "c247_keywords;",ARRAY_A);
		foreach($query AS $result){
			set_time_limit(30);

			if(preg_match('/\b'.$result['keyword'].'\b/is',$string) && ! in_array( $result['url'], $urlList )){

				if ( ! empty( $result ) && ! in_array( $result['rid'], $urlList ) ) {

					$aWords[]  = $result;
					$urlList[] = $result['rid'];

				}
			}
		}

		if($replaceExistingLinks == true){
			$aWords = array_merge($this->scanLinks($string),$aWords);
		}

		foreach($aWords AS &$word){
			if(in_array($word['id'],$disabled)){
				$word['disabled'] = true;
			}
		}

		return $aWords;
	}
	
	private function createSeoUrl($string){
		if(empty($string)){
			return false;
		}
		$replacearray = array('--', '&quot;', '&amp;', '!', '\t', '@', '#', '$', '%', '&euro;', '€', '^', '&', '*', '(', ')', '_', '+', '{', '}', '|', ':', '"', '<', '>', '?', '[', ']', '\\', ';', "'", ',', '/', '*', '+', '~', '`', '=',"’");
		$transliterationTable = array('á' => 'a', 'Á' => 'A', 'à' => 'a', 'À' => 'A', 'ă' => 'a', 'Ă' => 'A', 'â' => 'a', 'Â' => 'A', 'å' => 'a', 'Å' => 'A', 'ã' => 'a', 'Ã' => 'A', 'ą' => 'a', 'Ą' => 'A', 'ā' => 'a', 'Ā' => 'A', 'ä' => 'ae', 'Ä' => 'AE', 'æ' => 'ae', 'Æ' => 'AE', 'ḃ' => 'b', 'Ḃ' => 'B', 'ć' => 'c', 'Ć' => 'C', 'ĉ' => 'c', 'Ĉ' => 'C', 'č' => 'c', 'Č' => 'C', 'ċ' => 'c', 'Ċ' => 'C', 'ç' => 'c', 'Ç' => 'C', 'ď' => 'd', 'Ď' => 'D', 'ḋ' => 'd', 'Ḋ' => 'D', 'đ' => 'd', 'Đ' => 'D', 'ð' => 'dh', 'Ð' => 'Dh', 'é' => 'e', 'É' => 'E', 'è' => 'e', 'È' => 'E', 'ĕ' => 'e', 'Ĕ' => 'E', 'ê' => 'e', 'Ê' => 'E', 'ě' => 'e', 'Ě' => 'E', 'ë' => 'e', 'Ë' => 'E', 'ė' => 'e', 'Ė' => 'E', 'ę' => 'e', 'Ę' => 'E', 'ē' => 'e', 'Ē' => 'E', 'ḟ' => 'f', 'Ḟ' => 'F', 'ƒ' => 'f', 'Ƒ' => 'F', 'ğ' => 'g', 'Ğ' => 'G', 'ĝ' => 'g', 'Ĝ' => 'G', 'ġ' => 'g', 'Ġ' => 'G', 'ģ' => 'g', 'Ģ' => 'G', 'ĥ' => 'h', 'Ĥ' => 'H', 'ħ' => 'h', 'Ħ' => 'H', 'í' => 'i', 'Í' => 'I', 'ì' => 'i', 'Ì' => 'I', 'î' => 'i', 'Î' => 'I', 'ï' => 'i', 'Ï' => 'I', 'ĩ' => 'i', 'Ĩ' => 'I', 'į' => 'i', 'Į' => 'I', 'ī' => 'i', 'Ī' => 'I', 'ĵ' => 'j', 'Ĵ' => 'J', 'ķ' => 'k', 'Ķ' => 'K', 'ĺ' => 'l', 'Ĺ' => 'L', 'ľ' => 'l', 'Ľ' => 'L', 'ļ' => 'l', 'Ļ' => 'L', 'ł' => 'l', 'Ł' => 'L', 'ṁ' => 'm', 'Ṁ' => 'M', 'ń' => 'n', 'Ń' => 'N', 'ň' => 'n', 'Ň' => 'N', 'ñ' => 'n', 'Ñ' => 'N', 'ņ' => 'n', 'Ņ' => 'N', 'ó' => 'o', 'Ó' => 'O', 'ò' => 'o', 'Ò' => 'O', 'ô' => 'o', 'Ô' => 'O', 'ő' => 'o', 'Ő' => 'O', 'õ' => 'o', 'Õ' => 'O', 'ø' => 'oe', 'Ø' => 'OE', 'ō' => 'o', 'Ō' => 'O', 'ơ' => 'o', 'Ơ' => 'O', 'ö' => 'o', 'Ö' => 'O', 'ṗ' => 'p', 'Ṗ' => 'P', 'ŕ' => 'r', 'Ŕ' => 'R', 'ř' => 'r', 'Ř' => 'R', 'ŗ' => 'r', 'Ŗ' => 'R', 'ś' => 's', 'Ś' => 'S', 'ŝ' => 's', 'Ŝ' => 'S', 'š' => 's', 'Š' => 'S', 'ṡ' => 's', 'Ṡ' => 'S', 'ş' => 's', 'Ş' => 'S', 'ș' => 's', 'Ș' => 'S', 'ß' => 'SS', 'ť' => 't', 'Ť' => 'T', 'ṫ' => 't', 'Ṫ' => 'T', 'ţ' => 't', 'Ţ' => 'T', 'ț' => 't', 'Ț' => 'T', 'ŧ' => 't', 'Ŧ' => 'T', 'ú' => 'u', 'Ú' => 'U', 'ù' => 'u', 'Ù' => 'U', 'ŭ' => 'u', 'Ŭ' => 'U', 'û' => 'u', 'Û' => 'U', 'ů' => 'u', 'Ů' => 'U', 'ű' => 'u', 'Ű' => 'U', 'ũ' => 'u', 'Ũ' => 'U', 'ų' => 'u', 'Ų' => 'U', 'ū' => 'u', 'Ū' => 'U', 'ư' => 'u', 'Ư' => 'U', 'ü' => 'ue', 'Ü' => 'UE', 'ẃ' => 'w', 'Ẃ' => 'W', 'ẁ' => 'w', 'Ẁ' => 'W', 'ŵ' => 'w', 'Ŵ' => 'W', 'ẅ' => 'w', 'Ẅ' => 'W', 'ý' => 'y', 'Ý' => 'Y', 'ỳ' => 'y', 'Ỳ' => 'Y', 'ŷ' => 'y', 'Ŷ' => 'Y', 'ÿ' => 'y', 'Ÿ' => 'Y', 'ź' => 'z', 'Ź' => 'Z', 'ž' => 'z', 'Ž' => 'Z', 'ż' => 'z', 'Ż' => 'Z', 'þ' => 'th', 'Þ' => 'Th', 'µ' => 'u', 'а' => 'a', 'А' => 'a', 'б' => 'b', 'Б' => 'b', 'в' => 'v', 'В' => 'v', 'г' => 'g', 'Г' => 'g', 'д' => 'd', 'Д' => 'd', 'е' => 'e', 'Е' => 'e', 'ё' => 'e', 'Ё' => 'e', 'ж' => 'zh', 'Ж' => 'zh', 'з' => 'z', 'З' => 'z', 'и' => 'i', 'И' => 'i', 'й' => 'j', 'Й' => 'j', 'к' => 'k', 'К' => 'k', 'л' => 'l', 'Л' => 'l', 'м' => 'm', 'М' => 'm', 'н' => 'n', 'Н' => 'n', 'о' => 'o', 'О' => 'o', 'п' => 'p', 'П' => 'p', 'р' => 'r', 'Р' => 'r', 'с' => 's', 'С' => 's', 'т' => 't', 'Т' => 't', 'у' => 'u', 'У' => 'u', 'ф' => 'f', 'Ф' => 'f', 'х' => 'h', 'Х' => 'h', 'ц' => 'c', 'Ц' => 'c', 'ч' => 'ch', 'Ч' => 'ch', 'ш' => 'sh', 'Ш' => 'sh', 'щ' => 'sch', 'Щ' => 'sch', 'ъ' => '', 'Ъ' => '', 'ы' => 'y', 'Ы' => 'y', 'ь' => '', 'Ь' => '', 'э' => 'e', 'Э' => 'e', 'ю' => 'ju', 'Ю' => 'ju', 'я' => 'ja', 'Я' => 'ja');
		$newstring = str_replace('  ', ' ', $string);
		$newstring = str_replace($replacearray, '', $newstring);
		$newstring = strtolower($newstring);
		$newstring = str_replace(' ', '-', $newstring);
		$newstring = str_replace('.', '-', $newstring);
		$newstring = str_replace('--', '-', $newstring);
		$newstring = str_replace(array_keys($transliterationTable), array_values($transliterationTable), $newstring);
		$newstring = trim($newstring, '-');
		$newstring = str_replace('--', '-', $newstring);
		return $newstring;
	}
}