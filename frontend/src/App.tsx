// // src/main.tsx もしくは src/App.tsx
// import { createRoot } from "react-dom/client";
// import {BrowserRouter, Navigate, Route, useParams} from "react-router-dom";
// import Reserve from "./pages/PublicReserve";
// import Wait from "./pages/Wait";
// import Host from "./pages/Host";
// import Admin from "./pages/admin/Admin";
// import ReservePage from "./pages/ReservePage.tsx";
// import TenantLayout from "./layout/TenantLayout.tsx";
// import PublicSite from './public/PublicSite';
// function OldReserveRedirect() {
//     const { tenant } = useParams();
//     return <Navigate to={`/${tenant}/reserve`} replace />;
// }
// createRoot(document.getElementById("root")!).render(
//     <BrowserRouter>
//
//             <Route path="/" element={<Reserve />} />
//             {/*<Route path="/reserve" element={<Reserve />} />*/}
//             <Route path="/reserve" element={<ReservePage />} />
//             <Route path="/:tenant" element={<TenantLayout />}>
//                 <Route path="reserve" element={<ReservePage />} />  {/* /:tenant/reserve */}
//                 {/* 他にも /:tenant/... を増やすならここに */}
//             </Route>
//             <Route path="/reserve/:tenant" element={<OldReserveRedirect />} />
//             <Route path="/wait" element={<Wait />} />
//             <Route path="/host" element={<Host />} />
//             <Route path="/admin" element={<Admin />} />
//             <Route path="*" element={<div>404</div>} />
//         <Route path="/s/:slug/*" element={<PublicSite />} />
//     </BrowserRouter>
// );
