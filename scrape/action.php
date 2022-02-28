<?php
session_start();
include "dl.php";
function deleteFile($data){
    $image = explode("/",$data['medias'][0]['display_url']);
    sleep(5);
    unlink($image[1]);
}
$error = array();
if(!empty($_POST['url']) && !empty($_POST['action']) && !empty($_POST['csrf_token']) && filter_var($_POST['url'], FILTER_VALIDATE_URL)){
    if($_POST['csrf_token'] != $_SESSION['csrf_token']){
        $error[0]['mismatchCsrf'] = "Error : CSRF token does not match.";
        die(json_encode($error));
    }
    $domain = str_ireplace('www.', '', parse_url($_POST['url'], PHP_URL_HOST));
    if (!empty(explode('.', str_ireplace('www.', '', parse_url($_POST['url'], PHP_URL_HOST)))[1])) {
        $mainDomain = explode('.', str_ireplace('www.', '', parse_url($_POST['url'], PHP_URL_HOST)))[1];
    } else {
        $mainDomain = null;
    }
    if ($domain != 'instagram.com') {
        $error[0]['hostError'] = 'URL host must be instagram.com';
        die(json_encode($error));
    }
    $data = array();
    $data['user'] = array();
    $data['medias'] = array();
    switch ($_POST['action']) {
        case 'photo':
            $jsonData = getJsonData($_POST['url']);
            $data['medias'] = getPhoto($jsonData);
            // die(json_encode($data['medias']));
            saveTagsFile($jsonData,$data['medias']);
            // $tags = saveTagsFile($jsonData,$data['medias']);
            // die(json_encode($mediaUrls));
        break;            
        case 'profilePic':
            $username = extractUsername($_POST['url']);
            $url = "https://www.instagram.com/".$username."/";
            $jsonData = getJsonData($url);
            $data['medias'] = getProfilePic($jsonData);
        break;
        case 'video':
        case 'reel':
        case 'igtv':
            $jsonData = getJsonData($_POST['url']);
            $data['medias'] = getVideo($jsonData);
            saveTagsFile($jsonData,$data['medias']);
        break;
        case 'tags':
            $jsonData = getJsonData($_POST['url']);
            $data['medias'] = extractTags($jsonData);
        break;
        default:
            $error[0]['invalidAction'] = 'Invalid Action.';
            die(json_encode($error));
        break;
    }
    //  echo json_encode($jsonData);die();
        echo json_encode($data['medias']);
}
?>