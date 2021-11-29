<?php
function main(){
    require_once 'login.php';
    $conn = new mysqli($hn, $un, $pw, $db);
    if($conn->connect_error) die(errorHandler("Access to Database failed."));
    session_start();
    if (!isset($_SESSION['initiated']))
    {
        session_regenerate_id();
        $_SESSION['initiated'] = 1;
    }
    if(isset($_SESSION['username']))
    {
        $username = $_SESSION['username'];
        
        ini_set('session.gc_maxlifetime', 60 * 60 * 24);
        
        $_SESSION['check'] = hash('ripemd128', $_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
        if ($_SESSION['check'] != hash('ripemd128', $_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT'])) error_Handler('diff user');
        
        
        
        
        echo "Welcome back $username" . '<br>';
        echo "Choose a cipher: " . '<br>';
        echo <<<_END
    <html><head><title>Decryptoid</title></head><body>
    <form method='post' action='' enctype='multipart/form-data'>
    <input type = "radio" name = "cipher" value = "sub"> Substitution <br>
    <input type = "radio" name = "cipher" value = "trans"> Double Transposition <br>
    <input type = "radio" name = "cipher" value = "rc4"> RC4 <br>
    Select a File:
    <input type='file' name='filename' size='10'>
    <br>
    *Required for DOUBLE TRANSPOSITION and RC4 <br>
    Enter key1 <br>
    <input type = 'text' name = 'key1'>
    <br>
    *Required for DOUBLE TRANSPOSITION <br>
    Enter key2 <br>
    <input type = 'text' name = 'key2'>
    <br>
    <input type = "radio" name = "crypt" value = "encrypt"> Encrypt <br>
    <input type = "radio" name = "crypt" value = "decrypt"> Decrypt <br>
    <input type='submit' value='Submit'>
    
    </form>
_END;
        if($_FILES){
            if($_FILES['filename']['type'] == 'text/plain'){
                $file_string = file_get_contents($_FILES['filename']['tmp_name']);
                // removes spaces and new line characters
                $file_string = str_replace(' ', '', $file_string);
                $newLine = array(
                    "\n",
                    "\r\n",
                    "\r"
                );
                $file_string = str_replace($newLine, '', $file_string);
                
                $text = mysql_entities_fix_string($conn, $file_string);
                echo "Before: " . $file_string . '<br>';
                
                //encrypt substitution
                if(isset($_POST['cipher']) && $_POST['cipher'] == 'sub' && isset($_POST['crypt']) && $_POST['crypt'] == 'encrypt'){
                    $cipher = encrypt_substitution($text);
                    echo "After: " . $cipher;
                    insert($conn, $username, "Substitution", $cipher, $text);
                }
                
                //decrypt substitution
                if(isset($_POST['cipher']) && $_POST['cipher'] == 'sub' && isset($_POST['crypt']) && $_POST['crypt'] == 'decrypt'){
                    $cipher = decrypt_substitution($text);
                    echo "After: " . $cipher;
                    insert($conn, $username, "Substitution", $cipher, $text);
                }
                
                //rc4 encrypt
                if(isset($_POST['cipher']) && $_POST['cipher'] == 'rc4' && isset($_POST['crypt']) && isset($_POST['key1']) && $_POST['crypt'] == 'encrypt'){
                    $key = mysql_entities_fix_string($conn, $_POST['key1']);
                    $cipher = encrypt_rc4($key, $text);
                    echo "After: " . $cipher;
                    insert($conn, $username, "RC4", $cipher, $text);
                }
                //rc4 decrypt
                if(isset($_POST['cipher']) && $_POST['cipher'] == 'rc4' && isset($_POST['crypt']) && isset($_POST['key1']) && $_POST['crypt'] == 'decrypt'){
                    $key = mysql_entities_fix_string($conn, $_POST['key1']);
                    $cipher = decrypt_rc4($key, $text);
                    echo "After: " . $cipher;
                    insert($conn, $username, "RC4", $cipher, $text);
                }
                
                //encrypt double transposition
                if(isset($_POST['cipher']) && $_POST['cipher'] == 'trans' && isset($_POST['crypt'])
                    && $_POST['crypt'] == 'encrypt' && isset($_POST['key1']) && isset($_POST['key2'])){
                        $key1 = mysql_entities_fix_string($conn, $_POST['key1']);
                        $key2 = mysql_entities_fix_string($conn, $_POST['key1']);
                        $cipher = encrypt_double_transposition($text, $key1, $key2);
                        echo "After: " . $cipher;
                        insert($conn, $username, "Double Transposition", $cipher, $text);
                }
                
                //decrypt double transposition
                if(isset($_POST['cipher']) && $_POST['cipher'] == 'trans' && isset($_POST['crypt'])
                    && $_POST['crypt'] == 'decrypt' && isset($_POST['key1']) && isset($_POST['key2'])){
                        $key1 = mysql_entities_fix_string($conn, $_POST['key1']);
                        $key2 = mysql_entities_fix_string($conn, $_POST['key1']);
                        $cipher = decrypt_double_transposition($text, $key1, $key2);
                        echo "After: " . $cipher;
                        insert($conn, $username, "Double Transposition", $cipher, $text);
                }
            }
        }
        
    }
    $conn->close();
    
}
function encrypt_substitution($string) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $cipher =   'HPGQUIEMYANLFOXDKJCRSVZTBW';
    $uppercase = strtoupper($string);
    $encrypted = strtr($uppercase, $alphabet, $cipher);
    return $encrypted;
}
function decrypt_substitution($string) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $cipher =   'HPGQUIEMYANLFOXDKJCRSVZTBW';
    $uppercase = strtoupper($string);
    $decrypted = strtr($uppercase, $cipher, $alphabet);
    return $decrypted;
}
function encrypt_double_transposition($string, $key, $key2){
    $text = preg_replace('/\s+/', '', $string); //removes all whitespaces
    $text = str_split($text);
    $firstTranspose = array();
    $k = 0;     //pointer for string
    
    //fills 2D array with string message
    for($i = 0; $i < count($text); $i++){
        for($j = 0; $j < strlen($key); $j++){
            $firstTranspose[$i][$j] = $text[$k];
            $k++;
        }
    }
    
    $colIndex = str_split($key);
    $temp = $colIndex;
    sort($temp);
    for ($i = 0; $i < count($colIndex); $i++) {
        for ($j = 0; $j < count($colIndex); $j++) {
            if ($colIndex[$i] == $temp[$j]) {
                $colIndex[$i] = $j;
                $temp[$j] = "";
                break;
            }
        }
    }
    $tMsg = '';
    
    $k = 0; // column index we need to look at
    for($i = 0; $i < count($colIndex); $i++){       //checks through all elements in colIndex
        for($j = 0; $j < count($colIndex); $j++){   //looks for k
            if($colIndex[$j] == $k){
                for($n = 0; $n <= count($text)/strlen($key); $n++){
                    $tMsg .= $firstTranspose[$n][$j];       //appends characters from columns into a single string
                }
            }
        }
        $k++;
    }
    
    $tMsg = str_split($tMsg);
    
    $secondTranspose = array();
    $k = 0;     //pointer for message
    //assume second keyword is fruit
    //fills in 2D array with first transpose
    for($i = 0; $i < count($tMsg); $i++){
        for($j = 0; $j < strlen($key2); $j++){
            $secondTranspose[$i][$j] = $tMsg[$k];
            $k++;
        }
    }
    
    $colIndex = str_split($key2);
    $temp = $colIndex;
    sort($temp);
    for ($i = 0; $i < count($colIndex); $i++) {
        for ($j = 0; $j < count($colIndex); $j++) {
            if ($colIndex[$i] == $temp[$j]) {
                $colIndex[$i] = $j;
                $temp[$j] = "";
                break;
            }
        }
    }
    $stMsg = '';
    $k = 0; // column index we need to look at
    for($i = 0; $i < count($colIndex); $i++){       //checks through all elements in colIndex
        for($j = 0; $j < count($colIndex); $j++){   //looks for k
            if($colIndex[$j] == $k){
                for($n = 0; $n <= count($text)/strlen($key2); $n++){
                    $stMsg .= $secondTranspose[$n][$j];       //appends characters from columns into a single string
                }
            }
        }
        $k++;
    }
    
    return $stMsg;
}
function decrypt_double_transposition($string, $key, $key2){
    $text = preg_replace('/\s+/', '', $string); //removes all whitespaces
    $text = str_split($text);
    
    $colIndex = str_split($key);
    $temp = $colIndex;
    sort($temp);
    for ($i = 0; $i < count($colIndex); $i++) {
        for ($j = 0; $j < count($colIndex); $j++) {
            if ($colIndex[$i] == $temp[$j]) {
                $colIndex[$i] = $j;
                $temp[$j] = "";
                break;
            }
        }
    }
    $end = (strlen($string) % strlen($key));
    $colLength = ceil(count($text) / strlen($key));
    $firstTranspose = array();
    $k = 0;     //pointer for string
    $l = 0;
    //fills 2D array with string message
    for($i = 0; $i < count($colIndex); $i++){
        $order = $colIndex[$i];
        for($z = 0; $z < count($colIndex); $z++){
            if ($colIndex[$z] == $i){
                $column = $z;
                break;
            }
        }
        if ($column >= $end) {
            $l = $colLength - 1;
        } else {
            $l = $colLength;
        }
        for($j = 0; $j < $l; $j++){
            $firstTranspose[$column][$j] = $text[$k];
            $k++;
        }
    }
    $text2 = "";
    for($i = 0; $i < $colLength; $i++){
        for($j = 0; $j < strlen($key); $j++){
            $text2 .= $firstTranspose[$j][$i];
        }
    }
    
    $text2 = preg_replace('/\s+/', '', $text2); //removes all whitespaces
    $text2 = str_split($text2);
    
    $colIndex = str_split($key2);
    $temp = $colIndex;
    sort($temp);
    for ($i = 0; $i < count($colIndex); $i++) {
        for ($j = 0; $j < count($colIndex); $j++) {
            if ($colIndex[$i] == $temp[$j]) {
                $colIndex[$i] = $j;
                $temp[$j] = "";
                break;
            }
        }
    }
    $end = (strlen($string) % strlen($key2));
    $colLength = ceil(count($text) / strlen($key2));
    $secondTranspose = array();
    $k = 0;     //pointer for string
    $l = 0;
    //fills 2D array with string message
    for($i = 0; $i < count($colIndex); $i++){
        $order = $colIndex[$i];
        for($z = 0; $z < count($colIndex); $z++){
            if ($colIndex[$z] == $i){
                $column = $z;
                break;
            }
        }
        if ($column >= $end) {
            $l = $colLength - 1;
        } else {
            $l = $colLength;
        }
        for($j = 0; $j < $l; $j++){
            $secondTranspose[$column][$j] = $text2[$k];
            $k++;
        }
    }
    $text3 = "";
    for($i = 0; $i < $colLength; $i++){
        for($j = 0; $j < strlen($key2); $j++){
            $text3 .= $secondTranspose[$j][$i];
        }
    }
    return $text3;
}
//for both encryption and decryption
function rc4($key, $string) {
    $s = array(); //initial permutation table
    for ($i = 0; $i < 256; $i++) {
        $s[$i] = $i;
    }
    $j = 0;
    for ($i = 0; $i < 256; $i++) {
        $j = ($j + $s[$i] + ord($key[$i % strlen($key)])) % 256;
        $temp = $s[$i];
        $s[$i] = $s[$j];
        $s[$j] = $temp;
    }
    //stream generation
    $i = 0;
    $j = 0;
    $result = '';
    for ($y = 0; $y < strlen($string); $y++) {
        $i = ($i + 1) % 256;
        $j = ($j + $s[$i]) % 256;
        $temp = $s[$i];
        $s[$i] = $s[$j];
        $s[$j] = $temp;
        $result .= $string[$y] ^ chr($s[($s[$i] + $s[$j]) % 256]);
    }
    return $result;
}
function encrypt_rc4($key, $string) {
    return base64_encode(rc4($key, $string));
}
function decrypt_rc4($key, $string) {
    return rc4($key, base64_decode($string));
}
/**
 * Fixes user input against attacks
 */
function mysql_entities_fix_string($connection,$string)
{
    return htmlentities(mysql_fix_string($connection, $string));
}
function mysql_fix_string($conn, $string)
{
    if (get_magic_quotes_gpc())
        $string = stripslashes($string);
        
        return $conn->real_escape_string($string);
}
function errorHandler($err)
{
    echo $err;
    echo <<<_ERROR
       ____
    .-" +' "-.    __,  ,___,
   /.'.'A_'*`.\  (--|__| _,,_ ,_
  |:.*'/\-\. ':|   _|  |(_||_)|_)\/
  |:.'.||"|.'*:|  (        |  | _/
   \:~^~^~^~^:/          __,  ,___,
    /`-....-'\          (--|__| _ |' _| _,   ,
   /          \           _|  |(_)||(_|(_|\//_)
   `-.,____,.-'          (               _/
_ERROR;
}
function insert($conn, $username, $cipher, $encrypted, $original) {
    $stmt = $conn->prepare('INSERT INTO ciphers VALUES(?,?,?,?,?)');
    $timestamp = date('Y-m-d H:i:s');
    $stmt->bind_param('sssss', $username, $cipher, $timestamp, $encrypted, $original);
    $stmt->execute();
    $stmt->close();
}
main();
?>