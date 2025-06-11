<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header("Location: login.php");
    exit;
}

$name = htmlspecialchars($_SESSION['name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin Dashboard - MRS</title>
<style>
    /* Reset some basics */
    *, *::before, *::after {
        box-sizing: border-box;
    }
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #4b79a1, #283e51);
        margin: 0;
        padding: 0;
        color: #f0f0f0;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    header {
        background-color: rgba(0, 0, 0, 0.3);
        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        color: #fff;
        padding: 20px 40px;
        font-size: 2rem;
        font-weight: 700;
        letter-spacing: 1px;
        text-align: center;
        user-select: none;
    }
    nav {
        background-color: rgba(0,0,0,0.25);
        padding: 12px 40px;
        display: flex;
        justify-content: flex-end;
        box-shadow: inset 0 -1px 0 rgba(255,255,255,0.1);
    }
    nav a {
        color: #f0f0f0;
        text-decoration: none;
        font-weight: 600;
        font-size: 1rem;
        padding: 8px 15px;
        border-radius: 6px;
        transition: background-color 0.3s ease, color 0.3s ease;
    }
    nav a:hover, nav a:focus {
        background-color: #ff5c5c;
        color: white;
        outline: none;
    }
    main {
        flex: 1;
        padding: 40px 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        max-width: 900px;
        margin: 0 auto;
        width: 100%;
        text-align: center;
    }
    h1 {
        font-size: 2.8rem;
        font-weight: 800;
        margin-bottom: 0.25em;
        text-shadow: 1px 1px 5px rgba(0,0,0,0.4);
    }
    .welcome {
        font-size: 1.3rem;
        margin-bottom: 40px;
        color: #cdd9e5cc;
        font-weight: 500;
        text-shadow: 0 0 3px rgba(0,0,0,0.2);
    }
    .welcome-card {
        background: rgba(255,255,255,0.1);
        padding: 30px 40px;
        border-radius: 16px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.25);
        max-width: 600px;
        width: 100%;
        margin-bottom: 50px;
        transition: background 0.4s ease;
    }
    .welcome-card:hover {
        background: rgba(255,255,255,0.15);
    }
    .dashboard-links {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 25px;
        width: 100%;
        max-width: 700px;
    }
    .dashboard-links a {
        flex: 1 1 200px;
        background-color: #4285F4;
        color: white;
        padding: 18px 24px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1.1rem;
        text-decoration: none;
        box-shadow: 0 6px 12px rgba(66, 133, 244, 0.5);
        transition: background-color 0.3s ease, box-shadow 0.3s ease, transform 0.2s ease;
        user-select: none;
    }
    .dashboard-links a:hover, .dashboard-links a:focus {
        background-color: #3367D6;
        box-shadow: 0 10px 20px rgba(51, 103, 214, 0.6);
        transform: translateY(-4px);
        outline: none;
    }
    /* Responsive */
    @media (max-width: 600px) {
        .dashboard-links {
            flex-direction: column;
            max-width: 100%;
        }
        .dashboard-links a {
            flex: 1 1 auto;
            width: 100%;
        }
    }
</style>
</head>
<body>

<header>Maintenance Report System - Admin Dashboard</header>

<nav>
    <a href="logout.php" tabindex="0">Logout</a>
</nav>

<main>
    <div class="welcome-card" role="region" aria-label="Welcome message">
        <h1>Welcome, <?= $name ?>!</h1>
        <p class="welcome">You are logged in as an administrator.</p>
    </div>

    <div class="dashboard-links" role="navigation" aria-label="Admin dashboard links">
        <a href="manage_users.php" tabindex="0">Manage Users</a>
        <a href="view_reports.php" tabindex="0">View Reports</a>
    
    </div>
</main>

</body>
</html>
