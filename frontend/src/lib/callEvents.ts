// src/lib/callEvents.ts
import { v4 as uuid } from 'uuid';

const API_BASE =
    import.meta.env.VITE_API_BASE ?? 'http://localhost:8000/api'; // ← ここを使う

const urlJoin = (base: string, path: string) =>
    base.replace(/\/$/, '') + '/' + path.replace(/^\//, '');

function postEvent(url: string, payload: any) {
    const body = JSON.stringify(payload);
    const blob = new Blob([body], { type: 'application/json' });

    if (navigator.sendBeacon && document.visibilityState === 'hidden') {
        navigator.sendBeacon(url, blob);
        return Promise.resolve();
    }
    return fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body,
        keepalive: true,
        credentials: 'omit',
    }).then(() => {}).catch(() => {});
}

export function emitCallEvent(roomName: string, type:'start'|'end'|'heartbeat'|'error'|'silence', data:any = {}) {
    if (!roomName) return;
    const payload = { room: roomName, type, ts: new Date().toISOString(), event_id: uuid(), ...data };
    console.debug('[emit]', type, roomName, payload);
    return postEvent(join(API_BASE, '/calls/event'), payload); // ← ここをAPI_BASEに
}
