import  { useState} from "react";
import {Link} from "react-router-dom";
import {Button} from "react-bootstrap";
import dashboardImg from "../../../img/Dashboard.png";
import Lady from "../../../img/inTroubleLady.svg";

export function Features() {
    const [s] = useState<Record<string, string>>({});


    return (
        <div className="max-w-6xl mx-auto p-6">
            {/* <header className="flex items-center justify-between py-4">
                <div className="font-semibold text-lg">{s["site.name"] ?? "Regal"}</div>
                <nav className="space-x-4 text-sm">
                    <Link to="/">ホーム</Link>
                    <Link to=":tenant/reserve">予約</Link>
                    <Link to="s/:slug">TenantHome</Link>
                    <Link to="/offices">Offices</Link>
                    <Link to="/admin" className="btn btn-sm btn-primary">管理</Link>
                </nav>
            </header> */}
            <section className="py-16 text-center">
                <h1 className="text-4xl md:text-6xl font-bold">{s["hero.title"] ?? "初回相談のハードルが高い"}</h1>
                <img src={Lady} alt="" />
                <p className="text-lg">開業したてで集客が難しいと感じている方へ</p>
                <h1 className="mt-4 text-muted-foreground">{s["hero.subtitle"] ?? "予約～決済～相談をもっとシンプルに"}</h1>
                <div className="mt-8 flex gap-4 justify-center">
                    <img src={dashboardImg} alt="Dashboard interface showing booking management system with appointment schedule, client information forms, and reservation status indicators in a clean, organized layout" />
                </div>
                <div className="mt-8 flex gap-4 justify-center">
                    <div className="mt-8 gap-4 text-left" >
                        <h2>ホームページカスタマイズサービスあり</h2>
                        <p>ホームページを作りたいけど、どうやって作ればいいのかわからない。</p>
                        <p>ホームページを作りたいけど、高額な費用を出してまで設置したくない。</p>
                        <p>ホームページを作りたいけど、作る時間なんてない。</p>
                    </div>
                </div>
                <div className="mt-8 flex gap-4 justify-center">
                    
                    <Link to={s["cta.primary.href"] ?? "/reserve"}>
                        <Button variant="primary">{s["cta.primary.label"] ?? "予約してはじめる"}</Button>
                    </Link>
                    <a href={s["cta.secondary.href"] ?? "#features"}>
                        <Button variant="outline-primary">{s["cta.secondary.label"] ?? "機能を見る"}</Button>
                    </a>
                </div>
                {/*{s["hero.image"] && <img src={s["hero.image"]} className="mx-auto mt-10 rounded-2xl shadow" />}*/}
            </section>


        </div>
    )
}