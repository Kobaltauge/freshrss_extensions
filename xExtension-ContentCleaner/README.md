# Content Cleaner Extension for FreshRSS

This extension cleans up article content before saving it to FreshRSS and prevents existing entries from being truncated or overwritten during feed refreshes.

## Features

- **Author Box Removal**: Strips author avatars and author link bio sections (`/author/...`) from article content.
- **Advertisement Removal**: Removes paragraphs marked as advertisements (e.g. `<p>Werbung</p>`).
- **Prevent Overwrites**: Checks if an entry's GUID already exists in the database for the feed. If so, it blocks subsequent updates to prevent content truncation or overwriting saved articles.

## Installation

1. Copy the `xExtension-ContentCleaner` directory into your FreshRSS `./p/extensions/` directory (e.g., `/usr/share/freshrss/p/extensions/xExtension-ContentCleaner`).
2. Open FreshRSS in your web browser.
3. Go to **Settings** -> **Extensions**.
4. Enable **Content Cleaner**.
