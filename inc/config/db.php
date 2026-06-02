<?php

	// Connect to database
	try {
	    $conn = new PDO(DSN, DB_USER, DB_PASSWORD);
	    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	} catch (PDOException $e) {
	
	    /*
	        Security fix:
	        Do not expose raw database error details to the user.
	        Log the technical error on the server instead.
	    */
	    error_log('Database connection error: ' . $e->getMessage());
	
	    echo '<div class="alert alert-danger">Database connection failed. Please contact the system administrator.</div>';
	    exit();
	}

?>
