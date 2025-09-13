// src/lib/upload.ts
// export async function uploadImage(file: File): Promise<{id:number; url:string}> {
//     const fd = new FormData();
//     fd.append('file', file);
//     const res = await fetch('/api/media', {
//         method:'POST',
//         headers: { Authorization: (window as any).__BEARER__ || '' }, // ← 既に api.ts が付与するなら不要
//         body: fd,
//         credentials: 'omit', // Bearer で運用
//     });
//     if(!res.ok) throw new Error(await res.text());
//     return res.json();
// }
// frontend/src/lib/upload.ts
import API from "@/lib/api";

export async function uploadImage(file: File): Promise<string> {
    const fd = new FormData();
    fd.append("file", file);
    const res = await API.postForm<{url:string}>("/admin/upload", fd);
    return res.url; // 例: /storage/site/xxxx.png
}
export async function postForm<T=any>(url:string, fd:FormData): Promise<T> {
    const r = await fetch(url, { method:'POST', body: fd, credentials:'include' });
    if(!r.ok) throw new Error('upload failed');
    return r.json();
}
