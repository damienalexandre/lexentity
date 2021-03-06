<?php
namespace Lexentity\Token;

abstract class Token
{
    protected $text;
    protected $translatedText = null;
    protected static $types = array(
        'Singlequote' => "'",
        'Ellipsis' => '\.{3,}',
        'Quote' => '"',
        'Tag' => '<!--.*?-->|<\!\[CDATA\[.*\]\]>|<.*?>',
        'Emdash' => '—|--',
    );
    protected static $verbatimTags = array(
        'code','pre',
    );

    protected function __construct($text)
    {
        $this->text = $text;
    }

    public static function getRegex()
    {
        $regex = '/';
        $counter = count(self::$types);
        foreach (self::$types as $type) {
            $regex .= '(' . $type .')';
            if (--$counter) {
                $regex .= '|';
            }
        }
        $regex .= '/';
        return $regex;
    }
    
    public static function create($text)
    {
        // this is slow, but effective
        foreach (self::$types as $type => $regex) {
            if (preg_match('/^' . $regex . '$/', $text)) {
                $class = __NAMESPACE__ . '\\' . $type;
                return $class::createSubType($text);
            }
        }
        return Plaintext::createSubType($text);
    }
    
    protected static function createSubType($text)
    {
        return new static($text); // ftw!
    }
    
    public function translated()
    {
        return $this->translatedText ? $this->translatedText : $this->text;
    }
    
    public function __toString()
    {
        $set = Set::getInstance();
        if ($set->inTagContext(self::$verbatimTags)) {
            return $this->text;
        } else {
            return self::ampReplace($this->translated());
        }
    }

    protected static function isWhitespace($str)
    {
        return preg_match('/^\s+$/', $str);
    }

    public static function ampReplace($str)
    {
        $buf = htmlentities($str, ENT_NOQUOTES, 'UTF-8', false);
        $table = self::getEntitiesTable();
        $text = array_values($table);
        $numeric = array_keys($table);
        $class = get_called_class();
        array_walk($numeric, function (&$val) use ($class) {
            $val = '&#' . $class::uniord($val) . ';';
        });
        return str_replace(
            $text,
            $numeric,
            $buf
        );
    }

    // borrowed from:
    //   http://www.php.net/manual/en/function.ord.php#77905
    public static function uniord($c)
    {
        $h = ord($c[0]);
        if ($h <= 0x7F) {
            return $h;
        }
        if ($h < 0xC2) {
            return false;
        }
        if ($h <= 0xDF) {
            return ($h & 0x1F) << 6
                | (ord($c[1]) & 0x3F);
        }
        if ($h <= 0xEF) {
            return ($h & 0x0F) << 12
                | (ord($c[1]) & 0x3F) << 6
                | (ord($c[2]) & 0x3F);
        }
        if ($h <= 0xF4) {
            return ($h & 0x0F) << 18
                | (ord($c[1]) & 0x3F) << 12
                | (ord($c[2]) & 0x3F) << 6
                | (ord($c[3]) & 0x3F);
        }
        return false;
    }

    protected static function getEntitiesTable()
    {
        // the UTF-8 table is only available as of PHP 5.3.4
        if (version_compare(PHP_VERSION, '5.3.4', '>=')) {
            return get_html_translation_table(HTML_ENTITIES, ENT_QUOTES, 'UTF-8');
        } else {
            return array (
                ' ' => '&nbsp;',
                '¡' => '&iexcl;',
                '¢' => '&cent;',
                '£' => '&pound;',
                '¤' => '&curren;',
                '¥' => '&yen;',
                '¦' => '&brvbar;',
                '§' => '&sect;',
                '¨' => '&uml;',
                '©' => '&copy;',
                'ª' => '&ordf;',
                '«' => '&laquo;',
                '¬' => '&not;',
                '­' => '&shy;',
                '®' => '&reg;',
                '¯' => '&macr;',
                '°' => '&deg;',
                '±' => '&plusmn;',
                '²' => '&sup2;',
                '³' => '&sup3;',
                '´' => '&acute;',
                'µ' => '&micro;',
                '¶' => '&para;',
                '·' => '&middot;',
                '¸' => '&cedil;',
                '¹' => '&sup1;',
                'º' => '&ordm;',
                '»' => '&raquo;',
                '¼' => '&frac14;',
                '½' => '&frac12;',
                '¾' => '&frac34;',
                '¿' => '&iquest;',
                'À' => '&Agrave;',
                'Á' => '&Aacute;',
                'Â' => '&Acirc;',
                'Ã' => '&Atilde;',
                'Ä' => '&Auml;',
                'Å' => '&Aring;',
                'Æ' => '&AElig;',
                'Ç' => '&Ccedil;',
                'È' => '&Egrave;',
                'É' => '&Eacute;',
                'Ê' => '&Ecirc;',
                'Ë' => '&Euml;',
                'Ì' => '&Igrave;',
                'Í' => '&Iacute;',
                'Î' => '&Icirc;',
                'Ï' => '&Iuml;',
                'Ð' => '&ETH;',
                'Ñ' => '&Ntilde;',
                'Ò' => '&Ograve;',
                'Ó' => '&Oacute;',
                'Ô' => '&Ocirc;',
                'Õ' => '&Otilde;',
                'Ö' => '&Ouml;',
                '×' => '&times;',
                'Ø' => '&Oslash;',
                'Ù' => '&Ugrave;',
                'Ú' => '&Uacute;',
                'Û' => '&Ucirc;',
                'Ü' => '&Uuml;',
                'Ý' => '&Yacute;',
                'Þ' => '&THORN;',
                'ß' => '&szlig;',
                'à' => '&agrave;',
                'á' => '&aacute;',
                'â' => '&acirc;',
                'ã' => '&atilde;',
                'ä' => '&auml;',
                'å' => '&aring;',
                'æ' => '&aelig;',
                'ç' => '&ccedil;',
                'è' => '&egrave;',
                'é' => '&eacute;',
                'ê' => '&ecirc;',
                'ë' => '&euml;',
                'ì' => '&igrave;',
                'í' => '&iacute;',
                'î' => '&icirc;',
                'ï' => '&iuml;',
                'ð' => '&eth;',
                'ñ' => '&ntilde;',
                'ò' => '&ograve;',
                'ó' => '&oacute;',
                'ô' => '&ocirc;',
                'õ' => '&otilde;',
                'ö' => '&ouml;',
                '÷' => '&divide;',
                'ø' => '&oslash;',
                'ù' => '&ugrave;',
                'ú' => '&uacute;',
                'û' => '&ucirc;',
                'ü' => '&uuml;',
                'ý' => '&yacute;',
                'þ' => '&thorn;',
                'ÿ' => '&yuml;',
                'Œ' => '&OElig;',
                'œ' => '&oelig;',
                'Š' => '&Scaron;',
                'š' => '&scaron;',
                'Ÿ' => '&Yuml;',
                'ƒ' => '&fnof;',
                'ˆ' => '&circ;',
                '˜' => '&tilde;',
                'Α' => '&Alpha;',
                'Β' => '&Beta;',
                'Γ' => '&Gamma;',
                'Δ' => '&Delta;',
                'Ε' => '&Epsilon;',
                'Ζ' => '&Zeta;',
                'Η' => '&Eta;',
                'Θ' => '&Theta;',
                'Ι' => '&Iota;',
                'Κ' => '&Kappa;',
                'Λ' => '&Lambda;',
                'Μ' => '&Mu;',
                'Ν' => '&Nu;',
                'Ξ' => '&Xi;',
                'Ο' => '&Omicron;',
                'Π' => '&Pi;',
                'Ρ' => '&Rho;',
                'Σ' => '&Sigma;',
                'Τ' => '&Tau;',
                'Υ' => '&Upsilon;',
                'Φ' => '&Phi;',
                'Χ' => '&Chi;',
                'Ψ' => '&Psi;',
                'Ω' => '&Omega;',
                'α' => '&alpha;',
                'β' => '&beta;',
                'γ' => '&gamma;',
                'δ' => '&delta;',
                'ε' => '&epsilon;',
                'ζ' => '&zeta;',
                'η' => '&eta;',
                'θ' => '&theta;',
                'ι' => '&iota;',
                'κ' => '&kappa;',
                'λ' => '&lambda;',
                'μ' => '&mu;',
                'ν' => '&nu;',
                'ξ' => '&xi;',
                'ο' => '&omicron;',
                'π' => '&pi;',
                'ρ' => '&rho;',
                'ς' => '&sigmaf;',
                'σ' => '&sigma;',
                'τ' => '&tau;',
                'υ' => '&upsilon;',
                'φ' => '&phi;',
                'χ' => '&chi;',
                'ψ' => '&psi;',
                'ω' => '&omega;',
                'ϑ' => '&thetasym;',
                'ϒ' => '&upsih;',
                'ϖ' => '&piv;',
                ' ' => '&ensp;',
                ' ' => '&emsp;',
                ' ' => '&thinsp;',
                '‌' => '&zwnj;',
                '‍' => '&zwj;',
                '‎' => '&lrm;',
                '‏' => '&rlm;',
                '–' => '&ndash;',
                '—' => '&mdash;',
                '‘' => '&lsquo;',
                '’' => '&rsquo;',
                '‚' => '&sbquo;',
                '“' => '&ldquo;',
                '”' => '&rdquo;',
                '„' => '&bdquo;',
                '†' => '&dagger;',
                '‡' => '&Dagger;',
                '•' => '&bull;',
                '…' => '&hellip;',
                '‰' => '&permil;',
                '′' => '&prime;',
                '″' => '&Prime;',
                '‹' => '&lsaquo;',
                '›' => '&rsaquo;',
                '‾' => '&oline;',
                '⁄' => '&frasl;',
                '€' => '&euro;',
                'ℑ' => '&image;',
                '℘' => '&weierp;',
                'ℜ' => '&real;',
                '™' => '&trade;',
                'ℵ' => '&alefsym;',
                '←' => '&larr;',
                '↑' => '&uarr;',
                '→' => '&rarr;',
                '↓' => '&darr;',
                '↔' => '&harr;',
                '↵' => '&crarr;',
                '⇐' => '&lArr;',
                '⇑' => '&uArr;',
                '⇒' => '&rArr;',
                '⇓' => '&dArr;',
                '⇔' => '&hArr;',
                '∀' => '&forall;',
                '∂' => '&part;',
                '∃' => '&exist;',
                '∅' => '&empty;',
                '∇' => '&nabla;',
                '∈' => '&isin;',
                '∉' => '&notin;',
                '∋' => '&ni;',
                '∏' => '&prod;',
                '∑' => '&sum;',
                '−' => '&minus;',
                '∗' => '&lowast;',
                '√' => '&radic;',
                '∝' => '&prop;',
                '∞' => '&infin;',
                '∠' => '&ang;',
                '∧' => '&and;',
                '∨' => '&or;',
                '∩' => '&cap;',
                '∪' => '&cup;',
                '∫' => '&int;',
                '∴' => '&there4;',
                '∼' => '&sim;',
                '≅' => '&cong;',
                '≈' => '&asymp;',
                '≠' => '&ne;',
                '≡' => '&equiv;',
                '≤' => '&le;',
                '≥' => '&ge;',
                '⊂' => '&sub;',
                '⊃' => '&sup;',
                '⊄' => '&nsub;',
                '⊆' => '&sube;',
                '⊇' => '&supe;',
                '⊕' => '&oplus;',
                '⊗' => '&otimes;',
                '⊥' => '&perp;',
                '⋅' => '&sdot;',
                '⌈' => '&lceil;',
                '⌉' => '&rceil;',
                '⌊' => '&lfloor;',
                '⌋' => '&rfloor;',
                '〈' => '&lang;',
                '〉' => '&rang;',
                '◊' => '&loz;',
                '♠' => '&spades;',
                '♣' => '&clubs;',
                '♥' => '&hearts;',
                '♦' => '&diams;',
                '&' => '&amp;',
                '"' => '&quot;',
                '\'' => '&#039;',
                '<' => '&lt;',
                '>' => '&gt;',
            );
        }
    }
}

class Plaintext extends Token {}

class Singlequote extends Token
{
    protected function __construct($text)
    {
        $set = Set::getInstance();
        $prev = $set->getPrev();
        if (
            false === $prev
            || self::isWhitespace($prev)
            || self::isWhitespace(substr($prev, -1))
        ) {
            $this->translatedText = '&#8216;';
        } else {
            $this->translatedText = '&#8217;';
        }
        parent::__construct($text);
    }
}
class Ellipsis extends Token
{
    protected $translatedText = '&#8230;';
}
class Emdash extends Token
{
    protected $translatedText = '&#8201;&#8212;&#8201;';
}
class Quote extends Token
{
    protected function __construct($text)
    {
        $this->text = $text;
        $set = Set::getInstance();
        if ($set->withinQuotation) {
            $set->withinQuotation = false;
            $this->translatedText = '&#8221;';
        } else {
            $set->withinQuotation = true;
            $this->translatedText = '&#8220;';
        }
    }
}
abstract class Tag extends Token
{
    protected static function createSubType($text)
    {
        if (substr($text, 0, 2) == '</') {
            return new CloseTag($text);
        } else if (substr($text, -2) == '/>') {
            return new SelfClosingTag($text);
        } else {
            return new OpenTag($text);
        }
    }
    
    protected static function tagToText($tag)
    {
        // cheat!
        return preg_replace('!</?(\w+).*!', '\1', $tag);
    }
}
class OpenTag extends Tag
{
    const SPLIT = '/([\'" =])/';

    // these are just named so we can var_dump($this->context) for debugging
    const TYPE_TAG = 'tag';
    const TYPE_ATTRIBUTE = 'attr';
    const TYPE_EQUALS = 'eq';
    const TYPE_VALUE = 'val';
    const TYPE_QUOTE = 'quote';
    const TYPE_SPACE = 'space';
    
    const CONTEXT_BEGIN = 0;
    const CONTEXT_NONE = 1;
    const CONTEXT_ATTRIBUTE = 2;
    
    protected $capture = array();
    
    protected function __construct($text)
    {
        $this->text = $text;
        $set = Set::getInstance();
        $set->pushTagContext(self::tagToText($text));
        $this->calculateAttributes();
    }
    
    protected function calculateAttributes()
    {
        $context = self::CONTEXT_BEGIN;
        $tokens = preg_split(self::SPLIT, trim($this->text, '<>'), -1, PREG_SPLIT_DELIM_CAPTURE);
        $tokens = array_values(array_filter($tokens)); // drop actually empty values
        $attributeQuote = null;
        for ($i=0, $end=count($tokens); $i<$end; $i++) {
            switch ($context) {
                case self::CONTEXT_BEGIN:
                    $this->capture[] = array(
                        'type' => self::TYPE_TAG,
                        'value' => $tokens[$i],
                    );
                    $context = self::CONTEXT_NONE;
                    break;

                case self::CONTEXT_ATTRIBUTE:
                    // $attributeQuote = null;
                    while ($i<$end && $tokens[$i] != $attributeQuote) {
                        $this->capture[] = array(
                            'type' => self::TYPE_VALUE,
                            'value' => $tokens[$i++],
                        );
                    }
                    $this->capture[] = array(
                        'type' => self::TYPE_QUOTE,
                        'value' => $tokens[$i],
                    );
                    $context = self::CONTEXT_NONE;
                    break;

                case self::CONTEXT_NONE:
                    // whitespace is easy
                    if (self::isWhitespace($tokens[$i])) {
                        $this->capture[] = array(
                            'type' => self::TYPE_SPACE,
                            'value'=> $tokens[$i],
                        );
                        continue;
                    }
                    // otherwise must be an attribute name
                    $this->capture[] = array(
                        'type' => self::TYPE_ATTRIBUTE,
                        'value'=> $tokens[$i],
                    );
                    // there might be whitespace after an attribute name; if so capture it
                    if (isset($tokens[$i+1]) && self::isWhitespace($tokens[$i+1])) {
                        $this->capture[] = array(
                            'type' => self::TYPE_SPACE,
                            'value'=> $tokens[++$i],
                        );
                    }
                    // now check for equals. If there's not one, then just continue; attribute has no value.
                    if (!isset($tokens[$i+1]) || (isset($tokens[$i+1]) && '=' != $tokens[$i+1])) {
                        continue;
                    }
                    $this->capture[] = array(
                        'type' => self::TYPE_EQUALS,
                        'value'=> $tokens[++$i],
                    );
                    // there might be whitespace after an equals sign; if so capture it
                    if (self::isWhitespace($tokens[$i+1])) {
                        $this->capture[] = array(
                            'type' => self::TYPE_SPACE,
                            'value'=> $tokens[++$i],
                        );
                    }
                    // next token should be a quote; capture:
                    // note: it's possible that it's not a quote, but let's hope that no one is pretty-entitying really broken HTML
                    // note2: yeah yeah.. wishful thinking
                    $this->capture[] = array(
                        'type' => self::TYPE_QUOTE,
                        'value'=> $tokens[++$i],
                    );
                    $attributeQuote = $tokens[$i];
                    $context = self::CONTEXT_ATTRIBUTE;
                    break;
            }
        }
    }
    
    public function __toString()
    {
        $set = Set::getInstance();
        if ($set->inTagContext(self::$verbatimTags)) {
            return $this->text;
        } else {
            $str = '<';
            foreach ($this->capture as $token) {
                if (self::TYPE_VALUE == $token['type']) {
                    $str .= self::ampReplace($token['value']);
                } else {
                    $str .= $token['value'];
                }
            }
            return "{$str}>";
        }
    }
}
class CloseTag extends Tag {
    protected function __construct($text)
    {
        $this->text = $text;
        $set = Set::getInstance();
        $set->popTagContext(self::tagToText($text), false);
    }
    public function __toString()
    {
        $set = Set::getInstance();
        if ($set->inTagContext(self::$verbatimTags)) {
            return $this->text;
        } else {
            return $this->translated();
        }
    }
}
class SelfClosingTag extends OpenTag {}

class Set implements \Iterator
{
    protected static $instance = null;

    protected $lexer;
    protected $index = 0;
    protected $tagContext = array();
    public $withinQuotation = false;
    
    public static function getInstance(\LexEntity\Lexer $lexer = null)
    {
        if ($lexer || !self::$instance) {
            self::$instance = new self($lexer);
        }
        return self::$instance;
    }
    
    protected function __construct(\LexEntity\Lexer $lexer)
    {
        $this->Lexer = $lexer;
    }
    
    public function __toString()
    {
        $ret = '';
        foreach ($this as $token) {
            $ret .= (string)Token::create($token);
        }
        return $ret;
    }
    
    public function pushTagContext($tag)
    {
        array_push($this->tagContext, $tag);
    }
    
    /**
     * Pop
     *
     * @param string $tag     tag name
     * @param Bool   $strict  throw an exception if the current context is not the same as $tag
     * @param Bool   $unravel continue popping the tag stack until the proper context is found
     */
    public function popTagContext($tag, $strict = true, $unravel = false)
    {
        $poppedTag = array_pop($this->tagContext);
        if ($strict && $tag != $poppedTag) {
            throw new Exception('closing tag is not last opened tag');
        }
        if ($unravel) {
            while ($tag != $poppedTag && count($this->tagContext)) {
                $poppedTag = array_pop($this->tagContext);
            }
        } else if ($tag != $poppedTag) {
            // push it back on
            array_push($this->tagContext, $poppedTag);
        }
    }
    
    public function inTagContext($tags)
    {
        if (!is_array($tags)) {
            return in_array($tags, $this->tagContext);
        }
        foreach ($tags as $tag) {
            if (in_array($tag, $this->tagContext)) {
                return true;
            }
        }
        return false;
    }

    public function getPrev()
    {
        if ($this->index > 0) {
            return $this->Lexer->chunks[$this->index - 1];
        } else {
            return false;
        }
    }
    
    //// ITERATOR:
    
    public function current()
    {
        return $this->Lexer->chunks[$this->index];
    }
    public function next()
    {
        ++$this->index;
    }
    public function key()
    {
        return $this->index;
    }
    public function valid()
    {
        return $this->index >= 0 && $this->index < count($this->Lexer->chunks);
    }
    public function rewind()
    {
        $this->tagContext = array();
        $this->inQuote = false;
        return $this->index = 0;
    }
}
