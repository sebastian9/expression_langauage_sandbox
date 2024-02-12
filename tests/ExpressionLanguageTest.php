<?php

use App\RulesEngine;

class ExpressionLanguageTest extends \PHPUnit\Framework\TestCase

{
    private $expressionLanguage;

    protected function setUp(): void
    {
        $this->expressionLanguage = new RulesEngine();
    }

    public function testBasic() {
        $this->assertEquals(3, $this->expressionLanguage->evaluate('1 + 2'));
        $this->assertEquals(4, $this->expressionLanguage->evaluate('x + 2',['x' => 2]));
    }

    public function testAll()
    {
        $this->assertTrue($this->expressionLanguage->evaluate('all([true, 1, "yes"])'));
        $this->assertFalse($this->expressionLanguage->evaluate('all([true, 0, "yes"])'));
        $this->assertFalse($this->expressionLanguage->evaluate('all("not an array")'));
    }

    public function testAny()
    {
        $this->assertTrue($this->expressionLanguage->evaluate('any([false, 0, "yes"])'));
        $this->assertFalse($this->expressionLanguage->evaluate('any([false, 0, ""])'));
        $this->assertFalse($this->expressionLanguage->evaluate('any("not an array")'));
    }

    public function testFlag20()
    {
        $flag_20 = "(R50 == 'String Inverter without DC-DC Converters' and R131/I225 <= 0.5 and R131-I225 < -5) or
        (R50 == 'String Inverter without DC-DC Converters' and R55 == 'Yes' and R132/I226 <= 0.5 and R132-I226 < -5) or
        (R50 == 'String Inverter with DC-DC Converters' and R170/I225 <= 0.5 and R170-I225 < -5) or
        (R50 == 'String Inverter with DC-DC Converters' and R55 == 'Yes' and R171/I226 <= 0.5 and R171-I226 < -5)";

        $flag_20_context = [
            "R50" => "String Inverter without DC-DC Converters",
            "R55" => "Yes",
            "I225" => 40,
            "I226" => 40,
            "R131" => 40,
            "R132" => 20,
            "R170" => 20,
            "R171" => 20  
        ];

        $this->assertTrue($this->expressionLanguage->evaluate($flag_20, $flag_20_context));

        $flag_20_context = [
            "R50" => "String Inverter with DC-DC Converters",
            "R55" => "Yes",
            "I225" => 20,
            "I226" => 40,
            "R131" => null,
            "R132" => null,
            "R170" => 20,
            "R171" => 20  
        ];

        $this->assertTrue($this->expressionLanguage->evaluate($flag_20, $flag_20_context));

        $flag_20_context["R171"] = 40;

        $this->assertFalse($this->expressionLanguage->evaluate($flag_20, $flag_20_context));
    }


    public function testFlag35() {

        $flag_35 = "R802[i] == 'Yes' and R804[i]/I325[i] <= 0.5 and R804[i] - I325[i] < -5";

        $flag_35_context = [
            "R802" => ["Yes", "No", "Yes"],
            "R804" => [20, 30, 20],
            "I325" => [10, 20, 40],
        ];

        $index = 3;
        
        $this->assertTrue($this->expressionLanguage->unary_test_over_index($flag_35, $index, $flag_35_context));

        $flag_35_context = [
            "R802" => ["Yes", "No", "No"],
            "R804" => [20, 30, 20],
            "I325" => [10, 20, 40],
        ];

        $this->assertFalse($this->expressionLanguage->unary_test_over_index($flag_35, $index, $flag_35_context));
    }

    public function testC34() {

        // sum([C26[i] * I126[i] for i in range(int(I269)) if B1[i] and ess_list[i].merged["nominal_voltage_vac"] != "Not Applicable"])

        $step_c34 = "B1[i] and ess_list[i]['merged']['nominal_voltage_vac'] != 'Not Applicable' ? C26[i] * I126[i] : 0";
        $step_context = [
            "I126" => [1, 2, 3],
            "C26" => [10, 20, 30],
            "B1" => [true, true, true],
            "ess_list" => [
                ["merged" => ["nominal_voltage_vac" => 240]],
                ["merged" => ["nominal_voltage_vac" => "Not Applicable"]],
                ["merged" => ["nominal_voltage_vac" => 240]]
            ]
        ];
        $step_index = 3;
        $step_value = $this->expressionLanguage->evaluate_over_index($step_c34, $step_index, $step_context);

        $this->assertEquals([10,0,90], $step_value);

        $c34 = 'sum(step_c34)';
        $c34_context = [
            "step_c34" => $step_value
        ];

        $this->assertEquals(100, $this->expressionLanguage->evaluate($c34, $c34_context));

    }
}
