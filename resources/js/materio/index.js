import './vendor/helpers.js';
import './config.js';
import './vendor/menu.js';

import * as bootstrap from 'bootstrap';
import Waves from 'node-waves';

window.bootstrap = bootstrap;
window.Waves = Waves;

export { initMaterio } from './initMaterio.js';
export { default as useMaterio } from './composables/useMaterio.js';
