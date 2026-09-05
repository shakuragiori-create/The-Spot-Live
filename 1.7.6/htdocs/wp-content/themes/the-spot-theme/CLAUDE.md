# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

This is a WordPress theme for "The Spot Fast Food & Tea" — a restaurant in Khairahani-08, Parsa, Chitwan, Nepal. It is a single-page theme (no build tools, no dependencies) that renders the entire site from `index.php`.

## Architecture

- **Single-template theme**: All HTML, CSS, and JS live in `index.php`. There is no `header.php`, `footer.php`, or template parts.
- **CSS**: All styles are inlined in a `<style>` block within `<head>` of `index.php`. The `assets/main.css` and `assets/js/main.js` files are enqueued by `functions.php` but currently empty (reserved for future use).
- **JS**: Tab switching, hamburger menu, and smooth scroll are in a `<script>` block at the bottom of `index.php`.
- **CSS variables** (defined in `:root`): `--y` (yellow/gold #F5C300), `--b` (black #111), `--br` (brown #6B3A2A), `--cr` (cream #F5EFD6), `--w` (white), `--g` (gray #444).
- **Fonts**: Poppins (headings) and Inter (body), loaded from Google Fonts.

## Development

No build step. Edit files directly and test in a WordPress environment (local or remote).

To use this theme: place the `the-spot-theme` folder in `wp-content/themes/` and activate it from WP Admin > Appearance > Themes.

## Key Conventions

- Menu items use the `.mi` class (grid items with name + price). Special items get `.mi.sp`.
- Menu categories use `.cat` divs with `id="t-{slug}"`, toggled by `.tab` buttons with `data-t="{slug}"`.
- Vegetarian items are marked with `<span class="vdot"></span>` (green dot).
- Prices are plain numbers inside `.mi-price` spans; the "Rs. " prefix is added via CSS `::before`.
- The theme supports WordPress `custom-logo` and `title-tag` features (registered in `functions.php`).
