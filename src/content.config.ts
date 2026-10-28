import { defineCollection, reference, z } from 'astro:content';
import { glob } from 'astro/loaders';

const status = z.enum(['active', 'planned', 'shipped', 'paused', 'abandoned']);

const projects = defineCollection({
  loader: glob({ pattern: '**/*.{md,mdx}', base: './src/content/projects' }),
  schema: z.object({
    title: z.string(),
    order: z.number(),                       // shown as 01, 02 … and sorts the index
    summary: z.string(),                     // one line, used in lists
    dek: z.string(),                         // the sentence under the title
    domain: z.string(),
    years: z.string(),                       // e.g. "2026–" or "2024–25"
    status,
    why: z.string().optional(),              // required in spirit for paused / abandoned
    featured: z.number().optional(),         // 1 = homepage lead, 2–4 = the row below
    spec: z.array(z.object({ k: z.string(), v: z.string() })).default([]),
    roadmap: z.array(z.object({ label: z.string(), state: z.enum(['done', 'now', 'todo']) })).default([]),
    source: z.string().url().optional(),
    hn: z.string().regex(/^\d+$/).optional(),  // Hacker News item id, once submitted
    draft: z.boolean().default(false),
  }),
});

const writing = defineCollection({
  loader: glob({ pattern: '**/*.{md,mdx}', base: './src/content/writing' }),
  schema: z.object({
    title: z.string(),
    dek: z.string(),
    date: z.coerce.date(),
    tags: z.array(z.string()).default([]),
    project: reference('projects').optional(),
    hn: z.string().regex(/^(\d+|fixture)$/).optional(),   // "fixture" = local sample thread
    draft: z.boolean().default(false),
  }),
});

export const collections = { projects, writing };
