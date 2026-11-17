import { useCallback, useEffect, useRef, useState } from 'react';

export const PEEK = 0;
export const DEFAULT = 1;
export const FULL = 2;

/**
 * The add panel: a bottom sheet with three snaps (peek, default, full screen).
 *
 * T is the visible height. At or below the default height the sheet slides
 * with a transform; above it the sheet grows taller and the chips become a
 * full grid. Everything here runs on refs and writes styles directly, so a drag
 * never waits on a React render.
 */
export function useBottomSheet({ appRef, dockRef, grabRef, chipsRef, listRef, inputRef, atBottomRef, onCollapse }) {
    const [snap, setSnapState] = useState(DEFAULT);
    const s = useRef({ D: 0, PEEK: 0, T: 0, V: 0, state: DEFAULT, raf: 0, drag: null, tg: null }).current;
    const onCollapseRef = useRef(onCollapse);
    onCollapseRef.current = onCollapse;
    const api = useRef({});

    useEffect(() => {
        const app = appRef.current, dock = dockRef.current, grab = grabRef.current;
        const chips = chipsRef.current, list = listRef.current;
        const reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;

        const fullH = () => app.clientHeight;
        const target = () => (s.state === PEEK ? s.PEEK : s.state === DEFAULT ? s.D : fullH());

        function paint() {
            if (s.T <= s.D) {
                dock.classList.remove('full');
                dock.style.height = '';
                dock.style.transform = 'translate3d(0,' + (s.D - s.T) + 'px,0)';
            } else {
                dock.classList.add('full');
                dock.style.transform = '';
                dock.style.height = s.T + 'px';
            }
            list.style.paddingBottom = Math.min(s.D, Math.max(s.PEEK, s.T)) + 'px';
            if (atBottomRef.current) list.scrollTop = list.scrollHeight;
        }
        function measure() {
            if (!dock.classList.contains('full')) s.D = dock.offsetHeight;
            s.PEEK = grab.offsetHeight + (parseFloat(getComputedStyle(dock).paddingBottom) || 0);
            if (!s.drag && !s.raf) {
                s.T = target();
                paint();
            }
        }
        function settle(v0) {
            cancelAnimationFrame(s.raf);
            s.V = v0 || 0;
            if (reduce) {
                s.T = target(); s.V = 0; s.raf = 0; paint();
                return;
            }
            let last = performance.now();
            const step = (now) => {
                const dt = Math.min(0.032, (now - last) / 1000);
                last = now;
                const tgt = target();
                s.V += (-260 * (s.T - tgt) - 27 * s.V) * dt;
                s.T += s.V * dt;
                if (Math.abs(s.T - tgt) < 0.4 && Math.abs(s.V) < 8) {
                    s.T = tgt; s.V = 0; s.raf = 0; paint();
                    return;
                }
                paint();
                s.raf = requestAnimationFrame(step);
            };
            step(last);
        }
        function setState(n, v0) {
            s.state = n;
            if (n === PEEK) {
                inputRef.current && inputRef.current.blur();
                onCollapseRef.current && onCollapseRef.current();
            }
            setSnapState(n);
            settle(v0);
        }
        const toggle = () => setState(s.state === DEFAULT ? PEEK : DEFAULT, 0);

        function dragMove(y) {
            const d = s.drag, raw = d.T0 - (y - d.y0), lo = s.PEEK, hi = fullH();
            s.T = raw < lo ? lo - (lo - raw) * 0.25 : raw > hi ? hi + Math.min(24, (raw - hi) * 0.15) : raw;
            const n = performance.now();
            d.s.push([n, y]);
            while (d.s.length > 2 && n - d.s[0][0] > 100) d.s.shift();
            paint();
        }
        function endDrag(cancel) {
            if (!s.drag) return;
            const d = s.drag;
            s.drag = null;
            if (!d.moved) {
                if (cancel) settle(0); else toggle();
                return;
            }
            const a = d.s[0], b = d.s[d.s.length - 1];
            const v = b[0] > a[0] ? ((b[1] - a[1]) / (b[0] - a[0])) * 1000 : 0;
            const proj = s.T - v * 0.18, snaps = [s.PEEK, s.D, fullH()];
            let best = 0;
            snaps.forEach((x, i) => { if (Math.abs(x - proj) < Math.abs(snaps[best] - proj)) best = i; });
            setState(best, -v);
        }

        // mouse / pen: drag from the handle
        const onPointerDown = (e) => {
            if (e.pointerType === 'touch') return;
            cancelAnimationFrame(s.raf); s.raf = 0;
            s.drag = { y0: e.clientY, T0: s.T, moved: false, s: [[performance.now(), e.clientY]] };
            try { grab.setPointerCapture(e.pointerId); } catch (x) { /* not capturable */ }
        };
        const onPointerMove = (e) => {
            if (e.pointerType === 'touch' || !s.drag) return;
            if (!s.drag.moved) {
                if (Math.abs(e.clientY - s.drag.y0) <= 6) return;
                Object.assign(s.drag, { moved: true, y0: e.clientY, T0: s.T, s: [[performance.now(), e.clientY]] });
            }
            dragMove(e.clientY);
        };
        const onPointerUp = (e) => { if (e.pointerType !== 'touch') endDrag(false); };
        const onPointerCancel = (e) => { if (e.pointerType !== 'touch') endDrag(true); };
        const onGrabClick = (e) => { if (e.detail === 0) toggle(); }; // keyboard activation

        // touch: drag from anywhere on the panel. After a few pixels the gesture
        // is locked: mostly vertical moves the sheet, mostly horizontal scrolls
        // the chips, and in full screen the chip grid scrolls until it is at the top.
        const onTouchStart = (e) => {
            if (e.touches.length !== 1 || e.target.closest('.vp')) { s.tg = null; return; }
            const t = e.touches[0];
            s.tg = { x: t.clientX, y: t.clientY, own: null, anim: !!s.raf, grab: !!e.target.closest('.grab'), chips: !!e.target.closest('.chips') };
            cancelAnimationFrame(s.raf); s.raf = 0;
        };
        const onTouchMove = (e) => {
            const tg = s.tg;
            if (!tg) return;
            const t = e.touches[0], dx = t.clientX - tg.x, dy = t.clientY - tg.y;
            if (tg.own === null) {
                if (Math.abs(dx) < 6 && Math.abs(dy) < 6) return;
                const vertical = Math.abs(dy) > Math.abs(dx) * 1.2;
                if (!vertical) tg.own = false;
                else if (s.state === FULL && tg.chips && !(dy > 0 && chips.scrollTop <= 0)) tg.own = false;
                else if (s.state === FULL && dy < 0) tg.own = false;
                else {
                    tg.own = true;
                    s.drag = { y0: t.clientY, T0: s.T, moved: true, s: [[performance.now(), t.clientY]] };
                }
            }
            if (tg.own) {
                if (e.cancelable) e.preventDefault();
                dragMove(t.clientY);
            }
        };
        const touchEnd = (cancel) => {
            const g = s.tg;
            s.tg = null;
            if (!g) return;
            if (g.own) { endDrag(cancel); return; }
            if (g.own === null && g.grab && !cancel) toggle();
            else if (g.anim) settle(s.V);
        };
        const onTouchEnd = () => touchEnd(false);
        const onTouchCancel = () => touchEnd(true);

        // fit the layout to the area above a mobile keyboard
        function fit() {
            const vv = window.visualViewport;
            document.documentElement.style.height = (vv ? vv.height : window.innerHeight) + 'px';
            document.documentElement.classList.toggle('kb', !!vv && window.innerHeight - vv.height > 120);
            app.style.transform = vv && vv.offsetTop ? 'translateY(' + vv.offsetTop + 'px)' : '';
            if (atBottomRef.current) requestAnimationFrame(() => { list.scrollTop = list.scrollHeight; });
            measure();
        }

        grab.addEventListener('pointerdown', onPointerDown);
        grab.addEventListener('pointermove', onPointerMove);
        grab.addEventListener('pointerup', onPointerUp);
        grab.addEventListener('pointercancel', onPointerCancel);
        grab.addEventListener('click', onGrabClick);
        dock.addEventListener('touchstart', onTouchStart, { passive: true });
        dock.addEventListener('touchmove', onTouchMove, { passive: false });
        dock.addEventListener('touchend', onTouchEnd);
        dock.addEventListener('touchcancel', onTouchCancel);
        const ro = window.ResizeObserver ? new ResizeObserver(measure) : null;
        ro && ro.observe(dock);
        window.addEventListener('resize', fit);
        const vv = window.visualViewport;
        if (vv) { vv.addEventListener('resize', fit); vv.addEventListener('scroll', fit); }
        if (document.fonts && document.fonts.ready) document.fonts.ready.then(measure);

        api.current = { setState, measure };
        fit();
        measure();

        return () => {
            cancelAnimationFrame(s.raf);
            grab.removeEventListener('pointerdown', onPointerDown);
            grab.removeEventListener('pointermove', onPointerMove);
            grab.removeEventListener('pointerup', onPointerUp);
            grab.removeEventListener('pointercancel', onPointerCancel);
            grab.removeEventListener('click', onGrabClick);
            dock.removeEventListener('touchstart', onTouchStart);
            dock.removeEventListener('touchmove', onTouchMove);
            dock.removeEventListener('touchend', onTouchEnd);
            dock.removeEventListener('touchcancel', onTouchCancel);
            ro && ro.disconnect();
            window.removeEventListener('resize', fit);
            if (vv) { vv.removeEventListener('resize', fit); vv.removeEventListener('scroll', fit); }
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const setSnap = useCallback((n, v0) => api.current.setState && api.current.setState(n, v0), []);

    return { snap, setSnap };
}
