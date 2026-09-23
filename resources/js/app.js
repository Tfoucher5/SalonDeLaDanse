import Alpine from 'alpinejs';
import liveFilters from './live-filters';
import planning from './planning';
import selectMenu from './select-menu';

window.Alpine = Alpine;

Alpine.data('liveFilters', liveFilters);
Alpine.data('planning', planning);
Alpine.data('selectMenu', selectMenu);

Alpine.start();
