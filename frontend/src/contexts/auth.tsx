import { createContext, useContext, useEffect, useState } from "react";
import { api } from "../../src/lib/api";

export type User = { id:number; name:string; email:string; role?:string } | null;

type Ctx = {
    user: User;
    setUser: (u: User) => void;
    loading: boolean;
};

const AuthContext = createContext<Ctx>({ user: null, setUser: () => {}, loading: true });

export function AuthProvider({ children }: { children: React.ReactNode }) {
    const [user, setUser] = useState<User>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        // 起動時に現在ログイン済みかを確認（Sanctum Cookieベース）
        api.get<User>("/api/user")
            .then((u) => mounted && setUser(u))
            .catch(() => mounted && setUser(null))
            .finally(() => mounted && setLoading(false));
        return () => { mounted = false; };
    }, []);

    return (
        <AuthContext.Provider value={{ user, setUser, loading }}>
            {children}
        </AuthContext.Provider>
    );
}
export function useAuth() { return useContext(AuthContext); }
