// src/pages/admin/site/New.tsx
import { useState } from "react";
import { api } from "@/lib/api";
import { useNavigate } from "react-router-dom";

export default function NewSite() {
    const [title, setTitle] = useState("先生サイト");
    const [slug, setSlug]   = useState("");
    const navigate = useNavigate();

    const create = async () => {
        const { data } = await api.post("/sites", { title, slug });
        navigate(`/admin/site?site_id=${data.id}`); // Builderへ
    };

    return (
        <div>
            <h2>最初のサイトを作成</h2>
            <input value={title} onChange={e=>setTitle(e.target.value)} placeholder="タイトル" />
            <input value={slug} onChange={e=>setSlug(e.target.value)} placeholder="URLスラッグ" />
            <button className="btn btn-primary" onClick={create}>作成する</button>
        </div>
    );
}
