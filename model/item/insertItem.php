<?php
	require_once('../../inc/config/constants.php');
	require_once('../../inc/config/db.php');
	
	$initialStock = 0;
	
	if (isset($_POST['itemDetailsItemNumber'])) {
	
	    $itemNumber = trim($_POST['itemDetailsItemNumber']);
	    $itemName = trim($_POST['itemDetailsItemName']);
	    $discount = trim($_POST['itemDetailsDiscount']);
	    $quantity = trim($_POST['itemDetailsQuantity']);
	    $unitPrice = trim($_POST['itemDetailsUnitPrice']);
	    $status = trim($_POST['itemDetailsStatus']);
	    $description = trim($_POST['itemDetailsDescription']);
	
	    // Check if mandatory fields are not empty
	    if (!empty($itemNumber) && !empty($itemName) && $quantity !== '' && $unitPrice !== '') {
	
	        /*
	            Path traversal mitigation:
	            The item number must not contain slashes, dots, or path traversal characters.
	            This is also useful for keeping item numbers clean in the database.
	        */
	        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $itemNumber)) {
	            echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Invalid item number format.</div>';
	            exit();
	        }
	
	        // Validate quantity. It must be an integer.
	        if (filter_var($quantity, FILTER_VALIDATE_INT) === false) {
	            echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Please enter a valid number for quantity</div>';
	            exit();
	        }
	
	        // Validate unit price. It must be a number or floating point value.
	        if (filter_var($unitPrice, FILTER_VALIDATE_FLOAT) === false) {
	            echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Please enter a valid number for unit price</div>';
	            exit();
	        }
	
	        // Validate discount only if provided.
	        if (!empty($discount)) {
	            if (filter_var($discount, FILTER_VALIDATE_FLOAT) === false) {
	                echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Please enter a valid discount amount</div>';
	                exit();
	            }
	        } else {
	            $discount = 0;
	        }
	
	        /*
	            Important fix:
	            Removed mkdir($itemImageFolder).
	
	            The previous code created a filesystem path using $itemNumber,
	            which came from an HTTP POST parameter. This caused a path traversal
	            data flow into mkdir().
	
	            Image upload is now handled only by updateImage.php, where the file
	            is stored using a fixed upload directory and a server-generated filename.
	        */
	
	        // Check whether item already exists
	        $stockSql = 'SELECT stock FROM item WHERE itemNumber = :itemNumber';
	        $stockStatement = $conn->prepare($stockSql);
	        $stockStatement->execute(['itemNumber' => $itemNumber]);
	
	        if ($stockStatement->rowCount() > 0) {
	            echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Item already exists in DB. Please click the <strong>Update</strong> button to update the details. Or use a different Item Number.</div>';
	            exit();
	        } else {
	            // Item does not exist, therefore add it to DB as a new item
	            $insertItemSql = 'INSERT INTO item(itemNumber, itemName, discount, stock, unitPrice, status, description) 
	                              VALUES(:itemNumber, :itemName, :discount, :stock, :unitPrice, :status, :description)';
	
	            $insertItemStatement = $conn->prepare($insertItemSql);
	            $insertItemStatement->execute([
	                'itemNumber' => $itemNumber,
	                'itemName' => $itemName,
	                'discount' => $discount,
	                'stock' => $quantity,
	                'unitPrice' => $unitPrice,
	                'status' => $status,
	                'description' => $description
	            ]);
	
	            echo '<div class="alert alert-success"><button type="button" class="close" data-dismiss="alert">&times;</button>Item added to database.</div>';
	            exit();
	        }
	
	    } else {
	        echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Please enter all fields marked with a (*)</div>';
	        exit();
	    }
	}
?>
