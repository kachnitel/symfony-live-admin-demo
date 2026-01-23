import { startStimulusApp } from '@symfony/stimulus-bundle';
import BatchSelectController from '@kachnitel/admin-bundle/batch-select_controller.js';
import ThemeController from './controllers/theme_controller.js';

const app = startStimulusApp();
// register any custom, 3rd party controllers here
app.register('batch-select', BatchSelectController);
app.register('theme', ThemeController);
