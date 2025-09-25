<?php

namespace Sentiment\Procedures;

/*
    Identify sentiment-relevant string-level properties of input text.
*/

class SentiText
{
    public $words_and_emoticons;
    public $is_cap_diff;

    public const PUNC_LIST = ['.', '!', '?', ',', ';', ':', '-', "'", '"',
             '!!', '!!!', '??', '???', '?!?', '!?!', '?!?!', '!?!?'];

    public function __construct(private $text)
    {
        $this->words_and_emoticons = $this->_words_and_emoticons();
        // doesn't separate words from\
        // adjacent punctuation (keeps emoticons & contractions)
        $this->is_cap_diff = $this->allcap_differential($this->words_and_emoticons);
    }

    /*
        Remove all punctation from a string
    */
    public function strip_punctuation($string): string|array|null
    {
        //$string = strtolower($string);
        return preg_replace('/[[:punct:]]+/', '', (string) $string);
    }

    public function array_count_values_of($haystack, $needle): int
    {
        if (!in_array($needle, $haystack, true)) {
            return 0;
        }
        $counts = array_count_values($haystack);

        return $counts[$needle];
    }

    /*
        Check whether just some words in the input are ALL CAPS

        :param list words: The words to inspect
        :returns: `True` if some but not all items in `words` are ALL CAPS
    */
    private function allcap_differential($words)
    {
        $is_different = false;
        $allcap_words = 0;
        foreach ($words as $word) {
            //ctype is affected by the local of the processor see manual for more details
            if (ctype_upper((string) $word)) {
                $allcap_words += 1;
            }
        }
        $cap_differential = count($words) - $allcap_words;
        if ($cap_differential > 0 && $cap_differential < count($words)) {
            return true;
        }

        return $is_different;
    }

    public function _words_only()
    {
        $text_mod = $this->strip_punctuation($this->text);
        // removes punctuation (but loses emoticons & contractions)
        $words_only = preg_split('/\s+/', (string) $text_mod);
        // get rid of empty items or single letter "words" like 'a' and 'I'
        array_filter($words_only, fn ($word): bool => strlen($word) > 1);

        return $words_only;
    }

    public function _words_and_emoticons()
    {
        $wes = preg_split('/\s+/', (string) $this->text);

        // get rid of residual empty items or single letter words
        $wes = array_filter($wes, fn ($word): bool => strlen($word) > 1);
        //Need to remap the indexes of the array
        $wes = array_values($wes);
        $words_only = $this->_words_only();

        foreach ($words_only as $word_only) {
            foreach (self::PUNC_LIST as $punct) {
                //replace all punct + word combinations with word
                $pword = $punct.$word_only;

                $x1 = $this->array_count_values_of($wes, $pword);
                while ($x1 > 0) {
                    $i = array_search($pword, $wes, true);
                    unset($wes[$i]);
                    array_splice($wes, $i, 0, $word_only);
                    $x1 = $this->array_count_values_of($wes, $pword);
                }
                //Do the same as above but word then punct
                $wordp = $word_only.$punct;
                $x2 = $this->array_count_values_of($wes, $wordp);
                while ($x2 > 0) {
                    $i = array_search($wordp, $wes, true);
                    unset($wes[$i]);
                    array_splice($wes, $i, 0, $word_only);
                    $x2 = $this->array_count_values_of($wes, $wordp);
                }
            }
        }

        return $wes;
    }
}
