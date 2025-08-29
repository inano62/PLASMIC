/// <reference types="vite/client" />

interface ImportMetaEnv {
    readonly VITE_LK_URL: string;
    readonly VITE_LK_TOKEN: string;
}
interface ImportMeta {
    readonly env: ImportMetaEnv;
}
interface ImportMeta { readonly env: ImportMetaEnv }