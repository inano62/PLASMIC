// src/main.tsx
import React from "react";
import {createRoot} from "react-dom/client";
import {createBrowserRouter, RouterProvider, Navigate,} from "react-router-dom";

import {setToken} from "./lib/api";
import {ADMIN_TOKEN_KEY} from "./lib/auth";

const token = import.meta.env.VITE_API_TOKEN ?? null;
setToken(token || null);
if (token) localStorage.setItem(ADMIN_TOKEN_KEY, token);

// 画面
import SiteLayout from "./layouts/SiteLayout";
import Join from "./pages/Join";
import Wait from "./pages/Wait";
import Host from "./pages/Host";
import Quick from "./pages/Quick";
import Login from "./pages/admin/Login";
import AdminLayout from "./pages/admin/_layout";
import AdminDashboard from "./pages/admin/AdminDashboard.tsx";
import AdminCharts from "./pages/admin/Charts";
import AdminTables from "./pages/admin/Tables";
import AdminSiteBuilder from "./pages/admin/site/Builder";
import Reserve from "./pages/PublicReserve";     // 予約フォーム（共通）
import ReservePage from "./pages/ReservePage";   // /:tenant/reserve 用
import PublicSite from "./public/PublicSite";    // 先生サイト
import PlasmicLanding from "./features/landing/PlasmicLanding.tsx";
import Offices from "./pages/Offices";
import TenantHome from "./pages/TenantHome";
import Signup from "./pages/SignupAndCheckout"
import Thanks from "./pages/Thanks";
import "./App.css";
import "bootstrap/dist/css/bootstrap.min.css";
import "@fortawesome/fontawesome-free/css/all.min.css";
import "./styles/sb-admin.css";
import "./styles/hide-local.css";
import "./index.css";
import "@/assets/site-builder.css";
import "bootstrap";
import BillingSuccess from "./pages/billing/BillingSuccess.tsx";
import BillingCancel from "./pages/billing/BillingCancel.tsx";
import ProtectedRoute from "./components/ProtectedRoute.tsx";
import {AuthProvider} from "./contexts/auth.tsx";


const router = createBrowserRouter([
    // ✅ ここを“1個上”に出す（SiteLayoutの外側）
    {path: "/s/:slug/*", element: <PublicSite/>},
    // 先生サイト配下の予約にしたいならこれも直下で OK
    {path: "/s/:slug/reserve", element: <Reserve/>},

    {
        path: "/",
        element: <SiteLayout/>,
        children: [
            {index: true, element: <PlasmicLanding/>},
            {path: "reserve", element: <Reserve/>},
            {path: "thanks", element: <Thanks/>},
            {path: "offices", element: <Offices/>},
            {path: "signup", element: <Signup/>},
            {path: ":tenant/reserve", element: <ReservePage/>},
            {path: "/billing/success", element: <BillingSuccess/>},
            {path: "/billing/cancel", element: <BillingCancel/>},
            {path: "tenants/:slug", element: <TenantHome/>},
            {path: "wait", element: <Wait/>},
            {path: "host", element: <Host/>},
            {path: "join", element: <Join/>},
            {path: "quick", element: <Quick/>},
        ],
    },

    {path: "/admin/login", element: <Login/>},
    {
        path: "/admin",
        element: <ProtectedRoute/>,
        children: [
            {
                element: <AdminLayout/>,
                children: [
                    {index: true, element: <Navigate to="dashboard" replace/>},
                    {path: "dashboard", element: <AdminDashboard/>},
                    {path: "charts", element: <AdminCharts/>},
                    {path: "tables", element: <AdminTables/>},
                    {path: "site", element: <AdminSiteBuilder/>},
                ],
            },
        ],
    },

    {path: "*", element: <div style={{padding: 24}}>Not Found</div>},
]);

createRoot(document.getElementById("root")!).render(
    <React.StrictMode>
        <AuthProvider>
            <RouterProvider router={router}/>
        </AuthProvider>
    </React.StrictMode>
);
