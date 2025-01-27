<?php
// Check if a file was uploaded
if(isset($_FILES['file'])) {
    $target_dir = "uploads/"; // Create this directory first
    $target_file = $target_dir . basename($_FILES["file"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

    // Check if file already exists
    if (file_exists($target_file)) {
        echo "Sorry, file already exists.";
        $uploadOk = 0;
    }

    // Check file size (set to 5MB max)
    if ($_FILES["file"]["size"] > 5000000) {
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Upload the file if everything is ok
    if ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
            echo "The file ". basename( $_FILES["file"]["name"]). " has been uploaded.";
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<body>
    <form action="upload.php" method="post" enctype="multipart/form-data">
        Select file to upload:
        <input type="file" name="file">
        <input type="submit" value="Upload File" name="submit">
    </form>
</body>
</html>