<?php

use Sentiment\Procedures\SentiText;

describe('SentiText', function () {
    it('can be instantiated with text', function () {
        $sentiText = new SentiText('Hello world');
        expect($sentiText)->toBeInstanceOf(SentiText::class);
    });

    it('extracts words and emoticons correctly', function () {
        $sentiText = new SentiText('Hello world :) How are you?');
        expect($sentiText->words_and_emoticons)->toBeArray();
        expect($sentiText->words_and_emoticons)->toContain('Hello');
        expect($sentiText->words_and_emoticons)->toContain('world');
        expect($sentiText->words_and_emoticons)->toContain(':)');
    });

    it('strips punctuation correctly', function () {
        $sentiText = new SentiText('test');

        $result = $sentiText->strip_punctuation('Hello, world! How are you?');
        expect($result)->toBe('Hello world How are you');

        $result = $sentiText->strip_punctuation('Test... with... dots...');
        expect($result)->toBe('Test with dots');

        $result = $sentiText->strip_punctuation('No punctuation here');
        expect($result)->toBe('No punctuation here');
    });

    it('counts array values correctly', function () {
        $sentiText = new SentiText('test');

        $haystack = ['apple', 'banana', 'apple', 'cherry', 'apple'];

        expect($sentiText->array_count_values_of($haystack, 'apple'))->toBe(3);
        expect($sentiText->array_count_values_of($haystack, 'banana'))->toBe(1);
        expect($sentiText->array_count_values_of($haystack, 'cherry'))->toBe(1);
        expect($sentiText->array_count_values_of($haystack, 'orange'))->toBe(0);
    });

    it('detects capitalization differential', function () {
        // Test through the public property instead of private method

        // All lowercase - no differential
        $sentiText = new SentiText('hello world test');
        expect($sentiText->is_cap_diff)->toBeFalse();

        // All uppercase - no differential
        $sentiText = new SentiText('HELLO WORLD TEST');
        expect($sentiText->is_cap_diff)->toBeFalse();

        // Mixed case - has differential
        $sentiText = new SentiText('HELLO world TEST');
        expect($sentiText->is_cap_diff)->toBeTrue();

        // One uppercase among lowercase - has differential
        $sentiText = new SentiText('hello WORLD test');
        expect($sentiText->is_cap_diff)->toBeTrue();
    });

    it('sets is_cap_diff property correctly', function () {
        // All lowercase
        $sentiText = new SentiText('hello world test');
        expect($sentiText->is_cap_diff)->toBeFalse();

        // All uppercase
        $sentiText = new SentiText('HELLO WORLD TEST');
        expect($sentiText->is_cap_diff)->toBeFalse();

        // Mixed case
        $sentiText = new SentiText('HELLO world TEST');
        expect($sentiText->is_cap_diff)->toBeTrue();
    });

    it('handles punctuation list correctly', function () {
        expect(SentiText::PUNC_LIST)->toBeArray();
        expect(SentiText::PUNC_LIST)->toContain('.');
        expect(SentiText::PUNC_LIST)->toContain('!');
        expect(SentiText::PUNC_LIST)->toContain('?');
        expect(SentiText::PUNC_LIST)->toContain('!!!');
        expect(SentiText::PUNC_LIST)->toContain('???');
    });

    it('preserves emoticons when extracting words', function () {
        $emoticons = [':)', ':(', ':D', ';)', ':/', ':P'];
        $text = 'Hello :) this is good';

        $sentiText = new SentiText($text);

        // Check that emoticons are preserved (though they might be part of words)
        expect($sentiText->words_and_emoticons)->toContain(':)');
    });

    it('handles contractions correctly', function () {
        $contractions = [
            "don't" => "don't",
            "won't" => "won't",
            "can't" => "can't",
            "wouldn't" => "wouldn't",
        ];

        foreach ($contractions as $input => $expected) {
            $sentiText = new SentiText($input);
            expect($sentiText->words_and_emoticons)->toContain($expected);
        }
    });

    it('filters out single letter words', function () {
        $text = 'I a test of single letters x y z';
        $sentiText = new SentiText($text);

        // Single letters should be filtered out
        expect($sentiText->words_and_emoticons)->not->toContain('I');
        expect($sentiText->words_and_emoticons)->not->toContain('a');
        expect($sentiText->words_and_emoticons)->not->toContain('x');
        expect($sentiText->words_and_emoticons)->not->toContain('y');
        expect($sentiText->words_and_emoticons)->not->toContain('z');

        // Multi-letter words should remain
        expect($sentiText->words_and_emoticons)->toContain('test');
        expect($sentiText->words_and_emoticons)->toContain('of');
        expect($sentiText->words_and_emoticons)->toContain('single');
        expect($sentiText->words_and_emoticons)->toContain('letters');
    });

    it('handles empty and whitespace text', function () {
        $emptyText = new SentiText('');
        expect($emptyText->words_and_emoticons)->toBeArray();
        expect($emptyText->words_and_emoticons)->toBeEmpty();

        $whitespaceText = new SentiText('   ');
        expect($whitespaceText->words_and_emoticons)->toBeArray();
        expect($whitespaceText->words_and_emoticons)->toBeEmpty();
    });

    it('handles text with multiple punctuation marks', function () {
        $text = 'Wow!!! Really??? That is good';
        $sentiText = new SentiText($text);

        expect($sentiText->words_and_emoticons)->toContain('Wow');
        expect($sentiText->words_and_emoticons)->toContain('Really');
        expect($sentiText->words_and_emoticons)->toContain('That');
        expect($sentiText->words_and_emoticons)->toContain('is');
        expect($sentiText->words_and_emoticons)->toContain('good');
    });

    it('preserves word order in words_and_emoticons', function () {
        $text = 'First second third fourth';
        $sentiText = new SentiText($text);

        $words = $sentiText->words_and_emoticons;
        expect($words[0])->toBe('First');
        expect($words[1])->toBe('second');
        expect($words[2])->toBe('third');
        expect($words[3])->toBe('fourth');
    });
});
