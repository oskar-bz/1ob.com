// @ts-check
import { defineConfig } from 'astro/config';
import mdx from '@astrojs/mdx';
import sitemap from '@astrojs/sitemap';
import { unified } from '@astrojs/markdown-remark';
import remarkMath from 'remark-math';
import rehypeKatex from 'rehype-katex';

// Code colours: the two accents only, on the same inset tone as figures.
const codeTheme = {
  name: '1ob',
  type: 'light',
  colors: { 'editor.background': '#ebe8e0', 'editor.foreground': '#2b2a25' },
  tokenColors: [
    { scope: ['comment', 'punctuation.definition.comment'], settings: { foreground: '#8a877e' } },
    { scope: ['keyword', 'storage', 'storage.type', 'storage.modifier', 'keyword.control', 'keyword.operator.new', 'support.type.primitive'], settings: { foreground: '#2f2fd0' } },
    { scope: ['constant.numeric', 'constant.language', 'constant.character'], settings: { foreground: '#c13f12' } },
    { scope: ['string', 'string.quoted'], settings: { foreground: '#46443e' } },
    { scope: ['entity.name.function', 'support.function'], settings: { foreground: '#191917' } },
  ],
};

export default defineConfig({
  site: 'https://1ob.com',
  trailingSlash: 'always',          // Apache serves /path/index.html for /path/
  build: { format: 'directory' },
  integrations: [mdx(), sitemap()],
  markdown: {
    // remark/rehype pipeline (Astro 7 defaults to Sätteri, which doesn't run these plugins)
    processor: unified({
      remarkPlugins: [remarkMath],
      rehypePlugins: [rehypeKatex],  // maths rendered at build time; no maths JS ships
    }),
    shikiConfig: { theme: codeTheme, wrap: false },
  },
  prefetch: false,                   // no speculative requests (and none to count in analytics)
});
