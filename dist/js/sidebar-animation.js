/**
 * Puantor Sidebar Background Animation Engine (Constellation / Plexus & Particles)
 * Ultra-lightweight HTML5 Canvas particle network for sidebar.
 */
(function(window, document) {
    'use strict';

    var canvas = null;
    var ctx = null;
    var sidebar = null;
    var animationFrameId = null;
    var particles = [];
    var width = 0;
    var height = 0;
    var dpr = 1;
    var isRunning = false;
    var currentEffect = 'constellation'; // 'constellation' | 'particles' | 'geometric' | 'none'
    var mouse = { x: -1000, y: -1000, active: false, radius: 100 };
    var resizeObserver = null;

    // Renk Ayarları
    var colorScheme = {
        nodeColor: 'rgba(255, 255, 255, ',
        lineColor: 'rgba(180, 225, 255, ',
        accentColor: 'rgba(56, 189, 248, ',
        isLight: false
    };

    /**
     * Aktif sidebar temasına göre renk paletini belirler
     */
    function updateColors() {
        var html = document.documentElement;
        var sidebarTheme = html.getAttribute('data-sidebar-theme') || (sidebar ? sidebar.getAttribute('data-sidebar-theme') : 'klasik-koyu') || 'klasik-koyu';
        var bsTheme = html.getAttribute('data-bs-theme') || 'light';
        var isLight = (sidebarTheme === 'sade-beyaz' || sidebarTheme === 'slate-light' || sidebarTheme === 'platin-gri');

        colorScheme.isLight = isLight;

        if (isLight) {
            colorScheme.nodeColor = 'rgba(71, 85, 105, ';
            colorScheme.lineColor = 'rgba(99, 102, 241, ';
            colorScheme.accentColor = 'rgba(79, 70, 229, ';
        } else if (sidebarTheme === 'koyu-zumrut') {
            colorScheme.nodeColor = 'rgba(167, 243, 208, ';
            colorScheme.lineColor = 'rgba(52, 211, 153, ';
            colorScheme.accentColor = 'rgba(16, 185, 129, ';
        } else if (sidebarTheme === 'koyu-mor') {
            colorScheme.nodeColor = 'rgba(233, 213, 255, ';
            colorScheme.lineColor = 'rgba(192, 132, 252, ';
            colorScheme.accentColor = 'rgba(168, 85, 247, ';
        } else if (sidebarTheme === 'cyber-dark') {
            colorScheme.nodeColor = 'rgba(103, 232, 249, ';
            colorScheme.lineColor = 'rgba(34, 211, 238, ';
            colorScheme.accentColor = 'rgba(6, 182, 212, ';
        } else {
            // Varsayılan Koyu / Klasik Koyu / Slate / Okyanus
            colorScheme.nodeColor = 'rgba(255, 255, 255, ';
            colorScheme.lineColor = 'rgba(148, 210, 255, ';
            colorScheme.accentColor = 'rgba(56, 189, 248, ';
        }
    }

    /**
     * Canvas boyutlarını ayarlar
     */
    function resizeCanvas() {
        if (!sidebar || !canvas) return;
        var rect = sidebar.getBoundingClientRect();
        width = rect.width;
        height = rect.height;
        dpr = Math.min(window.devicePixelRatio || 1, 2);

        canvas.width = Math.floor(width * dpr);
        canvas.height = Math.floor(height * dpr);
        canvas.style.width = width + 'px';
        canvas.style.height = height + 'px';

        if (ctx) {
            ctx.setTransform(1, 0, 0, 1, 0, 0);
            ctx.scale(dpr, dpr);
        }

        initParticles();
    }

    /**
     * Parçacıkları oluşturur
     */
    function initParticles() {
        particles = [];
        if (!width || !height || currentEffect === 'none') return;

        var isCollapsed = width < 120;
        var count = isCollapsed ? 14 : Math.min(Math.max(Math.floor((width * height) / 14000), 22), 40);

        if (currentEffect === 'constellation') {
            for (var i = 0; i < count; i++) {
                particles.push({
                    x: Math.random() * width,
                    y: Math.random() * height,
                    vx: (Math.random() - 0.5) * 0.45,
                    vy: (Math.random() - 0.5) * 0.45,
                    radius: Math.random() * 1.6 + 1.2,
                    baseAlpha: Math.random() * 0.4 + 0.25,
                    alpha: 0.3,
                    pulseSpeed: Math.random() * 0.02 + 0.01,
                    pulseAngle: Math.random() * Math.PI * 2,
                    isSpecial: Math.random() > 0.75
                });
            }
        } else if (currentEffect === 'particles') {
            for (var j = 0; j < count; j++) {
                particles.push({
                    x: Math.random() * width,
                    y: Math.random() * height,
                    vx: (Math.random() - 0.5) * 0.3,
                    vy: -(Math.random() * 0.4 + 0.2), // Yükselen
                    radius: Math.random() * 3 + 1.5,
                    baseAlpha: Math.random() * 0.45 + 0.15,
                    alpha: 0.3,
                    wobble: Math.random() * Math.PI * 2,
                    wobbleSpeed: Math.random() * 0.03 + 0.01
                });
            }
        } else if (currentEffect === 'geometric') {
            for (var k = 0; k < count; k++) {
                particles.push({
                    x: Math.random() * width,
                    y: Math.random() * height,
                    vx: (Math.random() - 0.5) * 0.35,
                    vy: (Math.random() - 0.5) * 0.35,
                    size: Math.random() * 4 + 3,
                    rotation: Math.random() * Math.PI,
                    rotSpeed: (Math.random() - 0.5) * 0.02,
                    baseAlpha: Math.random() * 0.35 + 0.2,
                    alpha: 0.25
                });
            }
        }
    }

    /**
     * Animasyon döngüsü (60 FPS)
     */
    function render() {
        if (!isRunning || currentEffect === 'none' || !ctx) return;

        ctx.clearRect(0, 0, width, height);

        var isLight = colorScheme.isLight;

        if (currentEffect === 'constellation') {
            var maxDistance = width < 120 ? 65 : 85;
            var maxDistSq = maxDistance * maxDistance;

            // 1. Çizgileri çiz (Bağlantılar)
            for (var i = 0; i < particles.length; i++) {
                var p1 = particles[i];

                for (var j = i + 1; j < particles.length; j++) {
                    var p2 = particles[j];
                    var dx = p1.x - p2.x;
                    var dy = p1.y - p2.y;
                    var distSq = dx * dx + dy * dy;

                    if (distSq < maxDistSq) {
                        var dist = Math.sqrt(distSq);
                        var lineAlpha = (1 - dist / maxDistance) * (isLight ? 0.18 : 0.25);
                        ctx.beginPath();
                        ctx.strokeStyle = colorScheme.lineColor + lineAlpha + ')';
                        ctx.lineWidth = 0.85;
                        ctx.moveTo(p1.x, p1.y);
                        ctx.lineTo(p2.x, p2.y);
                        ctx.stroke();
                    }
                }

                // Fare ile bağlantı
                if (mouse.active) {
                    var mdx = p1.x - mouse.x;
                    var mdy = p1.y - mouse.y;
                    var mDistSq = mdx * mdx + mdy * mdy;
                    var mRadius = mouse.radius;
                    if (mDistSq < mRadius * mRadius) {
                        var mDist = Math.sqrt(mDistSq);
                        var mAlpha = (1 - mDist / mRadius) * (isLight ? 0.3 : 0.45);
                        ctx.beginPath();
                        ctx.strokeStyle = colorScheme.accentColor + mAlpha + ')';
                        ctx.lineWidth = 1;
                        ctx.moveTo(p1.x, p1.y);
                        ctx.lineTo(mouse.x, mouse.y);
                        ctx.stroke();

                        // Hafif itme
                        p1.x += (mdx / mDist) * 0.3;
                        p1.y += (mdy / mDist) * 0.3;
                    }
                }
            }

            // 2. Parçacık düğümlerini (Noktaları) çiz
            for (var k = 0; k < particles.length; k++) {
                var p = particles[k];

                p.pulseAngle += p.pulseSpeed;
                var pulseFactor = Math.sin(p.pulseAngle) * 0.25;
                p.alpha = Math.max(0.1, p.baseAlpha + pulseFactor);

                // Nokta
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
                if (p.isSpecial) {
                    ctx.fillStyle = colorScheme.accentColor + (p.alpha * 1.2) + ')';
                    // Minik parlama halesi
                    ctx.shadowBlur = 6;
                    ctx.shadowColor = colorScheme.accentColor + '0.6)';
                } else {
                    ctx.fillStyle = colorScheme.nodeColor + p.alpha + ')';
                    ctx.shadowBlur = 0;
                }
                ctx.fill();
                ctx.shadowBlur = 0;

                // Konum güncelle
                p.x += p.vx;
                p.y += p.vy;

                // Kenar çarpışmaları (Yumuşak sekme / sarma)
                if (p.x < 0) { p.x = 0; p.vx = -p.vx; }
                if (p.x > width) { p.x = width; p.vx = -p.vx; }
                if (p.y < 0) { p.y = 0; p.vy = -p.vy; }
                if (p.y > height) { p.y = height; p.vy = -p.vy; }
            }
        } else if (currentEffect === 'particles') {
            // Yüzen Bokeh / Işıklar
            for (var m = 0; m < particles.length; m++) {
                var bp = particles[m];
                bp.wobble += bp.wobbleSpeed;
                bp.x += Math.sin(bp.wobble) * 0.3 + bp.vx;
                bp.y += bp.vy;

                if (bp.y < -10) {
                    bp.y = height + 10;
                    bp.x = Math.random() * width;
                }

                ctx.beginPath();
                ctx.arc(bp.x, bp.y, bp.radius, 0, Math.PI * 2);
                var grad = ctx.createRadialGradient(bp.x, bp.y, 0, bp.x, bp.y, bp.radius);
                grad.addColorStop(0, colorScheme.accentColor + bp.baseAlpha + ')');
                grad.addColorStop(1, colorScheme.accentColor + '0)');
                ctx.fillStyle = grad;
                ctx.fill();
            }
        } else if (currentEffect === 'geometric') {
            // Geometrik Kare / Elmas
            for (var n = 0; n < particles.length; n++) {
                var gp = particles[n];
                gp.rotation += gp.rotSpeed;
                gp.x += gp.vx;
                gp.y += gp.vy;

                if (gp.x < 0 || gp.x > width) gp.vx = -gp.vx;
                if (gp.y < 0 || gp.y > height) gp.vy = -gp.vy;

                ctx.save();
                ctx.translate(gp.x, gp.y);
                ctx.rotate(gp.rotation);
                ctx.strokeStyle = colorScheme.accentColor + gp.baseAlpha + ')';
                ctx.lineWidth = 1;
                ctx.strokeRect(-gp.size / 2, -gp.size / 2, gp.size, gp.size);
                ctx.restore();
            }
        }

        animationFrameId = requestAnimationFrame(render);
    }

    /**
     * Efekti başlatır
     */
    function start() {
        if (isRunning) return;
        isRunning = true;
        updateColors();
        resizeCanvas();
        if (currentEffect !== 'none') {
            animationFrameId = requestAnimationFrame(render);
        }
    }

    /**
     * Efekti durdurur
     */
    function stop() {
        isRunning = false;
        if (animationFrameId) {
            cancelAnimationFrame(animationFrameId);
            animationFrameId = null;
        }
        if (ctx && width && height) {
            ctx.clearRect(0, 0, width, height);
        }
    }

    /**
     * Efekt tipini değiştirir ('constellation', 'particles', 'geometric', 'none')
     */
    function setEffect(effectName, savePreference) {
        currentEffect = effectName || 'constellation';

        if (savePreference !== false) {
            try {
                localStorage.setItem('app_sidebar_effect', currentEffect);
                document.cookie = "app_sidebar_effect=" + encodeURIComponent(currentEffect) + "; path=/; max-age=31536000; SameSite=Lax";
            } catch (e) {}
        }

        if (canvas) {
            canvas.style.display = (currentEffect === 'none') ? 'none' : 'block';
        }

        if (currentEffect === 'none') {
            stop();
        } else {
            if (!isRunning) {
                start();
            } else {
                initParticles();
            }
        }

        if (window.syncActiveSidebarEffectButtons) {
            window.syncActiveSidebarEffectButtons();
        }
    }

    /**
     * DOM Olaylarını ve Canvas Hazırlığını Başlatır
     */
    function init() {
        sidebar = document.getElementById('navbar') || document.querySelector('.navbar-vertical');
        if (!sidebar) return;

        canvas = document.getElementById('sidebar-particles-canvas');
        if (!canvas) {
            canvas = document.createElement('canvas');
            canvas.id = 'sidebar-particles-canvas';
            canvas.className = 'sidebar-particles-canvas';
            sidebar.insertBefore(canvas, sidebar.firstChild);
        }

        ctx = canvas.getContext('2d', { alpha: true });

        // Kaydedilmiş tercihi al (Varsayılan: 'constellation')
        var savedEffect = 'constellation';
        try {
            savedEffect = localStorage.getItem('app_sidebar_effect') || 'constellation';
        } catch (e) {}

        currentEffect = savedEffect;

        // Fare hareketleri
        sidebar.addEventListener('mousemove', function(e) {
            var rect = sidebar.getBoundingClientRect();
            mouse.x = e.clientX - rect.left;
            mouse.y = e.clientY - rect.top;
            mouse.active = true;
        }, { passive: true });

        sidebar.addEventListener('mouseleave', function() {
            mouse.active = false;
            mouse.x = -1000;
            mouse.y = -1000;
        }, { passive: true });

        // Sidebar genişlik/yükseklik değişimini izle (Menü daraltma vb.)
        if (window.ResizeObserver) {
            resizeObserver = new ResizeObserver(function() {
                resizeCanvas();
            });
            resizeObserver.observe(sidebar);
        } else {
            window.addEventListener('resize', resizeCanvas);
        }

        // Tab inaktif olduğunda durdur, aktifleşince başlat (Pil tasarrufu)
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                if (animationFrameId) cancelAnimationFrame(animationFrameId);
            } else {
                if (isRunning && currentEffect !== 'none') {
                    animationFrameId = requestAnimationFrame(render);
                }
            }
        });

        // Tema değişimlerini gözlemle (data-sidebar-theme veya data-bs-theme değiştiğinde)
        var themeObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'data-sidebar-theme' || mutation.attributeName === 'data-bs-theme') {
                    updateColors();
                }
            });
        });
        themeObserver.observe(document.documentElement, { attributes: true });

        // Başlat
        setEffect(currentEffect, false);
    }

    // Global API
    window.SidebarParticles = {
        init: init,
        setEffect: setEffect,
        getEffect: function() { return currentEffect; },
        updateColors: updateColors,
        resize: resizeCanvas,
        start: start,
        stop: stop
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})(window, document);
