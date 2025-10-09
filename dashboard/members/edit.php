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
$stmt = $db->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$id]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    header('Location: index.php');
    exit();
}

$errors = [];
$success = '';

if ($_POST) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $club_position = trim($_POST['club_position']);
    $home_address = trim($_POST['home_address']);
    $contact_number = trim($_POST['contact_number']);
    $philhealth_number = trim($_POST['philhealth_number']);
    $pagibig_number = trim($_POST['pagibig_number']);
    $tin_number = trim($_POST['tin_number']);
    $birthdate = $_POST['birthdate'];
    $height = $_POST['height'];
    $weight = $_POST['weight'];
    $blood_type = $_POST['blood_type'];
    $religion = trim($_POST['religion']);
    $emergency_contact_person = trim($_POST['emergency_contact_person']);
    $emergency_contact_number = trim($_POST['emergency_contact_number']);
    $club_affiliation = trim($_POST['club_affiliation']);
    $region = trim($_POST['region']);
    
    // Validation
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email)) $errors[] = "Email address is required";
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email address";
    if (empty($club_position)) $errors[] = "Club position is required";
    if (empty($home_address)) $errors[] = "Home address is required";
    if (empty($contact_number)) $errors[] = "Contact number is required";
    if (empty($birthdate)) $errors[] = "Birthdate is required";
    if (empty($emergency_contact_person)) $errors[] = "Emergency contact person is required";
    if (empty($emergency_contact_number)) $errors[] = "Emergency contact number is required";
    if (empty($region)) $errors[] = "Region is required";
    
    // Check if email already exists (excluding current member)
    if (!empty($email) && empty($errors)) {
        $checkEmail = $db->prepare("SELECT id FROM members WHERE email = ? AND id != ?");
        $checkEmail->execute([$email, $id]);
        if ($checkEmail->fetch(PDO::FETCH_ASSOC)) {
            $errors[] = "Email address is already registered";
        }
    }
    
    if (empty($errors)) {
        // Check if profile_photo column exists in DB
        $profilePhotoColumnExists = false;
        try {
            $colCheck = $db->query("SHOW COLUMNS FROM members LIKE 'profile_photo'");
            $profilePhotoColumnExists = $colCheck->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (Exception $e) {}
        if ($profilePhotoColumnExists) {
            $query = "UPDATE members SET name=?, email=?, club_position=?, home_address=?, contact_number=?, philhealth_number=?, pagibig_number=?, tin_number=?, birthdate=?, height=?, weight=?, blood_type=?, religion=?, emergency_contact_person=?, emergency_contact_number=?, club_affiliation=?, region=?, profile_photo=? WHERE id=?";
            $params = [
                $name, $email, $club_position, $home_address, $contact_number, $philhealth_number, 
                $pagibig_number, $tin_number, $birthdate, $height, $weight, $blood_type, 
                $religion, $emergency_contact_person, $emergency_contact_number, 
                $club_affiliation, $region, $id
            ];
        } else {
            $query = "UPDATE members SET name=?, email=?, club_position=?, home_address=?, contact_number=?, philhealth_number=?, pagibig_number=?, tin_number=?, birthdate=?, height=?, weight=?, blood_type=?, religion=?, emergency_contact_person=?, emergency_contact_number=?, club_affiliation=?, region=? WHERE id=?";
            $params = [
                $name, $email, $club_position, $home_address, $contact_number, $philhealth_number, 
                $pagibig_number, $tin_number, $birthdate, $height, $weight, $blood_type, 
                $religion, $emergency_contact_person, $emergency_contact_number, 
                $club_affiliation, $region, $id
            ];
        }
        $stmt = $db->prepare($query);
        $result = $stmt->execute($params);
        
        if ($result) {
            header('Location: index.php?updated=1');
            exit();
        } else {
            $errors[] = "Failed to update member";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Member - SmartUnion</title>
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
                    <h1 class="h2"><i class="fas fa-user-edit me-2"></i>Edit Member</h1>
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
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Member Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($member['name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($member['email'] ?? ''); ?>" required>
                                        <div class="form-text">We'll send notifications to this email address</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="club_position" class="form-label">Club Position *</label>
                                        <select class="form-select" id="club_position" name="club_position" required>
                                            <option value="">Select Position</option>
                                            <option value="President / Worthy President" <?php echo ($member['club_position'] == 'President / Worthy President') ? 'selected' : ''; ?>>President / Worthy President</option>
                                            <option value="Vice President / Worthy Vice President" <?php echo ($member['club_position'] == 'Vice President / Worthy Vice President') ? 'selected' : ''; ?>>Vice President / Worthy Vice President</option>
                                            <option value="Secretary / Worthy Secretary" <?php echo ($member['club_position'] == 'Secretary / Worthy Secretary') ? 'selected' : ''; ?>>Secretary / Worthy Secretary</option>
                                            <option value="Treasurer / Worthy Treasurer" <?php echo ($member['club_position'] == 'Treasurer / Worthy Treasurer') ? 'selected' : ''; ?>>Treasurer / Worthy Treasurer</option>
                                            <option value="Chaplain" <?php echo ($member['club_position'] == 'Chaplain') ? 'selected' : ''; ?>>Chaplain</option>
                                            <option value="Conductor" <?php echo ($member['club_position'] == 'Conductor') ? 'selected' : ''; ?>>Conductor</option>
                                            <option value="Inside Guard" <?php echo ($member['club_position'] == 'Inside Guard') ? 'selected' : ''; ?>>Inside Guard</option>
                                            <option value="Outside Guard" <?php echo ($member['club_position'] == 'Outside Guard') ? 'selected' : ''; ?>>Outside Guard</option>
                                            <option value="Trustees" <?php echo ($member['club_position'] == 'Trustees') ? 'selected' : ''; ?>>Trustees</option>
                                            <option value="Auditors" <?php echo ($member['club_position'] == 'Auditors') ? 'selected' : ''; ?>>Auditors</option>
                                            <option value="Marshal" <?php echo ($member['club_position'] == 'Marshal') ? 'selected' : ''; ?>>Marshal</option>
                                            <option value="Past President" <?php echo ($member['club_position'] == 'Past President') ? 'selected' : ''; ?>>Past President</option>
                                            <option value="President / Madam President" <?php echo ($member['club_position'] == 'President / Madam President') ? 'selected' : ''; ?>>President / Madam President</option>
                                            <option value="Vice President / Madam Vice President" <?php echo ($member['club_position'] == 'Vice President / Madam Vice President') ? 'selected' : ''; ?>>Vice President / Madam Vice President</option>
                                            <option value="Secretary / Madam Secretary" <?php echo ($member['club_position'] == 'Secretary / Madam Secretary') ? 'selected' : ''; ?>>Secretary / Madam Secretary</option>
                                            <option value="Treasurer / Madam Treasurer" <?php echo ($member['club_position'] == 'Treasurer / Madam Treasurer') ? 'selected' : ''; ?>>Treasurer / Madam Treasurer</option>
                                            <option value="Chaplain / Madam Chaplain" <?php echo ($member['club_position'] == 'Chaplain / Madam Chaplain') ? 'selected' : ''; ?>>Chaplain / Madam Chaplain</option>
                                            <option value="Conductor / Madam Conductor" <?php echo ($member['club_position'] == 'Conductor / Madam Conductor') ? 'selected' : ''; ?>>Conductor / Madam Conductor</option>
                                            <option value="Inside Guard / Madam Inside Guard" <?php echo ($member['club_position'] == 'Inside Guard / Madam Inside Guard') ? 'selected' : ''; ?>>Inside Guard / Madam Inside Guard</option>
                                            <option value="Outside Guard / Madam Outside Guard" <?php echo ($member['club_position'] == 'Outside Guard / Madam Outside Guard') ? 'selected' : ''; ?>>Outside Guard / Madam Outside Guard</option>
                                            <option value="Trustees / Auditors" <?php echo ($member['club_position'] == 'Trustees / Auditors') ? 'selected' : ''; ?>>Trustees / Auditors</option>
                                            <option value="Membership Chairman" <?php echo ($member['club_position'] == 'Membership Chairman') ? 'selected' : ''; ?>>Membership Chairman</option>
                                            <option value="Program/Activity Coordinator" <?php echo ($member['club_position'] == 'Program/Activity Coordinator') ? 'selected' : ''; ?>>Program/Activity Coordinator</option>
                                            <option value="Fraternal Committee Chairs" <?php echo ($member['club_position'] == 'Fraternal Committee Chairs') ? 'selected' : ''; ?>>Fraternal Committee Chairs</option>
                                            <option value="Member" <?php echo ($member['club_position'] == 'Member') ? 'selected' : ''; ?>>Member</option>
                                        </select>
                                    </div>
                                </div>
                            </div>


                            <div class="mb-3">
                                <label for="home_address" class="form-label">Home Address *</label>
                                <textarea class="form-control" id="home_address" name="home_address" rows="3" required><?php echo htmlspecialchars($member['home_address']); ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="contact_number" class="form-label">Contact Number *</label>
                                        <input type="tel" class="form-control" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($member['contact_number']); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="region" class="form-label">Region *</label>
                                        <select class="form-select" id="region" name="region" required>
                                            <option value="">Select Region</option>
                                            <optgroup label="Luzon Regions">
                                                <option value="National Capital Region (NCR)" <?php echo ($member['region'] == 'National Capital Region (NCR)') ? 'selected' : ''; ?>>National Capital Region (NCR)</option>
                                                <option value="Southern Luzon Region 1 (SLR-1)" <?php echo ($member['region'] == 'Southern Luzon Region 1 (SLR-1)') ? 'selected' : ''; ?>>Southern Luzon Region 1 (SLR-1)</option>
                                                <option value="Southern Luzon Region 2 (SLR-2)" <?php echo ($member['region'] == 'Southern Luzon Region 2 (SLR-2)') ? 'selected' : ''; ?>>Southern Luzon Region 2 (SLR-2)</option>
                                                <option value="Central Luzon Region (CLR)" <?php echo ($member['region'] == 'Central Luzon Region (CLR)') ? 'selected' : ''; ?>>Central Luzon Region (CLR)</option>
                                                <option value="Bicol Region" <?php echo ($member['region'] == 'Bicol Region') ? 'selected' : ''; ?>>Bicol Region</option>
                                            </optgroup>
                                            <optgroup label="Visayas Regions">
                                                <option value="Central Visayas Region II (CVR-II)" <?php echo ($member['region'] == 'Central Visayas Region II (CVR-II)') ? 'selected' : ''; ?>>Central Visayas Region II (CVR-II)</option>
                                                <option value="Central Visayas Region VI (CVR-VI)" <?php echo ($member['region'] == 'Central Visayas Region VI (CVR-VI)') ? 'selected' : ''; ?>>Central Visayas Region VI (CVR-VI)</option>
                                                <option value="Central Visayas Region VII (CVR-VII)" <?php echo ($member['region'] == 'Central Visayas Region VII (CVR-VII)') ? 'selected' : ''; ?>>Central Visayas Region VII (CVR-VII)</option>
                                                <option value="Western Visayas Region VI (WVR-VI)" <?php echo ($member['region'] == 'Western Visayas Region VI (WVR-VI)') ? 'selected' : ''; ?>>Western Visayas Region VI (WVR-VI)</option>
                                                <option value="Eastern Visayas Region VIII (EVR-VIII)" <?php echo ($member['region'] == 'Eastern Visayas Region VIII (EVR-VIII)') ? 'selected' : ''; ?>>Eastern Visayas Region VIII (EVR-VIII)</option>
                                            </optgroup>
                                            <optgroup label="Mindanao Regions">
                                                <option value="Northern Mindanao Region I (NMR-I)" <?php echo ($member['region'] == 'Northern Mindanao Region I (NMR-I)') ? 'selected' : ''; ?>>Northern Mindanao Region I (NMR-I)</option>
                                                <option value="Northern Mindanao Region II (NMR-II)" <?php echo ($member['region'] == 'Northern Mindanao Region II (NMR-II)') ? 'selected' : ''; ?>>Northern Mindanao Region II (NMR-II)</option>
                                                <option value="Northern Mindanao Region III (NMR-III)" <?php echo ($member['region'] == 'Northern Mindanao Region III (NMR-III)') ? 'selected' : ''; ?>>Northern Mindanao Region III (NMR-III)</option>
                                                <option value="Northern Mindanao Region IV (NMR-IV)" <?php echo ($member['region'] == 'Northern Mindanao Region IV (NMR-IV)') ? 'selected' : ''; ?>>Northern Mindanao Region IV (NMR-IV)</option>
                                                <option value="Western Mindanao Region I (WMR-I)" <?php echo ($member['region'] == 'Western Mindanao Region I (WMR-I)') ? 'selected' : ''; ?>>Western Mindanao Region I (WMR-I)</option>
                                                <option value="Western Mindanao Region II (WMR-II)" <?php echo ($member['region'] == 'Western Mindanao Region II (WMR-II)') ? 'selected' : ''; ?>>Western Mindanao Region II (WMR-II)</option>
                                                <option value="Western Mindanao Region III (WMR-III)" <?php echo ($member['region'] == 'Western Mindanao Region III (WMR-III)') ? 'selected' : ''; ?>>Western Mindanao Region III (WMR-III)</option>
                                                <option value="Western Mindanao Region IV (WMR-IV)" <?php echo ($member['region'] == 'Western Mindanao Region IV (WMR-IV)') ? 'selected' : ''; ?>>Western Mindanao Region IV (WMR-IV)</option>
                                                <option value="Camiguin Region" <?php echo ($member['region'] == 'Camiguin Region') ? 'selected' : ''; ?>>Camiguin Region</option>
                                                <option value="Amihan Bukidnon Region" <?php echo ($member['region'] == 'Amihan Bukidnon Region') ? 'selected' : ''; ?>>Amihan Bukidnon Region</option>
                                                <option value="Northern Bukidnon Region" <?php echo ($member['region'] == 'Northern Bukidnon Region') ? 'selected' : ''; ?>>Northern Bukidnon Region</option>
                                                <option value="Southern Bukidnon Region" <?php echo ($member['region'] == 'Southern Bukidnon Region') ? 'selected' : ''; ?>>Southern Bukidnon Region</option>
                                                <option value="CARAGA-I" <?php echo ($member['region'] == 'CARAGA-I') ? 'selected' : ''; ?>>CARAGA-I</option>
                                                <option value="CARAGA-II" <?php echo ($member['region'] == 'CARAGA-II') ? 'selected' : ''; ?>>CARAGA-II</option>
                                                <option value="CARAGA-III" <?php echo ($member['region'] == 'CARAGA-III') ? 'selected' : ''; ?>>CARAGA-III</option>
                                                <option value="Davao-I" <?php echo ($member['region'] == 'Davao-I') ? 'selected' : ''; ?>>Davao-I</option>
                                                <option value="Davao-II" <?php echo ($member['region'] == 'Davao-II') ? 'selected' : ''; ?>>Davao-II</option>
                                                <option value="Davao-III" <?php echo ($member['region'] == 'Davao-III') ? 'selected' : ''; ?>>Davao-III</option>
                                                <option value="SOCCSKSARGEN" <?php echo ($member['region'] == 'SOCCSKSARGEN') ? 'selected' : ''; ?>>SOCCSKSARGEN</option>
                                                <option value="BARMM-I" <?php echo ($member['region'] == 'BARMM-I') ? 'selected' : ''; ?>>BARMM-I</option>
                                                <option value="BARMM-II" <?php echo ($member['region'] == 'BARMM-II') ? 'selected' : ''; ?>>BARMM-II</option>
                                                <option value="BARMM-III" <?php echo ($member['region'] == 'BARMM-III') ? 'selected' : ''; ?>>BARMM-III</option>
                                            </optgroup>
                                            <optgroup label="International Regions / Chapters">
                                                <option value="United States" <?php echo ($member['region'] == 'United States') ? 'selected' : ''; ?>>United States</option>
                                                <option value="Canada" <?php echo ($member['region'] == 'Canada') ? 'selected' : ''; ?>>Canada</option>
                                                <option value="Middle East" <?php echo ($member['region'] == 'Middle East') ? 'selected' : ''; ?>>Middle East</option>
                                                <option value="Asia-Pacific" <?php echo ($member['region'] == 'Asia-Pacific') ? 'selected' : ''; ?>>Asia-Pacific</option>
                                            </optgroup>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="club_affiliation" class="form-label">Club Affiliation*</label>
                                        <select class="form-select" id="club_affiliation" name="club_affiliation" required data-current="<?php echo htmlspecialchars($member['club_affiliation']); ?>">
                                            <option value="">Select Club</option>
                                        </select>
                                        <div class="form-text">Select a region first to narrow the club choices.</div>
                                    </div>
                                </div>
                            </div>

                            

                            <h6 class="mt-4 mb-3"><i class="fas fa-id-card me-2"></i>Government IDs</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="philhealth_number" class="form-label">PhilHealth Number</label>
                                        <input type="text" class="form-control" id="philhealth_number" name="philhealth_number" value="<?php echo htmlspecialchars($member['philhealth_number']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="pagibig_number" class="form-label">Pag-IBIG Number</label>
                                        <input type="text" class="form-control" id="pagibig_number" name="pagibig_number" value="<?php echo htmlspecialchars($member['pagibig_number']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="tin_number" class="form-label">TIN Number</label>
                                        <input type="text" class="form-control" id="tin_number" name="tin_number" value="<?php echo htmlspecialchars($member['tin_number']); ?>">
                                    </div>
                                </div>
                            </div>

                            <h6 class="mt-4 mb-3"><i class="fas fa-user me-2"></i>Personal Information</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="birthdate" class="form-label">Birthdate *</label>
                                        <input type="date" class="form-control" id="birthdate" name="birthdate" value="<?php echo $member['birthdate']; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="height" class="form-label">Height (cm)</label>
                                        <input type="number" class="form-control" id="height" name="height" step="0.01" value="<?php echo $member['height']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="weight" class="form-label">Weight (kg)</label>
                                        <input type="number" class="form-control" id="weight" name="weight" step="0.01" value="<?php echo $member['weight']; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="blood_type" class="form-label">Blood Type</label>
                                        <select class="form-select" id="blood_type" name="blood_type">
                                            <option value="">Select Blood Type</option>
                                            <option value="A+" <?php echo $member['blood_type'] == 'A+' ? 'selected' : ''; ?>>A+</option>
                                            <option value="A-" <?php echo $member['blood_type'] == 'A-' ? 'selected' : ''; ?>>A-</option>
                                            <option value="B+" <?php echo $member['blood_type'] == 'B+' ? 'selected' : ''; ?>>B+</option>
                                            <option value="B-" <?php echo $member['blood_type'] == 'B-' ? 'selected' : ''; ?>>B-</option>
                                            <option value="AB+" <?php echo $member['blood_type'] == 'AB+' ? 'selected' : ''; ?>>AB+</option>
                                            <option value="AB-" <?php echo $member['blood_type'] == 'AB-' ? 'selected' : ''; ?>>AB-</option>
                                            <option value="O+" <?php echo $member['blood_type'] == 'O+' ? 'selected' : ''; ?>>O+</option>
                                            <option value="O-" <?php echo $member['blood_type'] == 'O-' ? 'selected' : ''; ?>>O-</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="religion" class="form-label">Religion</label>
                                        <input type="text" class="form-control" id="religion" name="religion" value="<?php echo htmlspecialchars($member['religion']); ?>">
                                    </div>
                                </div>
                            </div>

                            <h6 class="mt-4 mb-3"><i class="fas fa-phone me-2"></i>Emergency Contact</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="emergency_contact_person" class="form-label">Emergency Contact Person *</label>
                                        <input type="text" class="form-control" id="emergency_contact_person" name="emergency_contact_person" value="<?php echo htmlspecialchars($member['emergency_contact_person']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="emergency_contact_number" class="form-label">Emergency Contact Number *</label>
                                        <input type="tel" class="form-control" id="emergency_contact_number" name="emergency_contact_number" value="<?php echo htmlspecialchars($member['emergency_contact_number']); ?>" required>
                                    </div>
                                </div>
                            </div>


                            <div class="d-flex justify-content-end">
                                <a href="index.php" class="btn btn-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Update Member
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            var regionToClubs = {
                'National Capital Region (NCR)': ['Marikina Eagles Club','Quezon City Eagles Club','Manila Eagles Club','Pasig Eagles Club'],
                'Southern Luzon Region 1 (SLR-1)': ['Batangas Eagles Club','Laguna Eagles Club','Cavite Eagles Club','Rizal Eagles Club'],
                'Southern Luzon Region 2 (SLR-2)': ['Quezon Eagles Club','Marinduque Eagles Club','Mindoro Eagles Club','Palawan Eagles Club'],
                'Central Luzon Region (CLR)': ['Pampanga Eagles Club','Bulacan Eagles Club','Tarlac Eagles Club','Nueva Ecija Eagles Club'],
                'Bicol Region': ['Camarines Sur Eagles Club','Camarines Norte Eagles Club','Albay Eagles Club','Sorsogon Eagles Club'],
                'Central Visayas Region II (CVR-II)': ['Cebu City Eagles Club','Mandaue City Eagles Club','Lapu-Lapu City Eagles Club','Bohol Eagles Club'],
                'Central Visayas Region VI (CVR-VI)': ['Iloilo City Eagles Club','Negros Occidental Eagles Club','Capiz Eagles Club','Aklan Eagles Club'],
                'Central Visayas Region VII (CVR-VII)': ['Dumaguete City Eagles Club','Siquijor Eagles Club','Siquijor City Eagles Club','Cebu Province Eagles Club'],
                'Western Visayas Region VI (WVR-VI)': ['Iloilo City Eagles Club','Negros Occidental Eagles Club','Capiz Eagles Club','Aklan Eagles Club'],
                'Eastern Visayas Region VIII (EVR-VIII)': ['Tacloban City Eagles Club','Ormoc City Eagles Club','Samar Eagles Club','Leyte Eagles Club'],
                'Northern Mindanao Region I (NMR-I)': ['Cagayan de Oro City Eagles Club','Iligan City Eagles Club','Bukidnon Eagles Club','Misamis Oriental Eagles Club'],
                'Northern Mindanao Region II (NMR-II)': ['Butuan City Eagles Club','Surigao City Eagles Club','Agusan del Norte Eagles Club','Agusan del Sur Eagles Club'],
                'Northern Mindanao Region III (NMR-III)': ['Malaybalay City Eagles Club','Valencia City Eagles Club','Manolo Fortich Eagles Club','Maramag Eagles Club'],
                'Northern Mindanao Region IV (NMR-IV)': ['Gingoog City Eagles Club','Magsaysay Eagles Club','Claveria Eagles Club','Jasaan Eagles Club'],
                'Western Mindanao Region I (WMR-I)': ['Zamboanga City Eagles Club','Zamboanga del Norte Eagles Club','Zamboanga del Sur Eagles Club','Zamboanga Sibugay Eagles Club'],
                'Western Mindanao Region II (WMR-II)': ['Pagadian City Eagles Club','Dipolog City Eagles Club','Dapitan City Eagles Club','Lakewood Eagles Club'],
                'Western Mindanao Region III (WMR-III)': ['Tigbao Eagles Club','Lakewood Eagles Club','Midsalip Eagles Club','Molave Eagles Club'],
                'Western Mindanao Region IV (WMR-IV)': ['Labangan Eagles Club','Vincenzo Sagun Eagles Club','Tambulig Eagles Club','Mahayag Eagles Club'],
                'Camiguin Region': ['Mambajao Eagles Club','Catarman Eagles Club','Guinsiliban Eagles Club','Sagay Eagles Club'],
                'Amihan Bukidnon Region': ['Malaybalay City Eagles Club','Valencia City Eagles Club','Manolo Fortich Eagles Club','Maramag Eagles Club'],
                'Northern Bukidnon Region': ['Don Carlos Eagles Club','Baungon Eagles Club','Libona Eagles Club','Baungon Eagles Club'],
                'Southern Bukidnon Region': ['Quezon Eagles Club','Kibawe Eagles Club','Kadingilan Eagles Club','Maramag Eagles Club'],
                'CARAGA-I': ['Butuan City Eagles Club','Surigao City Eagles Club','Agusan del Norte Eagles Club','Agusan del Sur Eagles Club'],
                'CARAGA-II': ['Tandag City Eagles Club','Bislig City Eagles Club','Lingig Eagles Club','Hinatuan Eagles Club'],
                'CARAGA-III': ['Cantilan Eagles Club','Madrid Eagles Club','Carrascal Eagles Club','Lanuza Eagles Club'],
                'Davao-I': ['Davao City Eagles Club','Tagum City Eagles Club','Panabo City Eagles Club','Digos City Eagles Club'],
                'Davao-II': ['Davao del Norte Eagles Club','Davao del Sur Eagles Club','Davao Oriental Eagles Club','Davao Occidental Eagles Club'],
                'Davao-III': ['Davao del Norte Eagles Club','Davao del Sur Eagles Club','Davao Oriental Eagles Club','Davao Occidental Eagles Club'],
                'SOCCSKSARGEN': ['Cotabato City Eagles Club','General Santos City Eagles Club','Koronadal City Eagles Club','Tacurong City Eagles Club'],
                'BARMM-I': ['Marawi City Eagles Club','Maguindanao Eagles Club','Lanao del Sur Eagles Club','Lanao del Norte Eagles Club'],
                'BARMM-II': ['Sulu Eagles Club','Tawi-Tawi Eagles Club','Basilan Eagles Club','Zamboanga del Sur Eagles Club'],
                'BARMM-III': ['Lamitan City Eagles Club','Isabela City Eagles Club','Jolo Eagles Club','Bongao Eagles Club'],
                'United States': ['Los Angeles Eagles Club','New York Eagles Club','Chicago Eagles Club','Houston Eagles Club','Miami Eagles Club'],
                'Canada': ['Toronto Eagles Club','Vancouver Eagles Club','Montreal Eagles Club','Calgary Eagles Club'],
                'Middle East': ['Dubai Eagles Club','Abu Dhabi Eagles Club','Riyadh Eagles Club'],
                'Asia-Pacific': ['Manila Eagles Club (International Chapter)','Singapore Eagles Club','Hong Kong Eagles Club']
            };

            function populateClubs(region) {
                var clubSelect = document.getElementById('club_affiliation');
                if (!clubSelect) return;
                var current = clubSelect.getAttribute('data-current') || '';
                var options = ['<option value="">Select Club</option>'];
                var clubs = regionToClubs[region] || [];
                clubs.forEach(function(name){
                    var sel = (current && current === name) ? ' selected' : '';
                    options.push('<option value="' + name.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') + '"' + sel + '>' + name.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</option>');
                });
                clubSelect.innerHTML = options.join('');
            }

            document.addEventListener('DOMContentLoaded', function(){
                var regionSelect = document.getElementById('region');
                if (regionSelect) {
                    populateClubs(regionSelect.value);
                    regionSelect.addEventListener('change', function(){
                        // clear previous selection before repopulating
                        var clubSelect = document.getElementById('club_affiliation');
                        if (clubSelect) clubSelect.setAttribute('data-current','');
                        populateClubs(this.value);
                    });
                }
            });
        })();
    </script>
</body>
</html>
