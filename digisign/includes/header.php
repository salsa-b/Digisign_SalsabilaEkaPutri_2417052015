<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = $_GET['page'] ?? 'home';
$menu_items = [
    ['page' => 'home', 'icon' => '📊', 'label' => 'Dashboard'],
    ['page' => 'setup_otp', 'icon' => '🔐', 'label' => 'Setup OTP'],
    ['page' => 'request_id', 'icon' => '🪪', 'label' => 'Request Digital ID'],
    ['page' => 'set_signature', 'icon' => '🖊️', 'label' => 'Signature Specimen'],
    ['page' => 'sign_document', 'icon' => '✍️', 'label' => 'Sign PDF Document'],
    ['page' => 'signing_history', 'icon' => '📋', 'label' => 'Signing History'],
];
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DigiSign – Digital Signature System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet">
    <script>tailwind.config = {theme: {extend: {width: {sidebar: '280px'}, margin: {sidebar: '280px'}}}}</script>
    <style>
        #sidebar{width:280px;min-height:100vh;position:fixed;top:0;left:0;z-index:50;transition:transform .25s ease}
        #main-content{margin-left:280px;min-height:100vh}
        .menu-item-active{background-color:rgba(255,255,255,.12);border-left:3px solid #60a5fa}
        .menu-item-active span{color:#93c5fd}
        @media(max-width:767px){#sidebar{transform:translateX(-100%)}#sidebar.open{transform:translateX(0)}#main-content{margin-left:0}}
        #sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:40}
        #sidebar-overlay.visible{display:block}
    </style>
</head>
<body class="bg-slate-50 font-sans">
<div id="sidebar-overlay" onclick="closeSidebar()"></div>
<aside id="sidebar" class="bg-slate-900 text-white flex flex-col shadow-2xl">
    <div class="flex items-center gap-3 px-6 py-5 border-b border-slate-700">
        <span class="text-3xl select-none">🔏</span>
        <div><h1 class="text-lg font-bold leading-tight tracking-wide">DigiSign</h1><p class="text-xs text-slate-400 leading-none">Digital Signature System</p></div>
    </div>
    <nav class="flex-1 overflow-y-auto py-4">
        <ul class="space-y-1 px-3">
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li><a href="index.php?page=admin/dashboard" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm transition-all duration-150 cursor-pointer <?= ($current_page === 'admin/dashboard') ? 'menu-item-active font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>" onclick="closeSidebar()"><span class="text-lg w-6 text-center leading-none select-none"></span><span>Admin Dashboard</span></a></li>
            <?php else: ?>
            <?php foreach ($menu_items as $item): $is_active = ($current_page === $item['page']); ?>
            <li><a href="index.php?page=<?= $item['page'] ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm transition-all duration-150 cursor-pointer <?= $is_active ? 'menu-item-active font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>" onclick="closeSidebar()"><span class="text-lg w-6 text-center leading-none select-none"><?= $item['icon'] ?></span><span><?= htmlspecialchars($item['label']) ?></span><?php if ($is_active): ?><span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-400"></span><?php endif; ?></a></li>
            <?php endforeach; ?>
            <?php if (!empty($_SESSION['otp_verified']) && $_SESSION['otp_verified'] == 1): ?>
            <li><a href="index.php?page=reset_otp" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm transition-all duration-150 cursor-pointer <?= ($current_page === 'reset_otp') ? 'menu-item-active font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>" onclick="closeSidebar()"><span class="text-lg w-6 text-center leading-none select-none"></span><span>Reset OTP</span><?php if ($current_page === 'reset_otp'): ?><span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-400"></span><?php endif; ?></a></li>
            <?php endif; ?>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="px-5 py-4 border-t border-slate-700 text-xs text-slate-400">
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="flex items-center gap-2 mb-2">
            <span class="w-7 h-7 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold text-sm shrink-0"><?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?></span>
            <div class="min-w-0"><div class="truncate text-slate-200 font-medium"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></div><?php if (($_SESSION['role'] ?? '') === 'admin'): ?><div class="text-yellow-400 text-xs">Admin</div><?php endif; ?></div>
        </div>
        <a href="index.php?page=logout" class="flex items-center gap-2 px-3 py-2 rounded-lg text-red-300 hover:bg-red-500/20 transition-colors text-xs font-medium">🚪 Logout</a>
        <?php else: ?>
        <a href="index.php?page=login" class="text-blue-400 hover:underline">Login</a>
        <?php endif; ?>
    </div>
</aside>
<header class="md:hidden fixed top-0 left-0 right-0 z-30 bg-slate-900 text-white flex items-center gap-3 px-4 h-14 shadow-lg">
    <button onclick="toggleSidebar()" class="btn btn-ghost btn-sm text-white px-2" aria-label="Toggle menu"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
    <span class="text-lg font-bold tracking-wide">🔏 DigiSign</span>
</header>
<main id="main-content" class="pt-14 md:pt-0 p-6 bg-slate-50">
<script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebar-overlay').classList.toggle('visible');}function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebar-overlay').classList.remove('visible');}</script>