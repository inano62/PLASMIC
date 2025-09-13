// components/TimeRail.tsx
export function TimeRail({ open="09:00", close="17:00", step=30 }) {
    const [oh, om] = open.split(":").map(Number);
    const [ch, cm] = close.split(":").map(Number);
    const start = oh*60+om, end = ch*60+cm;
    const slots = [];
    for (let m=start; m<end; m+=step) {
        const h = String(Math.floor(m/60)).padStart(2,"0");
        const mm = String(m%60).padStart(2,"0");
        slots.push(`${h}:${mm}`);
    }
    return (
        <div className="time-rail">
            {slots.map(t => (
                <div key={t} className="time-row">{t}</div>
            ))}
        </div>
    );
}
