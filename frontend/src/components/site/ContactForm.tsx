// frontend/src/components/site/ContactForm.tsx
import { useState } from "react";
import {api} from "../../lib/api"; // 既存の API ヘルパ（VITE_API_BASE を使うやつ）

export default function ContactForm({ siteSlug = "demo" }: { siteSlug?: string }) {
    const [name, setName] = useState("");
    const [email, setEmail] = useState("");
    const [topic, setTopic] = useState("");
    const [message, setMessage] = useState("");
    const [sending, setSending] = useState(false);
    const [done, setDone] = useState(false);
    const API = api;
    async function submit(e: React.FormEvent) {
        e.preventDefault();
        if (!email || !message) { alert("メールと本文は必須です"); return; }
        setSending(true);
        try {
            await API.post("inquiries", { site_slug: siteSlug, name, email, topic, message });
            setDone(true);
            setName(""); setEmail(""); setTopic(""); setMessage("");
        } catch (err) {
            console.error(err);
            alert("送信に失敗しました");
        } finally {
            setSending(false);
        }
    }

    return (
        <form onSubmit={submit} className="space-y-3">
            {done && <div className="alert alert-success">送信しました。担当者から折り返します。</div>}
            <input className="form-control" placeholder="お名前（任意）" value={name} onChange={e=>setName(e.target.value)} />
            <input className="form-control" placeholder="メール *" value={email} onChange={e=>setEmail(e.target.value)} />
            <input className="form-control" placeholder="件名 / 相談種別（任意）" value={topic} onChange={e=>setTopic(e.target.value)} />
            <textarea className="form-control" rows={5} placeholder="ご相談内容 *" value={message} onChange={e=>setMessage(e.target.value)} />
            <button className="btn btn-primary" disabled={sending}>{sending ? "送信中..." : "送信"}</button>
        </form>
    );
}
