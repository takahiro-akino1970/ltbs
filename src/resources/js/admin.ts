import { type DefineComponent } from 'vue';
import { createPortalApp } from './createPortalApp';

createPortalApp(
    import.meta.glob<DefineComponent>('./pages/admin/**/*.vue'),
);
