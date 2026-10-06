<?php
/**
 * Global Header Component
 * Online Quiz Competition Platform
 */

require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? 'Online Quiz Competition Platform';
$extraCss = $extraCss ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= e($pageTitle) ?> | QuizArena</title>

    <!-- Core Stylesheet -->
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">

    <!-- Page Specific Stylesheets -->
    <?php foreach ($extraCss as $css): ?>
        <link rel="stylesheet" href="<?= url('assets/css/' . $css) ?>">
    <?php endforeach; ?>
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>

    <main class="main-content">
        <div class="container">
            <?php display_flash_messages(); ?>
