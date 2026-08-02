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
     * Highly optimized title sanitizer using fast-path checks to skip
     * regex and entity decoding overhead when not needed.
     *
     * @param string $title Raw entry title
     * @return string Sanitized title
     */
    public function sanitizeTitle($title) {
        if (!is_string($title) || $title === '') {
            return '';
        }

        // Fast path: If title has no HTML tags, entities, or special non-breaking spaces, just trim/normalize space
        if (strpos($title, '&') === false && strpos($title, '<') === false && strpos($title, "\xc2\xa0") === false) {
            return trim(preg_replace('/\s+/', ' ', $title));
        }

        // 1. Strip HTML tags (only if '<' exists)
        $clean = (strpos($title, '<') !== false) ? strip_tags($title) : $title;

        // 2. Decode HTML entities (only if '&' exists)
        if (strpos($clean, '&') !== false) {
            for ($i = 0; $i < 2; $i++) {
                $decoded = html_entity_decode($clean, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
                if ($decoded === $clean) {
                    break;
                }
                $clean = $decoded;
            }
        }

        // 3. Convert non-breaking spaces (\u{00A0}) to regular spaces
        if (strpos($clean, "\xc2\xa0") !== false) {
            $clean = str_replace("\xc2\xa0", ' ', $clean);
        }

        // 4. Collapse multiple whitespace characters and trim
        return trim(preg_replace('/\s+/u', ' ', $clean));
    }
}
