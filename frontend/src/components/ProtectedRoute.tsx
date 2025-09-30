// src/components/ProtectedRoute.tsx
import { useEffect } from "react";
import { Navigate, Outlet } from "react-router-dom";
import { useAuth } from "../contexts/auth";

export default function ProtectedRoute() {
    const { user, loading, refresh } = useAuth();

    useEffect(() => { refresh(); }, [refresh]); // /me を一回更新

    if (loading) return null;
    if (!user) return <Navigate to="/admin/login" replace />;
    return <Outlet />;
}
