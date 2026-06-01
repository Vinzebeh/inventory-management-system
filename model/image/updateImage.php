<?php
	require_once('../../inc/config/constants.php');
	require_once('../../inc/config/db.php');
	
	if (isset($_POST['itemImageItemNumber'])) {
	
	    $baseImageFolder = '../../data/item_images/';
	    $baseImageFolderRealPath = realpath($baseImageFolder);
	
	    if ($baseImageFolderRealPath === false) {
	        echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Upload directory is not configured correctly.</div>';
	        exit();
	    }
	
	    $itemImageItemNumber = trim($_POST['itemImageItemNumber']);
	
	    if (empty($itemImageItemNumber)) {
	        echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Please enter item number</div>';
	        exit();
	    }
	
	    // Validate item number format before using it in database queries
	    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $itemImageItemNumber)) {
	        echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Invalid item number format.</div>';
	        exit();
	    }
	
	    if (
	        !isset($_FILES['itemImageFile']) ||
	        $_FILES['itemImageFile']['error'] !== UPLOAD_ERR_OK ||
	        $_FILES['itemImageFile']['name'] === ''
	    ) {
	        echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Please select a valid image.</div>';
	        exit();
	    }
	
	    // Check if itemNumber exists in DB
	    $itemNumberSql = 'SELECT itemNumber FROM item WHERE itemNumber = :itemNumber';
	    $itemNumberStatement = $conn->prepare($itemNumberSql);
	    $itemNumberStatement->execute(['itemNumber' => $itemImageItemNumber]);
	
	    if ($itemNumberStatement->rowCount() <= 0) {
	        echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Item number does not exist.</div>';
	        exit();
	    }
	
	    // Validate extension
	    $originalFileName = $_FILES['itemImageFile']['name'];
	    $extension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
	
	    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
	
	    if (!in_array($extension, $allowedExtensions, true)) {
	        echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Image type is not allowed. Please select a valid image.</div>';
	        exit();
	    }
	
	    // Validate MIME type using actual file content
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
	
	    // Limit file size to 2MB
	    $maxFileSize = 2 * 1024 * 1024;
	
	    if ($_FILES['itemImageFile']['size'] > $maxFileSize) {
	        echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Image file is too large.</div>';
	        exit();
	    }
	
	    // Generate server-controlled filename
	    $fileName = time() . '_' . bin2hex(random_bytes(16)) . '.' . $extension;
	
	    /*
	        Important fix:
	        The target path is now built only from:
	        1. trusted fixed base directory
	        2. server-generated filename
	
	        No HTTP parameter is used in the filesystem path.
	    */
	    $targetPath = $baseImageFolderRealPath . DIRECTORY_SEPARATOR . $fileName;
	
	    // Final safety check: make sure final path remains inside the upload directory
	    $targetDirectoryRealPath = realpath(dirname($targetPath));
	
	    if ($targetDirectoryRealPath === false || strpos($targetDirectoryRealPath, $baseImageFolderRealPath) !== 0) {
	        echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>Invalid upload path.</div>';
	        exit();
	    }
	
	    if (move_uploaded_file($_FILES['itemImageFile']['tmp_name'], $targetPath)) {
	
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
