<?php

// CLI check
if (php_sapi_name()!='cli') {
    echo "CLI only\n";
    exit (101);
}

// Set-up
$dt  = new \DateTime ();
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


// Arguments
$prog = $argv[0];
array_shift ($argv);
if ((count($argv)%2)==0) {
    echo "Invalid arguments\n";
    exit (102);
}
while (1) {
    if (count($argv)==1) {
        $url = trim ($argv[0]);
        if (!strlen($url)) {
            echo "Invalid arguments\n";
            exit (103);
        }
        break;
    }
    if ($argv[0]!='-f' && !array_key_exists($k=substr($argv[0],1),$options)) {
        echo "Invalid arguments\n";
        exit (104);
    }
    if ($argv[0]=='-f') {
        try {
            $string            = file_get_contents ($argv[1]);
            $object            = json_decode ($string);
        }
        catch (\Exception $e) {
            echo "Could not JSON decode -f filename\n";
            exit (105);
        }
    }
    else {
        $options[$k] = trim ($argv[1]);
    }
    array_shift ($argv);
    array_shift ($argv);
    if (!strlen($options[$k])) {
        echo "Invalid arguments\n";
        exit (106);
    }
}

foreach ($options as $o) {
    if (!strlen($o)) {
        echo "Missing parameters:\n";
        echo $prog;
        foreach ($dfns as $k=>$v) {
            echo " -".$k." [".$v."]";
        }
        echo " [hpapi URL]\n";
        exit (107);
    }
}

$object->datetime                   = $dt->format(\DateTime::ATOM);
if (strlen($options['k'])) {
    $object->key                    = $options['k'];
}
$password                           = '';
if (strlen($options['e'])) {}
    if (strpos($options['e'],':')!==false) {
        $password                   = explode (':',$options['e']);
        $options['k']               = array_shift ($password);
        $password                   = implode (':',$password);
    }
    $object->email                  = $options['e'];
}
if (strlen($password)) {
    $object->password               = $password;
}
if (!property_exists($object,'method')) {
    $object->method                 = new \stdClass ();
}
elseif (!is_object($object->method)) {
    echo "{ method : ... } is not an object in -f filename\n";
    exit (108);
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
        exit (108);
    }
}
if (!is_array($object->method->arguments)) {
    echo "object->method->arguments not an array\n";
    exit (109);
}

if (!strlen($object->password)) {
    echo "Password: ";
    $object->password = exec ('./hpapi-read-s.bash');
}

echo json_encode ($object,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK|JSON_PRETTY_PRINT);

?>
