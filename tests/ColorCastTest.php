<?php

namespace Tests\Foyyay\ColorCast;

use Foyyay\ColorCast\ColorCast;
use PHPUnit\Framework\TestCase;

class ColorCastTest extends TestCase
{
    protected $caster;

    protected function setUp()
    {
        $config = [
            "0" => [
                "highlight" => ["saturation" => 75, "value" => 85],
                "darker" => ["saturation" => 80, "value" => 70],
            ],
            "20" => [
                "highlight" => ["saturation" => 80, "value" => 90],
                "darker" => ["saturation" => 85, "value" => 75],
            ],
        ];

        $this->caster = new ColorCast($config);
    }

    public function testEmptyConfiguration()
    {
        $this->expectException(\Foyyay\ColorCast\InvalidConfigurationException::class);
        new ColorCast([]);
    }

    public function testMismatchedNamesInConfig()
    {
        $this->expectException(\Foyyay\ColorCast\InvalidConfigurationException::class);
        new ColorCast([
            0 => ['one' => []],
            1 => ['two' => []],
        ]);
    }

    public function testMissingAllComponentsInConfig()
    {
        $this->expectException(\Foyyay\ColorCast\InvalidConfigurationException::class);
        new ColorCast([
            0 => ['one' => []],
            1 => ['one' => []],
        ]);
    }

    public function testMissingSomeComponentsInConfig()
    {
        $this->expectException(\Foyyay\ColorCast\InvalidConfigurationException::class);
        new ColorCast([
            0 => ['one' => ['saturation' => 0, 'value' => 0]],
            1 => ['one' => ['saturation' => 0]],
        ]);
    }

    public function testReturnValueHasSameKeysAsConfig()
    {
        $result = $this->caster->fromHue(0);
        $this->assertTrue(is_array($result));
        $this->assertTrue(array_key_exists('highlight', $result));
        $this->assertTrue(array_key_exists('darker', $result));
    }

    public function testHueValue5()
    {
        $result = $this->caster->fromHue(5);
        $this->assertTrue($result['darker'] == '#b62e22');
        $this->assertTrue($result['highlight'] == '#dc4234');
    }

    public function testHueValue0()
    {
        $result = $this->caster->fromHue(0);
        $this->assertTrue($result['darker'] == '#b32424');
        $this->assertTrue($result['highlight'] == '#d93636');
    }

    public function testHueValue360()
    {
        $result = $this->caster->fromHue(360);
        $this->assertTrue($result['darker'] == '#b32424');
        $this->assertTrue($result['highlight'] == '#d93636');
    }

    public function testHueValue365()
    {
        $result = $this->caster->fromHue(365);
        $this->assertTrue($result['darker'] == '#b62e22');
        $this->assertTrue($result['highlight'] == '#dc4234');
    }

    public function testHueValueNegative355()
    {
        $result = $this->caster->fromHue(-355);
        $this->assertTrue($result['darker'] == '#b62e22');
        $this->assertTrue($result['highlight'] == '#dc4234');
    }

    public function testHueValueFromColor()
    {
        $result = $this->caster->fromColor('#330400');
        $this->assertTrue($result['darker'] == '#b52c22');
        $this->assertTrue($result['highlight'] == '#db4035');
    }

}
