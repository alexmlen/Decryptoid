<?php
function main() {
    require_once 'login.php';
    $conn = new mysqli($hn, $un, $pw, $db);
    if($conn->connect_error) die(errorHandler("Access to Database failed."));
    
    if (isset($_POST['username']))
    {
        $username = mysql_entities_fix_string($conn, $_POST['username']);
    }
    if (isset($_POST['password']))
    {
        $password = mysql_entities_fix_string($conn, $_POST['password']);
    }
    if (isset($_POST['email']))
    {
        $email = mysql_entities_fix_string($conn, $_POST['email']);
    }
    
    $fail = validate_username($username);
    $fail .= validate_password($password);
    $fail .= validate_email($email);
    
    if($fail == ""){
        //insert crap into database
        echo 'Successfully created account.';
        $stmt = $conn->prepare('INSERT INTO users VALUES(?,?,?,?)');
        $stmt->bind_param('ssss', $username, $salt, $token, $email);
        $salt = generateSalt();
        $token = hash('ripemd128', "$salt$password");
        $stmt->execute();
        $stmt->close();
        /*
         $query = "INSERT INTO users VALUES('$username','$salt', '$token', '$email')";
         $result = $conn->query($query);
         if(!$result) die($conn->connect_error);
         */
    }
    
    echo <<<_END
    <!DOCTYPE html>
    <html>
    <head>
        <title> Form </title>
        <style>
        .signup {
            border: 1px solid #999999; font: normal 14px helevetica; color: #444444;
        }
        </style>
        
        <script>
            function validate(form)
            {
                fail  = validateUsername(form.username.value);
                fail += validatePassword(form.password.value);
                fail += validateEmail(form.email.value);
                
                if (fail == "") { return true; }
                else { alert(fail); return false; }
            }
            
            function validateUsername(field)
            {
                var MIN_USERNAME_LENGTH = 5;
                if (field == "") { return "No username was entered. ";}
                else if (field.length < MIN_USERNAME_LENGTH)
                    { return "Usernames must be at least " + MIN_USERNAME_LENGTH + " characters. "; }
                else if (/[^a-zA-Z0-9_-]/.test(field))
                    { return "Only a-z, A-Z, 0-9, - and _ allowed in usernames. "; }
                return "";
            }
            
            function validatePassword(field)
            {
                var MIN_PASS_LENGTH = 6;
                if (field == "") {return "No password was entered. ";}
                else if (field.length < MIN_PASS_LENGTH)
                    {return "Password must be at least " + MIN_PASS_LENGTH + " characters. ";}
                else if (!/[a-z]/.test(field) || !/[A-Z]/.test(field) || !/[0-9]/.test(field))
                    {return "Password requires one of each: of a-z, A-Z and 0-9.";}
                return "";
            }
            
            function validateEmail(field)
            {
                if (field == "") {return "No email was entered. ";}
                else if (!((field.indexOf(".") > 0) && (field.indexOf("@") > 0)) || /[^a-zA-Z0-9.@_-]/.test(field))
                    {return "The email address is invalid.";}
                return "";
            }
        </script>
        
    </head>
    <body>
	<table border="0" cellpadding="2" cellspacing="5" bgcolor="#eeeeee">
		<th colspan="2" align="center">Create an account:</th>
		<form method="post" action="adduser.php" onsubmit="return validate(this)">
			<tr><td>Username</td>
				<td><input type="text" maxlength="16" name="username"></td></tr>
			<tr><td>Password</td>
				<td><input type="text" maxlength="12" name="password"></td></tr>
			<tr><td>Email</td>
				<td><input type="text" maxlength="64" name="email"></td></tr>
			<tr><td colspan="2" align="center"><input type="submit"
				value="Signup"></td></tr>
		</form>
	</table>
	
    <table border="0" cellpadding="2" cellspacing="5" bgcolor="#eeeeee">
        <th colspan="2" align="center">Already have an account? Login:</th>
        <form method="post" action="adduser.php">
            <tr><td>Username</td>
				<td><input type="text" maxlength="16" name="loginuser"></td></tr>
			<tr><td>Password</td>
				<td><input type="text" maxlength="12" name="loginpass"></td></tr>
			<tr><td colspan="2" align="center"><input type="submit"
				value="Login"></td></tr>
		</form>
	</table>
</body>
</html>
_END;
    
    //authenticate user
    if(isset($_POST['loginuser']) && isset($_POST['loginpass'])){
        $un_temp = mysql_entities_fix_string($conn, $_POST['loginuser']);
        $pw_temp = mysql_entities_fix_string($conn, $_POST['loginpass']);
        
        $query = "SELECT * FROM users WHERE username = '$un_temp' ";
        $result = $conn->query($query);
        
        if(!$result) die (errorHandler("Access to database failed."));
        elseif ($result->num_rows){
            $row = $result->fetch_array(MYSQLI_NUM);
            $result->close();
            $salt = $row[1];    //assuming salt is in this position
            $token = hash('ripemd128', "$salt$pw_temp");
            if($token == $row[2]){  //assuming pass is stored here
                session_start();
                $_SESSION['username'] = $un_temp;
                $_SESSION['password'] = $pw_temp;
                echo "Hi $row[0], you are now logged in";
                die("<p><a href = continue.php> Click here to continue</a></p>");
            }
            else die("Invalid username/password combination");
        }
        else die("Invalid username/password combination");
    }
    $conn->close();
}
function validate_username($field)
{
    $MIN_USERNAME_LENGTH = 5;
    if ($field == "")
        return "No username was entered <br>";
        else if (strlen($field) < $MIN_USERNAME_LENGTH)
            return "Usernames must be at least $MIN_USERNAME_LENGTH characters. <br>";
            else if (preg_match("/[^a-zA-z0-9_-]/", $field))
                return "Only letters, numbers, - and _ in usernames <br>";
                return "";
}
function validate_password($field)
{
    $MIN_PASSWORD_LENGTH = 6;
    if ($field == "")
        return "No password was entered <br>";
        else if (strlen($field) < $MIN_PASSWORD_LENGTH)
            return "Password must be at least $MIN_PASSWORD_LENGTH characters. <br>";
            else if (!preg_match("/[a-z]/", $field) ||
                !preg_match("/[A-Z]/", $field) ||
                !preg_match("/[0-9]/", $field))
                return "Passwords require one of each: a-z, A-Z, and 0-9<br>";
                return "";
                
}
function validate_email($field)
{
    if($field == "")
        return "No email was entered <br>";
        else if (!((strpos($field, ".") > 0) && (strpos($field,"@") > 0)) ||
            preg_match("/[^a-zA-Z0-9.@_-]/", $field))
            return "The email address is invalid <br>";
            return "";
}
/**
 * Handles errors
 */
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
/**
 * Generates randoms string of characters to be used for salting
 */
function generateSalt()
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
    $charactersLength = strlen($characters);
    $saltString = '';
    for ($i = 0; $i < 5; $i++) {
        $saltString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $saltString;
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
main();
?>
