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
                                        <button id="downloadQrBtn" class="btn btn-sm btn-outline-secondary"><i class="fas fa-download me-1"></i>Download</button>
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
                                            <img id="serverQrCode" src="<?php echo $testUrl; ?>" alt="QR Code for Event <?php echo $event['id']; ?>" style="max-width: 192px; height: auto;" onerror="this.style.display='none'; document.getElementById('clientQrCode').style.display='block';" />
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
                btn.addEventListener('click', function(){
                    console.log('Download button clicked');
                    
                    // Check for server-side QR code first
                    var serverQr = document.getElementById('serverQrCode');
                    if (serverQr && serverQr.src) {
                        var a = document.createElement('a');
                        a.href = serverQr.src;
                        a.download = 'event_<?php echo (int)$event['id']; ?>_qr.png';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        console.log('Server-side QR code downloaded');
                        return;
                    }
                    
                    // Check for client-side QR code
                    var clientQr = document.getElementById('clientQrCode');
                    if (clientQr) {
                        var img = clientQr.querySelector('img');
                        var canvas = clientQr.querySelector('canvas');
                        
                        if (img && img.src) {
                            var a = document.createElement('a');
                            a.href = img.src;
                            a.download = 'event_<?php echo (int)$event['id']; ?>_qr.png';
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                            console.log('Client-side QR code downloaded as image');
                        } else if (canvas) {
                            var a2 = document.createElement('a');
                            a2.href = canvas.toDataURL('image/png');
                            a2.download = 'event_<?php echo (int)$event['id']; ?>_qr.png';
                            document.body.appendChild(a2);
                            a2.click();
                            document.body.removeChild(a2);
                            console.log('Client-side QR code downloaded as canvas');
                        }
                    }
                    
                    // Fallback: check container for any QR code
                    var img = container.querySelector('img');
                    var canvas = container.querySelector('canvas');
                    
                    if (img && img.src) {
                        var a = document.createElement('a');
                        a.href = img.src;
                        a.download = 'event_<?php echo (int)$event['id']; ?>_qr.png';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        console.log('QR code downloaded as image');
                    } else if (canvas) {
                        var a2 = document.createElement('a');
                        a2.href = canvas.toDataURL('image/png');
                        a2.download = 'event_<?php echo (int)$event['id']; ?>_qr.png';
                        document.body.appendChild(a2);
                        a2.click();
                        document.body.removeChild(a2);
                        console.log('QR code downloaded as canvas');
                    } else {
                        // Check if fallback data is available
                        var qrFallback = document.getElementById('qrFallback');
                        if (qrFallback && qrFallback.style.display !== 'none') {
                            console.log('No QR code image available, but fallback data is shown');
                            alert('QR code image not available for download. Please copy the QR code data manually.');
                        } else {
                            console.error('No QR code element found for download');
                            alert('QR code not available for download');
                        }
                    }
                });
            }
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
