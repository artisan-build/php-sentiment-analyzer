<?php

use Sentiment\Config\Config;

describe('Config', function () {
    it('has required constants defined', function () {
        // Check key constants exist
        expect(Config::NEGATE)->toBeArray();
        expect(Config::BOOSTER_DICT)->toBeArray();
        expect(Config::SPECIAL_CASE_IDIOMS)->toBeArray();
        expect(Config::SENTIMENT_LADEN_IDIOMS)->toBeArray();
    });

    it('has correct incremental values', function () {
        expect(Config::B_INCR)->toBeNumeric();
        expect(Config::B_DECR)->toBeNumeric();
        expect(Config::C_INCR)->toBeNumeric();
        expect(Config::N_SCALAR)->toBeNumeric();

        // B_INCR should be positive
        expect(Config::B_INCR)->toBeGreaterThan(0);
        // B_DECR should be negative
        expect(Config::B_DECR)->toBeLessThan(0);
    });

    it('contains expected negation words', function () {
        $expectedNegations = ['not', 'never', 'neither', 'nowhere', 'nothing', 'none', 'without'];

        foreach ($expectedNegations as $word) {
            expect(Config::NEGATE)->toContain($word);
        }
    });

    it('has booster words with correct values', function () {
        // Check some known booster words
        expect(Config::BOOSTER_DICT)->toHaveKey('absolutely');
        expect(Config::BOOSTER_DICT['absolutely'])->toBeNumeric();

        expect(Config::BOOSTER_DICT)->toHaveKey('very');
        expect(Config::BOOSTER_DICT['very'])->toBeNumeric();

        expect(Config::BOOSTER_DICT)->toHaveKey('slightly');
        expect(Config::BOOSTER_DICT['slightly'])->toBeNumeric();

        // Intensifiers should have positive values
        expect(Config::BOOSTER_DICT['absolutely'])->toBeGreaterThan(0);
        expect(Config::BOOSTER_DICT['very'])->toBeGreaterThan(0);

        // Diminishers should have negative values
        expect(Config::BOOSTER_DICT['slightly'])->toBeLessThan(0);
    });

    it('has special case idioms with sentiment values', function () {
        // Check some known idioms
        expect(Config::SPECIAL_CASE_IDIOMS)->toHaveKey('the shit');
        expect(Config::SPECIAL_CASE_IDIOMS)->toHaveKey('the bomb');
        expect(Config::SPECIAL_CASE_IDIOMS)->toHaveKey('bad ass');

        // These should have numeric sentiment values
        foreach (Config::SPECIAL_CASE_IDIOMS as $idiom => $value) {
            expect($value)->toBeNumeric();
        }
    });

    it('has sentiment laden idioms with values', function () {
        // Check structure
        expect(Config::SENTIMENT_LADEN_IDIOMS)->toBeArray();

        // Check some known idioms
        expect(Config::SENTIMENT_LADEN_IDIOMS)->toHaveKey('cut the mustard');
        expect(Config::SENTIMENT_LADEN_IDIOMS)->toHaveKey('on the ball');

        // All values should be numeric
        foreach (Config::SENTIMENT_LADEN_IDIOMS as $idiom => $value) {
            expect($value)->toBeNumeric();
        }
    });

    it('normalizes scores correctly', function () {
        // Test with different scores
        $testCases = [
            ['score' => 0, 'expected' => 0.0],
            ['score' => 5, 'alpha' => 15, 'min' => 0.7, 'max' => 0.8],
            ['score' => -5, 'alpha' => 15, 'min' => -0.8, 'max' => -0.7],
            ['score' => 15, 'alpha' => 15, 'min' => 0.96, 'max' => 0.98],
            ['score' => -15, 'alpha' => 15, 'min' => -0.98, 'max' => -0.96],
        ];

        foreach ($testCases as $test) {
            $score = $test['score'];
            $alpha = $test['alpha'] ?? 15;
            $result = Config::normalize($score, $alpha);

            // Result should be between -1 and 1
            expect($result)->toBeBetween(-1, 1);

            // Check expected value or range
            if (isset($test['expected'])) {
                expect($result)->toBe($test['expected']);
            } elseif (isset($test['min']) && isset($test['max'])) {
                expect($result)->toBeBetween($test['min'], $test['max']);
            }
        }
    });

    it('normalize function handles edge cases', function () {
        // Very large positive score should approach 1
        $largePositive = Config::normalize(1000, 15);
        expect($largePositive)->toBeLessThan(1);
        expect($largePositive)->toBeGreaterThan(0.95);

        // Very large negative score should approach -1
        $largeNegative = Config::normalize(-1000, 15);
        expect($largeNegative)->toBeGreaterThan(-1);
        expect($largeNegative)->toBeLessThan(-0.95);

        // Zero should return zero
        expect(Config::normalize(0))->toBe(0.0);
    });

    it('normalize function with different alpha values', function () {
        $score = 10;

        // Smaller alpha makes normalization more aggressive
        $smallAlpha = Config::normalize($score, 5);
        $normalAlpha = Config::normalize($score, 15);
        $largeAlpha = Config::normalize($score, 50);

        // With same score, smaller alpha should give larger normalized value
        expect($smallAlpha)->toBeGreaterThan($normalAlpha);
        expect($normalAlpha)->toBeGreaterThan($largeAlpha);

        // All should still be between -1 and 1
        expect($smallAlpha)->toBeBetween(0, 1);
        expect($normalAlpha)->toBeBetween(0, 1);
        expect($largeAlpha)->toBeBetween(0, 1);
    });
});
