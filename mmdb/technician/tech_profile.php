<?php
session_start();
require '../conn.php';

// Allow only technician
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 2) {
    header("Location: ../login.php");
    exit;
}

$tech_id = $_SESSION['user_id'];
$message = '';
$password_message = '';

// Get current profile info
$stmt = $conn->prepare("SELECT name, email, profilepic FROM sys_user WHERE user_id = ?");
$stmt->bind_param("i", $tech_id);
$stmt->execute();
$stmt->bind_result($name, $email, $profilepic);
$stmt->fetch();
$stmt->close();

// Default profile picture if not set
if (empty($profilepic)) {
    $profilepic = 'profilepic/default.jpeg';
}

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_name = trim($_POST['name']);
    $new_email = trim($_POST['email']);

    if (empty($new_name) || empty($new_email)) {
        $message = '<div class="message error">Name and Email cannot be empty.</div>';
    } else {
        // Handle file upload if a new picture is selected
        if (isset($_FILES['profilepic']) && $_FILES['profilepic']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['profilepic']['tmp_name'];
            $file_name = basename($_FILES['profilepic']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($file_ext, $allowed_ext)) {
                $new_filename = 'tech_' . $tech_id . '_' . time() . '.' . $file_ext;
                $upload_path = 'profilepic/' . $new_filename;

                if (!is_dir('../profilepic')) {
                    mkdir('../profilepic', 0777, true);
                }

                if (move_uploaded_file($file_tmp, '../' . $upload_path)) {
                    $stmt = $conn->prepare("UPDATE sys_user SET profilepic = ? WHERE user_id = ?");
                    $stmt->bind_param("si", $upload_path, $tech_id);
                    $stmt->execute();
                    $stmt->close();
                    $profilepic = $upload_path;
                } else {
                    $message = '<div class="message error">Failed to upload image.</div>';
                }
            } else {
                $message = '<div class="message error">Only JPG, JPEG, PNG, and GIF files are allowed.</div>';
            }
        }

        // Update name and email
        $stmt = $conn->prepare("UPDATE sys_user SET name = ?, email = ? WHERE user_id = ?");
        $stmt->bind_param("ssi", $new_name, $new_email, $tech_id);
        if ($stmt->execute()) {
            $_SESSION['name'] = $new_name;
            $message = '<div class="message success">Profile updated successfully.</div>';
            $name = $new_name;
            $email = $new_email;
        } else {
            $message = '<div class="message error">Error updating profile.</div>';
        }
    }
}

// Delete profile picture
if (isset($_POST['delete_picture']) && $profilepic !== 'profilepic/default.jpeg') {
    if (file_exists('../' . $profilepic)) {
        unlink('../' . $profilepic);
    }

    $stmt = $conn->prepare("UPDATE sys_user SET profilepic = 'profilepic/default.jpeg' WHERE user_id = ?");
    $stmt->bind_param("i", $tech_id);
    $stmt->execute();
    $stmt->close();

    $profilepic = 'profilepic/default.jpeg';
    $message = '<div class="message success">Profile picture deleted.</div>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Technician Profile - MRS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f7fafc; }
        header.staff-header { background: #4a90e2; color: white; padding: 1.3rem 0; font-size: 2rem; font-weight: 700; text-align: center; position: fixed; top: 0; width: 100%; z-index: 1000; }
        .sidebar { position: fixed; top: 0; left: 0; width: 220px; height: 100vh; background: #253444; color: #fff; display: flex; flex-direction: column; z-index: 1100; }
        .sidebar-header { padding: 2rem 1rem 1rem 2rem; font-size: 1.1rem;  font-weight: bold; background: #1d2937; }
        .sidebar nav { flex: 1; display: flex; flex-direction: column; gap: 8px; padding: 1.5rem 0.5rem 1.5rem 2rem; }
        .sidebar-section-title { font-size: 0.85rem; margin-top: 1.5rem; margin-bottom: 0.7rem; font-weight: bold; color: #b8e0fc; }
        .sidebar nav a { color: #cdd9e5; text-decoration: none;     font-size: 0.9rem;    padding: 8px 14px; border-radius: 6px; transition: background 0.2s; font-weight: 500; display: block; }
        .sidebar nav a.active, .sidebar nav a:hover { background: #4285F4; color: #fff; }
        .sidebar .logout-link { margin-top: auto; margin-bottom: 2rem; padding-left: 2rem; }
        .sidebar .logout-link a { color: #ffbdbd; background: #a94442; font-weight: bold; text-decoration: none;     font-size: 0.9rem;padding: 8px 14px; border-radius: 6px; display: inline-block; }
        .main-content { margin-left: 220px; padding-top: 90px; padding-bottom: 2rem; min-height: 100vh; background: #f7fafc; }
        .container { max-width: 700px; margin: 0 auto; padding: 20px; }
        .card-box { background: #ffffff; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .card-box h2 { font-size: 1.5rem; margin-bottom: 15px; color: #253444; }
        .form-group { margin-bottom: 15px; }
        .form-group label { font-weight: bold; display: block; margin-bottom: 6px; }
        .form-group input[type="text"], .form-group input[type="email"], .form-group input[type="password"] {
            width: 100%; padding: 10px; font-size: 1rem; border-radius: 6px; border: 1px solid #ccc;
        }
        .input-wrapper { position: relative; }
        .toggle-password {
            position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; font-size: 1.15rem; color: #888; padding: 0 5px;
        }
        .sidebar nav {overflow-y: auto;max-height: calc(100vh - 250px); }
        .sidebar nav::-webkit-scrollbar { width: 0px;background: none;}
        .toggle-password:active { color: #253444; }
        .btn { background: #4285F4; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-size: 15px; cursor: pointer; }
        .btn:hover { background: #3367D6; }
        .message { padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

<header class="staff-header">Maintenance Report System - Technician</header>
<aside class="sidebar">
<div class="sidebar-header" style="display: flex; flex-direction: column; align-items: center; padding: 1.5rem 1rem;">
    <img src="../<?= htmlspecialchars($profilepic) ?>" alt="Profile Picture"
         style="width: 70px; height: 70px; object-fit: cover; border-radius: 50%; border: 2px solid #ccc; box-shadow: 0 0 4px rgba(0,0,0,0.15); margin-bottom: 8px;">
    <div style="font-weight: bold; font-size: 0.95rem; color: #b8e0fc; margin-bottom: 4px;">
        <?= htmlspecialchars($name) ?>
    </div>
    <div style="font-size: 1.1rem; color: #fff;">MRS Technician</div>
</div>

    <!-- Navigation -->
    <nav>
        <a href="tech_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="tech_profile.php" class="active"><i class="fas fa-user"></i> Profile</a>
        <div class="sidebar-section-title">Task</div>
        <a href="tech_ass.php"><i class="fas fa-tasks"></i> Assignments</a>
        <a href="tech_archive.php"><i class="fas fa-archive"></i> Archived Reports</a>
        <div class="sidebar-section-title">Help</div>
        <a href="https://www.annamalaiuniversity.ac.in/studport/download/engg/civil%20and%20structural/Building%20Repairs%20and%20Maintenance.pdf" target="_blank"><i class="fas fa-book"></i> Maintenance Guide</a>
    </nav>

    <div class="logout-link">
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</aside>
<div class="main-content">
    <div class="container">
<div class="card-box">
    <h2>Update Profile</h2>
    <?= $message ?>
<?php
$profilepic_path = '../' . $profilepic;
if (!file_exists($profilepic_path)) {
    $profilepic = 'profilepic/default.jpeg';
    $profilepic_path = '../' . $profilepic;
}
?>

<div style="text-align:center; margin-bottom:15px;">
    <img src="<?= htmlspecialchars($profilepic_path) ?>" alt="Profile Picture"
         style="width:120px; height:120px; object-fit:cover; border-radius:50%; border: 3px solid #ccc; box-shadow: 0 0 8px rgba(0,0,0,0.15);">
</div>

<?php if ($profilepic !== 'profilepic/default.jpeg'): ?>
    <form method="POST" style="text-align:center; margin-bottom:20px;">
        <button type="submit" name="delete_picture" class="btn" style="background:#d9534f">Delete Profile Picture</button>
    </form>
<?php endif; ?>


    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
        </div>
        <div class="form-group">
            <label for="profilepic">Upload New Profile Picture</label>
            <input type="file" id="profilepic" name="profilepic" accept="image/*">
        </div>
        <button type="submit" name="update_profile" class="btn">Update Profile</button>
    </form>
</div>

        <div class="card-box">
            <h2>Change Password</h2>
            <?= $password_message ?>
            <form method="POST" autocomplete="off">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="current_password" name="current_password" required>
                        <button type="button" class="toggle-password" data-target="current_password">&#128065;</button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="new_password" name="new_password" required>
                        <button type="button" class="toggle-password" data-target="new_password">&#128065;</button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <button type="button" class="toggle-password" data-target="confirm_password">&#128065;</button>
                    </div>
                </div>
                <button type="submit" name="change_password" class="btn">Change Password</button>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.toggle-password').forEach(function(btn) {
    btn.addEventListener('click', function () {
        const input = document.getElementById(this.dataset.target);
        input.type = input.type === 'password' ? 'text' : 'password';
        this.innerHTML = input.type === 'password' ? "&#128065;" : "&#128586;";
    });
});
</script>
</body>
</html>
