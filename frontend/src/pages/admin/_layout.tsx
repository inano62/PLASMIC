import { useState} from "react";
import { Outlet, Link, NavLink } from "react-router-dom";
import {useAuth} from "../../contexts/auth.tsx";

/**
 * ポイント
 * - ルートに "sb-nav-fixed sb" を必ず付ける（全角スペース禁止）
 * - サイドナビの開閉は sb-sidenav-toggled クラスで制御（StartBootstrap流）
 * - a href="#" は押下でページトップに飛ぶので button に変更
 */
export default function AdminLayout() {
    const [open, setOpen] = useState(true);
    const { user } = useAuth();
    return (
        <div className={`sb-nav-fixed sb ${open ? "" : "sb-sidenav-toggled"}`}>
            {/* TopNav（固定ヘッダー） */}
            <nav className="sb-topnav navbar navbar-expand navbar-dark bg-dark">
                <Link className="navbar-brand ps-3" to="/admin">Start Bootstrap</Link>

                {/* 左のハンバーガー：サイドナビ開閉 */}
                <button
                    type="button"
                    className="btn btn-link btn-sm me-4"
                    aria-label="Toggle sidebar"
                    onClick={() => setOpen(o => !o)}
                >
                    <i className="fas fa-bars" />
                </button>

                {/* 疑似検索（右寄せ） */}
                <form className="d-none d-md-inline-block ms-auto me-3 my-2 my-md-0">
                    <div className="input-group">
                        <input className="form-control" placeholder="Search for..." />
                        <button className="btn btn-primary" type="button">
                            <i className="fas fa-search" />
                        </button>
                    </div>
                </form>

                {/* 右上ユーザメニュー */}
                <ul className="navbar-nav ms-auto me-3">
                    <li className="nav-item dropdown">
                        <button
                            className="nav-link dropdown-toggle btn btn-link p-0 text-decoration-none"
                            id="userMenu"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            type="button"
                        >
                            <i className="fas fa-user fa-fw" />
                        </button>
                        <ul className="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                            <li><button className="dropdown-item" type="button">Settings</button></li>
                            <li><button className="dropdown-item" type="button">Activity Log</button></li>
                            <li><hr className="dropdown-divider" /></li>
                            <li>
                                <button
                                    className="dropdown-item"
                                    type="button"
                                    onClick={() => {
                                        localStorage.removeItem("admin_token");
                                        location.href = "/admin/login";
                                    }}
                                >
                                    Logout
                                </button>
                            </li>
                        </ul>
                    </li>
                </ul>
            </nav>

            {/* レイアウト本体 */}
            <div id="layoutSidenav">
                {/* SideNav（左） */}
                <div id="layoutSidenav_nav">
                    <nav className="sb-sidenav accordion sb-sidenav-dark">
                        <div className="sb-sidenav-menu">
                            <div className="nav">
                                <div className="sb-sidenav-menu-heading">Core</div>
                                <NavLink className="nav-link" to="dashboard">
                                    <div className="sb-nav-link-icon"><i className="fas fa-tachometer-alt" /></div>
                                    Dashboard
                                </NavLink>

                                <div className="sb-sidenav-menu-heading">Addons</div>
                                <NavLink className="nav-link" to="charts">
                                    <div className="sb-nav-link-icon"><i className="fas fa-chart-area" /></div>
                                    Charts
                                </NavLink>
                                <NavLink className="nav-link" to="tables">
                                    <div className="sb-nav-link-icon"><i className="fas fa-table" /></div>
                                    Tables
                                </NavLink>
                                <NavLink className="nav-link" to="site">
                                    <div className="sb-nav-link-icon"><i className="fas fa-home" /></div>
                                    Site Builder
                                </NavLink>
                            </div>
                        </div>
                        <div className="sb-sidenav-footer">
                            <div className="small">Logged in as:</div>
                            <p>ログイン中: {user?.name} ({user?.email})</p>
                            <p> {user?.tenants?.[0]?.name} ({user?.tenants?.[0]?.role})</p>
                        </div>
                    </nav>
                </div>

                {/* Content（右） */}
                <div id="layoutSidenav_content">
                    {/* Builder 側の sticky を効かせるためパディングだけにする */}
                    <main className="p-4">
                        <Outlet />
                    </main>

                    <footer className="py-4 bg-light mt-auto">
                        <div className="container-fluid px-4 d-flex align-items-center justify-content-between small">
                            <div className="text-muted">Copyright © Your Website 2025</div>
                            <div>
                                <a href="#">Privacy Policy</a> · <a href="#">Terms &amp; Conditions</a>
                            </div>
                        </div>
                    </footer>
                </div>
            </div>
        </div>
    );
}
