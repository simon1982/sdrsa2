<?php
/* Self Discovering Recursive Sitemap Algorithm
* @author : szymondomanski	
*/

class sdrsa {
	
	public $url = '';
    public $domain ='';
    public $domain_stick ='';
    public $result;
    public $status;
    public $outputFormat;
    public $outputHolder;


    /**
     * @param $url
     * This switch between first and N run
     */
    public function run($url){
        if(empty($this->status)){
            $this->setUrl($url);
            $this->getMainLevel($this->url);
        }else{
            $this->getNLevel($this->result);
        }
    }


    /**
     * @param $url
     * @param $stick
     * Stick to domain this mean it will only visit URL in the domain that is the home domain.
     * Stick has value yes/no
     */
    public function setDomain($url, $stick){
        $this->domain = parse_url($url, PHP_URL_HOST);
        $this->domain_stick = $stick;
    }

    /**
     * @param $url
     * @coment
     */
    public function setUrl($url){
		$this->url = $url; 
	}

    /**
     * @return string
     */
    public function getUrl(){
		return $this->url;
	}


    /**
     * @param $url
     * @return bool
     * If we have HTTPS then it return Https if not we have Http
     */
    public function getProtocol($url){
        if( parse_url($url, PHP_URL_SCHEME) == 'https'){
            return 'Https';
        } else {
            return 'Http';
        }
	}

    /**
     * @param $url
     * @return mixed
     * In order to get an SSL page we have to use CURL Library
     */
    public function getWebSiteContentHttps($url){
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_USERAGENT      => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_1) AppleWebKit/534.30 (KHTML, like Gecko) Chrome/12.0.742.112 Safari/534.30",// who am i
            CURLOPT_AUTOREFERER    => true,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_VERBOSE        => 1
        );
        $ch      = curl_init($url);
        curl_setopt_array($ch,$options);
        $content = curl_exec($ch);
        curl_close($ch);
        return $content;
    }

    /**
     * @param $url
     * @return string
     * If we have an normal not secure page then we just grab the page
     */
    public function getWebSiteContentHttp($url){
        return file_get_contents($url);
    }

    /**
     * @param $content
     * @return mixed
     * The regular expression will extract all the links from a tags. This data is RAW this need to be filter.
     */
    public function getExtractUrl($content){
        preg_match_all("/<a\s+href=[\"']([^\"']+)[\"']/i", $content, $output_array);
        return $output_array[1];
    }

    /**
     * @param $string
     * @return bool
     */
    public function getFilter($string) {
        return $this->strpos_arr($string, array('#','mvc.do','{','http', 'https','mailto'),1) === false;
    }

    private function strpos_arr($haystack, $needle) {
        if(!is_array($needle)) $needle = array($needle);
        foreach($needle as $what) {
            if(!empty($what)){
                if(($pos = strpos($haystack, $what))!==false) return $pos;
            }
        }
        return false;
    }
    /**
     * @param $array
     * @return array
     * Remove from the links all data that point to dynamic like content or mvc.do etc this filter can be adjust.
     * Filter hold all the things that you dont want to search.
     * Result will be only URI or URL that can be visited
     */
    public function getArrayDataFilterd($array){
        return array_unique(array_filter($array, [$this, 'getFilter']));
    }

    /**
     * @param $array
     * This will add the unique result to the main result
     */
    public function setResult($array, $url = NULL){
        if(empty($this->result)){
            $this->result = $array;
        } else {
            array_push($this->result, $array);
        }
    }

    /**
     * @param $status
     * This will inform bot if we are ant the main level or we have n level
     */
    public function setStatus($status){
        $this->status = $status;
    }

    /**
     * @param $url
     */
    public function getMainLevel($url){
        $protocol = $this->getProtocol($url);
        $method = 'getWebSiteContent'.$protocol;
        $content = $this->$method($url);
        $extracted_url = $this->getArrayDataFilterd($this->getExtractUrl($content));
        $this->setResult($extracted_url,NULL);
        $this->status = 'afterMain';
        $this->run(NULL);
    }

    /**
     * @param $url
     * @return bool
     * Check if the url is URI or URL
     */
    private function getProperDomainUrl($url){
        if($this->strpos_arr($url, array('http','https'))){
            if (parse_url($url, PHP_URL_HOST) != parse_url($this->url, PHP_URL_HOST)){
                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    /**
     * @param $url
     * This will cobain the protocol with domain and uri
     */
    private function getProperUrl($url){
        if(!$this->strpos_arr($url, array('http','https'))){
            return parse_url($this->url, PHP_URL_SCHEME).'://'.parse_url($this->url, PHP_URL_HOST).$url;
        }
    }


    /**
     *
     */
    public function getNLevel(){
        ob_start();
        foreach ($this->result as $key => $value ){
            if($this->getProperDomainUrl($value)){
                $protocol = $this->getProtocol($this->getProperUrl($value));
                $method = 'getWebSiteContent'.$protocol;
                $content = $this->$method($this->getProperUrl($value));
                $extracted_url = $this->getArrayDataFilterd($this->getExtractUrl($content));
                $this->result[$value] = $extracted_url;
                ob_flush();
                flush();
            }
        }
        $this->run(NULL);
    }

    /**
     * @param $format
     * This set one of 3 formats for the output file csv, txt, xml
     */
    public function setSiteMapFormat($format){
        $this->outputFormat = $format;
    }

    /**
     *
     */
    public function getOutput(){
        array_walk_recursive($this->result, [$this, 'getOutPut'.$this->outputFormat]);
    }

    public function getOutPutCsv($item, $key){
        $this->outputHolder .='"'.$key.'","'.$item.'"'."\n";
    }

    public function getOutPutTxt($item, $key){
        $this->outputHolder .= $item."\n";
    }

    public function getOutPutXml($item, $key){
        $this->outputHolder .= $item."\n";
    }
}



