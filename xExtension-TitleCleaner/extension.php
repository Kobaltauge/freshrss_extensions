<?php

class TitleCleanerExtension extends Minz_Extension {
    public function init() {
        $this->registerHook('entry_before_insert', array($this, 'cleanTitle'));
    }

    public function cleanTitle($entry) {
        if ($entry === null) {
            return null;
        }

        if (method_exists($entry, 'title') && method_exists($entry, '_title')) {
            $title = $entry->title();
            if (is_string($title) && $title !== '') {
                $cleanTitle = $this->sanitizeTitle($title);
                $entry->_title($cleanTitle);
            }
        }

        return $entry;
    }

    /**
     * Sanitizes a feed title by stripping HTML tags, decoding HTML entities,
     * normalizing non-breaking spaces, and collapsing redundant whitespace.
     *
     * @param string $title Raw entry title
     * @return string Sanitized title
     */
    public function sanitizeTitle($title) {
        // 1. Strip HTML tags (e.g. <b>, <i>, <span>, <a>)
        $clean = strip_tags($title);

        // 2. Decode HTML entities repeatedly to handle double-encoded entities (e.g. &amp;amp; or &amp;quot;)
        for ($i = 0; $i < 3; $i++) {
            $decoded = html_entity_decode($clean, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
            if ($decoded === $clean) {
                break;
            }
            $clean = $decoded;
        }

        // 3. Convert non-breaking spaces (\u{00A0}) to regular spaces
        $clean = str_replace("\xc2\xa0", ' ', $clean);

        // 4. Collapse multiple consecutive whitespace characters into a single space
        $clean = preg_replace('/\s+/u', ' ', $clean);

        // 5. Trim leading and trailing whitespace
        return trim($clean);
    }
}
