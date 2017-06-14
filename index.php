<?php
/**
 * Created by PhpStorm.
 * User: szymondomanski
 * Date: 12.06.2017
 * Time: 15:16
 */
ini_set('memory_limit', '512M');
header('Cache-Control: no-cache'); 
include ('class/class.sdrsa.php');
$bot = New sdrsa();
$bot->setUrl('https://www.credit-suisse.com/ch/en.html');
$bot->setDomain('https://www.credit-suisse.com','yes');
$bot->run('https://www.credit-suisse.com/ch/en.html');
file_put_contents('result.txt',print_r($bot->result, true));
