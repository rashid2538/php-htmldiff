<?php

namespace HtmlDiff;

class Utils
{
    private static $_openingTagRegex = "/^\\s*<[^>]+>\\s*$/";
    private static $_closingTagTexRegex = "/^\\s*<\\/[^>]+>\\s*$/";
    private static $_tagWordRegex = "/<[^\s>]+$/";
    private static $_whitespaceRegex = "/^(\\s|&nbsp;)+$/";
    private static $_wordRegex = "/^[\w\#@]+$/";

    private static $_specialCaseWordTags = ["<img"];

    public static function isTag($item)
    {

        $result = array_filter(self::$_specialCaseWordTags, function ($re) use ($item) {
            !is_null($re) && substr($item, 0, strlen($re)) == $re;
        });
        if (count($result) > 0) {
            return false;
        }

        return self::isOpeningTag($item) || self::isClosingTag($item);
    }

    private static function isOpeningTag($item)
    {
        return preg_match(self::$_openingTagRegex, $item) > 0;
    }

    private static function isClosingTag($item)
    {
        return preg_match(self::$_closingTagTexRegex, $item) > 0;
    }

    public static function stripTagAttributes($word)
    {
        $tag = preg_match(self::$_tagWordRegex, $word, $tag);
        $word = $tag . (substr($word, -2) == '/>' ? "/>" : ">");
        return $word;
    }

    public static function wrapText($text, $tagName, $cssClass)
    {
        return "<{$tagName} class=\"{$cssClass}\">{$text}</{$tagName}>";
    }

    public static function isStartOfTag($val)
    {
        return $val == '<';
    }

    public static function isEndOfTag($val)
    {
        return $val == '>';
    }

    public static function isStartOfEntity($val)
    {
        return $val == '&';
    }

    public static function isEndOfEntity($val)
    {
        return $val == ';';
    }

    public static function isWhiteSpace($value)
    {
        return preg_match(self::$_whitespaceRegex, $value) > 0;
    }

    public static function stripAnyAttributes($word)
    {
        if (self::isTag($word)) {
            return self::stripTagAttributes($word);
        }
        return $word;
    }

    public static function isWord($text)
    {
        return preg_match(self::$_wordRegex, $text) > 0;
    }
}
