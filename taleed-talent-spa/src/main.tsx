import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { Provider } from 'react-redux';
import { store } from './app/store';
import App from './app/App';
import './styles/tokens.css';
import './styles/app.css';
const root = document.getElementById('root');
if (!root)
    throw new Error('The application root element was not found.');
createRoot(root).render(<StrictMode><Provider store={store}><App /></Provider></StrictMode>);

