<?php
// UPLOAD TEST SCRIPT - Diagnose upload issues
echo "<h1>🧪 UPLOAD TEST SCRIPT</h1>";
echo "<p>Testing file upload functionality and diagnosing issues</p>";

// Check PHP configuration
echo "<div style='background: #e8f4fd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>📋 PHP Upload Configuration</h3>";
echo "<strong>file_uploads:</strong> " . (ini_get('file_uploads') ? 'Enabled' : 'Disabled') . "<br>";
echo "<strong>upload_max_filesize:</strong> " . ini_get('upload_max_filesize') . " (unlimited if 0)<br>";
echo "<strong>post_max_size:</strong> " . ini_get('post_max_size') . " (unlimited if 0)<br>";
echo "<strong>max_file_uploads:</strong> " . ini_get('max_file_uploads') . "<br>";
echo "<strong>upload_tmp_dir:</strong> " . (ini_get('upload_tmp_dir') ?: 'Default system temp') . "<br>";
echo "<strong>max_execution_time:</strong> " . ini_get('max_execution_time') . " seconds<br>";
echo "<strong>memory_limit:</strong> " . ini_get('memory_limit') . "<br>";
echo "</div>";

// Check upload directory
echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>📁 Upload Directory Check</h3>";

$uploadDirFs = realpath(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'members';
echo "<strong>Upload Directory:</strong> " . htmlspecialchars($uploadDirFs) . "<br>";

if (!is_dir($uploadDirFs)) {
    echo "⚠️ <strong>Directory Status:</strong> Does not exist<br>";
    echo "🔧 <strong>Attempting to create...</strong><br>";
    
    if (@mkdir($uploadDirFs, 0775, true)) {
        echo "✅ <strong>Directory Created:</strong> Successfully<br>";
    } else {
        echo "❌ <strong>Directory Creation:</strong> Failed<br>";
        echo "<strong>Error:</strong> " . error_get_last()['message'] . "<br>";
    }
} else {
    echo "✅ <strong>Directory Status:</strong> Exists<br>";
}

if (is_dir($uploadDirFs)) {
    echo "<strong>Directory Permissions:</strong> " . substr(sprintf('%o', fileperms($uploadDirFs)), -4) . "<br>";
    echo "<strong>Directory Writable:</strong> " . (is_writable($uploadDirFs) ? 'Yes' : 'No') . "<br>";
}
echo "</div>";

// Check available functions
echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🔧 Available Functions</h3>";
echo "<strong>mime_content_type:</strong> " . (function_exists('mime_content_type') ? 'Available' : 'Not Available') . "<br>";
echo "<strong>finfo_file:</strong> " . (function_exists('finfo_file') ? 'Available' : 'Not Available') . "<br>";
echo "<strong>getimagesize:</strong> " . (function_exists('getimagesize') ? 'Available' : 'Not Available') . "<br>";
echo "<strong>move_uploaded_file:</strong> " . (function_exists('move_uploaded_file') ? 'Available' : 'Not Available') . "<br>";
echo "<strong>is_uploaded_file:</strong> " . (function_exists('is_uploaded_file') ? 'Available' : 'Not Available') . "<br>";
echo "</div>";

// Test file upload if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h3>🧪 Upload Test Results</h3>";
    
    $file = $_FILES['test_file'];
    
    echo "<strong>File Name:</strong> " . htmlspecialchars($file['name']) . "<br>";
    echo "<strong>File Size:</strong> " . number_format($file['size']) . " bytes<br>";
    echo "<strong>File Type:</strong> " . htmlspecialchars($file['type']) . "<br>";
    echo "<strong>Temp Name:</strong> " . htmlspecialchars($file['tmp_name']) . "<br>";
    echo "<strong>Upload Error:</strong> " . $file['error'] . "<br>";
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        echo "<strong>Upload Status:</strong> ✅ Success<br>";
        
        // Test MIME detection
        $mime = '';
        if (function_exists('mime_content_type')) {
            $mime = mime_content_type($file['tmp_name']);
            echo "<strong>MIME (mime_content_type):</strong> " . htmlspecialchars($mime) . "<br>";
        }
        
        if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_finfo = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            echo "<strong>MIME (finfo_file):</strong> " . htmlspecialchars($mime_finfo) . "<br>";
        }
        
        // Test file extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        echo "<strong>File Extension:</strong> " . htmlspecialchars($ext) . "<br>";
        
        // Test if it's an uploaded file
        echo "<strong>Is Uploaded File:</strong> " . (is_uploaded_file($file['tmp_name']) ? 'Yes' : 'No') . "<br>";
        
        // Test move
        if (is_dir($uploadDirFs) && is_writable($uploadDirFs)) {
            $testFilename = 'test_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
            $testPath = $uploadDirFs . DIRECTORY_SEPARATOR . $testFilename;
            
            if (@move_uploaded_file($file['tmp_name'], $testPath)) {
                echo "<strong>File Move:</strong> ✅ Success<br>";
                echo "<strong>Saved As:</strong> " . htmlspecialchars($testFilename) . "<br>";
                
                // Clean up test file
                @unlink($testPath);
                echo "<strong>Test File:</strong> Cleaned up<br>";
            } else {
                echo "<strong>File Move:</strong> ❌ Failed<br>";
                echo "<strong>Error:</strong> " . error_get_last()['message'] . "<br>";
            }
        } else {
            echo "<strong>File Move:</strong> ❌ Directory not writable<br>";
        }
    } else {
        echo "<strong>Upload Status:</strong> ❌ Failed<br>";
        
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
                echo "<strong>Error:</strong> File exceeds upload_max_filesize<br>";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                echo "<strong>Error:</strong> File exceeds MAX_FILE_SIZE<br>";
                break;
            case UPLOAD_ERR_PARTIAL:
                echo "<strong>Error:</strong> File was only partially uploaded<br>";
                break;
            case UPLOAD_ERR_NO_FILE:
                echo "<strong>Error:</strong> No file was uploaded<br>";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                echo "<strong>Error:</strong> Missing temporary folder<br>";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                echo "<strong>Error:</strong> Failed to write file to disk<br>";
                break;
            case UPLOAD_ERR_EXTENSION:
                echo "<strong>Error:</strong> File upload stopped by extension<br>";
                break;
            default:
                echo "<strong>Error:</strong> Unknown upload error<br>";
                break;
        }
    }
    
    echo "</div>";
}

// Test form
echo "<div style='background: #d1ecf1; padding: 20px; margin: 10px 0; border-radius: 10px; border: 2px solid #17a2b8;'>";
echo "<h3>🧪 Test File Upload</h3>";
echo "<form method='POST' enctype='multipart/form-data'>";
echo "<div style='margin-bottom: 15px;'>";
echo "<label for='test_file'><strong>Select a test image file:</strong></label><br>";
echo "<input type='file' id='test_file' name='test_file' accept='image/*' style='margin-top: 5px;'>";
echo "</div>";
echo "<button type='submit' style='background: #17a2b8; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Test Upload</button>";
echo "</form>";
echo "<p><strong>Note:</strong> This will test the upload functionality without saving the file permanently.</p>";
echo "</div>";

// Recommendations
echo "<div style='background: #e8f4fd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>💡 Recommendations</h3>";
echo "<ul>";
echo "<li><strong>If uploads are disabled:</strong> Contact your hosting provider to enable file uploads</li>";
echo "<li><strong>If directory creation fails:</strong> Check file permissions on the parent directory</li>";
echo "<li><strong>If MIME detection fails:</strong> The system will fall back to file extension validation</li>";
echo "<li><strong>If file move fails:</strong> Check directory permissions and disk space</li>";
echo "<li><strong>For Render:</strong> File uploads work but files are temporary (reset on redeploy)</li>";
echo "</ul>";
echo "</div>";
?>
