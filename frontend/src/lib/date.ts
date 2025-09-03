// src/lib/date.ts
export function groupByDate(rows:any[]){
    const map: Record<string, any[]> = {};
    for(const r of rows){
        const d = new Date(r.starts_at);
        const key = new Date(d.getFullYear(), d.getMonth(), d.getDate()).toISOString();
        (map[key] ||= []).push(r);
    }
    const entries = Object.entries(map).sort(([a],[b]) => +new Date(a) - +new Date(b));
    return entries; // [ [dateISO, rows[]], ... ]
}
