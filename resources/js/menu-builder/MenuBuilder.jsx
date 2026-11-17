import { useCallback, useEffect, useLayoutEffect, useMemo, useRef, useState } from 'react';
import { DEFAULT, PEEK, useBottomSheet } from './useBottomSheet.js';
import { useServerSync } from './useServerSync.js';
import { half, keyOf, plural, rnd, startOf, stepOf, titleCase } from './lib.js';

const DELW = 88; // width a row slides to reveal Remove
const REDUCED = typeof window !== 'undefined' && window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;

function QtyLabel({ line, item }) {
    const unit = item && item.unit ? item.unit.name : null;
    if (unit === 'kg' || unit === 'pcs') return <div className="q">{line.qty}<small>{unit}</small></div>;
    if (unit === 'whole') return <div className="q">{half(line.qty)}×</div>;
    return <div className="q">{line.qty}×</div>;
}

export default function MenuBuilder({ restaurant, order, catalog, urls }) {
    // ---- catalog -----------------------------------------------------------
    const [extras, setExtras] = useState(() => {
        const known = new Set(catalog.items.map((x) => x.name.toLowerCase()));
        const seen = new Set();
        return order.items
            .filter((o) => !known.has(o.name.toLowerCase()) && !seen.has(o.name.toLowerCase()) && seen.add(o.name.toLowerCase()))
            .map((o) => ({ id: null, name: o.name, tags: [], variants: null, unit: null, uses: 0 }));
    });
    const allItems = useMemo(() => catalog.items.concat(extras), [catalog.items, extras]);
    const byLower = useMemo(() => new Map(allItems.map((x) => [x.name.toLowerCase(), x])), [allItems]);
    const itemOf = useCallback((name) => byLower.get(name.toLowerCase()), [byLower]);

    // ---- menu state --------------------------------------------------------
    const [lines, setLines] = useState(() => order.items.map((o) => {
        const item = catalog.items.find((x) => x.name.toLowerCase() === o.name.toLowerCase());
        const name = item ? item.name : o.name;
        return { key: keyOf({ name, variant: o.variant }), menuItemId: o.menuItemId ?? (item ? item.id : null), name, variant: o.variant || '', qty: o.qty };
    }));
    const [people, setPeople] = useState(order.people);
    const [isNew] = useState(order.items.length === 0);
    const [sel, setSel] = useState([]);
    const [query, setQuery] = useState('');
    const [last, setLast] = useState('');
    const [editKey, setEditKey] = useState('');
    const [variantFor, setVariantFor] = useState(null);
    const [pop, setPop] = useState({ key: '', n: 0 });

    const snapshot = useMemo(() => ({
        people,
        items: lines.map(({ menuItemId, name, variant, qty }) => ({ menuItemId, name, variant, qty })),
    }), [people, lines]);
    const { status, flush } = useServerSync(urls.sync, snapshot);

    // ---- refs --------------------------------------------------------------
    const appRef = useRef(null), listRef = useRef(null), dockRef = useRef(null), grabRef = useRef(null);
    const chipsRef = useRef(null), inputRef = useRef(null), vpRef = useRef(null);
    const atBottomRef = useRef(true);
    const scrollToEnd = useRef(true);
    const rowEls = useRef(new Map());
    const open = useRef({ key: '', rc: null }); // the row swiped open to show Remove
    const editKeyRef = useRef(editKey);
    editKeyRef.current = editKey;

    const { snap, setSnap } = useBottomSheet({
        appRef, dockRef, grabRef, chipsRef, listRef, inputRef, atBottomRef,
        onCollapse: () => setVariantFor(null),
    });

    // ---- derived -----------------------------------------------------------
    const totals = useMemo(() => {
        const t = new Map();
        lines.forEach((o) => t.set(o.name, rnd((t.get(o.name) || 0) + o.qty)));
        return t;
    }, [lines]);
    const v = query.trim().toLowerCase();
    const matchSel = useCallback((x) => sel.every((t) => x.tags.includes(t)), [sel]);

    const chipItems = useMemo(() => {
        if (!v) return allItems.filter(matchSel);
        return allItems
            .filter((x) => x.name.toLowerCase().includes(v))
            .sort((a, b) => (b.name.toLowerCase().startsWith(v) ? 1 : 0) - (a.name.toLowerCase().startsWith(v) ? 1 : 0))
            .slice(0, 8);
    }, [allItems, v, matchSel]);
    const showAddPrompt = !!v && !byLower.has(titleCase(query).toLowerCase());

    const railTags = useMemo(() => {
        if (v) return null;
        const avail = new Set();
        allItems.filter(matchSel).forEach((x) => x.tags.forEach((t) => avail.add(t)));
        return catalog.tags.filter((t) => sel.includes(t) || avail.has(t));
    }, [v, allItems, matchSel, catalog.tags, sel]);

    // ---- actions -----------------------------------------------------------
    const add = useCallback((raw, variant) => {
        const n = titleCase(raw || '');
        if (!n) return;
        let item = byLower.get(n.toLowerCase());
        if (item && item.variants && variant == null) { setVariantFor(item); return; }
        if (!item) {
            item = { id: null, name: n, tags: sel.slice(), variants: null, unit: null, uses: 0 };
            setExtras((e) => e.concat(item));
        }
        const vr = variant || '', key = item.name + '|' + vr, step = stepOf(item), start = startOf(item);
        setLines((ls) => {
            const i = ls.findIndex((o) => o.key === key);
            if (i === -1) return ls.concat({ key, menuItemId: item.id, name: item.name, variant: vr, qty: start });
            const next = ls.slice();
            next[i] = { ...next[i], qty: rnd(next[i].qty + step) };
            return next;
        });
        setLast(key);
        setPop((p) => ({ key, n: p.n + 1 }));
        setVariantFor(null);
        setQuery('');
        scrollToEnd.current = true;
    }, [byLower, sel]);

    const changeQty = useCallback((key, d) => {
        const line = lines.find((o) => o.key === key);
        if (!line) return;
        const nq = rnd(line.qty + d * stepOf(itemOf(line.name)));
        if (nq <= 0.0001) {
            setLines((ls) => ls.filter((o) => o.key !== key));
            setEditKey('');
        } else {
            setLines((ls) => ls.map((o) => (o.key === key ? { ...o, qty: nq } : o)));
        }
        setLast('');
    }, [lines, itemOf]);

    const setX = (rc, x, animate) => {
        rc.style.transition = animate ? 'transform .22s cubic-bezier(.2,.8,.2,1)' : 'none';
        rc.style.transform = x ? 'translate3d(' + x + 'px,0,0)' : '';
    };
    const closeOpen = () => {
        if (open.current.rc) setX(open.current.rc, 0, true);
        open.current = { key: '', rc: null };
    };

    const removeRow = useCallback((key) => {
        const row = rowEls.current.get(key);
        const done = () => {
            setLines((ls) => ls.filter((o) => o.key !== key));
            setEditKey((k) => (k === key ? '' : k));
            if (open.current.key === key) open.current = { key: '', rc: null };
            setLast('');
        };
        if (!row || REDUCED) { done(); return; }
        setX(row.querySelector('.rc'), -row.offsetWidth, true);
        setTimeout(() => {
            row.style.height = row.offsetHeight + 'px';
            row.getBoundingClientRect();
            row.style.transition = 'height .2s ease';
            row.style.height = '0px';
            row.style.borderBottomWidth = '0';
            setTimeout(done, 200);
        }, 200);
    }, []);
    const removeRowRef = useRef(removeRow);
    removeRowRef.current = removeRow;

    const toggleTag = (t) => {
        if (!t) setSel([]);
        else setSel((s) => (s.includes(t) ? s.filter((x) => x !== t) : s.concat(t)));
    };

    const leave = async (e, href) => {
        e.preventDefault();
        if (await flush()) window.location.href = href;
    };

    // ---- effects -----------------------------------------------------------
    // a fresh filter or search starts the chips from the beginning
    const firstChips = useRef(true);
    useLayoutEffect(() => {
        if (firstChips.current) { firstChips.current = false; return; }
        const c = chipsRef.current;
        c.scrollLeft = 0;
        c.scrollTop = 0;
    }, [v, sel]);

    // follow the newest line, and pop it in
    useLayoutEffect(() => {
        const list = listRef.current;
        if (scrollToEnd.current) {
            scrollToEnd.current = false;
            list.scrollTop = list.scrollHeight;
            atBottomRef.current = true;
        }
    }, [lines]);
    useLayoutEffect(() => {
        if (!pop.key || REDUCED) return;
        const row = rowEls.current.get(pop.key);
        row && row.animate && row.animate(
            [{ transform: 'translateY(8px)', opacity: 0.3 }, { transform: 'none', opacity: 1 }],
            { duration: 220, easing: 'ease-out' },
        );
    }, [pop]);

    // swipe a row left: partway reveals Remove, a long swipe removes it.
    // Native listeners so touchmove can be non-passive.
    useEffect(() => {
        const list = listRef.current;
        let sw = null;
        const onScroll = () => { atBottomRef.current = list.scrollHeight - list.scrollTop - list.clientHeight < 40; };
        const onStart = (e) => {
            const rc = e.target.closest('.rc'), row = rc && rc.parentNode;
            if (e.touches.length !== 1 || !row) { sw = null; return; }
            const t = e.touches[0];
            sw = { x: t.clientX, y: t.clientY, own: null, row, rc, key: row.dataset.k, W: row.offsetWidth };
        };
        const onMove = (e) => {
            if (!sw) return;
            const t = e.touches[0], dx = t.clientX - sw.x, dy = t.clientY - sw.y;
            if (sw.own === null) {
                if (Math.abs(dx) < 6 && Math.abs(dy) < 6) return;
                if (Math.abs(dx) > Math.abs(dy) * 1.2) {
                    sw.own = true;
                    if (open.current.key && open.current.key !== sw.key) closeOpen();
                    sw.base = open.current.key === sw.key ? -DELW : 0;
                    sw.x0 = t.clientX;
                    sw.s = [[performance.now(), t.clientX]];
                    sw.rc.style.transition = 'none';
                } else sw.own = false;
            }
            if (!sw.own) return;
            if (e.cancelable) e.preventDefault();
            let x = sw.base + (t.clientX - sw.x0);
            x = x > 0 ? x * 0.2 : Math.max(x, -sw.W);
            sw.cur = x;
            sw.rc.style.transform = 'translate3d(' + x + 'px,0,0)';
            const n = performance.now();
            sw.s.push([n, t.clientX]);
            while (sw.s.length > 2 && n - sw.s[0][0] > 100) sw.s.shift();
        };
        const end = (cancel) => {
            const g = sw;
            sw = null;
            if (!g || !g.own) return;
            const a = g.s[0], b = g.s[g.s.length - 1];
            const vel = b[0] > a[0] ? ((b[1] - a[1]) / (b[0] - a[0])) * 1000 : 0;
            const proj = g.cur + vel * 0.12;
            if (!cancel && proj < -g.W * 0.55) removeRowRef.current(g.key);
            else if (!cancel && proj < -DELW / 2) { open.current = { key: g.key, rc: g.rc }; setX(g.rc, -DELW, true); }
            else {
                if (open.current.key === g.key) open.current = { key: '', rc: null };
                setX(g.rc, 0, true);
            }
        };
        const onEnd = () => end(false);
        const onCancel = () => end(true);
        list.addEventListener('scroll', onScroll, { passive: true });
        list.addEventListener('touchstart', onStart, { passive: true });
        list.addEventListener('touchmove', onMove, { passive: false });
        list.addEventListener('touchend', onEnd);
        list.addEventListener('touchcancel', onCancel);
        return () => {
            list.removeEventListener('scroll', onScroll);
            list.removeEventListener('touchstart', onStart);
            list.removeEventListener('touchmove', onMove);
            list.removeEventListener('touchend', onEnd);
            list.removeEventListener('touchcancel', onCancel);
        };
    }, []);

    // taps elsewhere fold the stepper back into the number, close a swiped
    // row, and dismiss the variant picker
    useEffect(() => {
        const onClick = (e) => {
            if (editKeyRef.current && !e.target.closest('.stp') && !e.target.closest('.qp')) setEditKey('');
            if (open.current.key && !e.target.closest('.del')) closeOpen();
        };
        const onPointerDown = (e) => {
            if (!e.target.closest('.vp') && !e.target.closest('.chip')) setVariantFor(null);
        };
        document.addEventListener('click', onClick);
        document.addEventListener('pointerdown', onPointerDown);
        return () => {
            document.removeEventListener('click', onClick);
            document.removeEventListener('pointerdown', onPointerDown);
        };
    }, []);

    // ---- render ------------------------------------------------------------
    const n = lines.length;
    const glab = snap === PEEK ? 'Add items' : snap === DEFAULT ? 'Hide' : plural(n, 'item') + ' in menu';
    const syncLabel = status === 'saving' ? 'Saving…' : status === 'error' ? 'Not saved, retrying' : 'Saved';

    return (
        // keep the keyboard up while tapping buttons
        <div className="mb" ref={appRef} onMouseDown={(e) => { if (e.target.closest('button')) e.preventDefault(); }}>
            <nav className="nav" aria-label="Menu builder">
                <a className="back" href={restaurant.url} onClick={(e) => leave(e, restaurant.url)}>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M15 5l-7 7 7 7" /></svg>
                    <span>{restaurant.name}</span>
                </a>
                <a className="done" href={urls.done} onClick={(e) => leave(e, urls.done)}>Done</a>
            </nav>

            <header className="head">
                <div>
                    <h1>{isNew ? 'New menu' : 'Menu'}</h1>
                    <small>
                        <span>{plural(n, 'item')}</span>
                        <span className={'sync ' + status} role="status" aria-live="polite">{syncLabel}</span>
                    </small>
                </div>
                <div className="ppl" role="group" aria-label="Number of people">
                    <button className="pb" type="button" aria-label="Fewer people" onClick={() => setPeople((p) => Math.max(1, p - 1))}>−</button>
                    <span className="pn"><b>{people}</b> people</span>
                    <button className="pb" type="button" aria-label="More people" onClick={() => setPeople((p) => Math.min(99, p + 1))}>+</button>
                </div>
            </header>

            <main className="list" ref={listRef}>
                {!n && <div className="empty">Nothing yet. Tap a suggestion below, or type a dish and press enter.</div>}
                {lines.map((o) => {
                    const item = itemOf(o.name), editing = editKey === o.key;
                    const isLast = o.qty - stepOf(item) <= 0.0001;
                    return (
                        <div
                            key={o.key}
                            className={'row' + (o.key === last ? ' hl' : '')}
                            data-k={o.key}
                            ref={(el) => { if (el) rowEls.current.set(o.key, el); else rowEls.current.delete(o.key); }}
                        >
                            <button className="del" type="button" tabIndex={-1} aria-label={'Remove ' + o.name} onClick={() => removeRow(o.key)}>Remove</button>
                            <div className="rc">
                                <div className="n">{o.name}{o.variant && <small>{o.variant}</small>}</div>
                                {editing ? (
                                    <div className="stp">
                                        <button className="sb" type="button" aria-label={(isLast ? 'Remove ' : 'Less ') + o.name} onClick={() => changeQty(o.key, -1)}>{isLast ? '✕' : '−'}</button>
                                        <QtyLabel line={o} item={item} />
                                        <button className="sb" type="button" aria-label={'More ' + o.name} onClick={() => changeQty(o.key, 1)}>+</button>
                                    </div>
                                ) : (
                                    <button className="qp" type="button" aria-label={'Change quantity of ' + o.name} onClick={() => setEditKey((k) => (k === o.key ? '' : o.key))}>
                                        <QtyLabel line={o} item={item} />
                                    </button>
                                )}
                            </div>
                        </div>
                    );
                })}
            </main>

            <div className="dock" ref={dockRef} data-snap={snap}>
                {variantFor && (
                    <div className="vp" ref={vpRef}>
                        <div className="vt">{variantFor.name}</div>
                        <div className="vw">
                            {variantFor.variants.map((x) => (
                                <button key={x} className="chip" type="button" onClick={() => { add(variantFor.name, x); inputRef.current.blur(); }}>{x}</button>
                            ))}
                        </div>
                    </div>
                )}
                <button ref={grabRef} className="grab" type="button" aria-expanded={snap !== PEEK} aria-label={snap === PEEK ? 'Show add panel' : snap === DEFAULT ? 'Hide add panel' : 'Collapse add panel'}>
                    <span className="bar" /><span className="glab">{glab}</span>
                </button>

                <div className="rail" role="group" aria-label="Filter dishes" hidden={!railTags || !catalog.tags.length}>
                    <button className="tag" type="button" aria-pressed={!sel.length} onClick={() => toggleTag('')}>All</button>
                    {railTags && railTags.map((t) => (
                        <button key={t} className="tag" type="button" aria-pressed={sel.includes(t)} onClick={() => toggleTag(t)}>{t}</button>
                    ))}
                </div>

                <div className={'chips' + (v ? ' one' : '')} ref={chipsRef}>
                    {showAddPrompt && (
                        <button className="chip p" type="button" onClick={() => { add(query); inputRef.current.blur(); }}>Add “{titleCase(query)}”</button>
                    )}
                    {!v && !chipItems.length && <span className="none">No dishes yet. Type one below to add it.</span>}
                    {chipItems.map((it) => {
                        const c = totals.get(it.name) || 0;
                        return (
                            <button
                                key={it.name}
                                className={'chip' + (c ? ' on' : '')}
                                type="button"
                                aria-label={c ? it.name + ', ' + c + ' in menu' : undefined}
                                onClick={() => { add(it.name); inputRef.current.blur(); }}
                            >
                                {it.name}
                                {it.variants && <span className="cv"> ▾</span>}
                                {!!c && <span className="bd">{it.unit && it.unit.name === 'whole' ? half(c) : c}</span>}
                            </button>
                        );
                    })}
                </div>

                <div className="inbox">
                    <input
                        ref={inputRef}
                        type="text"
                        value={query}
                        placeholder="Add a dish"
                        autoComplete="off"
                        autoCapitalize="words"
                        enterKeyHint="send"
                        aria-label="Add a dish"
                        onChange={(e) => { setVariantFor(null); setQuery(e.target.value); }}
                        onFocus={() => { if (snap === PEEK) setSnap(DEFAULT); }}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter') { e.preventDefault(); add(query); e.currentTarget.blur(); }
                            else if (e.key === 'Escape') setQuery('');
                        }}
                    />
                    <button className={'send' + (v ? '' : ' idle')} type="button" aria-label="Add dish" onClick={() => add(query)}>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M12 19V5" /><path d="M5.5 11.5L12 5l6.5 6.5" /></svg>
                    </button>
                </div>
            </div>
        </div>
    );
}

