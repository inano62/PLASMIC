// src/components/ProtectedRoute.tsx
import { Navigate, Outlet } from "react-router-dom";
import { useAuth } from "../../contexts/auth";
import { useEffect } from "react";


export default function ProtectedRoute() {
    const { user, loading, refresh } = useAuth();
    useEffect(() => { refresh(); }, [refresh]);
    if (loading) return null;                 // ローディング中は何も描かない等
    if (!user)   return <Navigate to="/admin/login" replace />;
    return <Outlet />;
}
