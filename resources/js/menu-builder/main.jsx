import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import MenuBuilder from './MenuBuilder.jsx';
import './menu-builder.css';

const el = document.getElementById('menu-builder');
const props = JSON.parse(el.dataset.props);

createRoot(el).render(
    <StrictMode>
        <MenuBuilder {...props} />
    </StrictMode>,
);
