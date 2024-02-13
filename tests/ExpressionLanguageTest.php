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

    public function testSum()
    {
        $this->assertEquals(6, $this->expressionLanguage->evaluate('sum([1, 2, 3])'));
        $this->assertEquals(6, $this->expressionLanguage->evaluate('sum([x, 2, 3])',['x' => 1]));
    }

    public function testFilterNull()
    {
        $this->assertEquals([1, 2, 3], $this->expressionLanguage->evaluate('filter([1, null, 2, null, 3])'));
    }

    public function testReplace()
    {
        $this->assertEquals("Hello, World!", $this->expressionLanguage->evaluate('replace("Hello, Earth!", "Earth", "World")'));
    }

    public function testJoin()
    {
        $this->assertEquals("1, 2, 3", $this->expressionLanguage->evaluate('join([1, 2, 3], ", ")'));
    }

    public function testSort()
    {
        $this->assertEquals([1, 2, 3], $this->expressionLanguage->evaluate('sort([3, 1, 2])'));
    }

    public function testReverse()
    {
        $this->assertEquals([3, 2, 1], $this->expressionLanguage->evaluate('reverse([1, 2, 3])'));
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

    public function testC94() {

        // next([I430 for (busbar,ix,ix_method) in zip(T100,I429,I430) if busbar in ("New Backup Load Center","Existing Subpanel") and ix == "Yes"],"No")

        $step_c94 = 'T100[i] in ["New Backup Load Center","Existing Subpanel"] and I429[i] == "Yes" ? I430[i] : null';
        $step_context = [
            "I430" => ["Method 1", "Method 2", "Method 3"],
            "I429" => ["Yes", "Yes", "No"],
            "T100" => ["New Backup Load Center", "Existing Subpanel", "New Backup Load Center"]
        ];
        $step_index = 3;

        $step_value = $this->expressionLanguage->evaluate_over_index($step_c94, $step_index, $step_context);

        $this->assertEquals(["Method 1", "Method 2", null], $step_value);

        $c94 = 'step_c94[0]';
        $c94_context = [
            "step_c94" => $step_value
        ];

        $this->assertEquals("Method 1", $this->expressionLanguage->evaluate($c94, $c94_context));

    }

    public function testC93() {

        // Solo Main if I406 == "Yes" else ("Main Lug Only" if I397 == "Yes" else "Regular")

        $c93 = 'I406 == "Yes" ? "Solo Main" : (I397 == "Yes" ? "Main Lug Only" : "Regular")';
        $context = [
            "I406" => "Yes",
            "I397" => "No"
        ];

        $this->assertEquals("Solo Main", $this->expressionLanguage->evaluate($c93, $context));

        $context["I406"] = "No";
        $context["I397"] = "Yes";

        $this->assertEquals("Main Lug Only", $this->expressionLanguage->evaluate($c93, $context));

        $context["I406"] = "No";
        $context["I397"] = "No";

        $this->assertEquals("Regular", $this->expressionLanguage->evaluate($c93, $context));
    }

    public function testC96() {
        
        // ",".join(sorted([busbar.replace(" Combiner Panel","") for busbar in T100 if "Combiner Panel" in busbar])) or "None"

        $step_c96 = 'T100[i] matches "/Combiner Panel/" ? replace(T100[i], " Combiner Panel", "") : null';
        $step_context = [
            "T100" => ["PV Combiner Panel", "ESS Combiner Panel", "Main Panel"]
        ];
        $step_index = 3;

        $step_value = $this->expressionLanguage->evaluate_over_index($step_c96, $step_index, $step_context);

        $this->assertEquals(["PV", "ESS", null], $step_value);

        $c96 = 'join(sort(filter(step_c96)), ",")';

        $c96_context = [
            "step_c96" => $step_value
        ];

        $this->assertEquals("ESS,PV", $this->expressionLanguage->evaluate($c96, $c96_context));

    }

    public function testFlag14 () {
        
        // (R50 == 'Microinverters' and not any(R725 == 'PV Combiner Panel') and R197 > 16.5)

        $step = "R725[i] == 'PV Combiner Panel'";
        $step_context = [
            "R725" => ["PV Combiner Panel", "ESS Combiner Panel", "Main Panel"]
        ];
        $step_index = 3;

        $step_value = $this->expressionLanguage->unary_test_over_index($step, $step_index, $step_context);

        $this->assertTrue($step_value);

        $flag_14 = "R50 == 'Microinverters' and not step and R197 > 16.5";

        $flag_14_context = [
            "R50" => "Microinverters",
            "R197" => 17,
            "step" => $step_value
        ];

        $this->assertFalse($this->expressionLanguage->evaluate($flag_14, $flag_14_context));

        $step_context = [
            "R725" => ["Main Panel", "Subpanel"]
        ];
        $step_index = 2;

        $step_value = $this->expressionLanguage->unary_test_over_index($step, $step_index, $step_context);

        $this->assertFalse($step_value);

        $flag_14_context["step"] = $step_value;

        $this->assertTrue($this->expressionLanguage->evaluate($flag_14, $flag_14_context));   

    }

    public function testFlag25() {

        // -> R2 == 'PV' and re.findall(r'\\b(ESS|battery|batteries|storage|pw|powerwalls?|PV\\+ST|ST|\\d*kWh)\\b',str(R3), flags=re.IGNORECASE)

        $flag_25 = "R2 == 'PV' and R3 matches '/\\\b(ESS|battery|batteries|storage|pw|powerwalls?|PV\\\+ST|ST|\\\d*kWh)\\\b/i'";
        $flag_25_context = [
            "R2" => "PV",
            "R3" => "ESS, 10kWh, PV+ST"
        ];

        $this->assertTrue($this->expressionLanguage->evaluate($flag_25, $flag_25_context));

        $flag_25_context["R3"] = "Main Panel";

        $this->assertFalse($this->expressionLanguage->evaluate($flag_25, $flag_25_context));
    }

    public function testFlag21() {
        // 'AC Modules' in R50 and R53 != R66 or 'Microinverter' in R50 and R53 == R66"

        $flag_21 = "'AC Modules' == R50 and R53 != R66 or 'Microinverters' == R50 and R53 == R66";
        $flag_21_context = [
            "R50" => "AC Modules",
            "R53" => 10,
            "R66" => 20
        ];

        $this->assertTrue($this->expressionLanguage->evaluate($flag_21, $flag_21_context));

        $flag_21_context["R50"] = "Microinverters";
        $flag_21_context["R53"] = 20;

        $this->assertTrue($this->expressionLanguage->evaluate($flag_21, $flag_21_context));

        $flag_21_context["R53"] = 10;

        $this->assertFalse($this->expressionLanguage->evaluate($flag_21, $flag_21_context));
    }

    public function testFlag9() {
        // -> R77 == 'Hazard Control System' and 'Tesla' not in R53

        $flag_9 = "R77 == 'Hazard Control System' and not( R53 matches '/Tesla/i' )";
        $flag_9_context = [
            "R77" => "Hazard Control System",
            "R53" => "Enphase"
        ];

        $this->assertTrue($this->expressionLanguage->evaluate($flag_9, $flag_9_context));

        $flag_9_context["R53"] = "Tesla";

        $this->assertFalse($this->expressionLanguage->evaluate($flag_9, $flag_9_context));
    }

    public function testFlag24() {
    
        //-> R449 < 12

        $flag_24 = "R449 < 12";
        $flag_24_context = [
            "R449" => 10
        ];

        $this->assertTrue($this->expressionLanguage->evaluate($flag_24, $flag_24_context));

        $flag_24_context["R449"] = 15;

        $this->assertFalse($this->expressionLanguage->evaluate($flag_24, $flag_24_context));
    
    }
    
    public function testFlag33() {
        
        //"-> I271 is ""No"" and re.findall(r'\\b(MPU|MPR|Main Panel Upgrade|Main Panel Replacement)\\b',str(R3), flags=re.IGNORECASE)"

        $flag_33 = "I271 == 'No' and R3 matches '/\\\b(MPU|MPR|Main Panel Upgrade|Main Panel Replacement)\\\b/i'";
        $flag_33_context = [
            "I271" => "No",
            "R3" => "MPU, MPR, Main Panel Upgrade, Main Panel Replacement"
        ];

        $this->assertTrue($this->expressionLanguage->evaluate($flag_33, $flag_33_context));

        $flag_33_context["R3"] = "Main Panel";

        $this->assertFalse($this->expressionLanguage->evaluate($flag_33, $flag_33_context));
    
    }
    
    public function testFlag34() {
        
        //-> R20 > 80

        $flag_34 = "R20 > 80";

        $flag_34_context = [
            "R20" => 90
        ];

        $this->assertTrue($this->expressionLanguage->evaluate($flag_34, $flag_34_context));

        $flag_34_context["R20"] = 70;

        $this->assertFalse($this->expressionLanguage->evaluate($flag_34, $flag_34_context));
    
    }
    
    public function testFlag40() {
        
        //"-> ""solaredge"" in I239.lower() and ""S"" in I240"

        $flag_40 = "I239 matches '/solaredge/i' and I240 matches '/^S/'";

        $flag_40_context = [
            "I239" => "SolarEdge",
            "I240" => "S"
        ];

        $this->assertTrue($this->expressionLanguage->evaluate($flag_40, $flag_40_context));

        $flag_40_context["I239"] = "Enphase";

        $this->assertFalse($this->expressionLanguage->evaluate($flag_40, $flag_40_context));
    
    }
    
    public function testFlag36() {
        
        //-> I102 > 10000

        $flag_36 = "I102 > 10000";

        $flag_36_context = [
            "I102" => 11000
        ];

        $this->assertTrue($this->expressionLanguage->evaluate($flag_36, $flag_36_context));

        $flag_36_context["I102"] = 9000;

        $this->assertFalse($this->expressionLanguage->evaluate($flag_36, $flag_36_context));
    
    }
    
    public function testFlag37() {
        
        //-> I291 > 90 and I19 == 'Microinverters'

        $flag_37 = "I291 > 90 and I19 == 'Microinverters'";

        $flag_37_context = [
            "I291" => 100,
            "I19" => "Microinverters"
        ];

        $this->assertTrue($this->expressionLanguage->evaluate($flag_37, $flag_37_context));

        $flag_37_context["I291"] = 80;

        $this->assertFalse($this->expressionLanguage->evaluate($flag_37, $flag_37_context));
    
    }
    
    public function testFlag38() {
        
        //-> int(R94) == 208

        $flag_38 = "R94 == 208";

        $flag_38_context = [
            "R94" => 208
        ];  

        $this->assertTrue($this->expressionLanguage->evaluate($flag_38, $flag_38_context));

        $flag_38_context["R94"] = 240;

        $this->assertFalse($this->expressionLanguage->evaluate($flag_38, $flag_38_context));
    
    }
    
    public function testFlag39() {
        
        //"-> any(I118 == ""OCPD 3 tier series rated"")"

        $flag_39 = "I118[i] == 'OCPD 3 tier series rated'";

        $flag_39_context = [
            "I118" => ["OCPD 3 tier series rated", "OCPD 2 tier series rated", "OCPD 1 tier series rated"]
        ];

        $index = 3;

        $this->assertTrue($this->expressionLanguage->unary_test_over_index($flag_39, $index, $flag_39_context));      
    
    }
    
    public function testFlag41() {
        
        //-> I13.lower() == 'invisimount' and I314 == '2' and any(I6 > 3)

        $step = "I6[i] > 3";

        $step_context = [
            "I6" => [1, 2, 4]
        ];

        $index = 3;

        $step_value = $this->expressionLanguage->unary_test_over_index($step, $index, $step_context);

        $this->assertTrue($step_value);

        $flag_41 = "I13 matches '/invisimount/i' and I314 == '2' and step";

        $flag_41_context = [
            "I13" => "InvisiMount",
            "I314" => "2",
            "step" => $step_value
        ];

        $this->assertTrue($this->expressionLanguage->evaluate($flag_41, $flag_41_context));

        $flag_41_context["I13"] = "Enphase";

        $this->assertFalse($this->expressionLanguage->evaluate($flag_41, $flag_41_context));

    }
    
    public function testFlag42() {
        
        //-> any(R281[i]/R554[i] <= 0.5 and R554[i] - R281[i] > 5 for i in I269)

        $flag_42 = "R281[i]/R554[i] <= 0.5 and R554[i] - R281[i] > 5";

        $flag_42_context = [
            "R281" => [10, 20, 30],
            "R554" => [20, 40, 60]
        ];

        $index = 3;

        $this->assertTrue($this->expressionLanguage->unary_test_over_index($flag_42, $index, $flag_42_context));

        $flag_42_context["R281"] = [20, 40, 60];

        $this->assertFalse($this->expressionLanguage->unary_test_over_index($flag_42, $index, $flag_42_context));
    
    }

    public function testFlag43() {

        // -> R50 == ""String Inverter without DC-DC Converters"" and R131/I225 < 0.8 and R131/I225 > 0.5
        // -> R50 == ""String Inverter without DC-DC Converters"" R55 == ""Yes""  and R132/I226  < 0.8 and R132/I226 > 0.5
        // -> R50 == ""String Inverter with DC-DC Converters"" and R170/I225 < 0.8 and R170/I225 > 0.5
        // -> R50 == ""String Inverter with DC-DC Converters"" R55 == ""Yes""  and R171/I226  < 0.8 and R171/I226 > 0.5

        $step1 = "R50 == 'String Inverter without DC-DC Converters' and R131/I225 < 0.8 and R131/I225 > 0.5";
        $step1_context = [
            "R50" => "String Inverter without DC-DC Converters",
            "R131" => 35,
            "I225" => 60
        ];

        $step1_value = $this->expressionLanguage->unary_test($step1, $step1_context);

        $this->assertTrue($step1_value);

        $step2 = "R50 == 'String Inverter without DC-DC Converters' and R55 == 'Yes' and R132/I226 < 0.8 and R132/I226 > 0.5";

        $step2_context = [
            "R50" => "String Inverter without DC-DC Converters",
            "R55" => "Yes",
            "R132" => 35,
            "I226" => 60
        ];

        $step2_value = $this->expressionLanguage->unary_test($step2, $step2_context);

        $this->assertTrue($step2_value);

        $step3 = "R50 == 'String Inverter with DC-DC Converters' and R170/I225 < 0.8 and R170/I225 > 0.5";

        $step3_context = [
            "R50" => "String Inverter with DC-DC Converters",
            "R170" => 35,
            "I225" => 60
        ];

        $step3_value = $this->expressionLanguage->unary_test($step3, $step3_context);

        $this->assertTrue($step3_value);

        $step4 = "R50 == 'String Inverter with DC-DC Converters' and R55 == 'Yes' and R171/I226 < 0.8 and R171/I226 > 0.5";

        $step4_context = [
            "R50" => "String Inverter with DC-DC Converters",
            "R55" => "Yes",
            "R171" => 35,
            "I226" => 60
        ];

        $step4_value = $this->expressionLanguage->unary_test($step4, $step4_context);

        $flag_43 = "step1 or step2 or step3 or step4";

        $flag_43_context = [
            "step1" => $step1_value,
            "step2" => $step2_value,
            "step3" => $step3_value,
            "step4" => $step4_value
        ];

        $this->assertTrue($this->expressionLanguage->evaluate($flag_43, $flag_43_context));

    }
    public function testFlag44() {

        // -> bool(I344[i]) and I344[i] < 0.5 * I325[i] for i in len(I329)
        // -> R725[i] == ""Existing Main Service Panel"" and bool(I214) and I214 < 0.5 * I325[i] for i in len(I329)

        $step1 = "I344[i] < 0.5 * I325[i]";
        $step1_context = [
            "I344" => [5, 20, 30],
            "I325" => [20, 40, 60]
        ];
        $index = 3;

        $step1_value = $this->expressionLanguage->unary_test_over_index($step1, $index, $step1_context);

        $this->assertTrue($step1_value);

        $step2 = "R725[i] == 'Existing Main Service Panel' and I214[i] < 0.5 * I325[i]";

        $step2_context = [
            "R725" => ["Existing Main Service Panel", "New Main Service Panel", "Existing Main Service Panel"],
            "I214" => [5, 20, 30],
            "I325" => [20, 40, 60]
        ];

        $step2_value = $this->expressionLanguage->unary_test_over_index($step2, $index, $step2_context);

        $this->assertTrue($step2_value);

        $flag_44 = "step1 and step2";

        $flag_44_context = [
            "step1" => $step1_value,
            "step2" => $step2_value
        ];

        $this->assertTrue($this->expressionLanguage->evaluate($flag_44, $flag_44_context));

    }
    
    public function testFlag45() {
        
        //"-> I444 == ""No"" and not any([""enphase"" in manufacturer.lower() and ""encharge"" in model.lower() for (manufacturer,model) in zip(I137,I138)])"

        $step = 'manufacturer[i] matches "/enphase/i" and model[i] matches "/encharge/i"';

        $step_context = [
            "manufacturer" => ["Enphase", "Enphase", "Tesla"],
            "model" => ["Encharge", "Encharge", "Powerwall"]
        ];

        $index = 3;

        $step_value = $this->expressionLanguage->unary_test_over_index($step, $index, $step_context);

        $this->assertTrue($step_value);

        $flag_45 = 'I444 == "No" and not step';

        $flag_45_context = [
            "I444" => "No",
            "step" => $step_value
        ];

        $this->assertFalse($this->expressionLanguage->evaluate($flag_45, $flag_45_context));

        $step_context["manufacturer"] = ["SolarEdge", "SolarEdge", "Tesla"];

        $this->assertFalse($this->expressionLanguage->unary_test_over_index($step, $index, $step_context));

        $flag_45_context["step"] = false;

        $this->assertTrue($this->expressionLanguage->evaluate($flag_45, $flag_45_context));
    
    }
    
    public function testFlag46() {
        
        //-> C46 < 100

        $flag_46 = "C46 < 100";

        $flag_46_context = [
            "C46" => 90
        ];

        $this->assertTrue($this->expressionLanguage->evaluate($flag_46, $flag_46_context));

        $flag_46_context["C46"] = 110;

        $this->assertFalse($this->expressionLanguage->evaluate($flag_46, $flag_46_context));
    
    }
    
}
