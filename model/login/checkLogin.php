<?php
	session_start();
	require_once('../../inc/config/constants.php');
	require_once('../../inc/config/db.php');
	
	$loginUsername = '';
	$loginPassword = '';
	
	if (isset($_POST['loginUsername'])) {
	
	    $loginUsername = trim($_POST['loginUsername']);
	    $loginPassword = $_POST['loginPassword'];
	
	    if (!empty($loginUsername) && !empty($loginPassword)) {
	
	        // Sanitize username
	        $loginUsername = filter_var($loginUsername, FILTER_SANITIZE_STRING);
	
	        // Check if username is empty after sanitization
	        if ($loginUsername == '') {
	            echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Please enter Username</div>';
	            exit();
	        }
	
	        // Check if password is empty
	        if ($loginPassword == '') {
	            echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Please enter Password</div>';
	            exit();
	        }
	
	        /*
	            Secure password verification:
	            Do not use md5().
	            Retrieve the user record by username, then verify the submitted password
	            against the stored password hash using password_verify().
	        */
	        $checkUserSql = 'SELECT * FROM user WHERE username = :username';
	        $checkUserStatement = $conn->prepare($checkUserSql);
	        $checkUserStatement->execute(['username' => $loginUsername]);
	
	        if ($checkUserStatement->rowCount() > 0) {
	
	            $row = $checkUserStatement->fetch(PDO::FETCH_ASSOC);
	            $storedPasswordHash = $row['password'];
	
	            if (password_verify($loginPassword, $storedPasswordHash)) {
	
	                // Valid credentials. Hence, start the session
	                $_SESSION['loggedIn'] = '1';
	                $_SESSION['fullName'] = $row['fullName'];
	
	                echo '<div class="alert alert-success"><button type="button" class="close" data-dismiss="alert">&times;</button>Login success! Redirecting you to home page...</div>';
	                exit();
	
	            } else {
	                echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Incorrect Username / Password</div>';
	                exit();
	            }
	
	        } else {
	            echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Incorrect Username / Password</div>';
	            exit();
	        }
	
	    } else {
	        echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Please enter Username and Password</div>';
	        exit();
	    }
	}
?>
