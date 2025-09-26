import { createContext, useContext, useEffect, useState } from "react";
import { AuthApi, setToken } from "@/lib/api";

export type User = { id:number; name:string; email:string } | null;

type Ctx = {
    user: User;
    loading: boolean;
    login: (email: string, password: string) => Promise<void>;
    logout: () => void;
    refresh: () => Promise<void>;
};

const AuthContext = createContext<Ctx>({
    user: null, loading: true,
    login: async () => {}, logout: () => {}, refresh: async () => {},
});

export function AuthProvider({ children }: { children: React.ReactNode }) {
    const [user, setUser]   = useState<User>(null);
    const [loading, setLd]  = useState(true);

    const refresh = async () => {
        try {
            const res = await AuthApi.me();
            setUser(res.user ?? null);
        } catch {
            setUser(null);
        }
    };

    useEffect(() => {
        (async () => { await refresh(); setLd(false); })();
    }, []);

    const login = async (email: string, password: string) => {
        const res = await AuthApi.login(email, password);
        setToken(res.token);
        setUser(res.user);
    };
    const logout = () => {
        setToken(null);
        setUser(null);
    };

    return (
        <AuthContext.Provider value={{ user, loading: loading, login, logout, refresh }}>
            {children}
        </AuthContext.Provider>
    );
}
export const useAuth = () => useContext(AuthContext);
