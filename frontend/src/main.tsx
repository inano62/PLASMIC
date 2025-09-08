import React from "react";
import { createRoot } from "react-dom/client";
import {createBrowserRouter, RouterProvider, Navigate, Outlet} from "react-router-dom";

import SiteLayout from "./layouts/SiteLayout";
import Home from "./pages/PlasmicLanding";
// import Reserve from "./pages/Reserve";
import Join from "./pages/Join";
import Wait from "./pages/Wait";
import Host from "./pages/Host";
import Quick from "./pages/Quick";

import AdminLogin from "./pages/admin/Login";
import AdminLayout from "./pages/admin/_layout";       // ← 新規（下にサンプル）
import AdminDashboard from "./pages/admin/Dashboard";  // ← 既存/新規どちらでもOK
import AdminCharts from "./pages/admin/Charts";        // ← 新規（任意）
import AdminTables from "./pages/admin/Tables";        // ← 新規（任意）
import AdminSiteBuilder from "./pages/admin/site/Builder.tsx"
import Reserve from "./pages/PublicReserve";
import { ADMIN_TOKEN_KEY } from "./lib/auth";
// main.tsx （上からこの順で）
import "bootstrap/dist/css/bootstrap.min.css";      // ★ これを追加（最優先）
import "@fortawesome/fontawesome-free/css/all.min.css"; // 使っているなら
import "./styles/sb-admin.css";                     // SB Admin の上書き
import "./styles/hide-local.css";
import "./index.css";                               // Tailwind 等の自前CSS
import "bootstrap";
import ReservePage from "./pages/ReservePage.tsx";                                 // JS（collapse 等）
import PublicSite from "./public/PublicSite";


function RequireAdmin() {
    const authed = !!localStorage.getItem(ADMIN_TOKEN_KEY);
    return authed ? <Outlet /> : <Navigate to="/admin/login" replace />;
}
const router = createBrowserRouter([
    // ── 公開サイト ─────────────────────────────
    {
        path: "/",
        element: <SiteLayout />,
        children: [
            { index: true, element: <Home /> },
            { path: "reserve", element: <Reserve /> },
            { path: ":tenant/reserve", element: <ReservePage /> },
            { path: "/s/:slug/*", element: <PublicSite /> },
            { path: "wait", element: <Wait /> },
            { path: "host", element: <Host /> },
            { path: "join", element: <Join /> },
            { path: "quick", element: <Quick /> },
        ],
    },

    // ── Admin：ログインだけはレイアウト外 ───────
    { path: "/admin/login", element: <AdminLogin /> },

    // ── Admin：配下はSB Adminのレイアウトで統一 ──
    {
        path: "/admin",
        element:
                <RequireAdmin/>,
        children: [
            {
                element: <AdminLayout />,           // ← レイアウト用のラッパーをここに
                children: [
                    { index: true, element: <Navigate to="dashboard" replace /> },
                    { path: "dashboard", element: <AdminDashboard /> },
                    { path: "charts", element: <AdminCharts /> },
                    { path: "tables", element: <AdminTables /> },
                    { path: "site", element: <AdminSiteBuilder /> },
                ],
            },
        ],
    },

    // 404
    { path: "*", element: <div style={{padding:24}}>Not Found</div> },
]);

createRoot(document.getElementById("root")!).render(
    <React.StrictMode>
        <RouterProvider router={router} />
    </React.StrictMode>
);
