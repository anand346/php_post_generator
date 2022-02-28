<?php
    function getHtmlFromUrl($url){
        $agents = array(
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:7.0.1) Gecko/20100101 Firefox/7.0.1',
            'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.1.9) Gecko/20100508 SeaMonkey/2.0.4',
            'Mozilla/5.0 (Windows; U; MSIE 7.0; Windows NT 6.0; en-US)',
            'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_7; da-dk) AppleWebKit/533.21.1 (KHTML, like Gecko) Version/5.0.5 Safari/533.21.1'
         
        );
        $header = array();
        $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
        $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
        $header[] = "Cache-Control: max-age=0";
        $header[] = "Connection: keep-alive";
        $header[] = "Keep-Alive: 300";
        $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
        $header[] = "Accept-Language: en-us,en;q=0.5";
        $header[] = "Pragma: ";
    //assign to the curl request.
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_AUTOREFERER,true);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_USERAGENT,$agents[array_rand($agents)]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $html = curl_exec($ch);
        curl_close($ch);
        return $html;
    }
    
    function extractUsername($profileUrl){
        $url = $profileUrl;
        $profileArray = explode("/",$url);
        if(strpos($profileArray[3],"?")){
            $usernameArray = explode("?",$profileArray[3]);
            $username = $usernameArray[0];
        }else{
            $username = $profileArray[3];
        }
        return $username;
    }

    function getJsonData($url){

        $data = getHtmlFromUrl($url);
        $pattern = "/window._sharedData = (.*);/";
        preg_match($pattern,$data,$matches);
        $data = json_decode($matches[1],true);
        return $data;
    
    }

    function forceDownload($remoteUrl, $fileName){

        $context_options = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        header('Pragma: public');
        if (isset($_SERVER['HTTP_REQUEST_USER_AGENT']) && strpos($_SERVER['HTTP_REQUEST_USER_AGENT'], 'MSIE') !== FALSE) {
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
        }
        header('Connection: Close');
        ob_clean();
        flush();
        readfile($remoteUrl, "", stream_context_create($context_options));
        exit;
    
    }

    function saveTagsFile($jsonData,$mediaUrls){
        $tagsArr = array();
        $entryData = $jsonData["entry_data"];
        if(array_key_exists("PostPage",$entryData)){
            $tagsEdgeString = $jsonData["entry_data"]["PostPage"][0]["graphql"]["shortcode_media"]["edge_media_to_caption"]["edges"];
            if(array_key_exists("0",$tagsEdgeString)){
                $tagsString = $jsonData["entry_data"]["PostPage"][0]["graphql"]["shortcode_media"]["edge_media_to_caption"]["edges"]["0"]["node"]["text"];
                preg_match_all("/#(.*)/",$tagsString,$tagMatches);
                // return $tagMatches;
                $allTagsArr = array();
                if(empty($tagMatches[0])){
                    $tagsArr[0]['noTags'] = "This post doesn't have any tags.";
                    return $tagsArr;
                }
                for($i = 0; $i < sizeof($tagMatches[0]); $i++){
                    $allTagsArr[] = explode(" ",$tagMatches[0][$i]);                    
                }
                $tagsArr[0]['tags'] = $allTagsArr;
                if(is_array($allTagsArr)){
                    foreach($allTagsArr as $tagsArr){
                        if(is_array($tagsArr)){
                            foreach($tagsArr as $tagsInd){
                                $allTagsTogether[] = $tagsInd;
                            }
                        }else{
                            $allTagsTogether[] = $tagsArr;
                        }
                    }
                }else{
                    $allTagsTogether[] = $allTagsArr;
                }
                $allTagsArrString = implode(" ",$allTagsTogether);
                $inp = file_get_contents('../jsonData/links.json');
                $tempArray = json_decode($inp);
                for($i = 0; $i < sizeof($mediaUrls); $i++){
                    $data = [
                        "tags" => $allTagsArrString,
                        "view_url" => $mediaUrls[$i]['display_url'],
                        "download_url" => $mediaUrls[$i]['url'].'&dl=1'
                    ];
                    array_push($tempArray, $data);
                }
                $jsonEncoded = json_encode($tempArray);
                file_put_contents('../jsonData/links.json', $jsonEncoded);
            }else{
                $tagsArr[0]['noTags'] = "This post doesn't have any tags.";
            }
        }else{
            $tagsArr[0]['noTags'] = "Instagram blocks your requests";
        }
    }
    function extractTags($jsonData){

        $tagsArr = array();
        $entryData = $jsonData["entry_data"];
        if(array_key_exists("PostPage",$entryData)){
            $tagsEdgeString = $jsonData["entry_data"]["PostPage"][0]["graphql"]["shortcode_media"]["edge_media_to_caption"]["edges"];
            if(array_key_exists("0",$tagsEdgeString)){
                $tagsString = $jsonData["entry_data"]["PostPage"][0]["graphql"]["shortcode_media"]["edge_media_to_caption"]["edges"]["0"]["node"]["text"];
                preg_match_all("/#(.*)/",$tagsString,$tagMatches);
                // return $tagMatches;
                $allTagsArr = array();
                if(empty($tagMatches[0])){
                    $tagsArr[0]['noTags'] = "This post doesn't have any tags.";
                    return $tagsArr;
                }
                for($i = 0; $i < sizeof($tagMatches[0]); $i++){
                    $allTagsArr[] = explode(" ",$tagMatches[0][$i]);                    
                }
                $tagsArr[0]['tags'] = $allTagsArr;
            }else{
                $tagsArr[0]['noTags'] = "This post doesn't have any tags.";
            }
        }else{
            $tagsArr[0]['noTags'] = "Instagram blocks your requests";
        }
        return $tagsArr;

    }

    function getPhoto($jsonData){
        $contentUrl = array();
        $entryData = $jsonData["entry_data"];
        if(array_key_exists("PostPage",$entryData)){
            if($jsonData["entry_data"]["PostPage"][0]["graphql"]["shortcode_media"]["__typename"] == "GraphImage"){
                // $imageUrl = $jsonData["entry_data"]["PostPage"][0]["graphql"]["shortcode_media"]["display_url"];
                $i = count($jsonData["entry_data"]["PostPage"][0]["graphql"]["shortcode_media"]["display_resources"]) - 1;
                $imageUrl = $jsonData["entry_data"]["PostPage"][0]["graphql"]["shortcode_media"]["display_resources"][$i]["src"];
                preg_match("/(http[s]*:\/\/)([a-z\-_0-9\/.]+)\.([a-z.]{2,3})\/([a-z0-9\-_\/._~:?#\[\]@!$&'()*+,;=%]*)([a-z0-9]+\.)(jpg|jpeg)/i",$imageUrl,$matches);
                $image_url = $matches[0];
                $image_url = explode("/",$image_url);
                $image_name = end($image_url);
                $image_media = getHtmlFromUrl($imageUrl);
                $fileHandle =  fopen($image_name,"wb");
                fwrite($fileHandle,$image_media);
                fclose($fileHandle);
                $contentUrl[0]['url'] = $imageUrl;
                $contentUrl[0]['display_url'] = "backend/".$image_name;
            }else if ($jsonData["entry_data"]["PostPage"][0]["graphql"]["shortcode_media"]["__typename"] == "GraphSidecar") {
                $children = $jsonData["entry_data"]["PostPage"][0]["graphql"]["shortcode_media"]["edge_sidecar_to_children"]["edges"];
                for($i = 0; $i < sizeof($children); $i++){
                    $contentUrl[$i]['url'] = $jsonData["entry_data"]["PostPage"][0]["graphql"]["shortcode_media"]["edge_sidecar_to_children"]["edges"][$i]["node"]["display_url"];
                    preg_match("/(http[s]*:\/\/)([a-z\-_0-9\/.]+)\.([a-z.]{2,3})\/([a-z0-9\-_\/._~:?#\[\]@!$&'()*+,;=%]*)([a-z0-9]+\.)(jpg|jpeg)/i",$contentUrl[$i]['url'],$matches);
                    $image_url = $matches[0];
                    $image_url = explode("/",$image_url);
                    $image_name = end($image_url);
                    $image_media = getHtmlFromUrl($contentUrl[$i]['url']);
                    $fileHandle =  fopen($image_name,"wb");
                    fwrite($fileHandle,$image_media);
                    fclose($fileHandle);
                    $contentUrl[$i]['display_url'] = "backend/".$image_name;
                }
            }else{
                $contentUrl[0]['invalidPostUrl'] = "Instagram blocks your requests.";
            }    
        }else{
            $contentUrl[0]['invalidPostUrl'] = "Instagram blocks your requests.";            
        }
        
        return $contentUrl;   
    }

    function getVideo($jsonData){
        $contentUrl = array();
        $entryData = $jsonData["entry_data"];
        if(array_key_exists("PostPage",$entryData)){
            if($jsonData["entry_data"]["PostPage"][0]["graphql"]["shortcode_media"]["__typename"] == "GraphVideo"){
                $videoUrl = $jsonData["entry_data"]["PostPage"][0]["graphql"]["shortcode_media"]["video_url"];
                $displayUrl = $jsonData["entry_data"]["PostPage"][0]["graphql"]["shortcode_media"]["display_url"];
                preg_match("/(http[s]*:\/\/)([a-z\-_0-9\/.]+)\.([a-z.]{2,3})\/([a-z0-9\-_\/._~:?#\[\]@!$&'()*+,;=%]*)([a-z0-9]+\.)(jpg|jpeg)/i",$displayUrl,$matches);
                $image_url = $matches[0];
                $image_url = explode("/",$image_url);
                $image_name = end($image_url);
                $image_media = getHtmlFromUrl($displayUrl);
                $fileHandle =  fopen($image_name,"wb");
                fwrite($fileHandle,$image_media);
                fclose($fileHandle);
                $contentUrl[0]['url'] = $videoUrl;
                $contentUrl[0]['display_url'] = "backend/".$image_name;        
            }else{
                $contentUrl[0]['invalidPostUrl'] = "Instagram blocks your requests.";
            }
        }else{
            $contentUrl[0]['invalidPostUrl'] = "Instagram blocks your requests.";
        }
        return $contentUrl;
    
    }

    function getIgtv($jsonData){

    }


    function getProfilePic($jsonData){

        // return $jsonData;
        $contentUrl = array();
        $entryData = $jsonData["entry_data"];
        if(array_key_exists("ProfilePage",$entryData)){
            $data = $jsonData["entry_data"]["ProfilePage"][0]["graphql"]["user"];
            $imageUrl = $data['profile_pic_url_hd'];
            preg_match("/(http[s]*:\/\/)([a-z\-_0-9\/.]+)\.([a-z.]{2,3})\/([a-z0-9\-_\/._~:?#\[\]@!$&'()*+,;=%]*)([a-z0-9]+\.)(jpg|jpeg)/i",$imageUrl,$matches);
            $image_url = $matches[0];
            $image_url = explode("/",$image_url);
            $image_name = end($image_url);
            $image_media = getHtmlFromUrl($imageUrl);
            $fileHandle =  fopen($image_name,"wb");
            fwrite($fileHandle,$image_media);
            fclose($fileHandle);
            $contentUrl[0]['url'] = $imageUrl;
            $contentUrl[0]['display_url'] = "backend/".$image_name;
        }else{
            $contentUrl[0]['invalidProfileUrl'] = "Instagram blocks your request.";            
        }
        
        return $contentUrl;
    }

    function getReel($jsonData){

    }
?>