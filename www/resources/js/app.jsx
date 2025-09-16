import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();
resources/js/app.jsx
import React from 'react'
import { createRoot } from 'react-dom/client'

function App() {
    return <div>SPA from Laravel Vite</div>
}

createRoot(document.getElementById('root')).render(<App />)
