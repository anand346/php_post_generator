<?php

// print_r($argv);die();

if(isset($argv[1])){
    if($argv[1] == "-h" || $argv[1] == "--help"){
        echo "for slider image\n";
        echo "syntax : php execute.php insta_url_of_image 2\n";
        echo "for single image\n";
        echo "syntax : php execute.php insta_url_of_image 1\n";
        die();
    }
}else{
    echo "for slider image\n";
    echo "syntax : php execute.php insta_url_of_image 2\n";
    echo "for single image\n";
    echo "syntax : php execute.php insta_url_of_image 1\n";
    die();
}
if(isset($argv[2])){
    if($argv[2] == 1){
        exec("php downloadImage.php ".$argv[1]);
        sleep(3);
        exec("php start1.php");
    }else if($argv[2] == 2){
        exec("php downloadImage.php ".$argv[1]);
        sleep(3);
        exec("python convertPdf.py");
        sleep(3);
        exec("php start.php");
    }
}else{
    die("not having all arguments");
}


?>