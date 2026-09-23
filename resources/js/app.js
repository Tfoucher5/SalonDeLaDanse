import Alpine from 'alpinejs';
import liveFilters from './live-filters';

window.Alpine = Alpine;

Alpine.data('liveFilters', liveFilters);

Alpine.start();
