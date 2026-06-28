/**
 * Puantor Premium Mobile Pull-to-Refresh Utility
 * Self-contained touch listener and animated SVG spinner indicator.
 */
(function() {
    document.addEventListener('DOMContentLoaded', () => {
        const scrollEl = document.querySelector('.app-content');
        const shellEl = document.querySelector('.app-shell');
        
        if (!scrollEl || !shellEl) return;
        
        // 1. Inject CSS Styles Dynamically
        const style = document.createElement('style');
        style.textContent = `
            .app-content {
                position: relative !important;
                z-index: 1010 !important;
                background-color: var(--mobile-bg-light, #f6f8fb) !important;
            }
            [data-bs-theme="dark"] .app-content {
                background-color: var(--mobile-bg-dark, #1a2234) !important;
            }
            /* Fallback for personnel PWA app.css theme variables */
            body[data-bs-theme="dark"] .app-content {
                background-color: #0f172a !important;
            }
            .pull-to-refresh-container {
                position: absolute;
                top: var(--app-header-height, 56px);
                left: 0;
                width: 100%;
                height: 50px;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1000;
                pointer-events: none;
                opacity: 0;
                transform: scale(0.8);
                transition: opacity 0.2s ease, transform 0.2s ease;
            }
            .pull-to-refresh-container.visible {
                opacity: 1;
                transform: scale(1);
            }
            .ptr-spinner-svg {
                width: 28px;
                height: 28px;
                transform: rotate(-90deg);
                transition: transform 0.1s linear;
            }
            .ptr-spinner-track {
                stroke: rgba(0, 0, 0, 0.08);
            }
            [data-bs-theme="dark"] .ptr-spinner-track {
                stroke: rgba(255, 255, 255, 0.12);
            }
            .ptr-spinner-head {
                stroke: var(--mobile-primary, #206bc4);
                stroke-linecap: round;
                transition: stroke-dashoffset 0.1s linear;
            }
            .ptr-refreshing .ptr-spinner-svg {
                animation: ptr-spin 0.8s linear infinite !important;
            }
            @keyframes ptr-spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
        
        // 2. Create Pull-To-Refresh Indicator HTML Element
        const ptrContainer = document.createElement('div');
        ptrContainer.className = 'pull-to-refresh-container';
        ptrContainer.innerHTML = `
            <svg class="ptr-spinner-svg" viewBox="0 0 36 36">
                <circle class="ptr-spinner-track" cx="18" cy="18" r="14" fill="none" stroke-width="3.5"></circle>
                <circle class="ptr-spinner-head" cx="18" cy="18" r="14" fill="none" stroke-width="3.5" stroke-dasharray="88" stroke-dashoffset="88"></circle>
            </svg>
        `;
        shellEl.insertBefore(ptrContainer, scrollEl);
        
        const spinnerHead = ptrContainer.querySelector('.ptr-spinner-head');
        const spinnerSvg = ptrContainer.querySelector('.ptr-spinner-svg');
        
        // Touch State Variables
        let startX = 0;
        let startY = 0;
        let isPulling = false;
        let isHorizontalScroll = false;
        let firstMoveChecked = false;
        let pullDistance = 0;
        
        const triggerThreshold = 60; // drag distance required to trigger refresh (px)
        const maxPullDistance = 90;   // upper cap for container displacement (px)
        
        const isAtTop = () => scrollEl.scrollTop === 0;
        
        // 3. Register Event Listeners
        scrollEl.addEventListener('touchstart', (e) => {
            if (isAtTop() && e.touches.length === 1) {
                startX = e.touches[0].pageX;
                startY = e.touches[0].pageY;
                isPulling = true;
                isHorizontalScroll = false;
                firstMoveChecked = false;
                scrollEl.style.transition = 'none';
            }
        }, { passive: true });
        
        scrollEl.addEventListener('touchmove', (e) => {
            if (!isPulling) return;
            
            const currentX = e.touches[0].pageX;
            const currentY = e.touches[0].pageY;
            
            const diffX = currentX - startX;
            const diffY = currentY - startY;
            
            // Check direction on first move to prevent conflict with swipe-to-delete cards
            if (!firstMoveChecked) {
                firstMoveChecked = true;
                if (Math.abs(diffX) > Math.abs(diffY)) {
                    isHorizontalScroll = true;
                    isPulling = false;
                    return;
                }
            }
            
            if (isHorizontalScroll) return;
            
            if (diffY > 0) {
                // Apply elastic-pull resistance curve
                pullDistance = Math.min(maxPullDistance, diffY * 0.45);
                
                ptrContainer.classList.add('visible');
                scrollEl.style.transform = `translateY(${pullDistance}px)`;
                
                // Animate progress circle stroke
                const progress = Math.min(1, pullDistance / triggerThreshold);
                const dashoffset = 88 - (progress * 66); // stroke fills up to 75%
                spinnerHead.style.strokeDashoffset = dashoffset;
                
                // Spin SVG relative to pull depth
                spinnerSvg.style.transform = `rotate(${-90 + (progress * 360)}deg)`;
                
                if (e.cancelable) {
                    e.preventDefault();
                }
            } else {
                isPulling = false;
                resetState();
            }
        }, { passive: false });
        
        scrollEl.addEventListener('touchend', () => {
            if (!isPulling || isHorizontalScroll) return;
            isPulling = false;
            
            if (pullDistance >= triggerThreshold) {
                triggerRefresh();
            } else {
                resetState();
            }
        });
        
        const resetState = () => {
            pullDistance = 0;
            scrollEl.style.transition = 'transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.1)';
            scrollEl.style.transform = 'translateY(0)';
            ptrContainer.classList.remove('visible');
            ptrContainer.classList.remove('ptr-refreshing');
            setTimeout(() => {
                spinnerHead.style.strokeDashoffset = 88;
            }, 300);
        };
        
        const triggerRefresh = () => {
            ptrContainer.classList.add('ptr-refreshing');
            spinnerHead.style.strokeDashoffset = 22; // hold partial circle while spinning
            
            scrollEl.style.transition = 'transform 0.2s ease';
            scrollEl.style.transform = `translateY(${triggerThreshold - 15}px)`; // slight visual offset
            
            if (window.navigator && window.navigator.vibrate) {
                window.navigator.vibrate(15);
            }
            
            setTimeout(() => {
                window.location.reload();
            }, 600);
        };
    });
})();
