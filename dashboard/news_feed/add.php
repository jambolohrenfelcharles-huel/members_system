<?php
session_start();
require_once '../../config/database.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$errors = [];

if ($_POST) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $userId = intval($_SESSION['user_id']);

    // Validation
    if (empty($title)) $errors[] = "Title is required";
    if (empty($description)) $errors[] = "Description is required";
    if (!isset($_FILES['media']) || $_FILES['media']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Media file is required";
    }

    if (empty($errors)) {
        // Upload directory
        $uploadsDir = rtrim(__DIR__ . '/../../uploads', '/\\') . DIRECTORY_SEPARATOR;
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        $originalName = $_FILES['media']['name'];
        $tmpPath = $_FILES['media']['tmp_name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        $allowedImages = ['jpg','jpeg','png','gif','webp'];
        $allowedVideos = ['mp4','webm','ogg','avi','mov','mkv'];
        $allowed = array_merge($allowedImages, $allowedVideos);

        if (!in_array($ext, $allowed)) {
            $errors[] = "Invalid file type. Allowed: " . implode(', ', $allowed);
        } else {
            $mediaType = in_array($ext, $allowedImages) ? 'image' : 'video';
            $safeBase = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
            $newFileName = time() . '_' . $safeBase . '.' . $ext;
            $targetPath = $uploadsDir . $newFileName;

            if (move_uploaded_file($tmpPath, $targetPath)) {
                try {
                    $stmt = $db->prepare("INSERT INTO news_feed (user_id, title, description, media_path, media_type) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$userId, $title, $description, $newFileName, $mediaType]);

                    header("Location: ../index.php?media=1");
                    exit();

                } catch (PDOException $e) {
                    if (file_exists($targetPath)) {
                        unlink($targetPath);
                    }
                    $errors[] = "Database error: " . $e->getMessage();
                }
            } else {
                $errors[] = "Failed to upload file.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add News Feed - SmartUnion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-newspaper me-2"></i>Create Post</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                      
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h6>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>News Feed Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title *</label>
                                <input type="text" class="form-control" id="title" name="title"
                                       value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="media" class="form-label">Upload Image/Video *</label>
                                <input type="file" class="form-control" id="media" name="media" accept="image/*,video/*" required>
                                <div class="form-text">Allowed: jpg, jpeg, png, gif, webp, mp4, webm, ogg, avi, mov, mkv</div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <a href="../index.php" class="btn btn-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Post
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
