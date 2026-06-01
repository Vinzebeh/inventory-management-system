<?php
	require_once('../../inc/config/constants.php');
	require_once('../../inc/config/db.php');
	
	if (isset($_POST['itemImageItemNumber'])) {
	
	    // Base upload directory
	    $baseImageFolder = '../../data/item_images/';
	
	    // Resolve the real absolute path of the base folder
	    $baseImageFolderRealPath = realpath($baseImageFolder);
	
	    if ($baseImageFolderRealPath === false) {
	        echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Upload directory is not configured correctly.</div>';
	        exit();
	    }
	
	    // Get item number from user input
	    $itemImageItemNumber = trim($_POST['itemImageItemNumber']);
	
	    if (empty($itemImageItemNumber)) {
	        echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Please enter item number</div>';
	        exit();
	    }
	
	    /*
	        Path traversal mitigation:
	        Only allow safe item number characters.
	        This prevents values such as ../../, ../, slashes, backslashes, and special path characters.
	    */
	    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $itemImageItemNumber)) {
	        echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Invalid item number format.</div>';
	        exit();
	    }
	
	    // Check if the user has selected an image
	    if (
	        !isset($_FILES['itemImageFile']) ||
	        $_FILES['itemImageFile']['error'] !== UPLOAD_ERR_OK ||
	        $_FILES['itemImageFile']['name'] == ''
	    ) {
	        echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Please select a valid image.</div>';
	        exit();
	    }
	
	    // Check if itemNumber is in DB
	    $itemNumberSql = 'SELECT itemNumber FROM item WHERE itemNumber = :itemNumber';
	    $itemNumberStatement = $conn->prepare($itemNumberSql);
	    $itemNumberStatement->execute(['itemNumber' => $itemImageItemNumber]);
	
	    if ($itemNumberStatement->rowCount() <= 0) {
	        echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Item number does not exist.</div>';
	        exit();
	    }
	
	    // Validate file extension
	    $originalFileName = $_FILES['itemImageFile']['name'];
	    $extension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
	
	    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
	
	    if (!in_array($extension, $allowedExtensions, true)) {
	        echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Image type is not allowed. Please select a valid image.</div>';
	        exit();
	    }
	
	    // Validate MIME type using server-side file inspection
	    $allowedMimeTypes = [
	        'jpg'  => 'image/jpeg',
	        'jpeg' => 'image/jpeg',
	        'png'  => 'image/png',
	        'gif'  => 'image/gif'
	    ];
	
	    $finfo = new finfo(FILEINFO_MIME_TYPE);
	    $detectedMimeType = $finfo->file($_FILES['itemImageFile']['tmp_name']);
	
	    if ($detectedMimeType !== $allowedMimeTypes[$extension]) {
	        echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Invalid image file content.</div>';
	        exit();
	    }
	
	    // Optional file size limit: 2MB
	    $maxFileSize = 2 * 1024 * 1024;
	
	    if ($_FILES['itemImageFile']['size'] > $maxFileSize) {
	        echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Image file is too large.</div>';
	        exit();
	    }
	
	    /*
	        Generate a safe server-side filename.
	        Do not trust the original uploaded filename because it may contain path tricks.
	    */
	    $fileName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
	
	    // Build item image folder path
	    $itemImageFolder = $baseImageFolderRealPath . DIRECTORY_SEPARATOR . $itemImageItemNumber;
	
	    /*
	        Extra path traversal protection:
	        Check that the final folder still stays inside the allowed base upload directory.
	    */
	    $parentFolder = dirname($itemImageFolder);
	    $parentFolderRealPath = realpath($parentFolder);
	
	    if ($parentFolderRealPath === false || strpos($parentFolderRealPath, $baseImageFolderRealPath) !== 0) {
	        echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Invalid upload path.</div>';
	        exit();
	    }
	
	    // Create image folder if it does not exist
	    if (!is_dir($itemImageFolder)) {
	        if (!mkdir($itemImageFolder, 0755, true)) {
	            echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Could not create image folder.</div>';
	            exit();
	        }
	    }
	
	    // Resolve real path after folder creation
	    $itemImageFolderRealPath = realpath($itemImageFolder);
	
	    if ($itemImageFolderRealPath === false || strpos($itemImageFolderRealPath, $baseImageFolderRealPath) !== 0) {
	        echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Invalid upload directory.</div>';
	        exit();
	    }
	
	    // Final target path
	    $targetPath = $itemImageFolderRealPath . DIRECTORY_SEPARATOR . $fileName;
	
	    // Upload file to server
	    if (move_uploaded_file($_FILES['itemImageFile']['tmp_name'], $targetPath)) {
	
	        // Update image url in item table
	        $updateImageUrlSql = 'UPDATE item SET imageURL = :imageURL WHERE itemNumber = :itemNumber';
	        $updateImageUrlStatement = $conn->prepare($updateImageUrlSql);
	        $updateImageUrlStatement->execute([
	            'imageURL' => $fileName,
	            'itemNumber' => $itemImageItemNumber
	        ]);
	
	        echo '<div class="alert alert-success"><button type="button" class="close" data-dismiss="alert">&times;</button>Image uploaded successfully.</div>';
	        exit();
	
	    } else {
	        echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Could not upload image.</div>';
	        exit();
	    }
	}
?>
