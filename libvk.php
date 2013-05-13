<?php
class VKPublic
{
    protected $groupId, $appId, $secretKey, $accessToken; 
 
    /**
     * @param int $groupId
     * @param int $appId
     * @param string $secretKey
     */
    public function __construct($groupId, $appId, $secretKey)
    {
        $this->groupId = $groupId;
        $this->appId = $appId;
        $this->secretKey = $secretKey;
    } 
 
    /**
     * @param string $accessToken
     */
    public function setAccessData($accessToken)
    {
        $this->accessToken = $accessToken;
    } 
 
    /**
     * Hack
     */
    public function getAccessData()
    {
        echo "<!doctype html><html><head><meta charset='windows-1251'></head>
            <body><a href='https://oauth.vk.com/authorize?" .
            "client_id={$this->appId}&scope=offline,wall,groups,pages," .
            "photos,docs,audio,video,notes,stats,messages,notify,notifications,nohttps&amp;" .
            "redirect_uri=http://api.vk.com/blank.html&amp;display=page&amp;response_type=token'
                target='_blank'>Получить CODE</a><br>Ссылка для получения токена:<br>
                <b>https://oauth.vk.com/access_token?client_id={$this->appId}" .
            "&amp;client_secret={$this->secretKey}&amp;code=CODE</b></body></html>"; 
 
        exit;
    } 
 
    /**
     * @param string $method
     * @param mixed $parameters
     * @return mixed
     */
    public function callMethod($method, $parameters)
    {
        if (!$this->accessToken) return false;
        if (is_array($parameters)) $parameters = http_build_query($parameters);
        $queryString = "/method/$method?$parameters&access_token={$this->accessToken}";
        return json_decode(file_get_contents(
		"https://api.vk.com{$queryString}"
        ));
    } 
    
        /**
     * @param string $method
     * @param mixed $parameters
     * @return mixed
     */
    public function callMethodGet($method, $parameters)
    {
        if (!$this->accessToken) return false;
        if (is_array($parameters)) $parameters = http_build_query($parameters);
        $queryString = "/method/$method?$parameters&access_token={$this->accessToken}";
        return json_decode(file_get_contents(
		"https://api.vk.com{$queryString}"
        ), true);
    } 
 
    /**
     * @param mixed $ColMessage
     * @return mixed
     */
    public function wallGetMsg($ColMessage)
    {
        return $this->callMethodGet('wall.get', array(
            'owner_id' => -1 * $this->groupId,  // 
            'count' => $ColMessage,
        ));
    } 
    
        /**
     * @param string $message
     * @param bool $fromGroup
     * @param bool $signed
     * @return mixed
     */
    public function wallPostMsg($message, $fromGroup = true, $signed = false)
    {
        return $this->callMethod('wall.post', array(
            'owner_id' => -1 * $this->groupId, //  
            'message' => $message,
            'from_group' => $fromGroup ? 1 : 0,
            'signed' => $signed ? 1 : 0,
        ));
    } 
 
    /**
     * @param string $attachment
     * @param null|string $message
     * @param bool $fromGroup
     * @param bool $signed
     * @return mixed
     */
    public function wallPostAttachment($attachment, $message = null, $fromGroup = true, $signed = false)
    {
        return $this->callMethod('wall.post', array(
            'owner_id' => -1 * $this->groupId,  // 
            'attachment' => strval($attachment),
            'message' => $message,
            'from_group' => $fromGroup ? 1 : 0,
            'signed' => $signed ? 1 : 0,
        ));
    } 
	
	 /**
     * @param mixed $photoId
     * @param String $caption
     * @return mixed
     */
    public function wallPhotosEdit($photoId, $caption)
    {
        return $this->callMethod('photos.edit', array(
            'owner_id' => -1 * $this->groupId, //  
            'pid' => $photoId,
            'caption' => $caption,
        ));
    } 
 
    /**
     * @param string $file1 relative file path
	 * @param mixed $yesno number of photos (if photo > 1) else 0
     * @return mixed
     */
    public function createPhotoAttachment($file1, $yesno)
    {
        $result = $this->callMethod('photos.getWallUploadServer', array(
            'gid' => $this->groupId,
        )); 
		
		// Загружаем файл в локаль
		$th = curl_init();
		$file_rez = fopen('image0.jpg', 'w');
		curl_setopt($th, CURLOPT_FILE, $file_rez);
		curl_setopt($th, CURLOPT_URL, $file1);
		curl_exec($th);
		curl_close($th);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $result->response->upload_url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if($yesno > 0){
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
		'file'.$yesno => '@'.dirname(__FILE__).'/image0.jpg'
        )); 
		} else {
	    curl_setopt($ch, CURLOPT_POSTFIELDS, array(
		'file1' => '@'.dirname(__FILE__).'/image0.jpg'
        )); 	
		}
 
        if (($upload = curl_exec($ch)) === false) {
            throw new Exception(curl_error($ch));
        } 
 
        curl_close($ch);
        $upload = json_decode($upload);	
        $result = $this->callMethod('photos.saveWallPhoto', array(
            'server' => $upload->server,
            'photo' => $upload->photo,
            'hash' => $upload->hash,
            'gid' => $this->groupId,
        )); 
 
        return $result->response[0]->id;
    } 
 
    public function combineAttachments()
    {
        $result = '';
        if (func_num_args() == 0) return '';
        foreach (func_get_args() as $arg) {
            $result .= strval($arg) . ',';
        }
        return substr($result, 0, strlen($result) - 1);
    }
}