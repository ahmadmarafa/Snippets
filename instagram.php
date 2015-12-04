<?php
// modification for http://lancenewman.me/posting-a-photo-to-instagram-without-a-phone/
// using https://github.com/mgp25/Instagram-API


function SendRequest($url, $post, $post_data, $user_agent, $cookies , $extraHeader = "" )
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://i.instagram.com/api/v1/'.$url);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, false);
    if($extraHeader != "")
    {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $extraHeader) ;
    }
    if($post)
    {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }
    if($cookies)
    {
        curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');            
    }
    else
    {
        curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
    }
    $response = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header_len = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header     = substr($response, 0, $header_len);
    $body       = substr($response, $header_len) ;
    curl_close($ch);
    return array($http, $body , $header);
}
function GenerateGuid()
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x', 
        mt_rand(0, 65535), 
        mt_rand(0, 65535), 
        mt_rand(0, 65535), 
        mt_rand(16384, 20479), 
        mt_rand(32768, 49151), 
        mt_rand(0, 65535), 
        mt_rand(0, 65535), 
        mt_rand(0, 65535)
    );
}
function GenerateSignature($data)
{
    return hash_hmac('sha256', $data, 'c1c7d84501d2f0df05c378f5efb9120909ecfb39dff5494aa361ec0deadb509a');
}

// Set the username and password of the account that you wish to post a photo to
$username = "" ;
$password = "" ;

$photo = "" ;
if(!isset($photo) || !is_file($photo))
{
    die("no photo found !");
}
else
{
    $imageInfo = getimagesize($photo);
    $width = $imageInfo["width"] ;
    $height = $imageInfo["height"] ; 
}
// Set the caption for the photo
$caption = "";
// Define the user agent
$agent = 'Instagram 7.10.0 Android (23/6.0; 515dpi; 1440x2416; huawei/google; Nexus 6P; angler; angler; en_US)';
// Define the GuID
$guid = GenerateGuid();
// Set the devide ID
$device_id = "android-".$guid;
/* LOG IN */
// You must be logged in to the account that you wish to post a photo too
// Set all of the parameters in the string, and then sign it with their API key using SHA-256
$data = '{"device_id":"'.$device_id.'","guid":"'.$guid.'","username":"'.$username.'","login_attempt_count":"1","password":"'.$password.'","Content-Type":"application/x-www-form-urlencoded; charset=UTF-8"}';
$sig = GenerateSignature($data);
$data = 'signed_body='.$sig.'.'.urlencode($data).'&ig_sig_key_version=4';
$login = SendRequest('accounts/login/', true, $data, $agent, false);
preg_match('#Set-Cookie: csrftoken=([^;]+)#', $login[2], $token);
preg_match('#Set-Cookie: ds_user_id=([^;]+)#', $login[2], $id);
$userid = $id[1];
function buildBody($bodies, $boundary)
{
    $body = "";
    foreach($bodies as $b)
    {
        $body .= "--".$boundary."\r\n";
        $body .= "Content-Disposition: ".$b["type"]."; name=\"".$b["name"]."\"";
        if(isset($b["filename"]))
        {
            $ext = pathinfo($b["filename"], PATHINFO_EXTENSION);
            $body .= "; filename=\"".substr(bin2hex($b["filename"]),0,48).".".$ext."\"";
        }
        if(isset($b["headers"]) && is_array($b["headers"]))
        {
            foreach($b["headers"] as $header)
            {
                $body.= "\r\n".$header;
            }
        }
        
        $body.= "\r\n\r\n".$b["data"]."\r\n";
    }
    $body .= "--".$boundary."--";
    return $body;
}

if(strpos($login[1], "Sorry, an error occurred while processing this request."))
{
    echo "Request failed, there's a chance that this proxy/ip is blocked";
}
else
{            
    if(empty($login[1]))
    {
        echo "Empty response received from the server while trying to login";
    }
    else
    {            
        // Decode the array that is returned
        $obj = @json_decode($login[1], true);
        if(empty($obj))
        {
            echo "Could not decode the response: ".$body;
        }
        else
        {
          
            // Post the picture
           $boundary = $guid ; 
           $bodies = [
                [
                    'type' => 'form-data',
                    'name' => 'upload_id',
                    'data' => round(microtime(true)*1000)
                ],
                [
                    'type' => 'form-data',
                    'name' => '_uuid',
                    'data' => $guid
                ],
                [
                    'type' => 'form-data',
                    'name' => '_csrftoken',
                    'data' => $token[1]
                ]/*,
                [
                    "type"=>"form-data",
                    "name"=>"image_compression"
                //	"data"=>"Your JSON DATA COMPRESSION HERE"
                ]*/,
                [
                    'type' => 'form-data',
                    'name' => 'photo',
                    'data' => file_get_contents($photo),
                    'filename' => basename($photo),
                    'headers' =>
                    [
                        'Content-type: application/octet-stream'
                    ]
                ]
            ];

            $data = buildBody($bodies,$boundary);
            $post = SendRequest('upload/photo/', true, $data, $agent, true ,  [
                'Proxy-Connection: keep-alive',
                'Connection: keep-alive',
                'Accept: */*',
                'Content-type: multipart/form-data; boundary='.$boundary,
                'Accept-Language: en-en',
                'Accept-Encoding: gzip, deflate',
            ]);
            preg_match('#Set-Cookie: csrftoken=([^;]+)#', $post[2], $token);
            if(empty($post[1]))
            {
                echo "Empty response received from the server while trying to post the image";
            }
            else
            {
                // Decode the response
                
                $obj = @json_decode($post[1], true);
                if(empty($obj))
                {
                    echo "Could not decode the response";
                }
                else
                {
                    $status = $obj['status'];
                    if($status == 'ok')
                    {
                        // Remove and line breaks from the caption
                        $caption = preg_replace("/\r|\n/", "", $caption);
                        $media_id = $obj['upload_id'];
                        // Now, configure the photo
                        $data = array(
                            'caption'     => $caption,
                            'upload_id'   => $media_id,
                            'source_type' => 3,
                            'edits'       =>
                             array(
                                'crop_zoom'          => 1.0000000,
                                'crop_center'        => array(0.0, -0.0),
                                'crop_original_size' => array($width, $height),
                                'black_pixels_ratio' => 0
                             ),
                             'device'      =>
                             array(
                                'manufacturer'    => 'asus',
                                'model'           => 'Nexus 7',
                                'android_version' => 22,
                                'android_release' => '5.1'
                             ),
                             '_csrftoken'  => $token[1],
                             '_uuid'       => $guid,
                             '_uid'        => $userid
                        );
                   
                        $sig = GenerateSignature(json_encode($data));
                        $new_data = 'signed_body='.$sig.'.'.urlencode(json_encode($data)).'&ig_sig_key_version=4';
                        $conf = SendRequest('media/configure/', true, $new_data, $agent, true);
                        if(empty($conf[1]))
                        {
                            echo "Empty response received from the server while trying to configure the image";
                        }
                        else
                        {
                            if(strpos($conf[1], "login_required"))
                            {
                                echo "You are not logged in. There's a chance that the account is banned";
                            }
                            else
                            {
                                $obj = @json_decode($conf[1], true);
                                $status = $obj['status'];
                                if($status != 'fail')
                                {
                                    echo "Success";
                                }
                                else
                                {
                                    echo 'Fail';
                                }
                            }
                        }
                    }
                    else
                    {
                        echo "Status isn't okay";
                    }
                }
            }
        }
    }
}
    
