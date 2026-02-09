// @ts-check
import { defineConfig } from 'astro/config';
import starlight from '@astrojs/starlight';

// https://astro.build/config
export default defineConfig({
	site: 'https://knobik.github.io',
	base: '/laravel-sql-agent',
	integrations: [
		starlight({
			title: 'Laravel SQL Agent',
			social: [{ icon: 'github', label: 'GitHub', href: 'https://github.com/knobik/laravel-sql-agent' }],
			sidebar: [
				{ label: 'Getting Started', autogenerate: { directory: 'getting-started' } },
				{ label: 'Guides', autogenerate: { directory: 'guides' } },
				{ label: 'Reference', autogenerate: { directory: 'reference' } },
				{ label: 'Troubleshooting', slug: 'troubleshooting' },
			],
		}),
	],
});
