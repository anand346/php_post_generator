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

function getJsonData($url){

    $data = getHtmlFromUrl($url);
    $pattern = "/window._sharedData = (.*);/";
    preg_match($pattern,$data,$matches);
    $data = json_decode($matches[1],true);
    return $data;

}

function getPhoto($jsonData){
    $files = glob('./downloads/*'); // get all file names
    foreach($files as $file){ // iterate files
        if(is_file($file)) {
            unlink($file); // delete file
        }
    }
    $contentUrl = array();
    $entryData = $jsonData["entry_data"];
    if(array_key_exists("PostPage",$entryData)){
        if($jsonData["entry_data"]["PostPage"][0]["graphql"]["shortcode_media"]["__typename"] == "GraphImage"){
            // $imageUrl = $jsonData["entry_data"]["PostPage"][0]["graphql"]["shortcode_media"]["display_url"];
            $i = count($jsonData["entry_data"]["PostPage"][0]["graphql"]["shortcode_media"]["display_resources"]) - 1;
            $imageUrl = $jsonData["entry_data"]["PostPage"][0]["graphql"]["shortcode_media"]["display_resources"][$i]["src"];
            preg_match("/(http[s]*:\/\/)([a-z\-_0-9\/.]+)\.([a-z.]{2,3})\/([a-z0-9\-_\/._~:?#\[\]@!$&'()*+,;=%]*)([a-z0-9]+\.)(jpg|jpeg|webp)/i",$imageUrl,$matches);
            $image_url = $matches[0];
            $image_url = explode("/",$image_url);
            $image_name = end($image_url);
            $image_media = getHtmlFromUrl($imageUrl);
            $fileHandle =  fopen("./downloads/"."img.jpg","wb");
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
                
                $fileHandle =  fopen("./downloads/"."img".$i.".jpg","wb");
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

if($argv){
    if(sizeof($argv) > 2){
        die('extra arguments passed');
    }else if(sizeof($argv) < 2){
        die("syntax : php downloadImage.php url_of_image");
    }
}

//check for argument 1
$url = "";
if($argv[1]){
    if($argv[1] == "--help" || $argv[1] == "-h"){
        die("syntax : php downloadImage.php url_of_image");
    }else{
        $url = $argv[1];
    }
}
$data = getJsonData($url);
getPhoto($data);


?>