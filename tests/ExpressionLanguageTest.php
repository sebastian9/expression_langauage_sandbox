<?php

use App\SpecExpressionLanguageProvider;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ExpressionLanguageTest extends \PHPUnit\Framework\TestCase

{
    private $expressionLanguage;

    protected function setUp(): void
    {
        $this->expressionLanguage = new ExpressionLanguage();
        $this->expressionLanguage->registerProvider(new SpecExpressionLanguageProvider());
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

    public function testMapWithValidArray()
    {
        $result = $this->expressionLanguage->evaluate('map(["hello", "world"], "upper")');

        $this->assertEquals(['HELLO', 'WORLD'], $result, 'The map function should uppercase each element in the array.');
    }

    public function testMapWithNonArray()
    {
        $result = $this->expressionLanguage->evaluate('map("not an array", "upper")');

        $this->assertEquals('not an array', $result, 'The map function should return the input unchanged if it is not an array.');
    }

    public function testMapWithEmptyArray()
    {
        $result = $this->expressionLanguage->evaluate('map([], "upper")');

        $this->assertEmpty($result, 'The map function should return an empty array when given an empty array.');
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


    public function TestFlag35() {
        $flag_35 = "any(R802[i] == 'Yes' and R804[i]/I325[i]<= 0.5 and R804[i] - I325[i] < -5 for i in R738)";

        $flag_35_context = [
            "R802" => ["Yes", "No", "Yes"],
            "R804" => [20, 30, 40],
            "I325" => [10, 20, 30],
            "R738" => 3
        ];
        
        $this->assertTrue($this->expressionLanguage->evaluate($flag_35, $flag_35_context));
    }
}
