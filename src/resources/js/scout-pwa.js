const STORAGE_PREFIX = 'scount-scout-queue:';

function queueKey(matchId) {
    return `${STORAGE_PREFIX}${matchId}`;
}

export function loadQueue(matchId) {
    try {
        const raw = localStorage.getItem(queueKey(matchId));
        return raw ? JSON.parse(raw) : [];
    } catch {
        return [];
    }
}

export function saveQueue(matchId, items) {
    localStorage.setItem(queueKey(matchId), JSON.stringify(items));
}

export function enqueueShot(matchId, payload) {
    const items = loadQueue(matchId);
    items.push({ ...payload, client_id: crypto.randomUUID(), queued_at: Date.now() });
    saveQueue(matchId, items);
    return items.length;
}

export async function flushQueue(matchId, csrf) {
    const items = loadQueue(matchId);
    if (items.length === 0 || !navigator.onLine) {
        return { ok: true, processed: 0, remaining: items.length };
    }

    const res = await fetch(`/partidas/${matchId}/scout/lote`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf,
        },
        credentials: 'same-origin',
        body: JSON.stringify({ shots: items }),
    });

    if (!res.ok) {
        return { ok: false, processed: 0, remaining: items.length };
    }

    const data = await res.json();
    saveQueue(matchId, []);
    return { ok: true, processed: data.processed ?? items.length, remaining: 0, team_score: data.team_score };
}

export function playFeedback(type = 'success') {
    if (localStorage.getItem('scount-scout-sound') === '0') {
        return;
    }
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const o = ctx.createOscillator();
        const g = ctx.createGain();
        o.connect(g);
        g.connect(ctx.destination);
        o.frequency.value = type === 'success' ? 880 : 440;
        g.gain.value = 0.08;
        o.start();
        setTimeout(() => {
            o.stop();
            ctx.close();
        }, type === 'success' ? 90 : 140);
    } catch {
        /* ignore */
    }
}

export function registerScoutPwa() {
    if (!('serviceWorker' in navigator)) {
        return;
    }
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw-scout.js').catch(() => {});
    });
}