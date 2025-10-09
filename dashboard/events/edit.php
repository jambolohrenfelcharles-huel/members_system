<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
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

$errors = [];
$success = '';

if ($_POST) {
    $name = trim($_POST['name']);
    $place = trim($_POST['place']);
    $event_date = $_POST['event_date'];
    $description = trim($_POST['description']);
    $region = trim($_POST['region']);
    $organizing_club = trim($_POST['organizing_club']);
    
    // Validation
    if (empty($name)) $errors[] = "Event name is required";
    if (empty($place)) $errors[] = "Event place is required";
    if (empty($event_date)) $errors[] = "Event date is required";
    if (empty($description)) $errors[] = "Event description is required";
    if (empty($region)) $errors[] = "Region is required";
    if (empty($organizing_club)) $errors[] = "Organizing club is required";
    
    if (empty($errors)) {
        // Determine status based on event date
        $today = date('Y-m-d');
        $eventDate = date('Y-m-d', strtotime($event_date));
        
        if ($eventDate < $today) {
            $status = 'completed';
        } elseif ($eventDate == $today) {
            $status = 'ongoing';
        } else {
            $status = 'upcoming';
        }
        
        $query = "UPDATE events SET title=?, place=?, status=?, event_date=?, description=?, region=?, organizing_club=? WHERE id=?";
        
        $stmt = $db->prepare($query);
        $result = $stmt->execute([$name, $place, $status, $event_date, $description, $region, $organizing_club, $id]);
        
        if ($result) {
            header('Location: index.php?updated=1');
            exit();
        } else {
            $errors[] = "Failed to update event";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - SmartUnion</title>
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
                    <h1 class="h2"><i class="fas fa-calendar-edit me-2"></i>Edit Event</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                       
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h6>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-calendar me-2"></i>Event Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">Event Name *</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($event['title']); ?>" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="region" class="form-label">Region *</label>
                                        <select class="form-select" id="region" name="region" required>
                                            <option value="">Select Region</option>
                                            <optgroup label="Luzon Regions">
                                                <option value="National Capital Region (NCR)" <?php echo ($event['region'] == 'National Capital Region (NCR)') ? 'selected' : ''; ?>>National Capital Region (NCR)</option>
                                                <option value="Southern Luzon Region 1 (SLR-1)" <?php echo ($event['region'] == 'Southern Luzon Region 1 (SLR-1)') ? 'selected' : ''; ?>>Southern Luzon Region 1 (SLR-1)</option>
                                                <option value="Southern Luzon Region 2 (SLR-2)" <?php echo ($event['region'] == 'Southern Luzon Region 2 (SLR-2)') ? 'selected' : ''; ?>>Southern Luzon Region 2 (SLR-2)</option>
                                                <option value="Central Luzon Region (CLR)" <?php echo ($event['region'] == 'Central Luzon Region (CLR)') ? 'selected' : ''; ?>>Central Luzon Region (CLR)</option>
                                                <option value="Bicol Region" <?php echo ($event['region'] == 'Bicol Region') ? 'selected' : ''; ?>>Bicol Region</option>
                                            </optgroup>
                                            <optgroup label="Visayas Regions">
                                                <option value="Central Visayas Region II (CVR-II)" <?php echo ($event['region'] == 'Central Visayas Region II (CVR-II)') ? 'selected' : ''; ?>>Central Visayas Region II (CVR-II)</option>
                                                <option value="Central Visayas Region VI (CVR-VI)" <?php echo ($event['region'] == 'Central Visayas Region VI (CVR-VI)') ? 'selected' : ''; ?>>Central Visayas Region VI (CVR-VI)</option>
                                                <option value="Central Visayas Region VII (CVR-VII)" <?php echo ($event['region'] == 'Central Visayas Region VII (CVR-VII)') ? 'selected' : ''; ?>>Central Visayas Region VII (CVR-VII)</option>
                                                <option value="Western Visayas Region VI (WVR-VI)" <?php echo ($event['region'] == 'Western Visayas Region VI (WVR-VI)') ? 'selected' : ''; ?>>Western Visayas Region VI (WVR-VI)</option>
                                                <option value="Eastern Visayas Region VIII (EVR-VIII)" <?php echo ($event['region'] == 'Eastern Visayas Region VIII (EVR-VIII)') ? 'selected' : ''; ?>>Eastern Visayas Region VIII (EVR-VIII)</option>
                                            </optgroup>
                                            <optgroup label="Mindanao Regions">
                                                <option value="Northern Mindanao Region I (NMR-I)" <?php echo ($event['region'] == 'Northern Mindanao Region I (NMR-I)') ? 'selected' : ''; ?>>Northern Mindanao Region I (NMR-I)</option>
                                                <option value="Northern Mindanao Region II (NMR-II)" <?php echo ($event['region'] == 'Northern Mindanao Region II (NMR-II)') ? 'selected' : ''; ?>>Northern Mindanao Region II (NMR-II)</option>
                                                <option value="Northern Mindanao Region III (NMR-III)" <?php echo ($event['region'] == 'Northern Mindanao Region III (NMR-III)') ? 'selected' : ''; ?>>Northern Mindanao Region III (NMR-III)</option>
                                                <option value="Northern Mindanao Region IV (NMR-IV)" <?php echo ($event['region'] == 'Northern Mindanao Region IV (NMR-IV)') ? 'selected' : ''; ?>>Northern Mindanao Region IV (NMR-IV)</option>
                                                <option value="Western Mindanao Region I (WMR-I)" <?php echo ($event['region'] == 'Western Mindanao Region I (WMR-I)') ? 'selected' : ''; ?>>Western Mindanao Region I (WMR-I)</option>
                                                <option value="Western Mindanao Region II (WMR-II)" <?php echo ($event['region'] == 'Western Mindanao Region II (WMR-II)') ? 'selected' : ''; ?>>Western Mindanao Region II (WMR-II)</option>
                                                <option value="Western Mindanao Region III (WMR-III)" <?php echo ($event['region'] == 'Western Mindanao Region III (WMR-III)') ? 'selected' : ''; ?>>Western Mindanao Region III (WMR-III)</option>
                                                <option value="Western Mindanao Region IV (WMR-IV)" <?php echo ($event['region'] == 'Western Mindanao Region IV (WMR-IV)') ? 'selected' : ''; ?>>Western Mindanao Region IV (WMR-IV)</option>
                                                <option value="Camiguin Region" <?php echo ($event['region'] == 'Camiguin Region') ? 'selected' : ''; ?>>Camiguin Region</option>
                                                <option value="Amihan Bukidnon Region" <?php echo ($event['region'] == 'Amihan Bukidnon Region') ? 'selected' : ''; ?>>Amihan Bukidnon Region</option>
                                                <option value="Northern Bukidnon Region" <?php echo ($event['region'] == 'Northern Bukidnon Region') ? 'selected' : ''; ?>>Northern Bukidnon Region</option>
                                                <option value="Southern Bukidnon Region" <?php echo ($event['region'] == 'Southern Bukidnon Region') ? 'selected' : ''; ?>>Southern Bukidnon Region</option>
                                                <option value="CARAGA-I" <?php echo ($event['region'] == 'CARAGA-I') ? 'selected' : ''; ?>>CARAGA-I</option>
                                                <option value="CARAGA-II" <?php echo ($event['region'] == 'CARAGA-II') ? 'selected' : ''; ?>>CARAGA-II</option>
                                                <option value="CARAGA-III" <?php echo ($event['region'] == 'CARAGA-III') ? 'selected' : ''; ?>>CARAGA-III</option>
                                                <option value="Davao-I" <?php echo ($event['region'] == 'Davao-I') ? 'selected' : ''; ?>>Davao-I</option>
                                                <option value="Davao-II" <?php echo ($event['region'] == 'Davao-II') ? 'selected' : ''; ?>>Davao-II</option>
                                                <option value="Davao-III" <?php echo ($event['region'] == 'Davao-III') ? 'selected' : ''; ?>>Davao-III</option>
                                                <option value="SOCCSKSARGEN" <?php echo ($event['region'] == 'SOCCSKSARGEN') ? 'selected' : ''; ?>>SOCCSKSARGEN</option>
                                                <option value="BARMM-I" <?php echo ($event['region'] == 'BARMM-I') ? 'selected' : ''; ?>>BARMM-I</option>
                                                <option value="BARMM-II" <?php echo ($event['region'] == 'BARMM-II') ? 'selected' : ''; ?>>BARMM-II</option>
                                                <option value="BARMM-III" <?php echo ($event['region'] == 'BARMM-III') ? 'selected' : ''; ?>>BARMM-III</option>
                                            </optgroup>
                                            <optgroup label="International Regions / Chapters">
                                                <option value="United States" <?php echo ($event['region'] == 'United States') ? 'selected' : ''; ?>>United States</option>
                                                <option value="Canada" <?php echo ($event['region'] == 'Canada') ? 'selected' : ''; ?>>Canada</option>
                                                <option value="Middle East" <?php echo ($event['region'] == 'Middle East') ? 'selected' : ''; ?>>Middle East</option>
                                                <option value="Asia-Pacific" <?php echo ($event['region'] == 'Asia-Pacific') ? 'selected' : ''; ?>>Asia-Pacific</option>
                                            </optgroup>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="organizing_club" class="form-label">Organizing Club *</label>
                                        <input type="text" class="form-control" id="organizing_club" name="organizing_club" value="<?php echo htmlspecialchars($event['organizing_club'] ?? ''); ?>" required>
                                        <div class="form-text">Enter the name of the club organizing this event</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="place" class="form-label">Event Place *</label>
                                        <input type="text" class="form-control" id="place" name="place" value="<?php echo htmlspecialchars($event['place']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="event_date" class="form-label">Event Date & Time *</label>
                                        <input type="datetime-local" class="form-control" id="event_date" name="event_date" value="<?php echo date('Y-m-d\TH:i', strtotime($event['event_date'])); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Event Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($event['description']); ?></textarea>
                            </div>

                            <div class="d-flex justify-content-end">
                                <a href="index.php" class="btn btn-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Update Event
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
