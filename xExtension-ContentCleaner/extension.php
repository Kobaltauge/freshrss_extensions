<?php

class ContentCleanerExtension extends Minz_Extension {
    public function init() {
        // Der Hook greift bei jedem Artikel, der im Feed geliefert wird
        $this->registerHook('entry_before_insert', array($this, 'cleanAndPreventOverwrite'));
    }

    public function cleanAndPreventOverwrite($entry) {
        $entryDAO = FreshRSS_Factory::createEntryDao();
        $guid = $entry->guid();

        // Feed-ID ermitteln (kompatibel mit verschiedenen FreshRSS-Versionen)
        $feed_id = null;
        if (method_exists($entry, 'feedId')) {
            $feed_id = $entry->feedId();
        } elseif (method_exists($entry, 'feed') && is_object($entry->feed())) {
            $feed_id = $entry->feed()->id();
        }

        if ($feed_id !== null && $guid !== '') {
            // Datenbank abfragen, ob die GUID für diesen Feed bereits existiert
            $existing_hashes = $entryDAO->listHashForFeedGuids($feed_id, array($guid));
            
            if (!empty($existing_hashes) && isset($existing_hashes[$guid])) {
                // Der Artikel existiert bereits in deiner Datenbank.
                // Durch die Rückgabe von null wird jegliche Aktualisierung (und somit die Kürzung) blockiert.
                return null;
            }
        }

        // --- Ab hier: Verarbeitung für komplett NEUE Artikel ---
        $content = $entry->content();

        // 1. Autoren-Box entfernen (Avatar & Absatz)
        $author_pattern = '/(?:<img\s+[^>]*local-avatars[^>]*>\s*)?<p[^>]*>\s*<strong>\s*<a\s+href="[^"]*\/author\/.*?<\/p>/is';
        $content = preg_replace($author_pattern, '', $content);

        // 2. Werbung entfernen
        $ad_pattern = '/<p[^>]*>\s*Werbung\s*<\/p>/is';
        $content = preg_replace($ad_pattern, '', $content);

        $entry->_content($content);
        return $entry;
    }
}
