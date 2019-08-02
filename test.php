<?php

//forced encoding set here
//params array_key value
//brotli,gzip,deflate
define('forceEncoding',"");






ob_start("sanitize_output");
    echo "hello world";
ob_end_flush();




function sanitize_output($buffer) {


    $search = array(
        '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
        '/[^\S ]+\</s',     // strip whitespaces before tags, except space
        '/(\s)+/s',         // shorten multiple whitespace sequences
        '/<!--(.|\s)*?-->/' // Remove HTML comments
    );

    $replace = array(
        '>',
        '<',
        '\\1',
        ''
    );

    if(!config['DEBUG'])
    $buffer = preg_replace($search, $replace, $buffer);

    //compressMethods list priorty is important here
    //if client supporting brotli i want send brotli_compress to client
    //etc
    $compressMethods = array(
        'brotli' => ["search" => "br","method" => "brotli_compress","level" => 11],
        'deflate' => ["search" => "deflate","method" => "gzdeflate","level" => 9],
        'gzip' => ["search" => "gzip","method" => "gzencode", "level" => 9]
    );

    $accepts = explode(",",$_SERVER['HTTP_ACCEPT_ENCODING']);
    $accepts = htmlspecialcharsDeep($accepts);

    $islem = "";
    foreach ($compressMethods as $key => $method){
        if(array_search($method['search'],$accepts)){
            $islem = $key;
            break;
        }
        continue;
    }


    $islem = forceEncoding != "" ? forceEncoding : $islem;

    if($islem !== ""){
        $content = $compressMethods[$islem]["method"](trim( preg_replace( '/\s+/', ' ', $buffer ) ),$compressMethods[$islem]["level"]);
        header('Content-Encoding: '.$compressMethods[$islem]["search"]);
    }else{
        $content = $buffer;
    }


    $offset = 60 * 60;
    $expire = "expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT";

    header("content-type: text/html; charset: UTF-8");
    header("cache-control: must-revalidate");
    header( $expire );
    header( 'Content-Length: ' . strlen( $content ) );
    header('Vary: Accept-Encoding');

    return $content;
}
