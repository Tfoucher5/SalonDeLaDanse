import Alpine from 'alpinejs';
import badgeScanner from './badge-scanner';
import badgeSelection from './badge-selection';
import liveFilters from './live-filters';
import planning from './planning';
import selectMenu from './select-menu';

window.Alpine = Alpine;

Alpine.data('badgeScanner', badgeScanner);
Alpine.data('badgeSelection', badgeSelection);
Alpine.data('liveFilters', liveFilters);
Alpine.data('planning', planning);
Alpine.data('selectMenu', selectMenu);

Alpine.start();
