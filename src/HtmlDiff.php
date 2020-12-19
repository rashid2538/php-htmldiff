<?php

namespace HtmlDiff;

class HtmlDiff
{

    const MatchGranularityMaximum = 4;

    private $_content = '';
    private $_newHtml = '';
    private $_oldHtml = '';

    private $_specialCaseClosingTags = [
        "</strong>" => 0,
        "</em>" => 0,
        "</b>" => 0,
        "</i>" => 0,
        "</big>" => 0,
        "</small>" => 0,
        "</u>" => 0,
        "</sub>" => 0,
        "</sup>" => 0,
        "</strike>" => 0,
        "</s>" => 0,
    ];

    private $_specialCaseOpeningTagRegex = '/^<((strong)|(b)|(i)|(em)|(big)|(small)|(u)|(sub)|(sup)|(strike)|(s))[\\>\\s]+$/i';

    private $_specialTagDiffStack = [];

    private $_newWords = [];
    private $_oldWords = [];
    private $_matchGranularity;
    private $_blockExpressions = [];

    public $repeatingWordsAccuracy = 1;
    public $ignoreWhitespaceDifferences;
    public $orphanMatchThreshold;

    public function __construct($oldText, $newText)
    {
        $this->_oldHtml = $oldText;
        $this->_newHtml = $newText;
    }

    public static function execute($oldText, $newText)
    {
        return (new self($oldText, $newText))->getDifference();
    }

    public function getOldHtml()
    {
        return $this->_oldHtml;
    }

    public function getNewHtml()
    {
        return $this->_newHtml;
    }

    public function addBlockExpression($regEx) {
        $this->_blockExpressions[] = $regEx;
    }

    public function splitInputsToWords()
    {
        $this->_oldWords = WordSplitter::convertHtmlToListOfWords($this->_oldHtml, $this->_blockExpressions);

        $this->_newWords = WordSplitter::convertHtmlToListOfWords($this->_newHtml, $this->_blockExpressions);
    }

    private function performOperation($operation)
    {
        switch ($operation->action) {
            case Action::Equal:
                $this->processEqualOperation($operation);
                break;
            case Action::Delete:
                $this->processDeleteOperation($operation, 'diffdel');
                break;
            case Action::Insert:
                $this->processInsertOperation($operation, 'diffins');
                break;
            case Action::Replace:
                $this->processReplaceOperation($operation);
                break;
        }
    }

    private function processReplaceOperation($operation)
    {
        $this->processDeleteOperation($operation, 'diffmod');
        $this->processInsertOperation($operation, 'diffmod');
    }

    private function processInsertOperation($operation, $cssClass)
    {
        $text = [];
        for ($i = 0; $i < count($this->_newWords); $i++) {
            if ($i >= $operation->startInOld && $i < $operation->endInOld) {
                $text[] = $this->_newWords[$i];
            }
        }
        $this->insertTag('ins', $cssClass, $text);
    }

    private function processDeleteOperation($operation, $cssClass)
    {
        $text = [];
        for ($i = 0; $i < count($this->_newWords); $i++) {
            if ($i >= $operation->startInOld && $i < $operation->endInOld) {
                $text[] = $this->_newWords[$i];
            }
        }
        $this->insertTag('del', $cssClass, $text);
    }

    private function processEqualOperation($operation)
    {
        $text = [];
        for ($i = 0; $i < count($this->_newWords); $i++) {
            if ($i >= $operation->startInNew && $i < $operation->endInNew) {
                $text[] = $this->_newWords[$i];
            }
        }
        $this->_content .= implode('', $text);
    }

    private function insertTag($tag, $cssClass, $words)
    {
        while (true) {
            if (count($words) == 0) {
                break;
            }
            $nonTags = $this->extractConsecutiveWords($words, function ($x) {
                return !Utils::isTag($x);
            });
            $specialCaseTagInjection = '';
            $specialCaseTagInjectionIsBefore = false;

            if (count($nonTags) != 0) {
                $this->_content .= Utils::wrapText(implode('', $nonTags), $tag, $cssClass);
            } else {
                if (preg_match($this->_specialCaseOpeningTagRegex, $words[0]) > 0) {
                    $this->_specialTagDiffStack[] = $words[0];
                    $specialCaseTagInjection = '<ins class="mod">';
                    if ($tag == 'del') {
                        array_splice($words, 0, 1);
                    }

                    while (count(words) > 0 && preg_match($this->_specialCaseOpeningTagRegex, $words[0]) > 0) {
                        array_splice($words, 0, 1);
                    }
                } else if (isset($this->_specialCaseClosingTags[$words[0]])) {
                    $openingTag = count($this->_specialTagDiffStack) > 0 ? null : array_pop($this->_specialTagDiffStack);
                    if (is_null($openingTag) || $openingTag != \str_replace('/', '', $words[count($words) - 1])) {
                        // do nothing
                    } else {
                        $specialCaseTagInjection = '</ins>';
                        $specialCaseTagInjectionIsBefore = true;
                    }

                    if ($tag = 'del') {
                        array_splice($words, 0, 1);
                        while (count($words) > 0 && isset($this->_specialCaseClosingTags[$words[0]])) {
                            array_splice($words, 0, 1);
                        }
                    }
                }
            }

            if (count($words) == 0 && count($specialCaseTagInjection) == 0) {
                break;
            }

            if ($specialCaseTagInjectionIsBefore) {
                $this->_content .= $specialCaseTagInjection . implode('', $this->extractConsecutiveWords($words, ['HtmlDiff\\Utils', 'isTag']));
            } else {
                $this->_content .= implode('', $this->extractConsecutiveWords($words, ['HtmlDiff\\Utils', 'isTag']));
            }
        }
    }

    private function extractConsecutiveWords($words, $filter)
    {
        $indexOfFirstTag = null;
        for ($i = 0; $i < count($words); $i++) {
            $word = $words[$i];
            if ($i == 0 && $word == ' ') {
                $words[$i] = '&nbsp;';
            }
            if (!$filter($word)) {
                $indexOfFirstTag = $i;
                break;
            }
        }
        if (!is_null($indexOfFirstTag)) {
            $text = [];
            for ($i = 0; $i < count($words); $i++) {
                if ($i >= 0 && $i < $indexOfFirstTag) {
                    $text[] = $words[$i];
                }
            }
            if ($indexOfFirstTag > 0) {
                array_splice($words, 0, $indexOfFirstTag);
            }
            return $text;
        } else {
            // Cross check
            return $words;
            /* $text = [];
        for($i = 0; $i < count($words);$i++) {
        if( $i >= 0 && $i <= $words.Count) {
        $text[] = $words[$i];
        }
        } */
        }
    }

    private function operations()
    {
        $positionInOld = 0;
        $positionInNew = 0;
        $operations = [];

        $matches = $this->matchingBlocks();
        $matches[] = new Match(count($this->_oldWords), count($this->_newWords), 0);

        $matchesWithoutOrphans = $this->removeOrphans($matches);

        foreach ($matchesWithoutOrphans as $match) {
            $matchStartsAtCurrentPositionInOld = ($positionInOld == $match->startInOld);
            $matchStartsAtCurrentPositionInNew = ($positionInNew == $match->startInNew);

            $action = null;
            if ($matchStartsAtCurrentPositionInOld == false
                && $matchStartsAtCurrentPositionInNew == false) {
                $action = Action::Replace;
            } else if ($matchStartsAtCurrentPositionInOld
                && $matchStartsAtCurrentPositionInNew == false) {
                $action = Action::Insert;
            } else if ($matchStartsAtCurrentPositionInOld == false) {
                $action = Action::Delete;
            } else // This occurs if the first few words are the same in both versions
            {
                $action = Action::None;
            }

            if ($action != Action::None) {
                $operations[] = new Operation($action,
                    $positionInOld,
                    $match->startInOld,
                    $positionInNew,
                    $match->startInNew);
            }

            if ($match->size != 0) {
                $operations[] = new Operation(
                    Action::Equal,
                    $match->startInOld,
                    $match->endInOld,
                    $match->startInNew,
                    $match->endInNew);
            }

            $positionInOld = $match->endInOld;
            $positionInNew = $match->endInNew;
        }

        return $operations;
    }

    private function removeOrphans($matches)
    {
        $prev = null;
        $curr = null;
        foreach ($matches as $next) {
            if ($curr == null) {
                $prev = new Match(0, 0, 0);
                $curr = $next;
                continue;
            }
            if ($prev->endInOld == $curr->startInOld && $prev->endInNew == $curr->startInNew || $curr->endInOld == $next->startInOld && $curr->endInNew == $next->startInNew) {
                yield $curr;
                $prev = $curr;
                $curr = $next;
                continue;
            }

            $oldDistanceInChars = array_reduce(array_map(function ($i) {
                return strlen($this->_oldWords[$i]);
            }, range($prev->endInOld, $next->startInOld - $prev->endInOld)), function ($carry, $item) {
                $carry += $item;
                return $carry;
            });
            $newDistanceInChars = array_reduce(array_map(function ($i) {
                return strlen($this->_newWords[$i]);
            }, range($prev->endInNew, $next->startInNew - $prev->endInNew)), function ($carry, $item) {
                $carry += $item;
                return $carry;
            });
            $currMatchLengthInChars = array_reduce(array_map(function ($i) {
                return strlen($this->_newWords[$i]);
            }, range($curr->startInNew, $curr->endInNew - $curr->startInNew)), function ($carry, $item) {
                $carry += $item;
                return $carry;
            });
            if ($currMatchLengthInChars > max($oldDistanceInChars, $newDistanceInChars) * $orphanMatchThreshold) {
                yield $curr;
            }
            $prev = $curr;
            $curr = $next;
        }

        yield $curr;
    }

    private function matchingBlocks()
    {
        return $this->findMatchingBlocks(0, count($this->_oldWords), 0, count($this->_newWords));
    }

    private function findMatchingBlocks($startInOld, $endInOld, $startInNew, $endInNew)
    {
        $matchingBlocks = [];
        $match = $this->findMatch($startInOld, $endInOld, $startInNew, $endInNew);
        if ($match != null) {
            if ($startInOld < $match->startInOld && $startInNew < $match->startInNew) {
                $matchingBlocks = array_merge($matchingBlocks, FindMatchingBlocks($startInOld, $match->startInOld, $startInNew, $match->startInNew));
            }

            $matchingBlocks[] = $match;

            if ($match->endInOld < $endInOld && $match->endInNew < $endInNew) {
                $matchingBlocks = array_merge($matchingBlocks, FindMatchingBlocks($match->endInOld, $endInOld, $match->endInNew, $endInNew));
            }
        }
        return $matchingBlocks;
    }

    private function findMatch($startInOld, $endInOld, $startInNew, $endInNew)
    {
        for ($i = $this->_matchGranularity; $i > 0; $i--) {
            $match = (new MatchFinder($this->_oldWords, $this->_newWords, $startInOld, $endInOld, $startInNew, $endInNew, new MatchOptions($i, $this->repeatingWordsAccuracy, $this->ignoreWhitespaceDifferences)))->findMatch();
            if (!is_null($match)) {
                return $match;
            }
        }
        return null;
    }

    public function getDifference()
    {
        if ($this->_oldHtml == $this->_newHtml) {
            return $this->_newHtml;
        }

        $this->splitInputsToWords();
        $this->_matchGranularity = min(self::MatchGranularityMaximum, min(count($this->_oldWords), count($this->_newWords)));

        foreach ($this->operations() as $operation) {
            $this->performOperation($operation);
        }

        return $this->_content;
    }
}
