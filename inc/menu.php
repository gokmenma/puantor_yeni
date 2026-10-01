<?php

//Model sayfaya dahil edilir
require_once "Model/Menus.php";
require_once "Model/Auths.php";

//Modelden yeni bir nesne oluşturulur
$menus = new Menus();
$Auths = new Auths();

//Kommit kontrol
// Sidebar tema seçimi
$sidebarThemeCookie = $_COOKIE['app_sidebar_theme'] ?? 'klasik-koyu';
$isLightSidebar = in_array($sidebarThemeCookie, ['sade-beyaz', 'slate-light', 'platin-gri'], true);
$navbarBsTheme = $isLightSidebar ? 'light' : 'dark';
?>

<aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="<?php echo $navbarBsTheme; ?>" data-sidebar-theme="<?php echo htmlspecialchars($sidebarThemeCookie, ENT_QUOTES, 'UTF-8'); ?>" id="navbar">
    <canvas id="sidebar-particles-canvas" class="sidebar-particles-canvas"></canvas>
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu"
            aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <h1 class="navbar-brand">
            <a href="/anasayfa" class="navbar-brand-link" aria-label="Puantor">
                <svg version="1.2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 700 187" class="navbar-brand-svg" width="700" height="187">
                    <g id="katman 1">
                        <g id="&lt;Group&gt;">
                            <g id="&lt;Group&gt;">
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" class="s0" d="m323.3 134.8l-1.8 5.5-2.1 6.3-4.2-11.8h-1l-4.1 11.8-2.1-6.3-2-5.4h-1.1l4.6 13.2h1.2l1.3-3.8 2.7-7.7 2.7 7.7 1.4 3.7h1.2l4.5-13.2z"/>
                                </g>
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" class="s0" d="m345.1 134.7l-1.8 5.5-2.2 6.3-4.1-11.7h-1l-4.1 11.7-2.1-6.2-1.9-5.5h-1.3l4.7 13.2h1.2l1.3-3.8 2.7-7.7 2.7 7.7 1.4 3.8 1.2-0.1 4.5-13.2z"/>
                                </g>
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" class="s0" d="m366.9 134.7l-1.8 5.4-2.1 6.3-4.2-11.7h-1l-4.1 11.7-2.2-6.2-1.8-5.5h-1.2l4.6 13.2h1.2l1.3-3.7 2.7-7.7 2.8 7.6 1.3 3.8h1.2l4.5-13.3z"/>
                                </g>
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" class="s0" d="m371.4 146.5q-0.2-0.3-0.7-0.3-0.3 0-0.6 0.3-0.2 0.2-0.2 0.6 0 0.4 0.2 0.7 0.3 0.2 0.7 0.2 0.4 0 0.6-0.2 0.3-0.3 0.3-0.7 0-0.4-0.3-0.6z"/>
                                </g>
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" fill-rule="evenodd" class="s0" d="m389.2 141.2c0 1.3-0.3 2.5-0.9 3.5q-0.8 1.6-2.3 2.4-1.5 0.9-3.4 0.9-2 0.1-3.4-0.8-1.5-0.8-2.3-2.3l0.1 9.3-1.2 0.1v-19.7h1l0.1 2.9c0.4-0.9 1.2-1.7 2.2-2.2q1.5-0.9 3.4-0.9 1.9 0 3.4 0.8 1.5 0.9 2.4 2.4c0.5 1.1 0.9 2.2 0.9 3.6zm-1.2 0c0-1.2-0.2-2.2-0.7-3.1-0.5-0.8-1.1-1.5-2-2-0.8-0.5-1.8-0.7-2.9-0.7q-1.6 0-2.8 0.7-1.3 0.8-2 2.1c-0.5 0.9-0.7 1.9-0.7 3 0 1.2 0.2 2.2 0.7 3q0.7 1.4 2 2.1 1.3 0.7 2.9 0.7c1 0 2-0.3 2.9-0.7 0.8-0.5 1.5-1.2 1.9-2.1 0.5-0.9 0.7-1.9 0.7-3z"/>
                                </g>
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" class="s0" d="m403 134.5v7.4q0 1.3-0.6 2.5-0.6 1.1-1.7 1.8-1.2 0.7-2.6 0.7c-1.4 0-2.5-0.4-3.3-1.3-0.8-0.8-1.2-1.9-1.2-3.5v-7.5h-1.1v7.6c0 1.8 0.5 3.2 1.5 4.2q1.5 1.6 4 1.5 1.7 0 3-0.7c0.9-0.5 1.6-1.2 2.1-2.1v2.6h1.1l-0.1-13.2z"/>
                                </g>
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" fill-rule="evenodd" class="s0" d="m421 134.5l0.1 13.2h-1.1v-3c-0.5 1-1.3 1.8-2.2 2.4q-1.6 0.8-3.5 0.8c-1.3 0-2.4-0.3-3.4-0.9q-1.5-0.8-2.3-2.4-0.9-1.5-0.9-3.5c0-1.3 0.3-2.5 0.9-3.5q0.8-1.6 2.3-2.5c1-0.5 2.1-0.9 3.4-0.9q1.9 0 3.4 0.9 1.5 0.8 2.3 2.3v-2.9zm-1.1 6.6c0-1.2-0.3-2.2-0.7-3.1-0.5-0.8-1.2-1.5-2-2q-1.3-0.7-2.9-0.7c-1 0-2 0.2-2.8 0.7q-1.3 0.7-2 2.1c-0.5 0.9-0.7 1.8-0.7 3 0 1.2 0.2 2.1 0.7 3q0.7 1.4 2 2.1c0.8 0.4 1.8 0.7 2.9 0.7q1.6 0 2.8-0.8c0.9-0.4 1.6-1.1 2-2 0.5-0.9 0.7-1.9 0.7-3z"/>
                                </g>
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" class="s0" d="m435.8 135.7q-1.5-1.5-4.1-1.5c-1.1 0-2.1 0.3-3 0.8q-1.4 0.7-2.2 2v-2.6h-1v13.2h1.1v-7.3c0-0.9 0.2-1.8 0.6-2.5 0.5-0.8 1.1-1.4 1.8-1.8q1.2-0.7 2.7-0.7 2.1 0 3.3 1.2 1.2 1.2 1.2 3.5v7.6h1.1v-7.6q0-2.7-1.5-4.3z"/>
                                </g>
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" class="s0" d="m449 146.2c-0.4 0.1-0.7 0.3-1.2 0.4q-0.6 0.2-1.1 0.2-1.4 0-1.9-0.8-0.5-0.8-0.5-2.4l-0.1-8.2h4.6v-1.1l-4.6 0.1v-4l-1.1 0.1v3.9h-2.8v1h2.8v8.2q0.1 2.1 0.8 3.2c0.6 0.7 1.5 1 2.9 1 0.8 0 1.7-0.2 2.7-0.7z"/>
                                </g>
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" fill-rule="evenodd" class="s0" d="m464.5 140.9q0 1.9-0.8 3.5-0.9 1.6-2.4 2.5c-1 0.6-2.2 0.9-3.5 0.9q-2 0-3.5-0.9-1.5-0.9-2.4-2.4-0.9-1.6-0.9-3.6c0-1.3 0.3-2.4 0.9-3.5q0.8-1.6 2.4-2.4c1-0.6 2.1-0.9 3.4-0.9 1.3 0 2.5 0.2 3.5 0.9q1.6 0.8 2.5 2.4c0.5 1 0.8 2.2 0.8 3.5zm-1.1 0q0-1.6-0.7-3-0.8-1.3-2.1-2-1.2-0.8-2.9-0.8c-1 0-2 0.3-2.8 0.8q-1.3 0.8-2 2.1-0.7 1.3-0.7 2.9 0 1.7 0.7 3 0.7 1.3 2 2.1c0.8 0.5 1.8 0.7 2.9 0.7q1.6 0 2.9-0.8 1.2-0.7 2-2 0.7-1.4 0.7-3z"/>
                                </g>
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" class="s0" d="m473.7 134.1q-1.5 0-2.7 0.8c-0.8 0.6-1.4 1.3-1.9 2.3v-2.9h-1v13.2h1.1v-6.8q0-1.5 0.6-2.8c0.4-0.8 0.9-1.5 1.6-2q1-0.7 2.3-0.7c0.8 0 1.5 0.2 2.2 0.6l0.5-1q-1.2-0.7-2.7-0.7z"/>
                                </g>
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" class="s0" d="m478.5 146.1q-0.2-0.3-0.6-0.3c-0.3 0-0.5 0.1-0.7 0.3q-0.2 0.2-0.2 0.6 0 0.4 0.2 0.7c0.2 0.1 0.4 0.2 0.7 0.2q0.4 0 0.6-0.2 0.3-0.3 0.3-0.7 0-0.4-0.3-0.6z"/>
                                </g>
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" class="s0" d="m492.4 144.9c-0.6 0.6-1.2 1-1.9 1.3q-1.1 0.4-2.2 0.4-1.6 0-2.9-0.7c-0.9-0.5-1.6-1.1-2.1-2q-0.8-1.3-0.8-3.1c0-1.2 0.3-2.2 0.8-3.1q0.7-1.3 2-2c0.9-0.4 1.9-0.7 2.9-0.7q1.1 0 2.2 0.4 1 0.4 1.8 1.2l0.7-0.7c-1.3-1.3-2.9-1.9-4.7-1.9q-1.9 0-3.5 0.8c-1 0.6-1.8 1.4-2.4 2.5q-0.9 1.5-0.9 3.5 0 2 0.9 3.6 0.9 1.5 2.5 2.4c1 0.6 2.2 0.9 3.5 0.9 0.9 0 1.7-0.2 2.6-0.5q1.2-0.6 2.2-1.5z"/>
                                </g>
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" fill-rule="evenodd" class="s0" d="m508.7 140.7q0 2-0.8 3.6-0.9 1.5-2.4 2.4c-1 0.6-2.2 0.9-3.5 0.9q-2 0-3.5-0.8-1.5-1-2.4-2.5-0.9-1.6-0.9-3.5c0-1.3 0.3-2.5 0.9-3.5q0.8-1.6 2.4-2.5c1-0.6 2.1-0.9 3.4-0.9 1.3 0 2.5 0.3 3.5 0.9q1.6 0.8 2.4 2.4c0.6 1 0.9 2.2 0.9 3.5zm-1.1 0q0-1.6-0.7-2.9-0.8-1.3-2.1-2.1-1.2-0.7-2.9-0.7c-1 0-2 0.2-2.9 0.7q-1.2 0.8-1.9 2.1-0.8 1.3-0.7 3 0 1.6 0.7 2.9 0.7 1.3 2 2.1c0.8 0.5 1.8 0.7 2.9 0.7q1.6 0 2.9-0.7 1.2-0.8 2-2.1 0.7-1.3 0.7-3z"/>
                                </g>
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" class="s0" d="m531.4 135.4c-1-1-2.3-1.6-3.9-1.5q-1.6 0-2.9 0.8-1.3 0.9-2 2.4-0.5-1.5-1.7-2.4c-0.9-0.5-1.9-0.8-3.1-0.8-0.9 0-1.8 0.2-2.6 0.7-0.8 0.5-1.4 1.1-1.9 2v-2.5h-1.1l0.1 13.2h1.1v-7.4c0-1 0.2-1.8 0.6-2.5q0.5-1.1 1.5-1.8 1.1-0.6 2.3-0.6 2 0 3.1 1.2 1 1.2 1 3.4l0.1 7.7h1.1v-7.4c0-1 0.2-1.8 0.5-2.6q0.6-1.1 1.6-1.7c0.7-0.4 1.5-0.7 2.3-0.7q2 0 3 1.2 1.1 1.2 1.1 3.5l0.1 7.6h1.1v-7.6q-0.1-2.8-1.4-4.2z"/>
                                </g>
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" class="s0" d="m538.3 145.9q-0.3-0.3-0.7-0.3c-0.2 0-0.4 0.1-0.6 0.3q-0.3 0.2-0.3 0.6 0 0.4 0.3 0.7c0.2 0.1 0.4 0.2 0.7 0.2q0.3 0 0.6-0.2 0.3-0.3 0.3-0.7 0-0.4-0.3-0.6z"/>
                                </g>
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" class="s0" d="m548.7 145.8c-0.4 0.2-0.7 0.3-1.2 0.4q-0.6 0.2-1.1 0.2-1.4 0-1.9-0.8-0.5-0.8-0.6-2.3v-8.3h4.6v-1h-4.6v-4l-1.1 0.1v3.9h-2.9v1h2.9v8.3q0 2.1 0.8 3.1c0.6 0.7 1.5 1.1 2.8 1.1 0.9 0 1.8-0.3 2.7-0.8z"/>
                                </g>
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" class="s0" d="m557.4 133.8q-1.5 0-2.6 0.8c-0.8 0.6-1.4 1.3-1.9 2.3v-2.9h-1.1l0.1 13.2h1.1v-6.8q0-1.5 0.5-2.8c0.4-0.8 1-1.5 1.7-2q1-0.7 2.2-0.7 1.2 0 2.2 0.6l0.5-1c-0.7-0.5-1.6-0.7-2.7-0.7z"/>
                                </g>
                            </g>
                            <g id="&lt;Group&gt;">
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" fill-rule="evenodd" class="s0" d="m268 44l0.2 51-46.3 0.2 0.1 13.9-18.6 0.1-0.2-65zm-46.2 18.7v14l27.8-0.1v-14z"/>
                                </g>
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" class="s0" d="m319 43.8l0.1 46.4-27.7 0.1-0.2-46.4h-18.5l0.2 65 64.8-0.2-0.2-65z"/>
                                </g>
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" fill-rule="evenodd" class="s0" d="m407 43.4l0.2 65-18.5 0.1-0.1-13.9-27.7 0.1v13.9l-18.5 0.1-0.2-65zm-46.3 18.7l0.1 14 27.7-0.1v-14z"/>
                                </g>
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" class="s0" d="m457.9 43.3l0.2 33.2-33.4-33.1h-13.1l0.3 65h18.5l-0.1-33.3 33.3 33.1h13.1l-0.2-65z"/>
                                </g>
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" class="s0" d="m481.1 43.2l0.1 18.6 23.1-0.1 0.2 46.4 18.5-0.1-0.2-46.4 23.2-0.1v-18.6z"/>
                                </g>
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" fill-rule="evenodd" class="s0" d="m615.4 42.7l0.3 65-64.9 0.2-0.2-65zm-46.3 18.8l0.1 27.8 27.8-0.1-0.1-27.8z"/>
                                </g>
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" fill-rule="evenodd" class="s0" d="m685.2 107.4l-26.2 0.1-20.2-20.1v20.2l-18.5 0.1-0.2-65 64.8-0.3 0.2 51.1h-13.9zm-46.5-46.2v13.9l27.8-0.1v-13.9z"/>
                                </g>
                            </g>
                            <g id="&lt;Group&gt;">
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" fill-rule="evenodd" class="s1" d="m74.7 69.8c0 4.3-1.7 8.2-4.5 11.1-2.8 2.8-6.7 4.6-11 4.6-8.7 0-15.7-7-15.7-15.6 0-4.3 1.7-8.2 4.5-11.1 2.8-2.8 6.7-4.6 11-4.6 4.4 0 8.3 1.7 11.1 4.5 2.9 2.9 4.6 6.8 4.6 11.1zm-9.3-1.9h-4.4v-4.4l-3.8 0.1v4.3l-4.4 0.1v3.8h4.4v4.3h3.8v-4.4h4.4z"/>
                                </g>
                            </g>
                            <g id="&lt;Group&gt;">
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" fill-rule="evenodd" class="s1" d="m74.8 106.5c0.1 4.4-1.7 8.3-4.5 11.1-2.8 2.9-6.7 4.6-11 4.6-8.6 0.1-15.6-6.9-15.7-15.5 0-4.4 1.7-8.3 4.6-11.2 2.8-2.8 6.7-4.6 11-4.6 4.3 0 8.2 1.7 11 4.6 2.9 2.8 4.6 6.7 4.6 11zm-13.7-6.4c0-1.1-0.9-1.9-1.9-1.9-1.1 0-1.9 0.8-1.9 1.9 0 1 0.9 1.9 1.9 1.9 1 0 1.9-0.9 1.9-1.9zm0 13c0-1.1-0.8-1.9-1.9-1.9-1 0-1.8 0.8-1.8 1.9 0 1 0.8 1.9 1.8 1.9 1.1 0 1.9-0.9 1.9-1.9zm-8.2-8.4l0.1 3.8 12.5-0.1v-3.7z"/>
                                </g>
                            </g>
                            <g id="&lt;Group&gt;">
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Compound Path&gt;" fill-rule="evenodd" class="s1" d="m111.4 69.7c0.1 4.3-1.7 8.2-4.5 11-2.8 2.9-6.7 4.6-11 4.7-8.6 0-15.7-7-15.7-15.6 0-4.4 1.7-8.3 4.6-11.1 2.8-2.9 6.7-4.6 11-4.6 4.3-0.1 8.2 1.7 11 4.5 2.9 2.8 4.6 6.7 4.6 11.1zm-12.9 0l3.1-3.1-2.7-2.7-3.1 3.1-3.1-3.1-2.7 2.7 3.1 3.1-3 3.2 2.6 2.6 3.1-3.1 3.1 3.1 2.7-2.7z"/>
                                </g>
                            </g>
                            <g id="&lt;Group&gt;">
                                <g id="&lt;Group&gt;">
                                    <path id="&lt;Path&gt;" class="s1" d="m87.4 102.8c-2.2 1.9-4.5 3.9-6.8 6.1q-0.2-1.2-0.3-2.4c0-4.3 1.7-8.3 4.6-11.1 2.8-2.8 6.7-4.6 11-4.6q2.8 0 5.2 0.9c-4.7 3.5-9.1 7-13.2 10.7-0.3 0.1-0.4 0.3-0.5 0.4z"/>
                                    <path id="&lt;Path&gt;" class="s1" d="m111.6 106.1v0.3c0 4.3-1.7 8.2-4.6 11.1-2.4 2.4-5.6 4.1-9.2 4.5 4.4-5.5 9-10.9 13.8-15.9z"/>
                                </g>
                            </g>
                            <g id="&lt;Group&gt;">
                                <path id="&lt;Compound Path&gt;" class="s2" d="m34.1 157.3c-1.2-2.3-2.5-4.5-3.9-6.6-1.6-2.4-3.3-4.8-4.9-7.1l-0.3-0.4c-2.4-3.2-4.8-6.2-7.3-9.1v8.2c0 4.6 1.9 8.8 4.9 11.9 3.1 3 7.3 4.9 11.9 4.9h0.5c-0.3-0.6-0.6-1.2-0.9-1.8zm100.4-73c-1.5 1.3-3 2.6-4.5 3.8-0.3 0.3-0.7 0.6-1.1 0.9l0.2 52.9c0 2.2-0.9 4.3-2.4 5.7-1.4 1.5-3.5 2.4-5.7 2.4l-42.8 0.2c-0.1 0.3-0.3 0.6-0.5 0.9l-2.4 4c-0.7 1.3-1.4 2.5-2.1 3.8l47.9-0.2c4.6 0 8.8-1.9 11.8-4.9 3-3.1 4.9-7.3 4.9-11.9l-0.2-60.2z"/>
                                <path id="&lt;Compound Path&gt;" fill-rule="evenodd" class="s2" d="m137.2 51.7l0.2 0.2 0.1 16.3q-0.4 0.2-0.8 0.4c-2.4 1.2-5 2.7-7.9 4.5v-17.4l-1.2-1h-11.5c-4.6 0-8.8-1.8-11.9-4.9-3-3-4.9-7.2-4.9-11.8l-0.1-11.4-0.3-0.5-64.9 0.2c-2.2 0-4.2 0.9-5.7 2.4-1.4 1.5-2.3 3.5-2.3 5.7l0.3 90.7q-2.8-1.5-5.7-2.9l-2.3-1c-0.2-0.1-0.5-0.2-0.7-0.3l-0.3-86.4c0-4.7 1.9-8.9 4.9-11.9 3-3.1 7.2-5 11.8-5l69.7-0.3c1 0.1 2 0.5 2.9 1.3l29.3 28.4c0.9 0.7 1.5 1.9 1.5 3.3q0 0.7-0.2 1.4zm-14.8-5.7l-14.5-14 0.1 6c0 2.2 0.9 4.2 2.4 5.7 1.4 1.4 3.4 2.3 5.7 2.3z"/>
                            </g>
                            <g id="&lt;Group&gt;">
                                <path id="&lt;Path&gt;" class="s3" d="m161.7 60.5c-6.2 3.8-12.4 7.9-18.2 12.4-2.1 1.5-4.1 3-6 4.4q-0.5 0.5-1 0.9l-4.2 3.5q-1.7 1.5-3.4 2.9c-0.4 0.3-0.8 0.6-1.1 1-0.4 0.2-0.7 0.5-1.1 0.8-1.6 1.5-3.1 2.9-4.7 4.4-1 1-2 2-3.1 2.9l-1.3 1.2c-0.8 0.9-1.7 1.7-2.6 2.5l-3.6 3.9c-0.2 0.2-0.3 0.4-0.5 0.5-0.3 0.3-0.5 0.6-0.8 0.8-5.4 5.8-10.8 12-15.9 18.4-0.2 0.3-0.5 0.6-0.7 0.9-3.1 4.1-6.1 8.1-8.9 12.1-1.2 1.7-2.3 3.4-3.5 5.2q-1.1 1.8-2.3 3.6c-1.4 2.1-2.7 4.3-4 6.5l-0.5 0.9-2 3.3c-0.8 1.5-1.7 3-2.5 4.6-0.2 0.3-0.3 0.6-0.5 0.8q-0.5 1-0.9 1.8c-0.8 1.5-1.5 3-2.3 4.6-0.4 0.8-0.8 1.5-1.1 2.3q-0.2 0.5-0.4 1l-22.3-0.1q-1.2-4.4-3.5-9.5c-0.1-0.2-0.3-0.5-0.4-0.7-0.4-0.9-0.8-1.7-1.3-2.6q-1.5-2.8-3.2-5.4-0.4-0.7-0.9-1.4c-1.6-2.5-3.3-5-5-7.3-0.1-0.2-0.2-0.4-0.3-0.4q-0.6-0.9-1.3-1.8c-2.5-3.1-5-6.2-7.5-9q-0.6-0.7-1.3-1.4l-0.6-0.7c-2.1-2.4-4.2-4.6-6.3-6.7q3 1.3 6.2 2.7l0.7 0.3 1.5 0.7c2.2 1 4.3 2.1 6.4 3.2q0.5 0.2 0.8 0.4c3.2 1.6 6.1 3.1 8.7 4.8 6.4 3.8 11.1 7 15.4 10.5l1.3 1 1.4-1.7c1-1.3 2.1-2.6 3.1-3.9 2.4-2.9 4.9-5.7 7.3-8.5l2.6-2.8c1.7-1.8 3.3-3.7 5.1-5.5 3.4-3.5 6.9-6.9 10.4-10.2 0.2-0.2 0.4-0.3 0.5-0.5 2.2-2.1 4.3-4 6.4-5.8q0.6-0.5 1.1-1l0.4-0.2q0.8-0.7 1.5-1.3c3.9-3.4 8-6.7 12.6-10.2l0.4-0.3 4.3-3.3c0.8-0.5 1.7-1.1 2.5-1.7 0.7-0.4 1.4-0.9 2-1.3l2.6-1.8q3.3-2.3 6.5-4.4l6.4-3.8c0.3-0.2 0.6-0.3 0.9-0.5 2.9-1.8 5.4-3.2 7.8-4.5q0.4-0.2 0.8-0.4c1-0.6 2.1-1.1 3.2-1.7 6.6-3.6 13.4-6.7 20.2-9.4z"/>
                            </g>
                        </g>
                    </g>
                </svg>
            </a>
        </h1>

        <div class="collapse navbar-collapse" id="sidebar-menu">
            <div id="menu-search-container" class="sidebar-search-wrap">
                <div class="sidebar-search">
                    <i class="ti ti-search" aria-hidden="true"></i>
                    <input type="search" id="menu-search-input" name="navigation_menu_filter" autocomplete="new-password" autocapitalize="none" spellcheck="false" readonly data-lpignore="true" data-1p-ignore="true" data-bwignore="true" class="sidebar-search-input" placeholder="Menüde ara..." aria-label="Menü ara">
                </div>
                <div class="sidebar-menu-settings dropdown">
                    <button type="button" class="btn btn-sm btn-menu-settings" id="sidebarMenuSettingsDropdown" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" title="Menü Ayarları">
                        <i class="ti ti-settings"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="sidebarMenuSettingsDropdown">
                        <h6 class="dropdown-header">Menü Ayarları</h6>
                        <a class="dropdown-item" href="javascript:;" id="btn-reset-menu-order">
                            <i class="ti ti-rotate-clockwise text-primary me-2"></i> Varsayılan Menü Sırası
                        </a>
                    </div>
                </div>
            </div>
            <ul class="navbar-nav 1" id="sortable-menu">

                <?php

                //Aktif sayfa alınır
                $active_page = $_GET['p'] ?? '';

                //Menü isimleri Model altındakii Menus.php sayfası ile tablodan getirilir
                $top_menus = $menus->getMenus($_SESSION['user']->id ?? null);

                //Gelen menü isimlerinde döngüye girilir
                foreach ($top_menus as $menu) {

                    $menu_auth = $Auths->getAuthIdByTitle($menu->page_name);
                    $is_superadmin = ($_SESSION['user']->superadmin ?? 0) == 1;

                    if ($is_superadmin && !$Auths->isSuperadminTopMenuAllowed($menu)) {
                        continue;
                    }

                    // Alt menü yetkisi üst menüyü görünür kılsa bile, auths tablosunda
                    // superadmin olarak işaretli bir yönetim menüsü normal kullanıcıya
                    // hiçbir koşulda gösterilmez.
                    if (($_SESSION['user']->superadmin ?? 0) != 1
                        && (($menu_auth && (int) ($menu_auth->superadmin ?? 0) === 1)
                            || (!$menu_auth && $Auths->isSuperadminOnlyPage($menu->page_link)))) {
                        continue;
                    }

                    // Superadmin menü kontrolü
                    if ($menu->page_link == 'supports/admin-tickets' && ($_SESSION['user']->superadmin ?? 0) != 1) {
                        continue;
                    }

                    // Paket/rol kısıtlamasında üst modülün kendisi değil de sadece alt yetkilerinden
                    // biri verilmiş olabilir (bkz. Model/Auths.php paket-modül kesişimi). Bu yüzden üst
                    // menünün kendi yetkisi yoksa bile, gösterilebilecek en az bir alt menüsü varsa
                    // üst menü yine de listelenir; alt menüler kendi yetkileriyle ayrıca filtrelenir.
                    $has_authorized_submenu = false;
                    foreach ($menus->getSubMenus($menu->id) as $candidate_sub_menu) {
                        if ($candidate_sub_menu->isMenu <= 0) {
                            continue;
                        }
                        if ($candidate_sub_menu->is_authorize != 1) {
                            $has_authorized_submenu = true;
                            break;
                        }
                        $candidate_auth_id = $Auths->getAuthIdByTitle($candidate_sub_menu->page_name)?->id ?? 0;
                        if ($Auths->AuthorizeByAuthId($candidate_auth_id)) {
                            $has_authorized_submenu = true;
                            break;
                        }
                    }

                    //Eğer menü yetkiye tabi ise yetki kontrolü yapılır
                    if ($menu->is_authorize == 1) {
                        //Sayfa Adından Auths tablosundaki title alanı ile sorgulanarak yetki id alınır
                        $auth_id = $menu_auth?->id ?? 0;

                        //Yetki id'si gelen sayfa için yetki kontrolü yapılır
                        if (!$Auths->AuthorizeByAuthId($auth_id) && !$has_authorized_submenu) {
                            continue;
                        }
                    }

                    // Alt menüleri kontrol et ve sadece menüde görünen yetkili alt menüleri topla
                    $all_sub_menus = $menus->getSubMenus($menu->id);
                    $authorized_visible_submenus = [];
                    $is_parent_active = ($active_page == $menu->page_link);
                    $has_active_child = false;

                    foreach ($all_sub_menus as $sub_menu) {
                        if ($active_page == $sub_menu->page_link) {
                            $has_active_child = true;
                            $is_parent_active = true;
                        }

                        if ($sub_menu->isMenu <= 0) {
                            continue;
                        }

                        if ($is_superadmin && !$Auths->isSuperadminPageAllowed((string) $sub_menu->page_link)) {
                            continue;
                        }

                        if ($sub_menu->is_authorize == 1) {
                            $auth_id = $Auths->getAuthIdByTitle($sub_menu->page_name)?->id ?? 0;
                            if (!$Auths->AuthorizeByAuthId($auth_id)) {
                                continue;
                            }
                        }

                        $authorized_visible_submenus[] = $sub_menu;
                    }

                    $has_dropdown = count($authorized_visible_submenus) > 0;
                    $dropdown_class = $has_dropdown ? 'dropdown' : '';
                    $dropdown_toggle_class = $has_dropdown ? 'dropdown-toggle' : 'no-arrow';
                    $is_open = $has_dropdown && $has_active_child;
                    $active_class = $is_parent_active ? 'active' : '';
                    $show_class = $is_open ? 'show' : '';
                    ?>

                    <!-- Menü oluşturulur -->
                    <li class="nav-item <?php echo $active_class; ?> <?php echo $dropdown_class; ?> <?php echo $show_class; ?>" data-id="<?php echo $menu->id; ?>">

                        <?php
                        $menuPath = $menu->page_link ? (\App\Routing\Router::pathForPage($menu->page_link) ?? $menu->page_link) : '';
                        $menuHref = $has_dropdown ? 'javascript:;' : ($menuPath !== '' ? '/' . ltrim($menuPath, '/') : 'javascript:;');
                        ?>
                        <a class="nav-link <?php echo $dropdown_toggle_class; ?> <?php echo $active_class; ?>" draggable="false"
                            href="<?php echo htmlspecialchars($menuHref, ENT_QUOTES, 'UTF-8'); ?>"
                            data-bs-auto-close="false" role="button" aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>">

                            <span class="nav-link-icon" data-tooltip-location="right">
                                <i class="ti ti-<?php echo htmlspecialchars($menu->icon, ENT_QUOTES, 'UTF-8'); ?> icon"></i>
                            </span>
                            <span class="nav-link-title">
                                <?php echo htmlspecialchars($menu->page_name, ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </a>

                        <?php if ($has_dropdown): ?>
                        <!-- Menü altında başka menüler varsa dropdown menü oluşturulur -->
                        <div class="dropdown-menu <?php echo $show_class; ?>" <?php echo $is_open ? 'style="display: block;"' : ''; ?>>
                            <div class="dropdown-menu-columns">
                                <div class="dropdown-menu-column">
                                    <?php foreach ($authorized_visible_submenus as $sub_menu):
                                        $is_sub_active = ($active_page == $sub_menu->page_link);
                                        $sub_active_class = $is_sub_active ? 'active active-link' : '';
                                        $subPath = $sub_menu->page_link ? (\App\Routing\Router::pathForPage($sub_menu->page_link) ?? $sub_menu->page_link) : '';
                                        $subHref = $subPath !== '' ? '/' . ltrim($subPath, '/') : 'javascript:;';
                                    ?>
                                        <a class="dropdown-item <?php echo $sub_active_class; ?>"
                                            href="<?php echo htmlspecialchars($subHref, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($sub_menu->page_name, ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Sub-menu End -->
                    </li>
                <?php } ?>
            </ul>
        </div>
    </div>
</aside>

<script src="/dist/js/Sortable.min.js"></script>
<script>
$(document).ready(function() {
    var $searchInput = $('#menu-search-input');
    var $menuItems = $('#sidebar-menu .navbar-nav > li.nav-item');

    $searchInput.one('pointerdown focus', function() {
        this.removeAttribute('readonly');
    });

    function clearAutofilledMenuSearch() {
        if ($searchInput.val().indexOf('@') !== -1) {
            $searchInput.val('').trigger('search');
        }
    }

    clearAutofilledMenuSearch();
    setTimeout(clearAutofilledMenuSearch, 150);
    setTimeout(clearAutofilledMenuSearch, 800);

    // Akordeon Tıklama Yönetimi (Aydınoğulları Stili Slide Animation)
    $('#sortable-menu > li.nav-item.dropdown > a.dropdown-toggle').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var $parentLi = $(this).closest('li.nav-item');
        var $submenu = $parentLi.find('.dropdown-menu');

        if ($parentLi.hasClass('show')) {
            $submenu.slideUp(180, function() {
                $parentLi.removeClass('show');
                $parentLi.find('.dropdown-toggle').attr('aria-expanded', 'false');
            });
        } else {
            $parentLi.addClass('show');
            $parentLi.find('.dropdown-toggle').attr('aria-expanded', 'true');
            $submenu.slideDown(180);
        }
    });

    // Menü Sırasını Sıfırlama Butonu
    $('#btn-reset-menu-order').on('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Menü Sırası Sıfırlansın mı?',
            text: 'Menü sıralaması varsayılan orijinal düzene döndürülecektir.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Evet, Sıfırla',
            cancelButtonText: 'Vazgeç',
            customClass: {
                confirmButton: 'btn btn-primary me-2',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'api/users/menu_order.php',
                    type: 'POST',
                    data: { action: 'reset_order' },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Başarılı',
                                text: response.message,
                                timer: 1400,
                                showConfirmButton: false
                            }).then(function() {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Hata', response.message || 'Sıfırlama başarısız oldu.', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Hata', 'İşlem sırasında sunucu ile iletişim hatası oluştu.', 'error');
                    }
                });
            }
        });
    });

    // Store original dropdown menu show states
    $menuItems.each(function() {
        var $dropdownMenu = $(this).find('.dropdown-menu');
        if ($dropdownMenu.length) {
            $dropdownMenu.attr('data-original-show', $dropdownMenu.hasClass('show') ? 'true' : 'false');
        }
    });

    // Turkish character aware lowercase function
    function toTurkishLowercase(str) {
        if (!str) return '';
        return str.replace(/I/g, 'ı')
                  .replace(/İ/g, 'i')
                  .replace(/Ğ/g, 'ğ')
                  .replace(/Ü/g, 'ü')
                  .replace(/Ş/g, 'ş')
                  .replace(/Ö/g, 'ö')
                  .replace(/Ç/g, 'ç')
                  .toLowerCase();
    }

    $searchInput.on('input search', function() {
        var query = toTurkishLowercase($(this).val().trim());

        if (query === '') {
            // Restore default menu visibility and dropdown expand states
            $menuItems.show();
            $menuItems.find('.dropdown-item').show();
            $menuItems.each(function() {
                var $dropdownMenu = $(this).find('.dropdown-menu');
                if ($dropdownMenu.length) {
                    var originalShow = $dropdownMenu.attr('data-original-show') === 'true';
                    if (originalShow) {
                        $(this).addClass('show');
                        $dropdownMenu.addClass('show').show();
                    } else {
                        $(this).removeClass('show');
                        $dropdownMenu.removeClass('show').hide();
                    }
                }
            });
            return;
        }

        $menuItems.each(function() {
            var $item = $(this);
            var parentTitle = toTurkishLowercase($item.find('.nav-link-title').text());
            var parentMatched = parentTitle.indexOf(query) !== -1;
            
            var $submenus = $item.find('.dropdown-item');
            var anySubMatched = false;

            if ($submenus.length > 0) {
                $submenus.each(function() {
                    var $sub = $(this);
                    var subText = toTurkishLowercase($sub.text());
                    var subMatched = subText.indexOf(query) !== -1;
                    
                    if (subMatched) {
                        anySubMatched = true;
                        $sub.show();
                    } else {
                        $sub.hide();
                    }
                });

                if (parentMatched || anySubMatched) {
                    $item.show();
                    $item.addClass('show');
                    var $dropdownMenu = $item.find('.dropdown-menu');
                    if (anySubMatched) {
                        $dropdownMenu.addClass('show').show();
                    } else {
                        $submenus.show();
                        $dropdownMenu.addClass('show').show();
                    }
                } else {
                    $item.hide();
                }
            } else {
                if (parentMatched) {
                    $item.show();
                } else {
                    $item.hide();
                }
            }
        });
    });

    // Sidebar aşağı kaydırıldığında arama alanını opaklaştır
    function updateSidebarSearchScroll() {
        var sidebarMenuEl = document.getElementById('sidebar-menu');
        var searchWrapEl = document.getElementById('menu-search-container');
        if (!searchWrapEl) return;
        
        var scrollTop = 0;
        if (sidebarMenuEl) {
            scrollTop = Math.max(scrollTop, sidebarMenuEl.scrollTop || 0);
        }
        var navbarEl = document.getElementById('navbar');
        if (navbarEl) {
            scrollTop = Math.max(scrollTop, navbarEl.scrollTop || 0);
        }
        
        if (scrollTop > 2) {
            searchWrapEl.classList.add('is-scrolled');
        } else {
            searchWrapEl.classList.remove('is-scrolled');
        }
    }

    var sidebarMenuEl = document.getElementById('sidebar-menu');
    if (sidebarMenuEl) {
        sidebarMenuEl.addEventListener('scroll', updateSidebarSearchScroll, { passive: true });
    }
    window.addEventListener('scroll', updateSidebarSearchScroll, { passive: true });
    $('#sidebar-menu').on('scroll', updateSidebarSearchScroll);
    updateSidebarSearchScroll();

    // Menü sürükle-bırak sıralama
    var sortableEl = document.getElementById('sortable-menu');
    if (sortableEl) {
        new Sortable(sortableEl, {
            animation: 150,
            ghostClass: 'menu-sortable-ghost',
            chosenClass: 'menu-sortable-chosen',
            dragClass: 'menu-sortable-drag',
            draggable: '.nav-item',
            onEnd: function (evt) {
                var order = [];
                $('#sortable-menu > li.nav-item').each(function(index) {
                    var menuId = $(this).attr('data-id');
                    if (menuId) {
                        order.push({
                            id: menuId,
                            index: index
                        });
                    }
                });

                if (order.length > 0) {
                    $.ajax({
                        url: 'api/users/menu_order.php',
                        type: 'POST',
                        data: {
                            action: 'save_order',
                            order: JSON.stringify(order)
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                const Toast = Swal.mixin({
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 1500,
                                    timerProgressBar: true,
                                    didOpen: (toast) => {
                                        toast.addEventListener('mouseenter', Swal.stopTimer)
                                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                                    }
                                });
                                Toast.fire({
                                    icon: 'success',
                                    title: 'Menü sırası güncellendi'
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Hata',
                                    text: response.message
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Hata',
                                text: 'İletişim hatası oluştu.'
                            });
                        }
                    });
                }
            }
        });
    }
});
</script>
