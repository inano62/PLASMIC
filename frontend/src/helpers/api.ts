export async function api<T=any>(path:string, opt?:RequestInit):Promise<T>{
    const res = await fetch(path, { headers:{'Content-Type':'application/json'}, ...opt });
    if(!res.ok) throw new Error(`${res.status} ${res.statusText}`);
    return res.json();
}
