export interface PortalAppConfig {
    appName: string;
    portal: 'order' | 'admin';
}

let appConfig: PortalAppConfig | null = null;

export function initializeAppConfig(config: PortalAppConfig): void {
    appConfig = config;
}

export function useAppConfig(): PortalAppConfig {
    if (appConfig === null) {
        throw new Error('The application configuration has not been initialized.');
    }

    return appConfig;
}
