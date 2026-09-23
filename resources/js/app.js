import Alpine from 'alpinejs';
import liveFilters from './live-filters';
import planning from './planning';

window.Alpine = Alpine;

Alpine.data('liveFilters', liveFilters);
Alpine.data('planning', planning);

Alpine.start();
