<?php

namespace App;

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class SpecExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @var \App\Services\ExpressionLanguageService
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
            $this->_buildMapExpression(),
            $this->_buildUpperExpression()
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
     * Builds the map expression to mimic array_map behavior.
     *
     * @return \Symfony\Component\ExpressionLanguage\ExpressionFunction
     */
    private function _buildMapExpression(): ExpressionFunction
    {
        return new ExpressionFunction('map', function ($arr, $callback): string {
            // Compilation logic: generate the PHP code to be executed
            // Note: This is a simplistic implementation and might need to be adapted based on the callback's complexity and context
            return sprintf('array_map(%2$s, %1$s)', $arr, $callback);
        }, function ($arguments, $arr, $callback) {
            // Evaluation logic: execute the callback on each element of the array
            if (!is_array($arr) || !is_callable($callback)) {
                throw new \InvalidArgumentException('The first argument must be an array and the second argument must be a callable');
            }

            return array_map($callback, $arr);
        });
    }


    /**
     * Registers the `upper` function
     * 
     * @return \Symfony\Component\ExpressionLanguage\ExpressionFunction
     */
    private function _buildUpperExpression(): ExpressionFunction
    {
        return new ExpressionFunction('upper', function ($str): string {
            return sprintf('strtoupper(%s)', $str);
        }, function ($arguments, $str) {
            return strtoupper($str);
        });
    }

}


