<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$id = $_GET['id'] ?? 0;
$stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Event - SmartUnion</title>
    
    <!-- Resource hints for faster loading -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://unpkg.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" as="style">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style">
    
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
                    <h1 class="h2"><i class="fas fa-calendar me-2"></i>View Event</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="index.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i>Back to Events
                        </a>
                       
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar me-2"></i><?php echo htmlspecialchars($event['title']); ?>
                            </h5>
                            <span class="badge <?php 
                                echo $event['status'] == 'upcoming' ? 'bg-warning' : 
                                    ($event['status'] == 'ongoing' ? 'bg-info' : 'bg-success'); 
                            ?>">
                                <?php echo ucfirst($event['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Event Name</label>
                                    <p class="form-control-plaintext"><?php echo htmlspecialchars($event['title']); ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Status</label>
                                    <p class="form-control-plaintext">
                                        <span class="badge <?php 
                                            echo $event['status'] == 'upcoming' ? 'bg-warning' : 
                                                ($event['status'] == 'ongoing' ? 'bg-info' : 'bg-success'); 
                                        ?>">
                                            <?php echo ucfirst($event['status']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Event Place</label>
                                    <p class="form-control-plaintext">
                                        <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($event['place']); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Event Date & Time</label>
                                    <p class="form-control-plaintext">
                                        <i class="fas fa-calendar me-1"></i><?php echo date('M d, Y', strtotime($event['event_date'])); ?>
                                        <br>
                                        <i class="fas fa-clock me-1"></i><?php echo date('h:i A', strtotime($event['event_date'])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Region</label>
                                    <p class="form-control-plaintext">
                                        <i class="fas fa-globe me-1"></i>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($event['region'] ?? 'Not specified'); ?></span>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Organizing Club</label>
                                    <p class="form-control-plaintext">
                                        <i class="fas fa-users me-1"></i><?php echo htmlspecialchars($event['organizing_club'] ?? 'Not specified'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Event Description</label>
                            <div class="form-control-plaintext">
                                <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                            </div>
                        </div>

                        <div class="row">
                            <?php if ($event['status'] === 'ongoing'): ?>
                            <div class="col-lg-6">
                                <div class="card mt-2">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i class="fas fa-qrcode me-2"></i>Attendance QR Code</h6>
                                        <button id="downloadQrBtn" class="btn btn-sm btn-outline-secondary" onclick="directDownload(); return false;"><i class="fas fa-download me-1"></i>Download</button>
                                        <a href="download_qr.php?event_id=<?php echo (int)$event['id']; ?>" download="event_<?php echo (int)$event['id']; ?>_qr.png" class="btn btn-sm btn-success ms-2" style="display: none;" id="directDownloadLink">
                                            <i class="fas fa-download me-1"></i>Direct Download
                                        </a>
                                    </div>
                                    <div class="card-body text-center">
                                        <div id="eventQr" class="d-inline-block bg-white p-3 rounded" style="min-height: 200px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                                            <?php
                                            // Server-side QR code generation as fallback
                                            $qrPayload = [
                                                'type' => 'attendance',
                                                'event_id' => (int)$event['id'],
                                                'event_name' => $event['title'],
                                                'ts' => time()
                                            ];
                                            $qrText = json_encode($qrPayload);
                                            
                                            // Generate QR code using multiple fallback methods
                                            $qrGenerated = false;
                                            
                                            // Method 1: Try Google Charts API
                                            $googleQrUrl = 'https://chart.googleapis.com/chart?chs=192x192&cht=qr&chl=' . urlencode($qrText);
                                            
                                            // Method 2: Try QR Server API
                                            $qrServerUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=192x192&data=' . urlencode($qrText);
                                            
                                            // Method 3: Try QR Code Generator API
                                            $qrGeneratorUrl = 'https://quickchart.io/qr?text=' . urlencode($qrText) . '&size=192';
                                            
                                            // Test which API is accessible
                                            $testUrl = $qrServerUrl; // Default to QR Server
                                            ?>
                                            <img id="serverQrCode" src="<?php echo $testUrl; ?>" alt="QR Code for Event <?php echo $event['id']; ?>" style="max-width: 192px; height: auto;" onload="preloadDownloadVersions();" onerror="this.style.display='none'; document.getElementById('clientQrCode').style.display='block';" />
                                            <div id="clientQrCode" style="display: none;"></div>
                                            <div id="qrFallback" style="display: none; text-align: center; padding: 20px;">
                                                <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; border: 2px dashed #dee2e6;">
                                                    <h6>QR Code Data</h6>
                                                    <code style="word-break: break-all; font-size: 12px;"><?php echo htmlspecialchars($qrText); ?></code>
                                                    <p class="small text-muted mt-2">Copy this data to generate QR code manually</p>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="small text-muted mt-2">Scan to check-in to this event</p>
                                        <div id="qrError" class="alert alert-warning d-none" role="alert">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <span id="qrErrorMessage">QR Code generation failed. Please refresh the page.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                    
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>Created on <?php echo date('F d, Y \a\t h:i A', strtotime($event['created_at'])); ?>
                            </small>
                        

                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Attendees Modal moved outside the card to prevent blinking -->
    <div class="modal fade" id="attendeesModal" tabindex="-1" aria-labelledby="attendeesModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="attendeesModalLabel">Attendees for <?php echo htmlspecialchars($event['title']); ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <?php
            $attStmt = $db->prepare("SELECT member_id, full_name, club_position, date FROM attendance WHERE event_id = ? ORDER BY date DESC");
            $attStmt->execute([$event['id']]);
            $attendees = $attStmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($attendees)) {
                echo '<div class="text-muted">No attendance records for this event.</div>';
            } else {
                echo '<div class="table-responsive"><table class="table table-sm table-hover"><thead><tr><th>Date</th><th>Member ID</th><th>Full Name</th><th>Club Position</th></tr></thead><tbody>';
                foreach ($attendees as $a) {
                    echo '<tr>';
                    echo '<td>' . date('M d, Y h:i A', strtotime($a['date'])) . '</td>';
                    echo '<td>' . htmlspecialchars($a['member_id']) . '</td>';
                    echo '<td>' . htmlspecialchars($a['full_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($a['club_position']) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table></div>';
            }
            ?>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Preload QR code libraries for faster loading -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js" as="script">
    <link rel="preload" href="https://unpkg.com/qrcode@1.5.3/build/qrcode.min.js" as="script">
    
    <!-- Load QR code libraries synchronously for immediate display -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script src="https://unpkg.com/qrcode@1.5.3/build/qrcode.min.js"></script>
    <script>
        // QR Code initialization function - multiple fallback methods
        function initializeQrCode() {
            console.log('Initializing QR code with multiple fallback methods...');
            
            // Helper function to show QR error
            function showQrError(message) {
                var errorDiv = document.getElementById('qrError');
                var errorMessage = document.getElementById('qrErrorMessage');
                
                if (errorDiv && errorMessage) {
                    errorMessage.textContent = message;
                    errorDiv.classList.remove('d-none');
                }
            }
            
            var container = document.getElementById('eventQr');
            if (!container) {
                console.log('QR container not found');
                return;
            }
            
            console.log('QR container found, checking QR code availability...');
            
            // Check server-side QR code
            var serverQr = document.getElementById('serverQrCode');
            var clientQr = document.getElementById('clientQrCode');
            var qrFallback = document.getElementById('qrFallback');
            
            if (serverQr) {
                console.log('Server-side QR code found');
                
                // Test if server-side QR code loaded successfully
                serverQr.onload = function() {
                    console.log('Server-side QR code loaded successfully');
                    serverQr.style.display = 'block';
                    
                    // Try to enhance with client-side QR code if libraries are available
                    if (typeof QRCode !== 'undefined' || typeof QRCodeLib !== 'undefined') {
                        console.log('Client-side libraries available, enhancing QR code...');
                        setTimeout(enhanceWithClientQr, 1000); // Delay to ensure server QR is visible first
                    }
                };
                
                serverQr.onerror = function() {
                    console.log('Server-side QR code failed to load, trying client-side...');
                    serverQr.style.display = 'none';
                    
                    // Try client-side generation
                    if (typeof QRCode !== 'undefined' || typeof QRCodeLib !== 'undefined') {
                        console.log('Generating client-side QR code...');
                        generateClientQr();
                    } else {
                        console.log('No QR code libraries available, showing fallback data');
                        showQrFallback();
                    }
                };
                
                // Set a timeout to check if QR code loaded
                setTimeout(function() {
                    if (serverQr.style.display !== 'none' && serverQr.complete && serverQr.naturalHeight > 0) {
                        console.log('Server-side QR code confirmed working');
                    } else {
                        console.log('Server-side QR code may have failed, checking alternatives...');
                    }
                }, 2000);
                
            } else {
                console.log('Server-side QR code not found, generating client-side...');
                generateClientQr();
            }
            
            // Download button functionality
            setupDownloadButton(container);
        }
        
        function showQrFallback() {
            var qrFallback = document.getElementById('qrFallback');
            if (qrFallback) {
                qrFallback.style.display = 'block';
                console.log('Showing QR code fallback data');
            }
        }
        
        function enhanceWithClientQr() {
            var payload = {
                type: 'attendance',
                event_id: <?php echo (int)$event['id']; ?>,
                event_name: <?php echo json_encode($event['title']); ?>,
                ts: Date.now()
            };
            var text = JSON.stringify(payload);
            
            if (typeof QRCode !== 'undefined') {
                try {
                    var clientQr = document.getElementById('clientQrCode');
                    var qr = new QRCode(clientQr, {
                        text: text,
                        width: 192,
                        height: 192,
                        correctLevel: QRCode.CorrectLevel.M,
                        colorDark: "#000000",
                        colorLight: "#ffffff"
                    });
                    console.log('Client-side QR code generated successfully');
                    
                    // Cache the QR code
                    cacheQrCode(clientQr);
                    
                    // Show client-side QR code, hide server-side
                    document.getElementById('serverQrCode').style.display = 'none';
                    clientQr.style.display = 'block';
                    
                } catch (error) {
                    console.error('Client-side QR generation failed:', error);
                    // Keep server-side QR code visible
                }
            }
        }
        
        function generateClientQr() {
            var payload = {
                type: 'attendance',
                event_id: <?php echo (int)$event['id']; ?>,
                event_name: <?php echo json_encode($event['title']); ?>,
                ts: Date.now()
            };
            var text = JSON.stringify(payload);
            
            if (typeof QRCode !== 'undefined') {
                console.log('Generating client-side QR code...');
                generateQrWithPrimary(text, document.getElementById('eventQr'));
            } else if (typeof QRCodeLib !== 'undefined') {
                console.log('Generating QR code with fallback library...');
                generateQrWithFallback(text, document.getElementById('eventQr'));
            } else {
                console.error('Both QRCode libraries failed to load');
                showQrError('QR Code libraries failed to load. Server-side QR code should be visible.');
            }
        }
        
        function generateQrWithPrimary(text, container) {
            try {
                var qr = new QRCode(container, {
                    text: text,
                    width: 192,
                    height: 192,
                    correctLevel: QRCode.CorrectLevel.M,
                    colorDark: "#000000",
                    colorLight: "#ffffff"
                });
                console.log('QR code generated successfully with primary library');
                
                // Cache the QR code for faster future loading
                cacheQrCode(container);
                
                // Optimize container styling
                container.style.minHeight = '192px';
                container.style.display = 'flex';
                container.style.alignItems = 'center';
                container.style.justifyContent = 'center';
                
            } catch (error) {
                console.error('Error generating QR code with primary library:', error);
                console.log('Trying fallback library...');
                generateQrWithFallback(text, container);
            }
        }
        
        function generateQrWithFallback(text, container) {
            try {
                // Clear container first
                container.innerHTML = '';
                
                // Generate QR code with fallback library
                QRCodeLib.toCanvas(container, text, {
                    width: 192,
                    height: 192,
                    color: {
                        dark: '#000000',
                        light: '#ffffff'
                    }
                }, function (error) {
                    if (error) {
                        console.error('Error generating QR code with fallback library:', error);
                        showQrError('Failed to generate QR code: ' + error.message);
                    } else {
                        console.log('QR code generated successfully with fallback library');
                        // Cache the QR code for faster future loading
                        cacheQrCode(container);
                    }
                });
                
            } catch (error) {
                console.error('Error generating QR code with fallback library:', error);
                showQrError('Failed to generate QR code: ' + error.message);
            }
        }
        
        function cacheQrCode(container) {
            try {
                var cacheKey = 'qr_event_<?php echo (int)$event['id']; ?>';
                var qrHtml = container.innerHTML;
                
                var cacheData = {
                    qrHtml: qrHtml,
                    timestamp: Date.now()
                };
                
                localStorage.setItem(cacheKey, JSON.stringify(cacheData));
                console.log('QR code cached for faster future loading');
            } catch (e) {
                console.log('Failed to cache QR code:', e);
            }
        }
        
        function setupDownloadButton(container) {
            var btn = document.getElementById('downloadQrBtn');
            if (btn) {
                btn.addEventListener('click', function(e){
                    e.preventDefault(); // Prevent default behavior
                    console.log('Direct download button clicked');
                    
                    // Direct download without loading states
                    directDownload();
                });
            }
        }
        
        function directDownload() {
            try {
                var eventId = <?php echo (int)$event['id']; ?>;
                var filename = 'event_' + eventId + '_qr.png';
                
                console.log('Initiating direct download for event ' + eventId);
                
                // Method 1: Use the hidden direct download link (most reliable)
                var directLink = document.getElementById('directDownloadLink');
                if (directLink) {
                    directLink.click();
                    console.log('Direct download link clicked');
                    showDownloadSuccess(filename);
                    return;
                }
                
                // Method 2: Create direct download link programmatically
                var downloadUrl = 'download_qr.php?event_id=' + eventId + '&_t=' + Date.now();
                var a = document.createElement('a');
                a.href = downloadUrl;
                a.download = filename;
                a.style.display = 'none';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                
                console.log('Direct download initiated');
                showDownloadSuccess(filename);
                
            } catch (error) {
                console.error('Direct download failed:', error);
                
                // Fallback: Try server-side QR code image
                try {
                    var serverQr = document.getElementById('serverQrCode');
                    if (serverQr && serverQr.src) {
                        var a = document.createElement('a');
                        a.href = serverQr.src;
                        a.download = 'event_<?php echo (int)$event['id']; ?>_qr.png';
                        a.style.display = 'none';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        console.log('Fallback server QR download completed');
                        showDownloadSuccess('event_<?php echo (int)$event['id']; ?>_qr.png');
                        return;
                    }
                } catch (fallbackError) {
                    console.error('Fallback download failed:', fallbackError);
                    
                    // Final fallback: Generate QR data for download
                    generateQrDataForDownload();
                }
            }
        }
        
        function downloadImage(src, filename) {
            try {
                // Method 1: Fast direct download with cache busting
                var a = document.createElement('a');
                a.href = src + (src.includes('?') ? '&' : '?') + '_t=' + Date.now();
                a.download = filename;
                a.style.display = 'none';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                console.log('Fast image download completed');
                
                // Show success message immediately
                showDownloadSuccess(filename);
                
            } catch (error) {
                console.error('Fast download failed:', error);
                
                // Method 2: Fetch and download (modern browsers)
                try {
                    fetch(src)
                        .then(response => response.blob())
                        .then(blob => {
                            var url = URL.createObjectURL(blob);
                            var a = document.createElement('a');
                            a.href = url;
                            a.download = filename;
                            a.style.display = 'none';
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                            URL.revokeObjectURL(url);
                            console.log('Fetch download completed');
                            showDownloadSuccess(filename);
                        })
                        .catch(fetchError => {
                            console.error('Fetch download failed:', fetchError);
                            // Fallback to manual methods
                            downloadImageFallback(src, filename);
                        });
                } catch (fetchError) {
                    console.error('Fetch not supported:', fetchError);
                    downloadImageFallback(src, filename);
                }
            }
        }
        
        function downloadImageFallback(src, filename) {
            try {
                // Method 3: Open in new tab (fallback)
                var newWindow = window.open(src, '_blank');
                if (newWindow) {
                    console.log('Opened QR code in new tab for manual download');
                    alert('QR code opened in new tab. Right-click and save as ' + filename);
                } else {
                    throw new Error('Popup blocked');
                }
            } catch (popupError) {
                console.error('Popup blocked:', popupError);
                
                // Method 4: Copy to clipboard (final fallback)
                copyQrDataToClipboard();
            }
        }
        
        function downloadCanvas(canvas, filename) {
            try {
                // Fast canvas to blob conversion with optimized settings
                canvas.toBlob(function(blob) {
                    if (blob) {
                        var url = URL.createObjectURL(blob);
                        var a = document.createElement('a');
                        a.href = url;
                        a.download = filename;
                        a.style.display = 'none';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                        console.log('Fast canvas download completed');
                        showDownloadSuccess(filename);
                    } else {
                        throw new Error('Canvas blob creation failed');
                    }
                }, 'image/png', 0.9); // Optimized quality for faster processing
                
            } catch (error) {
                console.error('Fast canvas download failed:', error);
                
                // Fallback: Convert to data URL with optimization
                try {
                    var dataUrl = canvas.toDataURL('image/png', 0.9); // Optimized quality
                    var a = document.createElement('a');
                    a.href = dataUrl;
                    a.download = filename;
                    a.style.display = 'none';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    console.log('Canvas downloaded via optimized data URL');
                    showDownloadSuccess(filename);
                } catch (dataUrlError) {
                    console.error('Optimized data URL download failed:', dataUrlError);
                    copyQrDataToClipboard();
                }
            }
        }
        
        function downloadFromServer() {
            try {
                var eventId = <?php echo (int)$event['id']; ?>;
                var downloadUrl = 'download_qr.php?event_id=' + eventId;
                
                console.log('Attempting fast server-side download from:', downloadUrl);
                
                // Method 1: Direct download link with cache busting
                var a = document.createElement('a');
                a.href = downloadUrl + '&_t=' + Date.now(); // Cache busting
                a.download = 'event_' + eventId + '_qr.png';
                a.style.display = 'none';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                
                console.log('Fast server-side download initiated');
                showDownloadSuccess('event_' + eventId + '_qr.png');
                
            } catch (error) {
                console.error('Server-side download failed:', error);
                
                // Fallback: Generate QR code data for manual download
                generateQrDataForDownload();
            }
        }
        
        function generateQrDataForDownload() {
            var payload = {
                type: 'attendance',
                event_id: <?php echo (int)$event['id']; ?>,
                event_name: <?php echo json_encode($event['title']); ?>,
                ts: Date.now()
            };
            var qrText = JSON.stringify(payload);
            
            // Create a downloadable text file
            var blob = new Blob([qrText], { type: 'text/plain' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'event_<?php echo (int)$event['id']; ?>_qr_data.txt';
            a.style.display = 'none';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            console.log('QR code data downloaded as text file');
            alert('QR code data downloaded as text file. You can use this data to generate a QR code manually.');
        }
        
        function copyQrDataToClipboard() {
            var payload = {
                type: 'attendance',
                event_id: <?php echo (int)$event['id']; ?>,
                event_name: <?php echo json_encode($event['title']); ?>,
                ts: Date.now()
            };
            var qrText = JSON.stringify(payload);
            
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(qrText).then(function() {
                    console.log('QR code data copied to clipboard');
                    alert('QR code data copied to clipboard. You can paste it into a QR code generator.');
                }).catch(function(error) {
                    console.error('Clipboard copy failed:', error);
                    showQrDataModal(qrText);
                });
            } else {
                console.log('Clipboard API not available, showing modal');
                showQrDataModal(qrText);
            }
        }
        
        function showQrDataModal(qrText) {
            var modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
            `;
            
            var content = document.createElement('div');
            content.style.cssText = `
                background: white;
                padding: 20px;
                border-radius: 8px;
                max-width: 500px;
                width: 90%;
                max-height: 80%;
                overflow-y: auto;
            `;
            
            content.innerHTML = `
                <h5>QR Code Data</h5>
                <p>Copy this data to generate a QR code manually:</p>
                <textarea readonly style="width: 100%; height: 100px; margin: 10px 0; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">${qrText}</textarea>
                <div style="text-align: right;">
                    <button onclick="this.closest('.modal').remove()" style="padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Close</button>
                </div>
            `;
            
            modal.appendChild(content);
            document.body.appendChild(modal);
            
            // Auto-select text for easy copying
            var textarea = content.querySelector('textarea');
            textarea.select();
        }
        
        function showDownloadSuccess(filename) {
            // Show success message
            var successDiv = document.createElement('div');
            successDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #d4edda;
                color: #155724;
                padding: 12px 20px;
                border-radius: 4px;
                border: 1px solid #c3e6cb;
                z-index: 10000;
                font-size: 14px;
            `;
            successDiv.innerHTML = '<i class="fas fa-check me-2"></i>Downloaded: ' + filename;
            document.body.appendChild(successDiv);
            
            // Remove after 3 seconds
            setTimeout(function() {
                if (successDiv.parentNode) {
                    successDiv.parentNode.removeChild(successDiv);
                }
            }, 3000);
        }
        
        function resetButton(btn, originalText) {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
        
        function preloadDownloadVersions() {
            // Preload different versions of QR code for faster downloads
            var eventId = <?php echo (int)$event['id']; ?>;
            var qrPayload = {
                type: 'attendance',
                event_id: eventId,
                event_name: <?php echo json_encode($event['title']); ?>,
                ts: Date.now()
            };
            var qrText = JSON.stringify(qrPayload);
            
            // Preload high-resolution version for download
            var highResUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=512x512&data=' + encodeURIComponent(qrText);
            var highResImg = new Image();
            highResImg.src = highResUrl;
            highResImg.onload = function() {
                console.log('High-resolution QR code preloaded for fast download');
            };
            
            // Preload download endpoint
            var downloadUrl = 'download_qr.php?event_id=' + eventId;
            var downloadImg = new Image();
            downloadImg.src = downloadUrl;
            downloadImg.onload = function() {
                console.log('Download endpoint preloaded');
            };
            
            console.log('Download versions preloaded for faster downloads');
        }
        
        // Initialize QR code immediately when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing QR code immediately...');
            
            // Initialize QR code immediately since libraries are loaded synchronously
            initializeQrCode();
        });
        
        // Fallback: Initialize immediately if DOM is already loaded
        if (document.readyState === 'loading') {
            // DOM is still loading, wait for DOMContentLoaded
        } else {
            // DOM is already loaded, initialize immediately
            console.log('DOM already loaded, initializing QR code immediately...');
            initializeQrCode();
        }
    </script>
</body>
</html>
