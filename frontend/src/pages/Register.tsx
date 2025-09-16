// src/pages/Register.tsx
export default function Register() {
    return (
        <div className="max-w-md mx-auto p-6 bg-white rounded-2xl shadow">
            <h1 className="text-xl font-bold mb-4">会員登録</h1>
            <form>
                <input type="text" placeholder="氏名 / 事務所名" className="input" />
                <input type="email" placeholder="メールアドレス" className="input" />
                <input type="password" placeholder="パスワード" className="input" />
                <button className="btn-primary w-full">登録する</button>
            </form>
        </div>
    );
}
