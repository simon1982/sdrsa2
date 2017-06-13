<?php
/**
 * Created by PhpStorm.
 * User: szymondomanski
 * Date: 12.06.2017
 * Time: 15:16
 */
ini_set('memory_limit', '512M');
// Turn off output buffering
ini_set('output_buffering', 'off');
// Turn off PHP output compression
ini_set('zlib.output_compression', false);

//Flush (send) the output buffer and turn off output buffering
//ob_end_flush();
while (@ob_end_flush());

// Implicitly flush the buffer(s)
ini_set('implicit_flush', true);
ob_implicit_flush(true);

//prevent apache from buffering it for deflate/gzip
header("Content-type: text/plain");
header('Cache-Control: no-cache'); // recommended to prevent caching of event data.
include ('class/class.sdrsa.php');
ob_flush();
flush();
$bot = New sdrsa();
$bot->setUrl('https://www.credit-suisse.com/ch/en.html');
$bot->setDomain('https://www.credit-suisse.com','yes');
$bot->run('https://www.credit-suisse.com/ch/en.html');
file_put_contents('result.txt',print_r($bot->result, true));
ob_flush();
flush();