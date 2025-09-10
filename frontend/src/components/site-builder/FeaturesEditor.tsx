import {useEffect, useState} from "react";
import {Button} from "react-bootstrap";

export default function FeaturesEditor({data, onSave}:{data:any; onSave:(diff:any)=>void}) {
    const [items,setItems] = useState<any[]>(data?.items || []);
    return (
        <div>
            {items.map((it, idx)=>(
                <div className="row g-2 align-items-end mb-2" key={idx}>
                    <div className="col-md-4">
                        <label className="form-label">title</label>
                        <input className="form-control" value={it.title||""}
                               onChange={e=>{ const a=[...items]; a[idx]={...a[idx], title:e.target.value}; setItems(a); }}/>
                    </div>
                    <div className="col-md-6">
                        <label className="form-label">text</label>
                        <input className="form-control" value={it.text||""}
                               onChange={e=>{ const a=[...items]; a[idx]={...a[idx], text:e.target.value}; setItems(a); }}/>
                    </div>
                    <div className="col-md-2">
                        <Button size="sm" variant="outline-danger"
                                onClick={()=>{ const a=[...items]; a.splice(idx,1); setItems(a); }}>削除</Button>
                    </div>
                </div>
            ))}
            <Button size="sm" className="me-2" onClick={()=>setItems([...items, {title:"",text:""}])}>+ 追加</Button>
            <Button onClick={()=>onSave({items})}>このブロックを保存</Button>
        </div>
    );
}
