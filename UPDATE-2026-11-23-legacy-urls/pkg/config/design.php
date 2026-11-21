<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Design token registry
|--------------------------------------------------------------------------
|
| One declaration of every value an administrator may change about how the
| public site looks, and the rules for what counts as a valid value.
|
| Everything downstream is generated from this file:
|
|   - App\Support\Theme reads it to validate stored values and emit CSS
|   - The Theme admin screen builds its form from it
|   - resources/css/app.css consumes the emitted custom properties
|
| That single source is the point. The alternative — a form field here, a
| validation rule there, a CSS variable somewhere else — is how a theme system
| becomes a hundred hard-coded classes that nobody dares change.
|
| `responsive => true` means a token may hold either a single value or a map
| keyed by breakpoint. Theme emits the base value on :root and each override
| inside a min-width media query, so "mobile is not just desktop made smaller"
| is expressible rather than aspirational.
|
| `type` selects the validator in App\Support\Theme::validate(). A value that
| fails is dropped, never corrected — a silently wrong colour is worse than a
| missing one, and a `<style>` block is the one place Blade escaping does not
| protect.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Breakpoints
    |--------------------------------------------------------------------------
    |
    | Min-width in pixels. `mobile` is the base and deliberately has no query:
    | the site is authored mobile-first, so every other tier is an override.
    |
    | These match Tailwind's sm/lg/xl/2xl so utility classes written by hand and
    | tokens emitted from here never disagree about where a layout changes.
    |
    */

    'breakpoints' => [
        'mobile' => 0,
        'tablet' => 768,
        'laptop' => 1024,
        'desktop' => 1280,
        'wide' => 1536,
    ],

    /*
    |--------------------------------------------------------------------------
    | Shadow presets
    |--------------------------------------------------------------------------
    |
    | Named rather than free-form. A shadow is four numbers and a colour, and
    | letting an administrator type all five produces muddy results far more
    | often than it produces good ones. `custom` exists as an escape hatch and
    | is validated against a constrained pattern.
    |
    */

    'shadows' => [
        'none' => 'none',
        'soft' => '0 1px 2px rgb(32 37 33 / 0.04), 0 1px 3px rgb(32 37 33 / 0.03)',
        'medium' => '0 2px 4px rgb(32 37 33 / 0.05), 0 8px 20px -8px rgb(32 37 33 / 0.10)',
        'strong' => '0 4px 10px rgb(32 37 33 / 0.08), 0 18px 40px -12px rgb(32 37 33 / 0.18)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Radius presets
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Section spacing scale
    |--------------------------------------------------------------------------
    |
    | Named steps for a homepage section's own top and bottom padding,
    | expressed relative to the global section padding rather than as fixed
    | lengths — so a section set to "loose" stays proportionally loose after an
    | administrator changes the site-wide spacing, instead of drifting out of
    | step with every other band on the page.
    |
    */

    'section_spacing' => [
        'none' => '0px',
        'tight' => 'calc(var(--wr-section-py) * 0.55)',
        'default' => 'var(--wr-section-py)',
        'loose' => 'calc(var(--wr-section-py) * 1.4)',
    ],

    'radii' => [
        'square' => '0px',
        'slight' => '2px',
        'rounded' => '8px',
        'soft' => '14px',
        'pill' => '9999px',
    ],

    /*
    |--------------------------------------------------------------------------
    | Font stacks
    |--------------------------------------------------------------------------
    |
    | An allowlist, not free text. Every family here is self-hosted from npm and
    | compiled into the bundle; a typed family name would either silently fall
    | back to a system font or require a request to a font CDN, and the shared
    | host this runs on has no outbound access from the browser's perspective
    | that we control.
    |
    */

    'fonts' => [
        'manrope' => "'Manrope', ui-sans-serif, system-ui, sans-serif",
        'plex-arabic' => "'IBM Plex Sans Arabic', 'Manrope', ui-sans-serif, system-ui, sans-serif",
        'system' => 'ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif',
        'serif' => 'ui-serif, Georgia, Cambria, "Times New Roman", serif',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tokens
    |--------------------------------------------------------------------------
    |
    | group => [ token key => definition ]
    |
    | Definition keys:
    |   var        the CSS custom property written to :root
    |   type       validator: color|length|number|ratio|opacity|shadow|radius|font|weight|choice
    |   default    used when nothing is stored
    |   responsive whether per-breakpoint values are accepted
    |   options    for `choice`, the permitted values
    |   label      English label for the admin form
    |
    */

    'tokens' => [

        // ------------------------------------------------------------ Colour
        'color' => [
            'primary' => ['var' => '--color-olive-deep', 'type' => 'color', 'default' => '#173f35', 'label' => 'Primary'],
            'primary_hover' => ['var' => '--color-olive-deep-hover', 'type' => 'color', 'default' => '#1d4d41', 'label' => 'Primary (hover)'],
            'secondary' => ['var' => '--color-forest', 'type' => 'color', 'default' => '#0e2e27', 'label' => 'Secondary / dark'],
            'accent' => ['var' => '--color-gold', 'type' => 'color', 'default' => '#c6a15b', 'label' => 'Accent'],
            'accent_hover' => ['var' => '--color-gold-hover', 'type' => 'color', 'default' => '#b38f4b', 'label' => 'Accent (hover)'],
            'muted_brand' => ['var' => '--color-olive-muted', 'type' => 'color', 'default' => '#5c6a36', 'label' => 'Muted brand'],

            'background' => ['var' => '--color-surface', 'type' => 'color', 'default' => '#ffffff', 'label' => 'Page background'],
            'surface_alt' => ['var' => '--color-surface-alt', 'type' => 'color', 'default' => '#f7f5ef', 'label' => 'Alternate surface'],
            'surface_sunken' => ['var' => '--color-surface-sunken', 'type' => 'color', 'default' => '#efece2', 'label' => 'Sunken surface'],
            'card' => ['var' => '--color-card', 'type' => 'color', 'default' => '#ffffff', 'label' => 'Card background'],

            'text' => ['var' => '--color-ink', 'type' => 'color', 'default' => '#202521', 'label' => 'Text'],
            'text_muted' => ['var' => '--color-ink-muted', 'type' => 'color', 'default' => '#5a635c', 'label' => 'Muted text'],
            'text_subtle' => ['var' => '--color-ink-subtle', 'type' => 'color', 'default' => '#646b65', 'label' => 'Subtle text'],

            'border' => ['var' => '--color-line', 'type' => 'color', 'default' => '#e3e0d6', 'label' => 'Border'],
            'border_strong' => ['var' => '--color-line-strong', 'type' => 'color', 'default' => '#cfcabb', 'label' => 'Border (strong)'],

            'link' => ['var' => '--color-link', 'type' => 'color', 'default' => '#173f35', 'label' => 'Link'],
            'link_hover' => ['var' => '--color-link-hover', 'type' => 'color', 'default' => '#c6a15b', 'label' => 'Link (hover)'],

            'header_bg' => ['var' => '--color-header-bg', 'type' => 'color', 'default' => '#ffffff', 'label' => 'Header background'],
            'header_text' => ['var' => '--color-header-text', 'type' => 'color', 'default' => '#5a635c', 'label' => 'Header text'],
            'footer_bg' => ['var' => '--color-footer-bg', 'type' => 'color', 'default' => '#0e2e27', 'label' => 'Footer background'],
            'footer_text' => ['var' => '--color-footer-text', 'type' => 'color', 'default' => '#cfd9d3', 'label' => 'Footer text'],

            'button_bg' => ['var' => '--color-button-bg', 'type' => 'color', 'default' => '#173f35', 'label' => 'Button background'],
            'button_text' => ['var' => '--color-button-text', 'type' => 'color', 'default' => '#ffffff', 'label' => 'Button text'],
            'input_bg' => ['var' => '--color-input-bg', 'type' => 'color', 'default' => '#ffffff', 'label' => 'Input background'],
            'overlay' => ['var' => '--color-overlay', 'type' => 'color', 'default' => '#0e2e27', 'label' => 'Overlay'],
        ],

        // -------------------------------------------------------- Typography
        'type' => [
            'font_body' => ['var' => '--font-sans', 'type' => 'font', 'default' => 'manrope', 'label' => 'Body font'],
            'font_heading' => ['var' => '--font-heading', 'type' => 'font', 'default' => 'manrope', 'label' => 'Heading font'],
            'font_arabic' => ['var' => '--font-arabic', 'type' => 'font', 'default' => 'plex-arabic', 'label' => 'Arabic font'],

            'weight_heading' => ['var' => '--weight-heading', 'type' => 'weight', 'default' => '700', 'label' => 'Heading weight'],
            'weight_body' => ['var' => '--weight-body', 'type' => 'weight', 'default' => '400', 'label' => 'Body weight'],

            /*
             | The type scale is responsive by default.
             |
             | Before this, `--text-display` was a flat 4rem and `--text-h1` a
             | flat 3rem — 64px and 48px on a 360px-wide phone, where a heading
             | of four words wraps to four lines. Only three templates
             | compensated, with `text-h1 lg:text-display`; the other
             | forty-odd headings on the site rendered at desktop size on
             | every device.
             |
             | Scaling the token rather than patching templates means the fix
             | reaches all of them, and stays configurable per tier.
             */
            'display' => [
                'var' => '--text-display', 'type' => 'length', 'responsive' => true, 'label' => 'Display size',
                'default' => ['mobile' => '2.5rem', 'tablet' => '3.25rem', 'laptop' => '4rem'],
            ],
            'h1' => [
                'var' => '--text-h1', 'type' => 'length', 'responsive' => true, 'label' => 'H1 size',
                'default' => ['mobile' => '2rem', 'tablet' => '2.5rem', 'laptop' => '3rem'],
            ],
            'h2' => [
                'var' => '--text-h2', 'type' => 'length', 'responsive' => true, 'label' => 'H2 size',
                'default' => ['mobile' => '1.625rem', 'tablet' => '1.875rem', 'laptop' => '2.25rem'],
            ],
            'h3' => [
                'var' => '--text-h3', 'type' => 'length', 'responsive' => true, 'label' => 'H3 size',
                'default' => ['mobile' => '1.25rem', 'tablet' => '1.375rem', 'laptop' => '1.5rem'],
            ],
            'body' => ['var' => '--text-body', 'type' => 'length', 'default' => '1rem', 'responsive' => true, 'label' => 'Body size'],
            'small' => ['var' => '--text-small', 'type' => 'length', 'default' => '0.875rem', 'responsive' => true, 'label' => 'Small text size'],
            'button' => ['var' => '--text-button', 'type' => 'length', 'default' => '0.9375rem', 'responsive' => true, 'label' => 'Button text size'],

            'line_height' => ['var' => '--leading-body', 'type' => 'ratio', 'default' => '1.65', 'label' => 'Body line height'],
            'line_height_heading' => ['var' => '--leading-heading', 'type' => 'ratio', 'default' => '1.12', 'label' => 'Heading line height'],
            'letter_spacing' => ['var' => '--tracking-heading', 'type' => 'length', 'default' => '-0.02em', 'label' => 'Heading letter spacing'],
        ],

        // ------------------------------------------------------------ Shapes
        'radius' => [
            'base' => ['var' => '--radius-base', 'type' => 'radius', 'default' => 'slight', 'label' => 'Base radius'],
            'card' => ['var' => '--radius-card', 'type' => 'radius', 'default' => 'slight', 'label' => 'Card radius'],
            'button' => ['var' => '--radius-button', 'type' => 'radius', 'default' => 'slight', 'label' => 'Button radius'],
            'input' => ['var' => '--radius-input', 'type' => 'radius', 'default' => 'slight', 'label' => 'Input radius'],
            'image' => ['var' => '--radius-image', 'type' => 'radius', 'default' => 'slight', 'label' => 'Image radius'],
            'badge' => ['var' => '--radius-badge', 'type' => 'radius', 'default' => 'slight', 'label' => 'Badge radius'],
        ],

        /*
         | Shadows
         |
         | These alone are written to `--wr-shadow-*` rather than to the
         | Tailwind token directly, and the reason is worth recording because
         | it is not guessable.
         |
         | Tailwind 4 parses a shadow value at build time so it can splice in
         | `--tw-shadow-color`, and compiles `.shadow-card` down to the literal
         | offsets. Overriding `--shadow-card` at runtime therefore changes
         | nothing. Wrapping the theme value in `var(--wr-shadow-card, …)` in
         | app.css defeats that parse: Tailwind cannot see inside a var(), so
         | it emits the reference verbatim and the utility becomes
         | runtime-overridable while still composing with ring utilities.
         |
         | Verified against Tailwind 4.3.3 by compiling a probe stylesheet and
         | reading the output, not assumed.
         |
         | Colours, radii and type sizes need no such indirection — those
         | utilities already compile to `var(--color-…)`, `var(--radius-…)` and
         | `var(--text-…)`, so they are overridden by name.
         */
        'shadow' => [
            'card' => ['var' => '--wr-shadow-card', 'type' => 'shadow', 'default' => 'soft', 'label' => 'Cards'],
            'button' => ['var' => '--wr-shadow-button', 'type' => 'shadow', 'default' => 'none', 'label' => 'Buttons'],
            'header' => ['var' => '--wr-shadow-header', 'type' => 'shadow', 'default' => 'soft', 'label' => 'Header'],
            'section' => ['var' => '--wr-shadow-section', 'type' => 'shadow', 'default' => 'none', 'label' => 'Sections'],
            'modal' => ['var' => '--wr-shadow-modal', 'type' => 'shadow', 'default' => 'strong', 'label' => 'Modals'],
            'image' => ['var' => '--wr-shadow-image', 'type' => 'shadow', 'default' => 'none', 'label' => 'Images'],
            'raised' => ['var' => '--wr-shadow-raised', 'type' => 'shadow', 'default' => 'medium', 'label' => 'Raised / hover'],
        ],

        // ------------------------------------------------------------- Glass
        'glass' => [
            'enabled' => ['var' => null, 'type' => 'choice', 'default' => 'on', 'options' => ['on', 'off'], 'label' => 'Glass effect'],
            // Percentages rather than 0–1 fractions: these feed color-mix()
            // and an alpha slot, both of which take a percentage directly, and
            // "72%" is plainer in an admin form than "0.72".
            'bg_opacity' => ['var' => '--wr-glass-bg-opacity', 'type' => 'percentage', 'default' => '72%', 'label' => 'Background opacity'],
            'blur' => ['var' => '--wr-glass-blur', 'type' => 'length', 'default' => '14px', 'label' => 'Backdrop blur'],
            'saturate' => ['var' => '--wr-glass-saturate', 'type' => 'number', 'default' => '1.4', 'label' => 'Backdrop saturation'],
            'border_opacity' => ['var' => '--wr-glass-border-opacity', 'type' => 'percentage', 'default' => '18%', 'label' => 'Border opacity'],
            'dark_bg_opacity' => ['var' => '--wr-glass-dark-bg-opacity', 'type' => 'percentage', 'default' => '62%', 'label' => 'Dark background opacity'],
        ],

        // ------------------------------------------------------------ Layout
        'layout' => [
            /*
             | A `default` may be a single value or a per-breakpoint map. The
             | maps below reproduce the hard-coded media queries that used to
             | live in app.css exactly — same values, same widths — so moving
             | layout into tokens changes nothing about how the site renders
             | until an administrator changes something.
             */
            'container_max' => ['var' => '--wr-container-max', 'type' => 'length', 'default' => '80rem', 'responsive' => true, 'label' => 'Container width'],

            'container_gutter' => [
                'var' => '--wr-container-gutter', 'type' => 'length', 'responsive' => true, 'label' => 'Side gutter',
                'default' => ['mobile' => '1.25rem', 'tablet' => '2rem', 'desktop' => '2.5rem'],
            ],

            'section_py' => [
                'var' => '--wr-section-py', 'type' => 'length', 'responsive' => true, 'label' => 'Section vertical padding',
                'default' => ['mobile' => '4.5rem', 'laptop' => '7rem'],
            ],

            'grid_gap' => [
                'var' => '--wr-grid-gap', 'type' => 'length', 'responsive' => true, 'label' => 'Grid gap',
                'default' => ['mobile' => '1.25rem', 'tablet' => '1.5rem'],
            ],

            'hero_min_height' => [
                'var' => '--wr-hero-min-height', 'type' => 'length', 'responsive' => true, 'label' => 'Hero minimum height',
                'default' => ['mobile' => '26rem', 'tablet' => '30rem', 'laptop' => '32rem'],
            ],
        ],

        // ------------------------------------------------------------ Header
        'header' => [
            'sticky' => ['var' => null, 'type' => 'choice', 'default' => 'on', 'options' => ['on', 'off'], 'label' => 'Sticky header'],
            'style' => ['var' => null, 'type' => 'choice', 'default' => 'glass', 'options' => ['solid', 'glass', 'transparent'], 'label' => 'Header style'],
            'height' => ['var' => '--wr-header-height', 'type' => 'length', 'default' => '5rem', 'responsive' => true, 'label' => 'Header height'],
            'height_scrolled' => ['var' => '--wr-header-height-scrolled', 'type' => 'length', 'default' => '4rem', 'responsive' => true, 'label' => 'Header height (scrolled)'],
            'logo_height' => ['var' => '--wr-header-logo-height', 'type' => 'length', 'default' => '3rem', 'responsive' => true, 'label' => 'Logo height'],
            'nav_gap' => ['var' => '--wr-header-nav-gap', 'type' => 'length', 'default' => '1.75rem', 'responsive' => true, 'label' => 'Navigation spacing'],
            'utility_bar' => ['var' => null, 'type' => 'choice', 'default' => 'on', 'options' => ['on', 'off'], 'label' => 'Contact strip above header'],
        ],

        // ------------------------------------------------------------ Footer
        'footer' => [
            'columns' => ['var' => '--wr-footer-columns', 'type' => 'integer', 'default' => '4', 'responsive' => true, 'label' => 'Footer columns'],
            'py' => ['var' => '--wr-footer-py', 'type' => 'length', 'default' => '4rem', 'responsive' => true, 'label' => 'Footer vertical padding'],
            'logo_height' => ['var' => '--wr-footer-logo-height', 'type' => 'length', 'default' => '2.75rem', 'label' => 'Footer logo height'],
            'show_social' => ['var' => null, 'type' => 'choice', 'default' => 'on', 'options' => ['on', 'off'], 'label' => 'Show social links'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Homepage section settings
    |--------------------------------------------------------------------------
    |
    | What an administrator may change about one band of the homepage, without
    | editing Blade or PHP.
    |
    | Same shape as the theme token registry above, and validated by the same
    | App\Support\Theme::validate() — a section setting reaches a stylesheet by
    | exactly the route a theme token does, so it gets exactly the same
    | scrutiny. `responsive => true` means the setting accepts a per-breakpoint
    | map, which is what lets a section run three columns on a desktop and one
    | on a phone rather than three shrunken ones.
    |
    | Adding a setting here gives it a form control, a validator and a scoped
    | CSS custom property. Adding a section *type* later needs no change to
    | this list — a type decides which of these it reads.
    |
    */

    'section_settings' => [
        'width' => [
            'type' => 'choice', 'default' => 'default',
            'options' => ['narrow', 'default', 'wide', 'full'],
            'label' => 'Content width',
        ],
        // `spacing` resolves a name to a CSS value through the scale above,
        // the same way `radius` and `shadow` resolve theirs. That is what lets
        // padding vary per breakpoint: a class-based version would need a
        // hand-written variant for every tier, and the ones nobody wrote would
        // silently do nothing.
        'padding_top' => [
            'type' => 'spacing', 'default' => 'default', 'responsive' => true,
            'var' => '--section-pt',
            'options' => ['none', 'tight', 'default', 'loose'],
            'label' => 'Space above',
        ],
        'padding_bottom' => [
            'type' => 'spacing', 'default' => 'default', 'responsive' => true,
            'var' => '--section-pb',
            'options' => ['none', 'tight', 'default', 'loose'],
            'label' => 'Space below',
        ],
        'align' => [
            'type' => 'choice', 'default' => 'start', 'responsive' => true,
            'options' => ['start', 'center', 'end'],
            'label' => 'Content alignment',
        ],
        'text_align' => [
            'type' => 'choice', 'default' => 'start', 'responsive' => true,
            'options' => ['start', 'center', 'end'],
            'label' => 'Text alignment',
        ],
        'columns' => [
            'type' => 'integer', 'default' => '3', 'responsive' => true,
            'var' => '--section-columns',
            'label' => 'Columns',
        ],
        'min_height' => [
            'type' => 'length', 'default' => '', 'responsive' => true,
            'var' => '--section-min-height',
            'label' => 'Minimum height',
        ],
        'image_position' => [
            'type' => 'choice', 'default' => 'start',
            'options' => ['start', 'end', 'top', 'bottom'],
            'label' => 'Image position',
        ],
        'overlay' => [
            'type' => 'percentage', 'default' => '0%',
            'var' => '--section-overlay',
            'label' => 'Overlay strength',
        ],
        'card_layout' => [
            'type' => 'choice', 'default' => 'card',
            'options' => ['card', 'bordered', 'plain'],
            'label' => 'Card style',
        ],
        'cta_layout' => [
            'type' => 'choice', 'default' => 'inline',
            'options' => ['inline', 'stacked'],
            'label' => 'Button layout',
        ],
    ],
];
