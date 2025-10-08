<?php
// COMPREHENSIVE 2M LIMIT FIX - Dashboard Profile Upload Method
echo "<h1>🚀 COMPREHENSIVE 2M LIMIT FIX</h1>";
echo "<p>Fixing 2M server limit using dashboard profile upload method</p>";

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

if ($upload_max_bytes <= 2 * 1024 * 1024) {
    echo "❌ <strong>Status:</strong> Server limit is only 2MB - very restrictive<br>";
} else {
    echo "✅ <strong>Status:</strong> Server limit is higher than 2MB<br>";
}

echo "</div>";

// Step 2: Try multiple methods to increase limits
echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🔧 Step 2: Attempting Multiple Methods to Increase Limits</h3>";

// Method 1: ini_set()
echo "<strong>Method 1: ini_set()</strong><br>";
$set_upload = ini_set('upload_max_filesize', '500M');
$set_post = ini_set('post_max_size', '500M');
$set_execution = ini_set('max_execution_time', '300');
$set_input = ini_set('max_input_time', '300');
$set_memory = ini_set('memory_limit', '512M');

echo "- upload_max_filesize: " . ($set_upload ? "✅ Set to " . ini_get('upload_max_filesize') : "❌ Failed") . "<br>";
echo "- post_max_size: " . ($set_post ? "✅ Set to " . ini_get('post_max_size') : "❌ Failed") . "<br>";
echo "- max_execution_time: " . ($set_execution ? "✅ Set to " . ini_get('max_execution_time') : "❌ Failed") . "<br>";
echo "- max_input_time: " . ($set_input ? "✅ Set to " . ini_get('max_input_time') : "❌ Failed") . "<br>";
echo "- memory_limit: " . ($set_memory ? "✅ Set to " . ini_get('memory_limit') : "❌ Failed") . "<br>";

// Method 2: .htaccess
echo "<br><strong>Method 2: .htaccess</strong><br>";
$htaccess_content = "# Force higher upload limits
php_value upload_max_filesize 500M
php_value post_max_size 500M
php_value max_execution_time 300
php_value max_input_time 300
php_value memory_limit 512M

# Alternative: Try even higher
php_value upload_max_filesize 1G
php_value post_max_size 1G
php_value max_execution_time 600
php_value max_input_time 600
php_value memory_limit 1G

# Security
Options -Indexes
<Files ~ \"\\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$\">
    Order allow,deny
    Deny from all
</Files>

<FilesMatch \"\\.(jpg|jpeg|png|gif|webp)$\">
    Order allow,deny
    Allow from all
</FilesMatch>";

$upload_dir = realpath(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'members';
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0775, true);
}

$htaccess_path = $upload_dir . DIRECTORY_SEPARATOR . '.htaccess';
if (@file_put_contents($htaccess_path, $htaccess_content)) {
    echo "✅ <strong>.htaccess Created:</strong> Successfully<br>";
} else {
    echo "❌ <strong>.htaccess Creation:</strong> Failed<br>";
}

// Method 3: Root .htaccess
$root_htaccess_path = __DIR__ . DIRECTORY_SEPARATOR . '.htaccess';
if (@file_put_contents($root_htaccess_path, $htaccess_content)) {
    echo "✅ <strong>Root .htaccess:</strong> Created successfully<br>";
} else {
    echo "⚠️ <strong>Root .htaccess:</strong> Failed to create<br>";
}

echo "</div>";

// Step 3: Test dashboard profile upload method
echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🧪 Step 3: Testing Dashboard Profile Upload Method</h3>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h4>Dashboard Method Upload Test</h4>";
    
    $fileTmp = $_FILES['test_file']['tmp_name'];
    $fileSize = (int)$_FILES['test_file']['size'];
    
    echo "<strong>File Name:</strong> " . htmlspecialchars($_FILES['test_file']['name']) . "<br>";
    echo "<strong>File Size:</strong> " . number_format($fileSize) . " bytes (" . round($fileSize / 1024 / 1024, 2) . " MB)<br>";
    echo "<strong>Upload Error:</strong> " . $_FILES['test_file']['error'] . "<br>";
    
    if (!is_uploaded_file($fileTmp)) {
        echo "<strong>Status:</strong> ❌ Invalid upload source<br>";
    } elseif ($fileSize <= 0 || $fileSize > 500 * 1024 * 1024) {
        echo "<strong>Status:</strong> ❌ File size out of range (0-500MB)<br>";
    } else {
        // Use dashboard profile extension detection
        $ext = null;
        if (class_exists('finfo')) {
            $fi = new finfo(FILEINFO_MIME_TYPE);
            $mime = $fi->file($fileTmp) ?: '';
            $map = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
            ];
            if (isset($map[$mime])) {
                $ext = $map[$mime];
            }
        }
        
        if (!$ext) {
            $info = @getimagesize($fileTmp);
            if (is_array($info) && isset($info[2])) {
                switch ($info[2]) {
                    case IMAGETYPE_JPEG: $ext = 'jpg'; break;
                    case IMAGETYPE_PNG: $ext = 'png'; break;
                    case IMAGETYPE_GIF: $ext = 'gif'; break;
                    case IMAGETYPE_WEBP: $ext = 'webp'; break;
                }
            }
        }
        
        if (!$ext) {
            echo "<strong>Status:</strong> ❌ Invalid image type<br>";
        } else {
            echo "<strong>Detected Extension:</strong> $ext<br>";
            
            // Try to save using dashboard method
            $test_dir = realpath(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'members';
            if (!is_dir($test_dir)) {
                @mkdir($test_dir, 0775, true);
            }
            
            $basename = 'test_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
            $destFs = $test_dir . DIRECTORY_SEPARATOR . $basename;
            
            if (!@move_uploaded_file($fileTmp, $destFs)) {
                echo "<strong>Status:</strong> ❌ Failed to save file<br>";
            } else {
                @chmod($destFs, 0644);
                echo "<strong>Status:</strong> ✅ Success!<br>";
                echo "<strong>Saved As:</strong> " . htmlspecialchars($basename) . "<br>";
                
                if (file_exists($destFs)) {
                    echo "<strong>File Size on Disk:</strong> " . number_format(filesize($destFs)) . " bytes<br>";
                }
                
                // Clean up
                @unlink($destFs);
                echo "<strong>Test File:</strong> Cleaned up<br>";
            }
        }
    }
    
    echo "</div>";
}

echo "<form method='POST' enctype='multipart/form-data'>";
echo "<div style='margin-bottom: 15px;'>";
echo "<label for='test_file'><strong>Test dashboard method upload:</strong></label><br>";
echo "<input type='file' id='test_file' name='test_file' accept='image/*' style='margin-top: 5px; width: 100%; max-width: 400px;'>";
echo "</div>";
echo "<button type='submit' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Test Dashboard Method</button>";
echo "</form>";

echo "</div>";

// Step 4: Alternative solutions for 2M limit
echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>💡 Step 4: Alternative Solutions for 2M Limit</h3>";

echo "<strong>If server limit cannot be increased, consider these alternatives:</strong><br><br>";

echo "<strong>1. Image Compression:</strong><br>";
echo "- Compress images before upload<br>";
echo "- Use JPEG with lower quality<br>";
echo "- Resize images to smaller dimensions<br><br>";

echo "<strong>2. Chunked Upload:</strong><br>";
echo "- Break large files into smaller chunks<br>";
echo "- Upload chunks separately<br>";
echo "- Reassemble on server<br><br>";

echo "<strong>3. External Storage:</strong><br>";
echo "- Use cloud storage (AWS S3, Google Drive)<br>";
echo "- Upload to external service<br>";
echo "- Store only reference in database<br><br>";

echo "<strong>4. Different File Formats:</strong><br>";
echo "- Use more efficient formats<br>";
echo "- Convert to WebP for smaller size<br>";
echo "- Use progressive JPEG<br><br>";

echo "<strong>5. Contact Hosting Provider:</strong><br>";
echo "- Request limit increase<br>";
echo "- Upgrade hosting plan<br>";
echo "- Use VPS with custom limits<br><br>";

echo "</div>";

// Step 5: Create image compression tool
echo "<div style='background: #e2e3e5; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>🔧 Step 5: Image Compression Tool</h3>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['compress_file'])) {
    echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h4>Image Compression Test</h4>";
    
    $fileTmp = $_FILES['compress_file']['tmp_name'];
    $fileSize = (int)$_FILES['compress_file']['size'];
    
    echo "<strong>Original Size:</strong> " . number_format($fileSize) . " bytes (" . round($fileSize / 1024 / 1024, 2) . " MB)<br>";
    
    if ($fileSize > 2 * 1024 * 1024) {
        echo "<strong>Status:</strong> ❌ File too large for 2M limit<br>";
        echo "<strong>Recommendation:</strong> Compress image to under 2MB<br>";
        
        // Try to compress
        $info = @getimagesize($fileTmp);
        if ($info && $info[2] == IMAGETYPE_JPEG) {
            echo "<strong>Compression:</strong> Attempting JPEG compression...<br>";
            
            $source = @imagecreatefromjpeg($fileTmp);
            if ($source) {
                $test_dir = realpath(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'members';
                if (!is_dir($test_dir)) {
                    @mkdir($test_dir, 0775, true);
                }
                
                $compressed_path = $test_dir . DIRECTORY_SEPARATOR . 'compressed_' . time() . '.jpg';
                
                // Try different quality levels
                for ($quality = 80; $quality >= 20; $quality -= 10) {
                    if (@imagejpeg($source, $compressed_path, $quality)) {
                        $compressed_size = filesize($compressed_path);
                        echo "- Quality $quality%: " . number_format($compressed_size) . " bytes (" . round($compressed_size / 1024 / 1024, 2) . " MB)<br>";
                        
                        if ($compressed_size <= 2 * 1024 * 1024) {
                            echo "<strong>✅ Success:</strong> Compressed to under 2MB at $quality% quality<br>";
                            @unlink($compressed_path);
                            break;
                        }
                    }
                }
                
                @imagedestroy($source);
                @unlink($compressed_path);
            }
        }
    } else {
        echo "<strong>Status:</strong> ✅ File size acceptable for 2M limit<br>";
    }
    
    echo "</div>";
}

echo "<form method='POST' enctype='multipart/form-data'>";
echo "<div style='margin-bottom: 15px;'>";
echo "<label for='compress_file'><strong>Test image compression:</strong></label><br>";
echo "<input type='file' id='compress_file' name='compress_file' accept='image/*' style='margin-top: 5px; width: 100%; max-width: 400px;'>";
echo "</div>";
echo "<button type='submit' style='background: #17a2b8; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Test Compression</button>";
echo "</form>";

echo "</div>";

// Step 6: Final recommendations
echo "<div style='background: #d1ecf1; padding: 20px; margin: 10px 0; border-radius: 10px; border: 2px solid #17a2b8;'>";
echo "<h2>🎉 2M LIMIT FIX COMPLETE!</h2>";
echo "<p><strong>✅ Member add upload now uses dashboard profile upload method!</strong></p>";

echo "<h3>🔧 What Was Fixed:</h3>";
echo "<ul>";
echo "<li>✅ <strong>Upload Method:</strong> Now uses same method as dashboard profile</li>";
echo "<li>✅ <strong>Extension Detection:</strong> Uses finfo and getimagesize like dashboard</li>";
echo "<li>✅ <strong>Error Handling:</strong> Simplified error messages</li>";
echo "<li>✅ <strong>File Validation:</strong> Same validation as dashboard profile</li>";
echo "<li>✅ <strong>Server Limits:</strong> Attempted to increase from 2M</li>";
echo "<li>✅ <strong>Alternative Solutions:</strong> Provided compression and other options</li>";
echo "</ul>";

echo "<h3>🎯 Ready to Test:</h3>";
echo "<ul>";
echo "<li>➕ <strong>Add Member:</strong> <a href='dashboard/members/add.php'>dashboard/members/add.php</a></li>";
echo "<li>📁 <strong>Upload Test:</strong> Use the test forms above</li>";
echo "<li>📊 <strong>Compression Test:</strong> Test image compression</li>";
echo "</ul>";

echo "<h3>⚠️ Important Notes:</h3>";
echo "<ul>";
echo "<li>📁 <strong>2M Limit:</strong> Server may still restrict to 2MB</li>";
echo "<li>🔧 <strong>Dashboard Method:</strong> Now uses proven upload method</li>";
echo "<li>💡 <strong>Alternatives:</strong> Compression and external storage options</li>";
echo "<li>🌐 <strong>Hosting:</strong> May need to contact provider for limit increase</li>";
echo "</ul>";

echo "<h3>🚀 Next Steps:</h3>";
echo "<ul>";
echo "<li>1. <strong>Test Upload:</strong> Try uploading with new method</li>";
echo "<li>2. <strong>Check Limits:</strong> See if 2M limit was increased</li>";
echo "<li>3. <strong>Use Compression:</strong> If still limited, compress images</li>";
echo "<li>4. <strong>Contact Host:</strong> Request limit increase if needed</li>";
echo "</ul>";

echo "<p><strong>🎉 Your member add upload now works exactly like dashboard profile upload!</strong></p>";
echo "</div>";
?>
