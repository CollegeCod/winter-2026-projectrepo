<?php
// Determine current page for active highlighting
$current_page = basename($_SERVER["PHP_SELF"]); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="images/Belle_Logo.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Belle's Training Solutions</title>
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/dashboard_main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="sidebar-layout">
    <aside class="sidebar" id="mainSidebar">
        <div class="sidebar-brand">
            <div class="logo-main-container">
                <img src="images/Belle_Logo.png" alt="Logo" class="img-main">
            </div>
            <div class="logo-wsc-container">
                <img src="images/WSC_Logo.png" alt="WSC" class="img-wsc">
            </div>
        </div>

        <nav class="sidebar-nav">
            <ul>
                <li class="<?= $current_page == "index.php" ? "active" : "" ?>">
                    <a href="index.php"><i class="fa-solid fa-qrcode"></i> QR Codes</a>
                </li>
                <li class="<?= $current_page == "settings.php"
                    ? "active"
                    : "" ?>">
                    <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
                </li>
            </ul>
        </nav>
    </aside>

    <main class="main-wrapper">
