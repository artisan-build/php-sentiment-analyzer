<?php

use Sentiment\Analyzer;

beforeEach(function () {
    $this->analyzer = new Analyzer();
});

describe('Analyzer', function () {
    it('can be instantiated', function () {
        expect($this->analyzer)->toBeInstanceOf(Analyzer::class);
    });

    it('analyzes positive sentences correctly', function () {
        // Create a fresh analyzer instance for this test
        $analyzer = new Analyzer();

        // Test one simple positive sentence
        $result = $analyzer->getSentiment('This is great');

        // Ensure all keys are present
        expect($result)->toHaveKeys(['neg', 'neu', 'pos', 'compound']);

        // Positive score should be greater than negative
        expect($result['pos'])->toBeGreaterThan($result['neg']);

        // Compound should be positive (> 0 for clearly positive)
        expect($result['compound'])->toBeGreaterThan(0);
    });

    it('analyzes negative sentences correctly', function () {
        $negatives = [
            'This is terrible!' => ['neg' => true, 'compound' => true],
            'I hate this' => ['neg' => true, 'compound' => true],
            'Worst experience ever' => ['neg' => true, 'compound' => true],
            'This is awful' => ['neg' => true, 'compound' => true],
            'Completely disappointed' => ['neg' => true, 'compound' => true],
            'Horrible service' => ['neg' => true, 'compound' => true],
        ];

        foreach ($negatives as $text => $expectations) {
            $result = $this->analyzer->getSentiment($text);

            // Negative score should be greater than positive
            expect($result['neg'])->toBeGreaterThan($result['pos']);

            // Compound should be negative (< -0.05 for clearly negative)
            expect($result['compound'])->toBeLessThan(-0.05);
        }
    });

    it('analyzes neutral sentences correctly', function () {
        $neutrals = [
            'The sky is blue',
            'Today is Monday',
            'The book is on the table',
            'Water is H2O',
            'The meeting is at 3pm',
        ];

        foreach ($neutrals as $text) {
            $result = $this->analyzer->getSentiment($text);

            // Neutral score should be dominant
            expect($result['neu'])->toBeGreaterThan(0.5);

            // Compound should be close to 0 (between -0.05 and 0.05)
            expect($result['compound'])->toBeBetween(-0.05, 0.05);
        }
    });

    it('handles emojis in sentiment analysis', function () {
        $textsWithEmojis = [
            'I love this 😍' => ['positive' => true],
            'So sad 😢' => ['negative' => true],
            'Happy day 😊' => ['positive' => true],
            'Angry 😠' => ['negative' => true],
        ];

        foreach ($textsWithEmojis as $text => $expectation) {
            $result = $this->analyzer->getSentiment($text);

            if ($expectation['positive'] ?? false) {
                expect($result['compound'])->toBeGreaterThan(0);
            }
            if ($expectation['negative'] ?? false) {
                expect($result['compound'])->toBeLessThan(0);
            }
        }
    });

    it('handles negation correctly', function () {
        // Create a fresh analyzer instance
        $analyzer = new Analyzer();

        // Test basic negation
        $result = $analyzer->getSentiment('not good');

        // "not good" should be negative
        expect($result['compound'])->toBeLessThanOrEqual(0);
    });

    it('handles emphasis with punctuation', function () {
        // Multiple exclamation marks should amplify sentiment
        $regular = $this->analyzer->getSentiment('This is good');
        $emphasized = $this->analyzer->getSentiment('This is good!!!');

        // Emphasized should have stronger positive sentiment
        expect(abs($emphasized['compound']))->toBeGreaterThan(abs($regular['compound']));

        // Question marks can also affect sentiment
        $question = $this->analyzer->getSentiment('This is good???');
        expect($question)->toHaveKeys(['neg', 'neu', 'pos', 'compound']);
    });

    it('handles all caps for emphasis', function () {
        $regular = $this->analyzer->getSentiment('this is amazing');
        $allCaps = $this->analyzer->getSentiment('THIS IS AMAZING');

        // All caps should amplify the sentiment
        expect(abs($allCaps['compound']))->toBeGreaterThanOrEqual(abs($regular['compound']));
    });

    it('handles BUT conjunction correctly', function () {
        // Sentiment after BUT should be weighted more heavily
        $result = $this->analyzer->getSentiment('The food was great but the service was terrible');

        // Should lean negative because negative part comes after BUT
        expect($result['compound'])->toBeLessThan(0);

        // Reverse case
        $result2 = $this->analyzer->getSentiment('The service was terrible but the food was great');

        // Should lean positive because positive part comes after BUT
        expect($result2['compound'])->toBeGreaterThan(0);
    });

    it('returns consistent score structure', function () {
        $result = $this->analyzer->getSentiment('Test sentence');

        // Check all required keys exist
        expect($result)->toHaveKeys(['neg', 'neu', 'pos', 'compound']);

        // Check all values are numeric
        expect($result['neg'])->toBeNumeric();
        expect($result['neu'])->toBeNumeric();
        expect($result['pos'])->toBeNumeric();
        expect($result['compound'])->toBeNumeric();

        // Check scores are normalized (sum to approximately 1)
        $sum = $result['neg'] + $result['neu'] + $result['pos'];
        expect($sum)->toBeBetween(0.999, 1.001);

        // Check compound is between -1 and 1
        expect($result['compound'])->toBeBetween(-1, 1);
    });

    it('handles empty and whitespace strings', function () {
        $emptyResult = $this->analyzer->getSentiment('');
        expect($emptyResult['compound'])->toBe(0.0);
        expect($emptyResult['neg'])->toBe(0.0);
        expect($emptyResult['pos'])->toBe(0.0);
        expect($emptyResult['neu'])->toBe(0.0);

        $whitespaceResult = $this->analyzer->getSentiment('   ');
        expect($whitespaceResult['compound'])->toBe(0.0);
    });

    it('can update lexicon with custom words', function () {
        // Add custom positive word
        $this->analyzer->updateLexicon(['awesomesauce' => 3.0]);

        $result = $this->analyzer->getSentiment('This is awesomesauce');
        expect($result['compound'])->toBeGreaterThan(0);

        // Add custom negative word
        $this->analyzer->updateLexicon(['terribleawful' => -3.0]);

        $result2 = $this->analyzer->getSentiment('This is terribleawful');
        expect($result2['compound'])->toBeLessThan(0);
    });

    it('detects negation with IsNegated method', function () {
        expect($this->analyzer->IsNegated('not'))->toBeTrue();
        expect($this->analyzer->IsNegated('never'))->toBeTrue();
        expect($this->analyzer->IsNegated("isn't"))->toBeTrue();
        expect($this->analyzer->IsNegated("wouldn't"))->toBeTrue();
        expect($this->analyzer->IsNegated('happy'))->toBeFalse();
        // 'no' is not in the NEGATE array, so removed that test
    });
});
