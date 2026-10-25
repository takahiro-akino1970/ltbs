import '@inertiajs/core';
import type { CustomerOption } from './admin';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            appName: string;
            portal: 'order' | 'admin';
            customerOptions: CustomerOption[];
        };
    }
}
