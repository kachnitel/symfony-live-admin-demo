import { Controller } from '@hotwired/stimulus';

/**
 * Theme toggle controller with three-way switching:
 * - light: Force light mode
 * - dark: Force dark mode
 * - system: Follow OS preference
 *
 * Uses localStorage for persistence and applies 'dark' class to <html>.
 */
export default class extends Controller {
    static targets = ['button', 'icon', 'label'];

    static THEMES = ['light', 'dark', 'system'];

    static ICONS = {
        light: '\u2600\uFE0F',  // sun
        dark: '\uD83C\uDF19',   // moon
        system: '\uD83D\uDCBB'  // computer
    };

    static LABELS = {
        light: 'Light',
        dark: 'Dark',
        system: 'System'
    };

    connect() {
        this.currentTheme = localStorage.getItem('theme') || 'system';
        this.mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        this.applyTheme();

        this.boundSystemChange = this.handleSystemChange.bind(this);
        this.mediaQuery.addEventListener('change', this.boundSystemChange);
    }

    disconnect() {
        if (this.mediaQuery) {
            this.mediaQuery.removeEventListener('change', this.boundSystemChange);
        }
    }

    toggle() {
        const currentIndex = this.constructor.THEMES.indexOf(this.currentTheme);
        const nextIndex = (currentIndex + 1) % this.constructor.THEMES.length;
        this.currentTheme = this.constructor.THEMES[nextIndex];

        localStorage.setItem('theme', this.currentTheme);
        this.applyTheme();
    }

    applyTheme() {
        const html = document.documentElement;

        if (this.currentTheme === 'dark') {
            html.classList.add('dark');
        } else if (this.currentTheme === 'light') {
            html.classList.remove('dark');
        } else {
            if (this.mediaQuery.matches) {
                html.classList.add('dark');
            } else {
                html.classList.remove('dark');
            }
        }

        this.updateUI();
    }

    handleSystemChange() {
        if (this.currentTheme === 'system') {
            this.applyTheme();
        }
    }

    updateUI() {
        const Icons = this.constructor.ICONS;
        const Labels = this.constructor.LABELS;

        if (this.hasIconTarget) {
            this.iconTarget.textContent = Icons[this.currentTheme];
        }

        if (this.hasLabelTarget) {
            this.labelTarget.textContent = Labels[this.currentTheme];
        }

        if (this.hasButtonTarget) {
            this.buttonTarget.setAttribute('title', `Current: ${Labels[this.currentTheme]}. Click to change.`);
            this.buttonTarget.setAttribute('aria-label', `Theme: ${Labels[this.currentTheme]}`);
        }
    }
}
