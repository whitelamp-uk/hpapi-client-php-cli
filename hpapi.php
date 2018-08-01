<?php

// CLI check
if (php_sapi_name()!='cli') {
    echo "CLI only\n";
    exit (101);
}

// Set-up
$dt  = new \DateTime ();
$curl = exec ('command -v curl');
if (!$curl) {
    echo "curl is not availabe\n";
    exit (102);
}
$object = new \stdClass ();
$request = null;
$url = '';
$options = array (
    'k'  => ''
   ,'e'  => ''
   ,'v'  => ''
   ,'p'  => ''
   ,'c'  => ''
   ,'m'  => ''
   ,'a'  => ''
);
$dfns = array (
    'k'  => 'key'
   ,'e'  => 'email'
   ,'v'  => 'vendor'
   ,'p'  => 'package'
   ,'c'  => 'class'
   ,'m'  => 'method'
   ,'a'  => 'JSON ["arg1","arg2",...] args'
);
$props   = array ('key','email');
$mprops  = array ('vendor','package','class','method');
function usage ($prog,$dfns) {
    echo "Missing parameter:\n";
    echo $prog;
    foreach ($dfns as $k=>$v) {
        echo " -".$k." [".$v."]";
    }
    echo " [hpapi URL]\n";
}



// Arguments
$prog = $argv[0];
array_shift ($argv);
if ((count($argv)%2)==0) {
    echo "Invalid arguments\n";
    exit (103);
}
while (1) {
    if (count($argv)==1) {
        $url = trim ($argv[0]);
        if (!strlen($url)) {
            echo "Invalid arguments\n";
            usage ($prog,$dfns);
            exit (104);
        }
        break;
    }
    if ($argv[0]!='-f' && !array_key_exists($k=substr($argv[0],1),$options)) {
            usage ($prog,$dfns);
        echo "Invalid arguments\n";
        exit (105);
    }
    if ($argv[0]=='-f') {
        try {
            $string            = file_get_contents ($argv[1]);
            $object            = json_decode ($string);
        }
        catch (\Exception $e) {
            echo "Could not JSON decode -f filename\n";
            exit (106);
        }
    }
    else {
        $options[$k] = trim ($argv[1]);
    }
    array_shift ($argv);
    array_shift ($argv);
}

$object->datetime                   = $dt->format(\DateTime::ATOM);
if (strlen($options['k'])) {
    $object->key                    = $options['k'];
}
if (strlen($options['e'])) {
    $password                       = '';
    if (strpos($options['e'],':')!==false) {
        $password                   = explode (':',$options['e']);
        $options['k']               = array_shift ($password);
        $password                   = implode (':',$password);
    }
    $object->email                  = $options['e'];
    $object->password               = $password;
}
if (strlen($password)) {
    $object->password               = $password;
}
if (!property_exists($object,'method')) {
    $object->method                 = new \stdClass ();
}
elseif (!is_object($object->method)) {
    echo "{ method : ... } is not an object in -f filename\n";
    exit (107);
}
if (strlen($options['v'])) {
    $object->method->vendor         = $options['v'];
}
if (strlen($options['p'])) {
    $object->method->package        = $options['p'];
}
if (strlen($options['c'])) {
    $object->method->class          = $options['c'];
}
if (strlen($options['m'])) {
    $object->method->method         = $options['m'];
}
if (strlen($options['a'])) {
    try {
        $object->method->arguments  = json_decode ($options['a']);
    }
    catch (\Exception $e) {
        echo "Could not JSON decode -a arguments\n";
        usage ($prog,$dfns);
        exit (108);
    }
}
foreach ($props as $p) {
    if (!property_exists($object,$p) || !strlen($object->$p)) {
        echo "Parameter: object->".$p." not given\n";
        usage ($prog,$dfns);
        exit (109);
    }
}
foreach ($mprops as $p) {
    if (!property_exists($object->method,$p) || !strlen($object->method->$p)) {
        echo "Parameter: object->method->".$p." not given\n";
        usage ($prog,$dfns);
        exit (110);
    }
}
if (!property_exists($object->method,'arguments') || !is_array($object->method->arguments)) {
    echo "Parameter: object->method->arguments not an array\n";
    usage ($prog,$dfns);
    exit (111);
}

if (!strlen($object->password)) {
    echo "Password: ";
    $object->password = exec ('./hpapi-read-s.bash');
    echo "\n";
}


$request    = json_encode ($object,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK|JSON_PRETTY_PRINT);
$out        = realpath(dirname($prog)).'/'.getmypid().'.json';
$in         = $out.'.request';
$fp         = fopen ($in,'w');
fwrite ($fp,$request);
fclose ($fp);
exec ('curl --header "Content-Type: application/json; charset=utf8" --data '.escapeshellarg('@'.$in).' --insecure '.escapeshellarg($url).' > '.escapeshellarg($out),$o,$x);
if ($x>0) {
    echo "Curl command failed\n";
    exit (112);
}
echo file_get_contents ($out);
unlink ($in);
unlink ($out);

?>
