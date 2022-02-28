<?php

// print_r($argv);die();

if($argv[2]){
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