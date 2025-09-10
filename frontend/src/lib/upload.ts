// src/lib/upload.ts
export async function uploadImage(file: File): Promise<{id:number; url:string}> {
    const fd = new FormData();
    fd.append('file', file);
    const res = await fetch('/api/media', {
        method:'POST',
        headers: { Authorization: (window as any).__BEARER__ || '' }, // ← 既に api.ts が付与するなら不要
        body: fd,
        credentials: 'omit', // Bearer で運用
    });
    if(!res.ok) throw new Error(await res.text());
    return res.json();
}
