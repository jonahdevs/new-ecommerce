import { defineConfig } from '@maizzle/framework'
import { cpSync, existsSync } from 'node:fs'
import { resolve, join } from 'node:path'

/**
 * Maizzle is the source of truth for our transactional emails.
 *
 * Authoring lives in `emails/{orders,quotes}/*.vue`. The build compiles each
 * one to inlined, email-client-safe HTML written as a `.blade.php` file, then
 * the `afterBuild` hook copies the results into `resources/views/mails/` where
 * Laravel renders them with real data.
 *
 * Dynamic Laravel data (Blade echoes, @foreach/@if, money(), route()) is kept
 * verbatim by wrapping it in <Raw> — its slot content is extracted before Vue
 * compiles, so `{{ ... }}` and `@directives` pass straight through.
 *
 * We build into a staging folder (`build/`) rather than straight into the mail
 * views directory because Maizzle wipes `output.path` on every run; staging +
 * a targeted copy guarantees we never delete a mail view we didn't generate.
 */
const MAIL_VIEWS = resolve('../resources/views/mails')

/**
 * The CSS inliner (juice) and HTML serializer escape text nodes — so a Blade
 * operator like `->` becomes `-&gt;` and `@if ($x > 0)` becomes `&gt; 0`,
 * which is invalid PHP. We undo that, but ONLY inside Blade constructs, so
 * legitimate HTML entities (&copy;, &#8199;, &nbsp;) are left untouched.
 */
function decodeEntities(code) {
  return code
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&quot;/g, '"')
    .replace(/&#0?39;/g, "'")
    .replace(/&#x27;/gi, "'")
    .replace(/&apos;/g, "'")
    .replace(/&amp;/g, '&') // must run last so the others aren't double-decoded
}

const BLADE_DIRECTIVE = /^@([a-zA-Z]+)\s*\(/

function decodeBladeServerCode(html) {
  let out = ''
  let i = 0

  while (i < html.length) {
    // {{ ... }} echoes
    if (html.startsWith('{{', i)) {
      const end = html.indexOf('}}', i + 2)
      if (end !== -1) {
        out += '{{' + decodeEntities(html.slice(i + 2, end)) + '}}'
        i = end + 2
        continue
      }
    }

    // {!! ... !!} unescaped echoes
    if (html.startsWith('{!!', i)) {
      const end = html.indexOf('!!}', i + 3)
      if (end !== -1) {
        out += '{!!' + decodeEntities(html.slice(i + 3, end)) + '!!}'
        i = end + 3
        continue
      }
    }

    // @php ... @endphp blocks
    if (html.startsWith('@php', i)) {
      const end = html.indexOf('@endphp', i)
      if (end !== -1) {
        out += '@php' + decodeEntities(html.slice(i + 4, end)) + '@endphp'
        i = end + 7
        continue
      }
    }

    // @directive( ...balanced parens... ) — @if, @foreach, @forelse, etc.
    const directive = BLADE_DIRECTIVE.exec(html.slice(i, i + 40))
    if (directive) {
      const open = i + directive[0].length - 1
      let depth = 0
      let j = open
      for (; j < html.length; j++) {
        if (html[j] === '(') {
          depth++
        } else if (html[j] === ')') {
          depth--
          if (depth === 0) {
            j++
            break
          }
        }
      }
      out += html.slice(i, open) + decodeEntities(html.slice(open, j))
      i = j
      continue
    }

    out += html[i]
    i++
  }

  return out
}

export default defineConfig({
  content: ['emails/**/*.vue'],

  output: {
    path: 'build',
    extension: 'blade.php',
  },

  css: {
    inline: true,
    purge: true,
  },

  // Don't re-encode literal glyphs (em dash, etc.) to named entities; UTF-8
  // covers them and it keeps the output diff-friendly.
  useTransformers: {
    entities: false,
  },

  html: {
    format: true,
  },

  // Last stage before the file is written: repair Blade operators that the
  // inliner/serializer entity-escaped.
  afterTransform({ html }) {
    return decodeBladeServerCode(html)
  },

  afterBuild() {
    for (const group of ['orders', 'quotes']) {
      const from = resolve('build', group)

      if (existsSync(from)) {
        cpSync(from, join(MAIL_VIEWS, group), { recursive: true })
      }
    }
  },
})
