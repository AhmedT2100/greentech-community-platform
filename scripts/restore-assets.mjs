import { cpSync, mkdirSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
const mappings = [
  ['bootstrap/dist', 'bootstrap'],
  ['jquery/dist/jquery.min.js', 'jquery/jquery.min.js'],
  ['jquery-migrate/dist/jquery-migrate.min.js', 'jquery/jquery-migrate.min.js'],
  ['jquery.counterup/jquery.counterup.min.js', 'counterup/counterup.min.js'],
  ['jquery.easing/jquery.easing.js', 'easing/easing.js'],
  ['jquery.easing/jquery.easing.min.js', 'easing/easing.min.js'],
  ['ionicons/css', 'ionicons/css'], ['ionicons/fonts', 'ionicons/fonts'],
  ['isotope-layout/dist', 'isotope'], ['lightbox2/dist', 'lightbox'],
  ['owl.carousel/dist', 'owlcarousel'],
  ['superfish/dist/js/superfish.js', 'superfish/superfish.js'],
  ['superfish/dist/js/superfish.min.js', 'superfish/superfish.min.js'],
  ['jquery-hoverintent/jquery.hoverIntent.js', 'superfish/hoverIntent.js'],
  ['jquery-touchswipe/jquery.touchSwipe.js', 'touchSwipe/jquery.touchSwipe.js'],
  ['jquery-touchswipe/jquery.touchSwipe.min.js', 'touchSwipe/jquery.touchSwipe.min.js'],
  ['waypoints/lib/jquery.waypoints.min.js', 'waypoints/waypoints.min.js'],
];
const roots = ['public/vendor', 'public/html-business-template/vendor',
  'public/event-reservations/html-business-template/vendor',
  'public/event-reservations/mvc/Public/vendor'];
for (const root of roots) {
  for (const [source, destination] of mappings) {
    const target = resolve(root, destination);
    mkdirSync(dirname(target), { recursive: true });
    cpSync(resolve('node_modules', source), target, { recursive: true });
  }
}
console.log('Restored browser dependencies for all template and event layouts.');

for (const directory of ['public/uploads', 'public/articles/uploads', 'public/FrontOffice/uploads', 'public/innovation/uploads']) { mkdirSync(directory, { recursive: true }); }
