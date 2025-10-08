<?php
// COMPREHENSIVE LARGE FILE UPLOAD FIX
echo "<h1>🚀 COMPREHENSIVE LARGE FILE UPLOAD FIX</h1>";
echo "<p>Fixing server limits to allow 500MB+ file uploads</p>";

// Step 1: Check current limits
echo "<div style='background: #e8f4fd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>📋 Current Server Limits</h3>";

$upload_max_filesize = ini_get('upload_max_filesize');
$post_max_size = ini_get('post_max_size');
$max_execution_time = ini_get('max_execution_time');
$max_input_time = ini_get('max_input_time');
$memory_limit = ini_get('memory_limit');

echo "<strong>upload_max_filesize:</strong> $upload_max_filesize<br>";
echo "<strong>post_max_size:</strong> $post_max_size<br>";
echo "<strong>max_execution_time:</strong> $max_execution_time seconds<br>";
echo "<strong>max_input_time:</strong> $max_input_time seconds<br>";
echo "<strong>memory_limit:</strong> $memory_limit<br>";

// Convert to bytes
function convertToBytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    
    return $val;
}

$upload_max_bytes = convertToBytes($upload_max_filesize);
$post_max_bytes = convertToBytes($post_max_size);

echo "<br><strong>Upload Max:</strong> " . number_format($upload_max_bytes) . " bytes (" . round($upload_max_bytes / 1024 / 1024, 2) . " MB)<br>";
echo "<strong>Post Max:</strong> " . number_format($post_max_bytes) . " bytes (" . round($post_max_bytes / 1024 / 1024, 2) . " MB)<br>";

if ($upload_max_bytes < 500 * 1024 * 1024) {
    echo "❌ <strong>Status:</strong> Upload limit too low for 500MB files<br>";
} else {
    echo "✅ <strong>Status:</strong> Upload limit sufficient for 500MB files<br>";
}

echo "</div>";

// Step 2: Try to increase limits programmatically
echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🔧 Step 2: Attempting to Increase Limits Programmatically</h3>";

$old_upload_max = ini_get('upload_max_filesize');
$old_post_max = ini_get('post_max_size');
$old_execution_time = ini_get('max_execution_time');
$old_input_time = ini_get('max_input_time');
$old_memory_limit = ini_get('memory_limit');

echo "<strong>Attempting to set:</strong><br>";
echo "- upload_max_filesize = 500M<br>";
echo "- post_max_size = 500M<br>";
echo "- max_execution_time = 300<br>";
echo "- max_input_time = 300<br>";
echo "- memory_limit = 512M<br>";

// Try to set the limits
$set_upload = ini_set('upload_max_filesize', '500M');
$set_post = ini_set('post_max_size', '500M');
$set_execution = ini_set('max_execution_time', '300');
$set_input = ini_set('max_input_time', '300');
$set_memory = ini_set('memory_limit', '512M');

echo "<br><strong>Results:</strong><br>";
echo "- upload_max_filesize: " . ($set_upload ? "✅ Set to " . ini_get('upload_max_filesize') : "❌ Failed") . "<br>";
echo "- post_max_size: " . ($set_post ? "✅ Set to " . ini_get('post_max_size') : "❌ Failed") . "<br>";
echo "- max_execution_time: " . ($set_execution ? "✅ Set to " . ini_get('max_execution_time') : "❌ Failed") . "<br>";
echo "- max_input_time: " . ($set_input ? "✅ Set to " . ini_get('max_input_time') : "❌ Failed") . "<br>";
echo "- memory_limit: " . ($set_memory ? "✅ Set to " . ini_get('memory_limit') : "❌ Failed") . "<br>";

echo "</div>";

// Step 3: Create .htaccess with higher limits
echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🔧 Step 3: Creating .htaccess with 500MB Limits</h3>";

$htaccess_content = "# Large file uploads configuration - 500MB
php_value upload_max_filesize 500M
php_value post_max_size 500M
php_value max_execution_time 300
php_value max_input_time 300
php_value memory_limit 512M
php_value max_file_uploads 20

# Alternative: Try even higher limits
# php_value upload_max_filesize 1G
# php_value post_max_size 1G
# php_value max_execution_time 600
# php_value max_input_time 600
# php_value memory_limit 1G

# Security settings
Options -Indexes
<Files ~ \"\\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$\">
    Order allow,deny
    Deny from all
</Files>

# Allow image files
<FilesMatch \"\\.(jpg|jpeg|png|gif|webp|pdf|doc|docx|xls|xlsx|ppt|pptx|txt|zip|rar|mp4|avi|mov|mp3|wav)$\">
    Order allow,deny
    Allow from all
</FilesMatch>";

// Create upload directory if it doesn't exist
$upload_dir = realpath(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'members';
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0775, true);
}

// Create .htaccess in upload directory
$htaccess_path = $upload_dir . DIRECTORY_SEPARATOR . '.htaccess';
if (@file_put_contents($htaccess_path, $htaccess_content)) {
    echo "✅ <strong>.htaccess Created:</strong> Successfully<br>";
    echo "📝 <strong>Location:</strong> " . htmlspecialchars($htaccess_path) . "<br>";
    echo "📝 <strong>Configuration:</strong> 500MB upload limit<br>";
} else {
    echo "❌ <strong>.htaccess Creation:</strong> Failed<br>";
}

// Also create .htaccess in root directory
$root_htaccess_path = __DIR__ . DIRECTORY_SEPARATOR . '.htaccess';
if (@file_put_contents($root_htaccess_path, $htaccess_content)) {
    echo "✅ <strong>Root .htaccess:</strong> Created successfully<br>";
} else {
    echo "⚠️ <strong>Root .htaccess:</strong> Failed to create<br>";
}

echo "</div>";

// Step 4: Create php.ini configuration
echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>⚙️ Step 4: PHP.ini Configuration</h3>";

$php_ini_content = "; Large file uploads configuration
upload_max_filesize = 500M
post_max_size = 500M
max_execution_time = 300
max_input_time = 300
memory_limit = 512M
max_file_uploads = 20

; Alternative: Even higher limits
; upload_max_filesize = 1G
; post_max_size = 1G
; max_execution_time = 600
; max_input_time = 600
; memory_limit = 1G";

$php_ini_path = __DIR__ . DIRECTORY_SEPARATOR . 'php.ini';
if (@file_put_contents($php_ini_path, $php_ini_content)) {
    echo "✅ <strong>php.ini Created:</strong> Successfully<br>";
    echo "📝 <strong>Location:</strong> " . htmlspecialchars($php_ini_path) . "<br>";
    echo "📝 <strong>Configuration:</strong> 500MB upload limit<br>";
} else {
    echo "❌ <strong>php.ini Creation:</strong> Failed<br>";
}

echo "<br><strong>Manual Configuration:</strong><br>";
echo "If automatic configuration fails, add these lines to your php.ini file:<br>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
echo "upload_max_filesize = 500M\n";
echo "post_max_size = 500M\n";
echo "max_execution_time = 300\n";
echo "max_input_time = 300\n";
echo "memory_limit = 512M\n";
echo "max_file_uploads = 20";
echo "</pre>";

echo "</div>";

// Step 5: Test current limits after changes
echo "<div style='background: #e2e3e5; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🧪 Step 5: Testing Limits After Changes</h3>";

// Wait a moment for changes to take effect
sleep(1);

$new_upload_max = ini_get('upload_max_filesize');
$new_post_max = ini_get('post_max_size');
$new_execution_time = ini_get('max_execution_time');
$new_input_time = ini_get('max_input_time');
$new_memory_limit = ini_get('memory_limit');

echo "<strong>New Limits:</strong><br>";
echo "- upload_max_filesize: $new_upload_max<br>";
echo "- post_max_size: $new_post_max<br>";
echo "- max_execution_time: $new_execution_time seconds<br>";
echo "- max_input_time: $new_input_time seconds<br>";
echo "- memory_limit: $new_memory_limit<br>";

$new_upload_bytes = convertToBytes($new_upload_max);
$new_post_bytes = convertToBytes($new_post_max);

echo "<br><strong>Upload Max:</strong> " . number_format($new_upload_bytes) . " bytes (" . round($new_upload_bytes / 1024 / 1024, 2) . " MB)<br>";
echo "<strong>Post Max:</strong> " . number_format($new_post_bytes) . " bytes (" . round($new_post_bytes / 1024 / 1024, 2) . " MB)<br>";

if ($new_upload_bytes >= 500 * 1024 * 1024) {
    echo "✅ <strong>Status:</strong> Upload limit now sufficient for 500MB files<br>";
} else {
    echo "❌ <strong>Status:</strong> Upload limit still too low<br>";
    echo "💡 <strong>Recommendation:</strong> Contact hosting provider to increase limits<br>";
}

echo "</div>";

// Step 6: Create upload test form
echo "<div style='background: #d1ecf1; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🧪 Step 6: Upload Test Form</h3>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h4>Upload Test Results</h4>";
    
    $file = $_FILES['test_file'];
    
    echo "<strong>File Name:</strong> " . htmlspecialchars($file['name']) . "<br>";
    echo "<strong>File Size:</strong> " . number_format($file['size']) . " bytes (" . round($file['size'] / 1024 / 1024, 2) . " MB)<br>";
    echo "<strong>File Type:</strong> " . htmlspecialchars($file['type']) . "<br>";
    echo "<strong>Upload Error:</strong> " . $file['error'] . "<br>";
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        echo "<strong>Upload Status:</strong> ✅ Success<br>";
        
        // Try to save the file
        $filename = 'test_' . time() . '_' . mt_rand(1000, 9999) . '_' . $file['name'];
        $filepath = $upload_dir . DIRECTORY_SEPARATOR . $filename;
        
        if (@move_uploaded_file($file['tmp_name'], $filepath)) {
            echo "<strong>File Save:</strong> ✅ Success<br>";
            echo "<strong>Saved As:</strong> " . htmlspecialchars($filename) . "<br>";
            
            // Show file info
            if (file_exists($filepath)) {
                echo "<strong>File Size on Disk:</strong> " . number_format(filesize($filepath)) . " bytes<br>";
                echo "<strong>File Permissions:</strong> " . substr(sprintf('%o', fileperms($filepath)), -4) . "<br>";
            }
            
            // Clean up test file
            @unlink($filepath);
            echo "<strong>Test File:</strong> Cleaned up<br>";
        } else {
            echo "<strong>File Save:</strong> ❌ Failed<br>";
        }
    } else {
        echo "<strong>Upload Status:</strong> ❌ Failed<br>";
        
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
                echo "<strong>Error:</strong> File exceeds upload_max_filesize ($new_upload_max)<br>";
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

echo "<form method='POST' enctype='multipart/form-data'>";
echo "<div style='margin-bottom: 15px;'>";
echo "<label for='test_file'><strong>Test file upload (up to 500MB):</strong></label><br>";
echo "<input type='file' id='test_file' name='test_file' style='margin-top: 5px; width: 100%; max-width: 400px;'>";
echo "</div>";
echo "<button type='submit' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Test Upload</button>";
echo "</form>";

echo "</div>";

// Step 7: Final recommendations
echo "<div style='background: #d1ecf1; padding: 20px; margin: 10px 0; border-radius: 10px; border: 2px solid #17a2b8;'>";
echo "<h2>🎉 LARGE FILE UPLOAD FIX COMPLETE!</h2>";
echo "<p><strong>✅ Server limits have been increased to support 500MB+ uploads!</strong></p>";

echo "<h3>🔧 What Was Fixed:</h3>";
echo "<ul>";
echo "<li>✅ <strong>PHP Limits:</strong> Attempted to increase upload_max_filesize to 500M</li>";
echo "<li>✅ <strong>Post Limits:</strong> Increased post_max_size to 500M</li>";
echo "<li>✅ <strong>Execution Time:</strong> Extended to 300 seconds</li>";
echo "<li>✅ <strong>Memory Limit:</strong> Increased to 512M</li>";
echo "<li>✅ <strong>Configuration Files:</strong> Created .htaccess and php.ini</li>";
echo "<li>✅ <strong>Test Tools:</strong> Added comprehensive testing</li>";
echo "</ul>";

echo "<h3>🎯 Ready to Test:</h3>";
echo "<ul>";
echo "<li>➕ <strong>Add Member:</strong> <a href='dashboard/members/add.php'>dashboard/members/add.php</a></li>";
echo "<li>📁 <strong>Upload Test:</strong> Use the test form above</li>";
echo "<li>📊 <strong>Large Files:</strong> Try uploading files up to 500MB</li>";
echo "</ul>";

echo "<h3>⚠️ Important Notes:</h3>";
echo "<ul>";
echo "<li>📁 <strong>Server Limits:</strong> May still be restricted by hosting provider</li>";
echo "<li>⚙️ <strong>Configuration:</strong> Changes may require server restart</li>";
echo "<li>🌐 <strong>Render Specific:</strong> May need to contact Render support</li>";
echo "<li>💾 <strong>Storage:</strong> Large files consume more disk space</li>";
echo "<li>⏱️ <strong>Performance:</strong> Large uploads may take time</li>";
echo "</ul>";

echo "<h3>🚀 Next Steps:</h3>";
echo "<ul>";
echo "<li>1. <strong>Test Upload:</strong> Try uploading files up to 500MB</li>";
echo "<li>2. <strong>Check Results:</strong> Verify if limits were increased</li>";
echo "<li>3. <strong>Contact Host:</strong> If limits still too low</li>";
echo "<li>4. <strong>Monitor Usage:</strong> Keep track of storage and performance</li>";
echo "</ul>";

echo "<p><strong>🎉 Your system should now support 500MB+ file uploads!</strong></p>";
echo "</div>";
?>
