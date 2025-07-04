/*
 *  Document   : bootstrap.js
 *  Author     : pixelcave
 *  Description: Import global dependencies
 *
 */

 // Import all vital core JS files..
 import jQuery from 'jquery';
 import SimpleBar from 'simplebar';
 import Cookies from 'js-cookie';
 import 'bootstrap';
 import 'popper.js';
 import 'jquery.appear';
 import 'jquery-scroll-lock';
 import 'jquery-countto';

import Echo from 'laravel-echo';

window.Pusher = require('pusher-js');

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: true
});

 // ..and assign to window the ones that need it
 window.$ = window.jQuery    = jQuery;
 window.SimpleBar            = SimpleBar;
 window.Cookies              = Cookies;


