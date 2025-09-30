import { createContext, useContext, useEffect, useState } from "react";
import {ADMIN_TOKEN_KEY, AuthApi, setToken} from "../lib/api";
export type User = { id:number; name:string; email:string; role?: string } | null;


type TenantMini = { id:number; slug:string; name:string; role:string };
export type Me = {
    id:number;
    name:string;
    email:string;
    role?:string;
    tenants?: TenantMini[];
    primary_tenant_id?: number|null;
} | null;
type AuthCtx = {
    user: Me;
    loading: boolean;
    login: (email: string, password: string) => Promise<void>;
    logout: () => void;
    refresh: () => Promise<void>;
};
const AuthContext = createContext<AuthCtx | undefined>(undefined);
const MeContext = createContext<Me | undefined>(undefined);
export function AuthProvider({ children }: { children: React.ReactNode }) {
    const [user, setUser] = useState<User>(null);
    const [loading, setLoading] = useState(true);

    const refresh = async () => {
        try {
            const me = await AuthApi.me();
            setUser(me);
        } catch {
            setUser(null);
            setToken(null);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        const hasToken = !!localStorage.getItem(ADMIN_TOKEN_KEY);
        if (hasToken) refresh();       // ★ トークンがある時だけ /me
        else setLoading(false);        // ★ 無ければ即 loading を解除
    }, []);

    const login = async (email: string, password: string) => {
        const res = await AuthApi.login(email, password);
        setToken(res.token);
        setUser(res.user);
    };

    const logout = () => {
        AuthApi.logout();
        setToken(null);
        setUser(null);
    };

    return (
        <AuthContext.Provider value={{ user, loading, login, logout, refresh }}>
            {children}
        </AuthContext.Provider>
    );
}
/* eslint-disable react-refresh/only-export-components */

export function useAuth() {
    const ctx = useContext(AuthContext);;
    if (!ctx) throw new Error("useAuth must be used inside <AuthProvider>");
    return ctx;
}


