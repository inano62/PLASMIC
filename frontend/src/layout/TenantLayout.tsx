// TenantLayout.tsx（任意：共通の取得やバリデーションをここで）
import { Outlet, useParams, Navigate } from "react-router-dom";

const VALID_TENANTS = ["plasmic-law", "foo-clinic"]; // 例。API から検証でもOK

export default function TenantLayout() {
    const { tenant } = useParams();
    if (!tenant || !VALID_TENANTS.includes(tenant)) {
        return <Navigate to="/reserve" replace />; // もしくは専用の404
    }
    // ここで Context に入れたり、共通の fetch をしたりできる
    return <Outlet />;
}
