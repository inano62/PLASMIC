// src/main.tsx
import React from "react";
import { createRoot } from "react-dom/client";
import { createBrowserRouter, RouterProvider, Navigate, Outlet } from "react-router-dom";

import { setToken } from "@/lib/api";
import { ADMIN_TOKEN_KEY } from "./lib/auth";

// ← Bearer を一度だけセット（.env.local から）
const token = import.meta.env.VITE_API_TOKEN ?? null;
setToken(token || null);
if (token) localStorage.setItem(ADMIN_TOKEN_KEY, token);

// 画面類
import SiteLayout from "./layouts/SiteLayout";
import Home from "./pages/PlasmicLanding";
import Join from "./pages/Join";
import Wait from "./pages/Wait";
import Host from "./pages/Host";
import Quick from "./pages/Quick";
import AdminLogin from "./pages/admin/Login";
import AdminLayout from "./pages/admin/_layout";
import AdminDashboard from "./pages/admin/Dashboard";
import AdminCharts from "./pages/admin/Charts";
import AdminTables from "./pages/admin/Tables";
import AdminSiteBuilder from "./pages/admin/site/Builder";
import Reserve from "./pages/PublicReserve";
import ReservePage from "./pages/ReservePage";
import PublicSite from "./public/PublicSite";

// CSS
import "bootstrap/dist/css/bootstrap.min.css";
import "@fortawesome/fontawesome-free/css/all.min.css";
import "./styles/sb-admin.css";
import "./styles/hide-local.css";
import "./index.css";
import "bootstrap";

function RequireAdmin() {
    const authed = !!localStorage.getItem(ADMIN_TOKEN_KEY);
    return authed ? <Outlet /> : <Navigate to="/admin/login" replace />;
}

const router = createBrowserRouter([
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
    { path: "/admin/login", element: <AdminLogin /> },
    {
        path: "/admin",
        element: <RequireAdmin />,
        children: [
            {
                element: <AdminLayout />,
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
    { path: "*", element: <div style={{ padding: 24 }}>Not Found</div> },
]);

createRoot(document.getElementById("root")!).render(
    <React.StrictMode>
        <RouterProvider router={router} />
    </React.StrictMode>
);
