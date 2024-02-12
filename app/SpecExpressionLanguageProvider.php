<?php

namespace App;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class SpecExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @var \App\SpecExpressionLanguageProvider
     */
    private $_lang;

    public function __construct()
    {
        $this->_lang = new ExpressionLanguage();
    }

    /**
     * Returns all custom functions to be used in lang
     *
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            $this->_buildAnyExpression(),
            $this->_buildAllExpression(),
            $this->_buildSumExpression()
        ];
    }

    /**
     * Returns results for an "any" request
     *
     * @return \Symfony\Component\ExpressionLanguage\ExpressionFunction
     */
    private function _buildAnyExpression(): ExpressionFunction
    {
        return new ExpressionFunction('any', function ($arr): string {
            // Generate the PHP code that will be executed when the expression is compiled
            return sprintf('is_array(%1$s) ? in_array(true, array_map("boolval", %1$s), true) : false', $arr);
        }, function ($arguments, $arr): bool {
            // The evaluator code, executed when the expression is evaluated without compiling
            if (!is_array($arr)) {
                return false;
            }
        
            foreach ($arr as $value) {
                if ($value) { // Check if the value is truthy
                    return true;
                }
            }
        
            return false;
        });
    }

    /**
     * Returns results for an "all" request
     *
     * @return \Symfony\Component\ExpressionLanguage\ExpressionFunction
     */
    private function _buildAllExpression(): ExpressionFunction
    {
        return new ExpressionFunction('all', function ($arr): string {
            return sprintf('is_array(%1$s) ? !in_array(false, array_map("boolval", %1$s), true) : false', $arr);
        }, function ($arguments, $arr): bool {
            if (!is_array($arr)) {
                return false;
            }
        
            foreach ($arr as $value) {
                if (!$value) { // Check if the value is falsy
                    return false;
                }
            }
        
            return true;
        });
    }

    /**
     * Returns results for a "sum" request, adding all the values in an array
     *
     * @return \Symfony\Component\ExpressionLanguage\ExpressionFunction
     */
    private function _buildSumExpression(): ExpressionFunction
    {
        return new ExpressionFunction('sum', function ($arr): string {
            return sprintf('is_array(%1$s) ? array_sum(%1$s) : 0', $arr);
        }, function ($arguments, $arr): int {
            if (!is_array($arr)) {
                return 0;
            }
            return array_sum($arr);
        });
    }

}


