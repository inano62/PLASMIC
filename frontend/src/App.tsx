// src/main.tsx もしくは src/App.tsx
import { createRoot } from "react-dom/client";
import { BrowserRouter, Routes, Route } from "react-router-dom";
import Reserve from "./pages/Reserve";
import Wait from "./pages/Wait";
import Host from "./pages/Host";
import Admin from "./pages/admin/Admin";

createRoot(document.getElementById("root")!).render(
    <BrowserRouter>
        <Routes>
            <Route path="/" element={<Reserve />} />
            <Route path="/reserve" element={<Reserve />} />
            <Route path="/wait" element={<Wait />} />
            <Route path="/host" element={<Host />} />
            <Route path="/admin" element={<Admin />} />
            <Route path="*" element={<div>404</div>} />
        </Routes>
    </BrowserRouter>
);
