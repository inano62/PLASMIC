// src/pages/billing/BillingCancel.tsx
export default function BillingCancel() {
    return (
        <div className="min-h-screen grid place-items-center p-6">
            <div className="max-w-md w-full rounded-2xl border p-6">
                <h1 className="text-xl font-semibold mb-3">決済をキャンセルしました</h1>
                <p>必要であれば再度お試しください。</p>
                <a className="text-indigo-600 underline mt-4 inline-block" href="/signup">戻る</a>
            </div>
        </div>
    );
}
