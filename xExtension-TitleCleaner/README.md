# Title Cleaner Extension for FreshRSS

This extension automatically cleans up HTML tags and unescapes HTML entities in feed entry titles before they are stored in FreshRSS.

## Features

- **Entity Unescaping**: Decodes HTML entities such as `&amp;` -> `&`, `&quot;` -> `"`, `&#039;` -> `'`, `&lt;` -> `<`, `&gt;` -> `>`, etc.
- **Double-Encoding Resolution**: Handles nested or double-encoded entities (e.g. `&amp;amp;`).
- **HTML Tag Removal**: Strips any HTML tags present in title strings (e.g., `<i>`, `<b>`, `<span>`, `<a>`).
- **Whitespace Normalization**: Converts non-breaking spaces to standard spaces and collapses multiple spaces.

## Installation

1. Copy the `xExtension-TitleCleaner` directory into your FreshRSS `./p/extensions/` directory (e.g., `/usr/share/freshrss/p/extensions/xExtension-TitleCleaner`).
2. Open FreshRSS in your web browser.
3. Go to **Settings** -> **Extensions**.
4. Enable **Title Cleaner**.
