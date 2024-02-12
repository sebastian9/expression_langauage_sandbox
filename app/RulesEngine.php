<?php

namespace App;

require __DIR__ . '/../vendor/autoload.php';

use App\SpecExpressionLanguageProvider;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class RulesEngine {

    private $_lang;

    public function __construct()
    {
        $this->_lang = new ExpressionLanguage();
        $this->_lang->registerProvider(new SpecExpressionLanguageProvider());
    }

    public function evaluate($expression, array $values = []) {
        return $this->_lang->evaluate($expression, $values);
    }


    /**
     * Evaluates a unary test expression using the specified values.
     *
     * @param string $expression The unary test expression to evaluate.
     * @param array $values The values to use in the evaluation.
     * @return bool The result of the evaluation.
     */
    public function unary_test($expression, array $values = []) {
        return (bool) $this->_lang->evaluate($expression, $values);
    }

    /**
     * Evaluates an expression over an index or array of indices.
     *
     * @param string $expression The expression to evaluate.
     * @param array|int $index The index or array of indices.
     * @param array $values The values to use in the expression evaluation.
     * @return array The results of the expression evaluation for each index.
     * @throws \InvalidArgumentException If the index is not an array or an integer.
     */
    public function evaluate_over_index($expression, array | int $index, array $values = []) {
        $results = [];
        if (!is_array($index) && !is_int($index)) {
            throw new \InvalidArgumentException('Index must be an array or an integer');
        }
        if (is_int($index)) {
            $index = range(0, $index - 1);
        }
        foreach ($index as $i) {
            $values['i'] = $i;
            $results[] = $this->_lang->evaluate($expression, $values);
        }
        return $results;
    }

    /**
     * Evaluates a unary test over an index.
     *
     * @param string $expression The expression to evaluate.
     * @param array|int $index The index to evaluate the expression over.
     * @param array $values The values to use in the evaluation.
     * @return bool Returns true if the expression evaluates to a truthy value, false otherwise.
     */
    public function unary_test_over_index($expression, array | int $index, array $values = []) {
        $results = $this->evaluate_over_index($expression, $index, $values);
        foreach ($results as $value) {
            if ($value) { // Check if the value is truthy
                return true;
            }
        }    
        return false;
    }
}