<?php

class ContentCleanerExtension extends Minz_Extension {
    private static $entryDAO = null;

    public function init() {
        // Der Hook greift bei jedem Artikel, der im Feed geliefert wird
        $this->registerHook('entry_before_insert', array($this, 'cleanAndPreventOverwrite'));
    }

    public function cleanAndPreventOverwrite($entry) {
        if ($entry === null) {
            return null;
        }

        if (self::$entryDAO === null) {
            self::$entryDAO = FreshRSS_Factory::createEntryDao();
        }

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
            $existing_hashes = self::$entryDAO->listHashForFeedGuids($feed_id, array($guid));
            
            if (!empty($existing_hashes) && isset($existing_hashes[$guid])) {
                // Der Artikel existiert bereits in deiner Datenbank.
                // Durch die Rückgabe von null wird jegliche Aktualisierung (und somit die Kürzung) blockiert.
                return null;
            }
        }

        // --- Ab hier: Verarbeitung für komplett NEUE Artikel ---
        $content = $entry->content();

        if (is_string($content) && $content !== '') {
            // 0. Komplette Autorenbox (<div id="autorbox">) entfernen, unabhängig vom Inhalt
            if (stripos($content, 'autorbox') !== false) {
                $content = preg_replace('/<div\s[^>]*\bid\s*=\s*["\']?autorbox["\']?[^>]*>.*?<\/div>\s*/is', '', $content, 1);
            }

            // 1. Autoren-Box entfernen (Avatar & Absatz)
            if (strpos($content, 'author/') !== false) {
                $author_pattern = '/(?:<img\s+[^>]*local-avatars[^>]*>\s*)?<p[^>]*>\s*<strong>\s*<a\s+href="[^"]*\/author\/.*?<\/p>/is';
                $content = preg_replace($author_pattern, '', $content);
            }

            // 1b. Autoren-Box ohne /author/-Link: "Bild von NAME" + Absatz, der mit NAME beginnt
            if (strpos($content, 'Bild von') !== false) {
                $content = $this->removeNamedAuthorBox($content);
            }

            // 2. Werbung entfernen
            if (strpos($content, 'Werbung') !== false) {
                $ad_pattern = '/<p[^>]*>\s*Werbung\s*<\/p>/is';
                $content = preg_replace($ad_pattern, '', $content);
            }

            $entry->_content($content);
        }

        return $entry;
    }

    /**
     * Entfernt Avatar-Beschriftung ("Bild von NAME", als <img alt>, Text oder Element)
     * und den darauffolgenden Kurzbio-Absatz, der mit demselben Namen beginnt.
     * Wirkt nur auf die ersten 3000 Zeichen, um Fehltreffer im Artikeltext zu vermeiden.
     */
    private function removeNamedAuthorBox($content) {
        $head = substr($content, 0, 3000);
        $tail = (string) substr($content, 3000);

        if (!preg_match('/Bild von\s+([^<>"\n]+?)\s*(?:["<]|\n|$)/u', $head, $m)) {
            return $content;
        }
        $name = preg_quote(trim($m[1]), '/');

        // a) Bild-Tag mit alt="Bild von NAME" (inkl. optionalem <figure>/<a>-Wrapper)
        $head = preg_replace('/<img\s+[^>]*Bild von\s+' . $name . '[^>]*>\s*/iu', '', $head, 1);
        // b) Reines Textelement "Bild von NAME"
        $head = preg_replace('/<(p|div|span|figcaption)[^>]*>\s*Bild von\s+' . $name . '\s*<\/\1>\s*/iu', '', $head, 1);
        $head = preg_replace('/^\s*Bild von\s+' . $name . '\s*/iu', '', $head, 1);

        // c) Kurzbio: Absatz/Div, dessen Text mit dem Namen beginnt
        $head = preg_replace(
            '/<(p|div)[^>]*>\s*(?:<[^>]+>\s*)*' . $name . '\b.*?<\/\1>\s*/isu',
            '',
            $head,
            1
        );

        return $head . $tail;
    }
}
