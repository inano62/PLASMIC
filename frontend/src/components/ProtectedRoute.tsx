import { Navigate, Outlet } from "react-router-dom";
import { useAuth } from "../contexts/auth";

export default function ProtectedRoute() {
    const { user, loading } = useAuth();
    if (loading) return null;              // ここはスピナー等にしてもOK
    return user ? <Outlet /> : <Navigate to="/admin/login" replace />;
}
