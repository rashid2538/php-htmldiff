<?php

namespace HtmlDiff;

class MatchFinder
{

    private $_oldWords;
    private $_newWords;
    private $_startInOld;
    private $_endInOld;
    private $_startInNew;
    private $_endInNew;
    private $_wordIndices;
    private $_options;

    public function __construct($oldWords, $newWords, $startInOld, $endInOld, $startInNew, $endInNew, $options)
    {
        $this->_oldWords = $oldWords;
        $this->_newWords = $newWords;
        $this->_startInOld = $startInOld;
        $this->_endInOld = $endInOld;
        $this->_startInNew = $startInNew;
        $this->_endInNew = $endInNew;
        $this->_options = $options;
    }

    private function indexNewWords()
    {
        $this->_wordIndices = [];
        $block = [];
        for ($i = $this->_startInNew; $i < $this->_endInNew; $i++) {
            $word = $this->normalizeForIndex($this->_newWords[$i]);
            $key = $this->putNewWord($block, $word, $this->_options->blockSize);

            if (is_null($key)) {
                continue;
            }

            if (isset($this->_wordIndices[$key])) {
                $this->_wordIndices[$key][] = $i;
            } else {
                $this->_wordIndices[$key] = [$i];
            }
        }
    }

    private function putNewWord(&$block, $word, $blockSize)
    {
        array_unshift($block, $word);
        if (count($block) > $blockSize) {
            array_splice($block, 0, 1);
        }
        if (count($block) != $blockSize) {
            return null;
        }

        $result = '';
        foreach ($block as $s) {
            $result .= $s;
        }
        return $result;
    }

    private function normalizeForIndex($word)
    {
        $word = Utils::stripAnyAttributes($word);
        if ($this->_options->ignoreWhitespaceDifferences && Utils::isWhiteSpace($word)) {
            return ' ';
        }
        return $word;
    }

    private function removeRepeatingWords()
    {
        $threshold = count($this->_newWords) * $this->_options->repeatingWordsAccuracy;
        $repeatingWords = [];
        foreach ($this->_wordIndices as $key => $words) {
            if (count($words) > $threshold) {
                unset($this->_wordIndices[$key]);
            }
        }
    }

    public function findMatch()
    {
        $this->indexNewWords();
        $this->removeRepeatingWords();

        if (empty($this->_wordIndices)) {
            return null;
        }

        $bestMatchInOld = $this->_startInOld;
        $bestMatchInNew = $this->_startInNew;
        $bestMatchSize = 0;

        $matchLengthAt = [];
        $block = [];

        for ($indexInOld = $this->_startInOld; $indexInOld < $this->_endInOld; $indexInOld++) {
            $word = $this->normalizeForIndex($this->_oldWords[$indexInOld]);
            $index = $this->putNewWord($block, $word, $this->_options->blockSize);

            if (is_null($index)) {
                continue;
            }

            $newMatchLengthAt = [];

            if (!isset($this->_wordIndices[$index])) {
                $matchLengthAt = $newMatchLengthAt;
                continue;
            }

            foreach ($this->_wordIndices[$index] as $indexInNew) {
                $newMatchLength = (isset($matchLengthAt[$indexInNew - 1]) ? $matchLengthAt[$indexInNew - 1] : 0) + 1;
                $newMatchLengthAt[$indexInNew] = $newMatchLength;

                if ($newMatchLength > $bestMatchSize) {
                    $bestMatchInOld = $indexInOld - $newMatchLength + 2 - $this->_options->blockSize;
                    $bestMatchInNew = $indexInNew - $newMatchLength + 2 - $this->_options->blockSize;
                    $bestMatchSize = $newMatchLength;
                }
            }
            $matchLengthAt = $newMatchLengthAt;
        }
        return $bestMatchSize != 0 ? new Match($bestMatchInOld, $bestMatchInNew, $bestMatchSize + $this->_options->blockSize - 1) : null;
    }
}
