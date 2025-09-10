import {Button} from "react-bootstrap";
import {useState} from "react";

export default function FooterEditor({data, onSave}:{data:any; onSave:(d:any)=>void}) {
    const [cols,setCols] = useState<any[]>(data?.columns || [{title:'Menu',links:[]}] );
    // links: [{text,href}]
    // 省略: cols を編集する簡単なUI
    return (
        <div>
            {/* ここに columns の編集UI（タイトル入力、リンク配列の追加/削除） */}
            <Button onClick={()=>onSave({columns: cols})}>保存</Button>
        </div>
    );
}
