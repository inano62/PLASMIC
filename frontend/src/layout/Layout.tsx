// src/layout/Layout.tsx
import { useState } from 'react';
import { Outlet, Link } from 'react-router-dom';

export default function Layout() {
    const [open, setOpen] = useState(true);
    return (
        <div className="sb-nav-fixed">
            {/* Top Nav */}
            <nav className="sb-topnav navbar navbar-expand navbar-dark bg-dark">
                <Link className="navbar-brand ps-3" to="/">Start Bootstrap</Link>
                <button className="btn btn-link btn-sm me-4" onClick={() => setOpen(!open)}>
                    <i className="fas fa-bars" />
                </button>
                <form className="d-none d-md-inline-block ms-auto me-3 my-2 my-md-0">
                    <div className="input-group">
                        <input className="form-control" placeholder="Search for..." />
                        <button className="btn btn-primary" type="button"><i className="fas fa-search" /></button>
                    </div>
                </form>
                <ul className="navbar-nav ms-auto me-3">
                    <li className="nav-item dropdown">
                        <a className="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i className="fas fa-user fa-fw" />
                        </a>
                        <ul className="dropdown-menu dropdown-menu-end">
                            <li><a className="dropdown-item" href="#">Settings</a></li>
                            <li><a className="dropdown-item" href="#">Activity Log</a></li>
                            <li><hr className="dropdown-divider" /></li>
                            <li><a className="dropdown-item" href="#">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>

            <div id="layoutSidenav" className={open ? '' : 'toggled'}>
                {/* Side Nav */}
                <div id="layoutSidenav_nav">
                    <nav className="sb-sidenav accordion sb-sidenav-dark">
                        <div className="sb-sidenav-menu">
                            <div className="nav">
                                <div className="sb-sidenav-menu-heading">Core</div>
                                <Link className="nav-link" to="/dashboard">
                                    <div className="sb-nav-link-icon"><i className="fas fa-tachometer-alt" /></div>
                                    Dashboard
                                </Link>

                                <div className="sb-sidenav-menu-heading">Addons</div>
                                <a className="nav-link" href="#"><div className="sb-nav-link-icon"><i className="fas fa-chart-area" /></div>Charts</a>
                                <a className="nav-link" href="#"><div className="sb-nav-link-icon"><i className="fas fa-table" /></div>Tables</a>
                            </div>
                        </div>
                        <div className="sb-sidenav-footer">
                            <div className="small">Logged in as:</div>
                            Start Bootstrap
                        </div>
                    </nav>
                </div>

                {/* Content */}
                <div id="layoutSidenav_content">
                    <main className="p-4">
                        <Outlet />
                    </main>
                    <footer className="py-4 bg-light mt-auto">
                        <div className="container-fluid px-4 d-flex align-items-center justify-content-between small">
                            <div className="text-muted">Copyright © Your Website 2025</div>
                            <div><a href="#">Privacy Policy</a> · <a href="#">Terms &amp; Conditions</a></div>
                        </div>
                    </footer>
                </div>
            </div>
        </div>
    );
}
