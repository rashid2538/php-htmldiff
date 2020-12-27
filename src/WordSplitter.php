<?php

namespace HtmlDiff;

class WordSplitter
{

    public static function convertHtmlToListOfWords($html, $blockExpressions)
    {
        $mode = Mode::Character;
        $currentWord = [];
        $words = [];
        $blockLocations = self::findBlocks($html, $blockExpressions);
        $isBlockCheckRequired = !empty($blockLocations);
        $isGrouping = false;
        $groupingUntil = -1;

        for ($index = 0; $index < \strlen($html); $index++) {
            $character = substr($html, $index, 1);
            if ($isBlockCheckRequired) {
                if ($groupingUntil == $index) {
                    $groupingUntil = -1;
                    $isGrouping = false;
                }
                if (isset($blockLocations[$index])) {
                    $isGrouping = true;
                    $groupingUntil = $blockLocations[$index];
                }
                if ($isGrouping) {
                    $currentWord[] = $character;
                    $mode = Mode::Character;
                    continue;
                }
            }
            switch ($mode) {
                case Mode::Character:
                    if (Utils::isStartOfTag($character)) {
                        if (count($currentWord) != 0) {
                            $words[] = implode('', $currentWord);
                        }
                        $currentWord = ['<'];
                        $mode = Mode::Tag;
                    } else if (Utils::isStartOfEntity($character)) {
                        if (count($currentWord) != 0) {
                            $words[] = implode('', $currentWord);
                        }
                        $currentWord = [$character];
                        $mode = Mode::Entity;
                    } else if (Utils::isWhiteSpace($character)) {
                        if (count($currentWord) != 0) {
                            $words[] = implode('', $currentWord);
                        }
                        $currentWord = [$character];
                        $mode = Mode::Whitespace;
                    } else if (Utils::isWord($character) && (count($currentWord) == 0 || Utils::isWord($currentWord[count($currentWord) - 1]))) {
                        $currentWord[] = $character;
                    } else {
                        if (count($currentWord) != 0) {
                            $words[] = implode('', $currentWord);
                        }
                        $currentWord = [$character];
                    }
                    break;
                case Mode::Tag:
                    if (Utils::isEndOfTag($character)) {
                        $currentWord[] = $character;
                        $words[] = implode('', $currentWord);
                        $currentWord = [];
                        $mode = Utils::isWhiteSpace($character) ? Mode::Whitespace : Mode::Character;
                    } else {
                        $currentWord[] = $character;
                    }
                    break;
                case Mode::Whitespace:
                    if (Utils::isStartOfTag($character)) {
                        if (count($currentWord) != 0) {
                            $words[] = implode('', $currentWord);
                        }
                        $currentWord = [$character];
                        $mode = Mode::Tag;
                    } else if (Utils::isStartOfEntity($character)) {
                        if (count($currentWord) != 0) {
                            $words[] = implode('', $currentWord);
                        }
                        $currentWord = [$character];
                        $mode = Mode::Entity;
                    } else if (Utils::isWhiteSpace($character)) {
                        $currentWord[] = $character;
                    } else {
                        if (count($currentWord) != 0) {
                            $words[] = implode('', $currentWord);
                        }
                        $currentWord = [$character];
                        $mode = Mode::Character;
                    }
                    break;
                case Mode::Entity:
                    if (Utils::isStartOfTag($character)) {
                        if (count($currentWord) != 0) {
                            $words[] = implode('', $currentWord);
                        }
                        $currentWord = [$character];
                        $mode = Mode::Tag;
                    } else if (Utils::isWhiteSpace($character)) {
                        if (count($currentWord) != 0) {
                            $words[] = implode('', $currentWord);
                        }
                        $currentWord = [$character];
                        $mode = Mode::Whitespace;
                    } else if (Utils::isEndOfEntity($character)) {
                        $switchToNextMode = true;
                        if (count($currentWord) != 0) {
                            $currentWord[] = $character;
                            $words[] = implode('', $currentWord);
                            if (count($words) > 2 && Utils::isWhiteSpace($words[\count($words) - 2]) && Utils::isWhiteSpace($words[\count($words) - 1])) {
                                $w1 = $words[\count($words) - 2];
                                $w2 = $words[\count($words) - 1];
                                unset($words[\count($words) - 1]);
                                unset($words[\count($words) - 1]);
                                $currentWord = str_split($w1);
                                $currentWord = array_merge($currentWord, str_split($w2));
                                $mode = Mode::Whitespace;
                                $switchToNextMode = false;
                            }
                        }
                        if ($switchToNextMode) {
                            $currentWord = [];
                            $mode = Mode::Character;
                        }
                    } else if (Utils::isWord($character)) {
                        $currentWord[] = $character;
                    } else {
                        if (count($currentWord) != 0) {
                            $words[] = implode('', $currentWord);
                        }
                        $currentWord = [$character];
                        $mode = Mode::Character;
                    }
                    break;
            }
        }
        if (count($currentWord) != 0) {
            $words[] = implode('', $currentWord);
        }

        return $words;
    }

    private static function findBlocks($html, $blockExpressions)
    {
        $blockLocations = [];
        if (is_null($blockExpressions)) {
            return $blockLocations;
        }
        foreach ($blockExpressions as $exp) {
            if (preg_match_all($exp, $html, $matches)) {
                $processedMatches = [];
                foreach ($matches as $match) {
                    $matchIndex = strpos($html, $match[0], isset($processedMatches[$matches[0]]) ? $processedMatches[$matches[0]] + 1 : 0);
                    $processedMatches[$matches[0]] = $matchIndex;
                    if (isset($blockLocations[$matchIndex])) {
                        throw new \Exception("One of more block expressions result in a text sequence that overlaps. Current expression: {$exp}");
                    }
                    $blockLocations[$matchIndex] = $matchIndex + strlen($match[0]);
                }
            }
        }
        return $blockLocations;
    }
}
