import 'bootstrap';
import { createApp, type DefineComponent } from 'vue';
import { initializeAppConfig } from './appConfig';

type PageModules = Record<string, () => Promise<DefineComponent>>;

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function readPageProps(): Record<string, unknown> {
    const propsElement = document.getElementById('page-props');

    if (propsElement === null) {
        throw new Error('The page props element is missing.');
    }

    const props: unknown = JSON.parse(propsElement.textContent ?? '');

    if (!isRecord(props)) {
        throw new Error('The page props must be a JSON object.');
    }

    return props;
}

/**
 * Create and mount the portal app
 *
 * @param pages The page modules to load
 */
export async function createPortalApp(pages: PageModules): Promise<void> {
    const root = document.getElementById('app');

    if (root === null) {
        throw new Error('The Vue application root element is missing.');
    }

    const { appName, page, portal } = root.dataset;

    if (!appName || !page || (portal !== 'order' && portal !== 'admin')) {
        throw new Error('The Vue application configuration is incomplete.');
    }

    const pageLoader = pages[`./pages/${page}.vue`];

    if (pageLoader === undefined) {
        throw new Error(`Unknown Vue page: ${page}`);
    }

    initializeAppConfig({ appName, portal });

    const component = await pageLoader();
    createApp(component, readPageProps()).mount(root);
}
