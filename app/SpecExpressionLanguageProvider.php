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
            $this->_buildSumExpression(),
            $this->_buildFilterNullExpression(),
            $this->_buildReplaceExpression(),
            $this->_buildJoinExpression(),
            $this->_buildSortExpression(),
            $this->_buildReverseExpression()
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

    /**
     * Returns results for a "filter" request, filtering all null values out of an array
     *
     * @return \Symfony\Component\ExpressionLanguage\ExpressionFunction
     */
    private function _buildFilterNullExpression(): ExpressionFunction
    {
        return new ExpressionFunction('filter', function ($arr): string {
            return sprintf('is_array(%1$s) ? array_filter(%1$s, function($value) { return $value !== null; }) : []', $arr);
        }, function ($arguments, $arr): array {
            if (!is_array($arr)) {
                return [];
            }
            return array_values(array_filter($arr, function($value) { return $value !== null; })); // Reindex the array
        });
    }

    /**
     * Returns results for a string "replace" request, replacing all instances of a substring in a string
     *
     * @return \Symfony\Component\ExpressionLanguage\ExpressionFunction
     */
    private function _buildReplaceExpression(): ExpressionFunction
    {
        return new ExpressionFunction('replace', function ($str, $search, $replace): string {
            return sprintf('is_string(%1$s) ? str_replace(%2$s, %3$s, %1$s) : ""', $str, $search, $replace);
        }, function ($arguments, $str, $search, $replace): string {
            if (!is_string($str)) {
                return "";
            }
            return str_replace($search, $replace, $str);
        });
    }

    /**
     * Returns results for a "join" request, joining all elements of an array into a string
     *
     * @return \Symfony\Component\ExpressionLanguage\ExpressionFunction
     */
    private function _buildJoinExpression(): ExpressionFunction
    {
        return new ExpressionFunction('join', function ($arr, $separator): string {
            return sprintf('is_array(%1$s) ? implode(%2$s, %1$s) : ""', $arr, $separator);
        }, function ($arguments, $arr, $separator): string {
            if (!is_array($arr)) {
                return "";
            }
            return implode($separator, $arr);
        });
    }

    /**
     * Returns results for a "sort" request, sorting an array in ascending order
     *
     * @return \Symfony\Component\ExpressionLanguage\ExpressionFunction
     */
    private function _buildSortExpression(): ExpressionFunction
    {
        return new ExpressionFunction('sort', function ($arr): string {
            return sprintf('is_array(%1$s) ? sort(%1$s) : []', $arr);
        }, function ($arguments, $arr): array {
            if (!is_array($arr)) {
                return [];
            }
            sort($arr);
            return $arr;
        });
    }

    /**
     * Returns results for a "reverse" request, reversing the order of an array
     *
     * @return \Symfony\Component\ExpressionLanguage\ExpressionFunction
     */
    private function _buildReverseExpression(): ExpressionFunction
    {
        return new ExpressionFunction('reverse', function ($arr): string {
            return sprintf('is_array(%1$s) ? array_reverse(%1$s) : []', $arr);
        }, function ($arguments, $arr): array {
            if (!is_array($arr)) {
                return [];
            }
            return array_reverse($arr);
        });
    }
}


