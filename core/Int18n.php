<?php
namespace Bro\core;

class Int18n {

    /**
     * Convert IPv4 to integer
     * 
     * @param int $ip
     * @return string
     */
    public function ip2int($ip) {
        $part = explode(".", $ip);
        $int = 0;
        if (count($part) == 4) {
            $int = $part[3] + 256 * ($part[2] + 256 * ($part[1] + 256 * $part[0]));
        }
        return $int;
    }

    /**
     * Convert int to IPv4 string
     * 
     * @param int $int
     * @return string
     */
    public function int2ip($int) {
        $w = $int / 16777216 % 256;
        $x = $int / 65536 % 256;
        $y = $int / 256 % 256;
        $z = $int % 256;
        $zz = $z < 0 ? $z + 256 : $z;
        return "$w.$x.$y.$zz";
    }

    /**
     * Detect language by URL or user IPv4
     * 
     * 
     * @param type $ip
     * @param type $languages
     * @return type
     */
    public function detectLanguage($ip, $languages) {
        $lang = $this->detectLanguageByLink($languages);
        if ($lang === FALSE) {
            return $this->detectLanguageByIP($ip, $languages);
        } else {
            return $lang;
        }
    }
    
    /**
     * Detect user language by his IPv4 address
     * 
     * @param type $ip
     * @param type $languages
     * @return string
     */
    public function detectLanguageByIP($ip, $languages) {
        global $languageDb;
        
        $ipnum = $this->ip2int($ip);
        $q = "SELECT country_id FROM {$languageDb}net_country_ip n where begin_ip<=$ipnum and end_ip>$ipnum";
        $db = Database::getInstance();

        $result = 'en';
        $row = $db->queryOneRow($q);
        if ($row) {
            foreach ($languages as $cc => $language) {
                $countryId = $row['country_id'];
                if (isset($language['codes']) && in_array($countryId, $language['codes']))
                    return $cc;
            }
        }
        return $result;
    }

    /**
     * Detect language by page URL
     * 
     * @param type $languages
     * @return array|boolean
     */
    public function detectLanguageByLink($languages)
    {
        if (!isset($languages)) return FALSE;
        
        $ccList = join('|', array_keys($languages));
        $regexp = "/^\/($ccList)(.*)$/";
        $uri = isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:'/';
        $matches = array();
        if (preg_match($regexp, $uri, $matches)) {
            return $matches[1];
        } else {
            return FALSE;
        }
        
    }

    /**
     * Init gettext I18N
     * 
     * @param string $lang
     * @param array $languages
     */
    public function gettextInit($lang, $languages) {
        $locale = $languages[$lang]['locale'];

        putenv("LC_ALL=$locale");
        setlocale(LC_ALL, $locale);

        // Specify the location of the translation tables
        $bindPath = dirname(__FILE__) . "/../../localize/locale";
        bindtextdomain('messages', $bindPath);
        bind_textdomain_codeset('messages', 'UTF-8');
        // Choose domain
        textdomain('messages');

    }

    /**
     * Get links to current page in different languages
     * 
     * @param type $languages
     * @return type
     */
    public function getLinks($languages) {
        $ccList = join('|', array_keys($languages));
        $regexp = "/^\/($ccList)(.*)$/";
        $uri = isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:'/';
        $matches = array();
        $result = array();
        if (preg_match($regexp, $uri, $matches)) {
            foreach ($languages as $cc => $lang) {
                $leftUrl = $matches[2];
                $result[] = array('cc' => $cc, 'url' => "/$cc$leftUrl", 'name' => $lang['name']);
            }
        };
        return $result;
    }
    
}

