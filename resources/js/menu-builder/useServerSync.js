import { useCallback, useEffect, useRef, useState } from 'react';

const DEBOUNCE_MS = 400;
const RETRY_MS = 4000;

/**
 * Keeps the server copy of the menu in step with the page.
 *
 * Edits are applied locally straight away; the whole menu is PUT a moment
 * after the last change. Only one request runs at a time, and whatever
 * changed while it was in flight is sent straight after. When the page is
 * hidden or closed, any unsaved change goes out with keepalive.
 *
 * Returns the save status ('saved' | 'saving' | 'error') and flush(), which
 * resolves true once everything is saved.
 */
export function useServerSync(url, snapshot) {
    const [status, setStatus] = useState('saved');
    const latest = useRef(JSON.stringify(snapshot));
    const sent = useRef(latest.current);
    const running = useRef(null);
    const timer = useRef(0);
    const csrf = useRef(document.querySelector('meta[name="csrf-token"]')?.content || '');

    latest.current = JSON.stringify(snapshot);

    const request = useCallback((body, keepalive = false) => fetch(url, {
        method: 'PUT',
        credentials: 'same-origin',
        keepalive,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf.current,
        },
        body,
    }), [url]);

    const pump = useCallback(() => {
        clearTimeout(timer.current);
        if (running.current) return running.current;
        running.current = (async () => {
            try {
                while (latest.current !== sent.current) {
                    const body = latest.current;
                    setStatus('saving');
                    const res = await request(body);
                    if (!res.ok) {
                        const err = new Error('Save failed: ' + res.status);
                        err.retry = res.status >= 500 || res.status === 429;
                        throw err;
                    }
                    sent.current = body;
                }
                setStatus('saved');
                return true;
            } catch (e) {
                setStatus('error');
                // network errors and server hiccups retry; validation errors wait for the next edit
                if (e.retry !== false) timer.current = setTimeout(pump, RETRY_MS);
                return false;
            } finally {
                running.current = null;
            }
        })();
        return running.current;
    }, [request]);

    useEffect(() => {
        if (latest.current === sent.current) return;
        setStatus('saving');
        clearTimeout(timer.current);
        timer.current = setTimeout(pump, DEBOUNCE_MS);
    }, [latest.current, pump]);

    useEffect(() => {
        const flushOnHide = (e) => {
            if (e.type === 'visibilitychange' && document.visibilityState !== 'hidden') return;
            if (latest.current === sent.current) return;
            clearTimeout(timer.current);
            const body = latest.current;
            request(body, true).then((r) => { if (r.ok) sent.current = body; }).catch(() => {});
        };
        document.addEventListener('visibilitychange', flushOnHide);
        window.addEventListener('pagehide', flushOnHide);
        return () => {
            clearTimeout(timer.current);
            document.removeEventListener('visibilitychange', flushOnHide);
            window.removeEventListener('pagehide', flushOnHide);
        };
    }, [request]);

    const flush = useCallback(async () => {
        if (running.current) await running.current;
        return latest.current === sent.current ? true : pump();
    }, [pump]);

    /**
     * Delete the order on the server (used to drop an empty new order), and
     * stop saving it.
     */
    const discard = useCallback(async (discardUrl) => {
        clearTimeout(timer.current);
        sent.current = latest.current;
        const res = await fetch(discardUrl, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf.current },
        });
        if (!res.ok) throw new Error('Discard failed: ' + res.status);
    }, []);

    return { status, flush, discard };
}
