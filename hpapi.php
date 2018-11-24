<?php

/* Copyright 2018 Whitelamp http://www.whitelamp.com/ */

// CLI check
if (php_sapi_name()!='cli') {
    echo "CLI only\n";
    exit (101);
}

// Set-up
$dt             = new \DateTime ();
$curl           = exec ('command -v curl');
if (!$curl) {
    echo "curl is not availabe\n";
    exit (102);
}
$object         = new \stdClass ();
$request        = null;
$url            = '';
$props          = array ('key','email','method');
$mprops         = array ('vendor','package','class','method','arguments');


// Arguments
$prog           = $argv[0];
if ((count($argv))!==3) {
    echo $prog.' [JSON file] [hpapi URL]'."\n";
    exit (103);
}
$file           = $argv[1];
$url            = $argv[2];

// Load JSON
if (!is_readable($file)) {
    echo $prog.' [JSON file] [hpapi URL]'."\n";
    echo "Cannot read file '".$file."'\n";
    exit (104);
}
$string         = file_get_contents ($file);
$object         = json_decode ($string);
if (($err=json_last_error())!=JSON_ERROR_NONE) {
    echo $prog.' [JSON file] [hpapi URL]'."\n";
    echo "Could not decode JSON in '".$file."'\n";
    exit (105);
}
foreach ($props as $p) {
    if (!property_exists($object,$p)) {
        echo "JSON property '".$p."'' was not given\n";
        exit (106);
    }
}
if (!is_object($object->method)) {
    echo "JSON property 'method' is not an object\n";
    exit (107);
}
foreach ($mprops as $p) {
    if (!property_exists($object->method,$p)) {
        echo "JSON property method->".$p." was not given\n";
        exit (108);
    }
}
if (!is_array($object->method->arguments)) {
    echo "JSON property method->arguments is not an array\n";
    exit (109);
}

// Add interactive password to object
if (property_exists($object,'password') && strlen($object->password)==0) {
    echo "Password: ";
    $object->password = exec (dirname($prog).'/.hpapi/hpapi-read-s.bash');
    echo "\n";
}

// Add datetime to object
$object->datetime  = $dt->format(\DateTime::ATOM);

// Create JSON string
$request           = json_encode ($object,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
if (($err=json_last_error())!=JSON_ERROR_NONE) {
    echo "Failed to encode request JSON\n";
    exit (110);
}

// Define response file and write input file
$out               = realpath (dirname($prog)).'/'.getmypid().'.json';
$in                = $out.'.request';
$fp                = fopen ($in,'w');
fwrite ($fp,$request);
fclose ($fp);

// Execute curl
$insecure          = '';
if (strpos($url,'https://')===0) {
    $insecure      = '--insecure';
}
exec ('curl -s --header "Content-Type: application/json; charset=utf8" --data '.escapeshellarg('@'.$in).' '.$insecure.' '.escapeshellarg($url).' > '.escapeshellarg($out),$o,$x);
if ($x>0) {
    echo "Curl command failed\n";
    exit (111);
}

// Give response and delete temporary files
echo file_get_contents ($out);
unlink ($in);
unlink ($out);

