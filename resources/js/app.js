import './bootstrap';
import './qronly';
import './scanner';
import './edit_design';
import { Html5Qrcode } from 'html5-qrcode';


import Alpine from 'alpinejs';

window.Alpine = Alpine;
window.Html5Qrcode = Html5Qrcode;

Alpine.start();
